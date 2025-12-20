<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Models\Contact;
use App\Models\SenderID;
use App\Models\WalletTransaction;
use App\Services\SmsService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Redirect admins to their dedicated admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        // Get KPI metrics for the current user
        $metrics = $this->getDashboardMetrics($user);

        // For admins, prefer live Beem balance for initial render
        try {
            if (property_exists($user, 'role') ? $user->role === 'admin' : (method_exists($user, 'isAdmin') && $user->isAdmin())) {
                $beemService = new \App\Services\BeemSmsService();
                $balanceResult = $beemService->getBalance();
                if (is_array($balanceResult) && ($balanceResult['success'] ?? false)) {
                    $metrics['sms_credits'] = (int) ($balanceResult['sms_credits'] ?? $metrics['sms_credits']);
                }
            }
        } catch (\Exception $e) {
            // Non-fatal: fall back to DB value already in $metrics
        }
        
        // Get recent activities
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Get weekly SMS data for chart
        $weeklyData = $this->getWeeklySmsData($user->id);
        $userSenderIds = SenderID::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id','sender_name','status','approved_at','created_at']);
        
        return view('dashboard.modern', [
            'smsCredits' => $metrics['sms_credits'],
            'recentCampaigns' => $recentCampaigns,
            'weeklyData' => $weeklyData,
            'userSenderIds' => $userSenderIds,
        ]);
    }

    /**
     * Show the modern dashboard
     */
    public function modern()
    {
        $user = Auth::user();
        
        // Redirect admins to their dedicated admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('dashboard');
    }
    
    /**
     * Get user balance via AJAX
     */
    public function getUserBalance()
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'success' => true,
                'sms_credits' => $user->sms_credits,
                'last_updated' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch balance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get dashboard metrics for the user
     */
    private function getDashboardMetrics($user)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfDay = $now->copy()->startOfDay();
        
        return [
            // SMS Credits metrics
            'sms_credits' => $user->sms_credits,
            
            // Campaign metrics
            'total_campaigns' => Campaign::where('user_id', $user->id)->count(),
            'campaigns_this_month' => Campaign::where('user_id', $user->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'active_campaigns' => Campaign::where('user_id', $user->id)
                ->whereIn('status', ['queued', 'processing'])
                ->count(),
                
            // SMS metrics
            'total_sms_sent' => SmsMessage::where('user_id', $user->id)->count(),
            'sms_sent_today' => SmsMessage::where('user_id', $user->id)
                ->where('created_at', '>=', $startOfDay)
                ->count(),
            'sms_sent_this_week' => SmsMessage::where('user_id', $user->id)
                ->where('created_at', '>=', $startOfWeek)
                ->count(),
            'sms_sent_this_month' => SmsMessage::where('user_id', $user->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
                
            // Delivery metrics
            'delivery_rate' => $this->calculateDeliveryRate($user->id),
            'delivered_today' => SmsMessage::where('user_id', $user->id)
                ->where('status', 'delivered')
                ->where('delivered_at', '>=', $startOfDay)
                ->count(),
                
            // Sender ID metrics
            'total_sender_ids' => SenderID::where('user_id', $user->id)->count(),
            'approved_sender_ids' => SenderID::where('user_id', $user->id)
                ->where('status', 'approved')
                ->count(),
            'pending_sender_ids' => SenderID::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
                
            // Financial metrics
            'total_spent' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'sms_cost')
                ->sum('amount'),
            'spent_this_month' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'sms_cost')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
            'total_topups' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'topup')
                ->sum('amount'),
        ];
    }
    
    /**
     * Calculate delivery rate percentage
     */
    private function calculateDeliveryRate($userId)
    {
        $totalSent = SmsMessage::where('user_id', $userId)
            ->whereIn('status', ['delivered', 'failed'])
            ->count();
            
        if ($totalSent === 0) {
            return 0;
        }
        
        $delivered = SmsMessage::where('user_id', $userId)
            ->where('status', 'delivered')
            ->count();
            
        return round(($delivered / $totalSent) * 100, 2);
    }
    
    /**
     * Get current SMS credits for real-time updates
     * Fetches live balance from Beem API
     */
    public function getSmsCredits()
    {
        try {
            $user = Auth::user();
            
            // For admin users, get live balance from Beem API
            if ($user->role === 'admin') {
                $beemService = new \App\Services\BeemSmsService();
                $balanceResult = $beemService->getBalance();
                
                if ($balanceResult['success']) {
                    return response()->json([
                        'success' => true,
                        'sms_credits' => $balanceResult['sms_credits'],
                        'source' => 'beem_api'
                    ]);
                } else {
                    // Fallback to database value if API fails
                    return response()->json([
                        'success' => true,
                        'sms_credits' => $user->fresh()->sms_credits,
                        'source' => 'database',
                        'api_error' => $balanceResult['error']
                    ]);
                }
            } else {
                // For regular users, return database value
                return response()->json([
                    'success' => true,
                    'sms_credits' => $user->fresh()->sms_credits,
                    'source' => 'database'
                ]);
            }
        } catch (\Exception $e) {
            // Fallback to database value on any error
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'sms_credits' => $user->fresh()->sms_credits,
                'source' => 'database',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get weekly SMS data for charts
     */
    private function getWeeklySmsData($userId)
    {
        $weeklyData = [];
        $startOfWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = SmsMessage::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();
            $weeklyData[] = $count;
        }
        
        return $weeklyData;
    }
    
    /**
     * Send quick SMS from dashboard
     */
    public function sendQuickSms(Request $request)
    {
        $user = Auth::user();
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'phone' => 'required|string|regex:/^(\+?255[0-9]{9})$/',
            'message' => 'required|string|max:160'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if sender ID belongs to user and is approved
        $senderID = SenderID::where('user_id', $user->id)
            ->where('sender_id', $request->sender_id)
            ->where('status', 'approved')
            ->first();
            
        if (!$senderID) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unapproved sender ID'
            ], 400);
        }
        
        // Calculate SMS cost (30 TZS per SMS)
        $smsCost = 30;
        
        // Check if user has sufficient credits
        if ($user->sms_credits < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient SMS credits. Please top up your account.'
            ], 400);
        }
        
        try {
            // Normalize phone number to ensure it has +255 prefix
            $phoneNumber = $request->phone;
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+' . $phoneNumber;
            }
            
            // Create a quick campaign for this SMS
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => 'Quick SMS - ' . now()->format('Y-m-d H:i:s'),
                'mode' => 'quick',
                'sender_id' => $senderID->id,
                'message' => $request->message,
                'schedule_at' => null,
                'audience_config' => json_encode(['phones' => [$phoneNumber]]),
                'status' => 'processing',
                'estimated_recipients' => 1,
                'estimated_parts' => 1,
                'estimated_cost' => $smsCost
            ]);
            
            // Create SMS message record
            $smsMessage = SmsMessage::create([
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'phone' => $phoneNumber,
                'message_content' => $request->message,
                'sender_id' => $request->sender_id,
                'parts_count' => 1,
                'cost' => $smsCost,
                'status' => 'queued'
            ]);
            
            // Send SMS using SmsService
            $smsService = new SmsService();
            $result = $smsService->sendSms(
                $phoneNumber,
                $request->message,
                $request->sender_id
            );
            
            if ($result['success']) {
                // Deduct SMS credit from user
                $user->decrement('sms_credits', 1);
                
                // Update SMS message status
                $smsMessage->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'beem_message_id' => $result['message_id'] ?? null
                ]);
                
                // Update campaign status
                $campaign->update([
                    'status' => 'completed',
                    'sent_count' => 1,
                    'completed_at' => now()
                ]);
                
                // Create wallet transaction
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'sms_cost',
                    'amount' => $smsCost,
                    'balance_before' => ($user->sms_credits + 1) * 30, // Before deduction
                    'balance_after' => $user->sms_credits * 30, // After deduction
                    'sms_credits' => 1,
                    'description' => 'Quick SMS to ' . $phoneNumber,
                    'status' => 'completed'
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully!',
                    'remaining_credits' => $user->fresh()->sms_credits
                ]);
            } else {
                // Update SMS message status to failed
                $smsMessage->update([
                    'status' => 'failed',
                    'failure_reason' => $result['message'] ?? 'Unknown error',
                    'failed_at' => now()
                ]);
                
                // Update campaign status
                $campaign->update([
                    'status' => 'failed',
                    'failed_count' => 1,
                    'failure_reason' => $result['message'] ?? 'Unknown error'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS: ' . ($result['message'] ?? 'Unknown error')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS: ' . $e->getMessage()
            ], 500);
        }
    }
}
