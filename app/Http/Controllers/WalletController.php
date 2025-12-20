<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\AuditLog;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display SMS credits dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get recent transactions
        $recentTransactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get monthly statistics
        $monthlyStats = $this->getMonthlyStats($user->id);
        
        return view('wallet.index', compact('user', 'recentTransactions', 'monthlyStats'));
    }

    /**
     * Show SMS credits top-up form
     */
    public function showTopUp()
    {
        return view('wallet.topup');
    }

    /**
     * Process SMS credits top-up request
     */
    public function topUp(Request $request)
    {
        // Handle both old format (sms_credits) and new format (sms_count + amount)
        if ($request->has('sms_count') && $request->has('amount')) {
            $request->validate([
                'sms_count' => 'required|integer|min:1|max:50000',
                'amount' => 'required|numeric|min:100',
                'payment_method' => 'required|in:selcom'
            ]);
            $smsCredits = $request->sms_count;
            $amount = $request->amount;
        } else {
            $request->validate([
                'sms_credits' => 'required|integer|min:100|max:50000', // Min 100 SMS, Max 50,000 SMS
                'payment_method' => 'required|in:selcom'
            ]);
            $smsCredits = $request->sms_credits;
            $amount = $smsCredits * 30; // 30 TZS per SMS
        }

        $user = Auth::user();
        
        // Proceed to payment without pre-checking admin balance.
        // Admin balance will be reconciled during/after payment processing.
        $beemService = new BeemSmsService();
        
        DB::beginTransaction();
        
        try {
            // Create pending transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amount,
                'sms_credits' => $smsCredits,
                'balance_before' => $user->sms_credits,
                'balance_after' => $user->sms_credits, // Will be updated after payment confirmation
                'description' => 'SMS credits purchase: ' . number_format($smsCredits) . ' SMS via ' . strtoupper($request->payment_method),
                'reference' => 'SMS-' . strtoupper(Str::random(10)),
                'payment_method' => $request->payment_method,
                'status' => 'pending'
            ]);
            
            // Log the action
            AuditLog::logWallet($user->id, 'sms_topup_initiated', $smsCredits, [
                'transaction_id' => $transaction->id,
                'payment_method' => $request->payment_method,
                'amount' => $amount
            ]);
            
            DB::commit();
            
            // TODO: Integrate with Selcom payment gateway
            // For now, we'll simulate successful payment and process SMS transfer
            return $this->processPaymentAndTransferSms($transaction);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process SMS credits purchase. Please try again.']);
        }
    }

    /**
     * Simulate payment success (temporary until Selcom integration)
     */
    private function processPaymentAndTransferSms(WalletTransaction $transaction)
    {
        DB::beginTransaction();
        
        try {
            $user = $transaction->user;
            $beemService = new BeemSmsService();
            
            // Process SMS purchase through BeemSmsService
            $result = $beemService->processSMSPurchase(
                $user->id,
                $transaction->sms_credits,
                'SMS credits purchase via ' . strtoupper($transaction->payment_method),
                $transaction->reference
            );
            
            if (!$result['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => $result['message']]);
            }
            
            $newCredits = $user->sms_credits + $transaction->sms_credits;
            
            // Update user SMS credits
            $user->update(['sms_credits' => $newCredits]);
            
            // Update transaction
            $transaction->update([
                'balance_after' => $newCredits,
                'status' => 'completed',
                'payment_reference' => 'SIM-' . strtoupper(Str::random(12)),
                'processed_at' => now()
            ]);
            
            // Log successful top-up
            AuditLog::logWallet($user->id, 'sms_topup_completed', $transaction->sms_credits, [
                'transaction_id' => $transaction->id,
                'new_credits' => $newCredits,
                'amount_paid' => $transaction->amount,
                'sms_transaction_id' => $result['sms_transaction_id']
            ]);
            
            DB::commit();
            
            return redirect()->route('wallet.index')
                ->with('success', 'SMS credits purchased successfully! New balance: ' . number_format($newCredits) . ' SMS credits');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Payment processing failed. Please contact support.']);
        }
    }

    /**
     * Charge user SMS credits for sending
     */
    public static function chargeSms(User $user, int $smsCount, int $campaignId = null): bool
    {
        if ($user->sms_credits < $smsCount) {
            return false; // Insufficient credits
        }
        
        DB::beginTransaction();
        
        try {
            $newCredits = $user->sms_credits - $smsCount;
            $equivalentAmount = $smsCount * 30; // For record keeping
            
            // Update user SMS credits
            $user->update(['sms_credits' => $newCredits]);
            
            // Create debit transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'campaign_id' => $campaignId,
                'type' => 'sms_cost',
                'amount' => $equivalentAmount,
                'sms_credits' => $smsCount,
                'balance_before' => $user->sms_credits + $smsCount,
                'balance_after' => $newCredits,
                'description' => "SMS sent: {$smsCount} SMS credits used",
                'reference' => 'SMS-' . strtoupper(Str::random(10)),
                'status' => 'completed',
                'processed_at' => now()
            ]);
            
            // Log SMS charge
            AuditLog::logWallet($user->id, 'sms_sent', $smsCount, [
                'sms_count' => $smsCount,
                'campaign_id' => $campaignId,
                'new_credits' => $newCredits
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('WalletController::chargeSms failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get transaction history
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        $query = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');
        
        // Filter by type if specified
        if ($request->has('type') && in_array($request->type, ['topup', 'sms_cost'])) {
            $query->where('type', $request->type);
        }
        
        // Filter by status if specified
        if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'failed'])) {
            $query->where('status', $request->status);
        }
        
        $transactions = $query->paginate(20);
        
        return view('wallet.transactions', compact('transactions'));
    }

    /**
     * Get monthly wallet statistics
     */
    private function getMonthlyStats(int $userId): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        $monthlyTransactions = WalletTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->get();
        
        $totalTopUps = $monthlyTransactions->where('type', 'topup')->sum('amount');
        $totalSpent = $monthlyTransactions->where('type', 'sms_cost')->sum('amount');
        $transactionCount = $monthlyTransactions->count();
        
        return [
            'total_topups' => $totalTopUps,
            'total_spent' => $totalSpent,
            'transaction_count' => $transactionCount,
            'net_change' => $totalTopUps - $totalSpent
        ];
    }

    /**
     * Calculate SMS cost
     */
    public function calculateCost(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'recipient_count' => 'required|integer|min:1'
        ]);
        
        $message = $request->message;
        $recipientCount = $request->recipient_count;
        
        // Calculate SMS parts
        $messageLength = mb_strlen($message);
        $partsPerSms = $messageLength <= 160 ? 1 : ceil($messageLength / 153);
        
        $costPerPart = 30;
        $totalParts = $partsPerSms * $recipientCount;
        $totalCost = $totalParts * $costPerPart;
        
        return response()->json([
            'message_length' => $messageLength,
            'parts_per_sms' => $partsPerSms,
            'recipient_count' => $recipientCount,
            'total_parts' => $totalParts,
            'cost_per_part' => $costPerPart,
            'total_cost' => $totalCost,
            'formatted_cost' => 'TZS ' . number_format($totalCost, 2)
        ]);
    }
}
