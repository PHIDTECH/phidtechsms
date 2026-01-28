<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SenderID;
use App\Models\WalletTransaction;
use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Models\Setting;
use App\Models\SmsTransaction;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        // Get comprehensive admin statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_campaigns' => Campaign::count(),
            'total_messages' => SmsMessage::count(),
            'pending_sender_ids' => SenderID::where('status', 'pending')->count(),
            'approved_sender_ids' => SenderID::where('status', 'approved')->count(),
            'total_revenue' => WalletTransaction::where('type', 'credit')->sum('amount'),
            'total_payments' => Payment::where('status', 'completed')->sum('amount'),
            // Added: new users in the last 7 days
            'new_users' => User::whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])->count(),
        ];

        // Get SMS balance summary
        $beemService = new BeemSmsService();
        $balanceSummary = $beemService->getBalanceSummary();
        
        $beemBalance = [
            'admin_balance' => $balanceSummary['admin_balance'],
            'total_user_balance' => $balanceSummary['total_user_balance'],
            'last_sync' => $balanceSummary['last_synced'] ?? null,
            'is_configured' => Setting::get('beem_api_key') && Setting::get('beem_secret_key'),
        ];

        // Get recent campaigns
        $recentCampaigns = Campaign::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent transactions
        $recentTransactions = WalletTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Added: recent users
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Get weekly SMS data for charts
        $weeklySmsData = $this->getWeeklyAdminSmsData();

        // Calculate delivery statistics
        $totalSent = SmsMessage::count();
        $delivered = SmsMessage::where('status', 'delivered')->count();
        $failed = SmsMessage::where('status', 'failed')->count();
        $pending = SmsMessage::where('status', 'pending')->count();

        $deliveryStats = [
            'delivered_percentage' => $totalSent > 0 ? round(($delivered / $totalSent) * 100, 1) : 0,
            'failed_percentage' => $totalSent > 0 ? round(($failed / $totalSent) * 100, 1) : 0,
            'pending_percentage' => $totalSent > 0 ? round(($pending / $totalSent) * 100, 1) : 0,
        ];

        return view('admin.dashboard.modern', compact(
            'stats',
            'beemBalance',
            'recentCampaigns',
            'recentTransactions',
            'recentUsers',
            'weeklySmsData',
            'deliveryStats'
        ));
    }

    /**
     * Get weekly SMS data for admin dashboard charts
     */
    private function getWeeklyAdminSmsData()
    {
        $weeklyData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayName = $date->format('D');
            
            $sent = SmsMessage::whereDate('created_at', $date->toDateString())->count();
            $delivered = SmsMessage::whereDate('created_at', $date->toDateString())
                ->where('status', 'delivered')->count();
            $failed = SmsMessage::whereDate('created_at', $date->toDateString())
                ->where('status', 'failed')->count();
            
            $weeklyData[] = [
                'day' => $dayName,
                'date' => $date->toDateString(),
                'sent' => $sent,
                'delivered' => $delivered,
                'failed' => $failed
            ];
        }
        
        return $weeklyData;
    }

    /**
     * Legacy admin dashboard
     */
    public function legacy()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_campaigns' => Campaign::count(),
            'total_messages' => SmsMessage::count(),
            'pending_sender_ids' => SenderID::where('status', 'pending')->count(),
            'total_revenue' => WalletTransaction::where('type', 'credit')->sum('amount'),
        ];

        // Get SMS balance summary (local cached/admin balance info)
        $beemService = new BeemSmsService();
        $balanceSummary = $beemService->getBalanceSummary();
        
        $beemBalance = [
            'admin_balance' => $balanceSummary['admin_balance'],
            'total_user_balance' => $balanceSummary['total_user_balance'],
            'last_sync' => $balanceSummary['last_synced'] ?? null,
            'is_configured' => Setting::get('beem_api_key') && Setting::get('beem_secret_key'),
            'recent_transactions' => $balanceSummary['recent_transactions'] ?? []
        ];

        // Live Beem balance (exact from Beem API)
        $beemLive = $beemService->getBalance();
        $beemLiveBalance = [
            'success' => $beemLive['success'] ?? false,
            'balance' => $beemLive['balance'] ?? 0, // TZS
            'sms_credits' => $beemLive['sms_credits'] ?? 0,
            'currency' => $beemLive['currency'] ?? 'TZS',
            'endpoint' => $beemLive['endpoint_used'] ?? null,
            'error' => $beemLive['error'] ?? null,
        ];

        // Recent activities
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $recentTransactions = WalletTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $recentCampaigns = Campaign::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        $recentSenderIds = SenderID::with('user')->orderBy('created_at', 'desc')->limit(5)->get();

        // Fetch sender names from Beem (approved and pending)
        $normalizeSenderItems = function ($result) {
            if (!($result['success'] ?? false)) {
                return [[], $result['error'] ?? ''];
            }
            $data = $result['data'] ?? [];
            // Drill down to the array of items
            if (is_array($data)) {
                if (isset($data['data']) && is_array($data['data'])) {
                    $items = $data['data'];
                } elseif (isset($data['items']) && is_array($data['items'])) {
                    $items = $data['items'];
                } elseif (isset($data['sender_names']) && is_array($data['sender_names'])) {
                    $items = $data['sender_names'];
                } else {
                    $items = $data; // might already be the array
                }
            } else {
                $items = [];
            }
            // Normalize fields
            $normalized = [];
            foreach ($items as $it) {
                if (!is_array($it)) continue;
                $name = $it['senderid'] ?? $it['sender_id'] ?? $it['sender'] ?? $it['name'] ?? null;
                $status = $it['status'] ?? $it['state'] ?? null;
                $created = $it['created_at'] ?? $it['created'] ?? null;
                $normalized[] = [
                    'name' => $name,
                    'status' => $status,
                    'raw' => $it,
                    'created_at' => $created,
                ];
            }
            return [$normalized, null];
        };

        $approvedResult = $beemService->getSenderNames(null, 'approved');
        [$beemSenderApproved, $approvedError] = $normalizeSenderItems($approvedResult);

        $pendingResult = $beemService->getSenderNames(null, 'pending');
        [$beemSenderPending, $pendingError] = $normalizeSenderItems($pendingResult);

        return view('admin.dashboard', compact(
            'stats',
            'beemBalance',
            'beemLiveBalance',
            'recentUsers',
            'recentTransactions',
            'recentCampaigns',
            'recentSenderIds',
            'beemSenderApproved',
            'beemSenderPending',
            'approvedError',
            'pendingError'
        ));
    }

    public function getDashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'pending_sender_ids' => SenderID::where('status', 'pending')->count(),
        ];

        // Added: new users today for lightweight auto-refresh
        $stats['new_users'] = User::whereDate('created_at', Carbon::today())->count();

        // Get SMS balance summary
        $beemService = new BeemSmsService();
        $balanceSummary = $beemService->getBalanceSummary();
        $stats['total_user_balance'] = $balanceSummary['total_user_balance'];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Manage all users
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->withCount(['campaigns', 'walletTransactions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * View specific user details
     */
    public function showUser(User $user)
    {
        $user->load(['campaigns', 'walletTransactions', 'senderIDs']);
        
        $userStats = [
            'total_campaigns' => $user->campaigns->count(),
            'total_messages' => SmsMessage::where('user_id', $user->id)->count(),
            'total_spent' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->sum('amount'),
            'total_credited' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->sum('amount'),
        ];

        return view('admin.users.show', compact('user', 'userStats'));
    }

    /**
     * Deduct SMS credits from user
     */
    public function deductCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:' . $user->sms_credits,
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $user) {
            // Compute balances in TZS before and after
            $beforeCredits = $user->sms_credits;
            $afterCredits = $beforeCredits - $request->amount;
            $beforeTZS = $beforeCredits * 30;
            $afterTZS = $afterCredits * 30;

            // Deduct credits
            $user->decrement('sms_credits', $request->amount);

            // Create transaction record
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'sms_cost',
                'amount' => $request->amount * 30, // Convert to TZS for record
                'sms_credits' => $request->amount,
                'balance_before' => $beforeTZS,
                'balance_after' => $afterTZS,
                'description' => 'Admin deduction: ' . $request->reason,
                'reference' => 'ADMIN_DEDUCT_' . time(),
                'status' => 'completed',
            ]);
        });

        return redirect()->back()->with('success', "Successfully deducted {$request->amount} SMS credits from {$user->name}.");
    }

    /**
     * Add SMS credits to user
     */
    public function addCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:10000',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $user) {
            // Compute balances in TZS before and after
            $beforeCredits = $user->sms_credits;
            $afterCredits = $beforeCredits + $request->amount;
            $beforeTZS = $beforeCredits * 30;
            $afterTZS = $afterCredits * 30;

            // Add credits
            $user->increment('sms_credits', $request->amount);

            // Create transaction record
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $request->amount * 30, // Convert to TZS for record
                'sms_credits' => $request->amount,
                'balance_before' => $beforeTZS,
                'balance_after' => $afterTZS,
                'description' => 'Admin credit: ' . $request->reason,
                'reference' => 'ADMIN_CREDIT_' . time(),
                'status' => 'completed',
            ]);
        });

        return redirect()->back()->with('success', "Successfully added {$request->amount} SMS credits to {$user->name}.");
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "User {$user->name} has been {$status}.");
    }

    /**
     * Show user edit form
     */
    public function editUser(User $user)
    {
        $user->load(['campaigns', 'walletTransactions', 'senderIDs']);
        
        $userStats = [
            'total_campaigns' => $user->campaigns->count(),
            'total_messages' => SmsMessage::where('user_id', $user->id)->count(),
            'total_spent' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->sum('amount'),
            'total_credited' => WalletTransaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->sum('amount'),
        ];

        return view('admin.users.edit', compact('user', 'userStats'));
    }

    /**
     * Update user information
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:user,admin',
            'is_active' => 'boolean',
            'sms_credits' => 'nullable|integer|min:0',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->has('is_active'),
            'sms_credits' => $request->sms_credits ?? $user->sms_credits,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * View all payments and transactions
     */
    public function payments(Request $request)
    {
        // Get Payment records (Selcom payments)
        $paymentQuery = Payment::with('user');
        
        // Filter payments by status
        if ($request->filled('status') && $request->status !== 'all') {
            $paymentQuery->where('status', $request->status);
        }
        
        // Filter payments by search
        if ($request->filled('search')) {
            $search = $request->search;
            $paymentQuery->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        // Date range filter for payments
        if ($request->filled('date_from')) {
            $paymentQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $paymentQuery->whereDate('created_at', '<=', $request->date_to);
        }
        
        $payments = $paymentQuery->orderBy('created_at', 'desc')->paginate(20);
        
        // Get summary statistics
        $summary = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'total_transactions' => Payment::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'total_credits_sold' => Payment::where('status', 'completed')->sum('credits'),
            'failed_payments' => Payment::where('status', 'failed')->count(),
        ];
        
        return view('admin.payments.index', compact('payments', 'summary'));
    }
    
    /**
     * Approve a pending payment manually
     */
    public function approvePayment(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is not in pending status'
            ]);
        }
        
        try {
            DB::beginTransaction();
            
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'manually_approved_by' => Auth::id(),
                    'manually_approved_at' => now()->toISOString()
                ])
            ]);
            
            // Process SMS purchase through BeemSmsService
            $beemService = new BeemSmsService();
            $result = $beemService->processSMSPurchase(
                $payment->user_id,
                $payment->credits,
                'Manual payment approval - Order: ' . $payment->reference,
                $payment->reference
            );
            
            if ($result['success']) {
                DB::commit();
                
                // Log the action
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'payment_approved',
                    'description' => "Manually approved payment {$payment->reference} for user {$payment->user->name}",
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'credits' => $payment->credits,
                        'user_id' => $payment->user_id
                    ]
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment approved and SMS credits transferred successfully'
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to transfer SMS credits: ' . $result['error']
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual payment approval failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the payment'
            ]);
        }
    }
    
    /**
     * Reject a pending payment
     */
    public function rejectPayment(Request $request, Payment $payment)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is not in pending status'
            ]);
        }
        
        try {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'manually_rejected_by' => Auth::id(),
                    'manually_rejected_at' => now()->toISOString(),
                    'rejection_reason' => $request->reason
                ])
            ]);
            
            // Log the action
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'payment_rejected',
                'description' => "Manually rejected payment {$payment->reference} for user {$payment->user->name}. Reason: {$request->reason}",
                'metadata' => [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'credits' => $payment->credits,
                    'user_id' => $payment->user_id,
                    'reason' => $request->reason
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Manual payment rejection failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the payment'
            ]);
        }
    }

    /**
     * Show admin settings index page
     */
    public function settings()
    {
        return view('admin.settings.index');
    }

    /**
     * Show API settings page
     */
    public function apiSettings()
    {
        $beemSettings = Setting::getBeemSettings();
        $selcomSettings = Setting::getSelcomSettings();
        
        // Test connection status
        $beemStatus = null;
        if ($beemSettings['api_key'] && $beemSettings['secret_key']) {
            $beemService = new BeemSmsService($beemSettings['api_key'], $beemSettings['secret_key']);
            $beemStatus = $beemService->testConnection();
        }
        
        return view('admin.settings.api', compact('beemSettings', 'selcomSettings', 'beemStatus'));
    }

    /**
     * Update Beem SMS settings
     */
    public function updateBeemSettings(Request $request)
    {
        // Get current settings first
        $currentSettings = Setting::getBeemSettings();
        
        $request->validate([
            'api_key' => 'required|string',
            'secret_key' => $currentSettings['secret_key'] ? 'nullable|string' : 'required|string',
            'base_url' => 'nullable|url',
            'default_sender_id' => 'nullable|string|max:11',
        ]);
        
        // Use existing credentials if empty or asterisks are submitted
        $apiKey = (empty($request->api_key) || str_contains($request->api_key, '*')) ? $currentSettings['api_key'] : $request->api_key;
        $secretKey = (empty($request->secret_key) || str_contains($request->secret_key, '*')) ? $currentSettings['secret_key'] : $request->secret_key;

        Setting::setBeemSettings(
            $apiKey,
            $secretKey,
            $request->base_url,
            $request->default_sender_id
        );

        // Test connection
        $beemService = new BeemSmsService($apiKey, $secretKey);
        $testResult = $beemService->testConnection();

        if ($testResult['success']) {
            return redirect()->back()->with('success', 'Beem SMS settings updated successfully and connection verified!');
        } else {
            return redirect()->back()->with('warning', 'Settings saved but connection test failed: ' . $testResult['error']);
        }
    }

    /**
     * Update Selcom payment settings
     */
    public function updateSelcomSettings(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|string',
            'api_key' => 'required|string',
            'secret_key' => 'required|string',
            'base_url' => 'nullable|url',
        ]);

        Setting::setSelcomSettings(
            $request->vendor_id,
            $request->api_key,
            $request->secret_key,
            $request->base_url
        );

        return redirect()->back()->with('success', 'Selcom payment settings updated successfully!');
    }

    /**
     * Sync sender IDs from Beem API
     */
    public function syncSenderIds()
    {
        try {
            $beemService = new BeemSmsService();
            
            // Get approved sender IDs from Beem
            $approvedResult = $beemService->getSenderNames(null, 'approved');
            $pendingResult = $beemService->getSenderNames(null, 'pending');
            
            $syncedCount = 0;
            $errors = [];
            
            // Process approved sender IDs
            if ($approvedResult['success'] && isset($approvedResult['data'])) {
                $syncedCount += $this->processSenderIdData($approvedResult['data'], 'approved');
            } else {
                $errors[] = 'Failed to fetch approved sender IDs: ' . ($approvedResult['error'] ?? 'Unknown error');
            }
            
            // Process pending sender IDs
            if ($pendingResult['success'] && isset($pendingResult['data'])) {
                $syncedCount += $this->processSenderIdData($pendingResult['data'], 'pending');
            } else {
                $errors[] = 'Failed to fetch pending sender IDs: ' . ($pendingResult['error'] ?? 'Unknown error');
            }
            
            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully synchronized {$syncedCount} sender IDs from Beem API",
                    'synced_count' => $syncedCount
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => implode('; ', $errors),
                    'synced_count' => $syncedCount
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Sender ID sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync sender IDs: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Process sender ID data from Beem API
     */
    private function processSenderIdData($data, $status)
    {
        $syncedCount = 0;
        
        // Handle different response structures
        $items = [];
        if (is_array($data)) {
            if (isset($data['data']) && is_array($data['data'])) {
                $items = $data['data'];
            } elseif (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (isset($data['sender_names']) && is_array($data['sender_names'])) {
                $items = $data['sender_names'];
            } else {
                $items = $data;
            }
        }
        
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            
            $senderId = $item['senderid'] ?? $item['sender_id'] ?? $item['sender'] ?? $item['name'] ?? null;
            $sampleContent = $item['sample_content'] ?? $item['description'] ?? 'Synced from Beem API';
            $useCase = $item['use_case'] ?? 'Synced from Beem reseller account';
            $beemId = $item['id'] ?? null;
            
            if ($senderId) {
                // Check if sender ID already exists in our system
                $existingSender = SenderID::where('sender_id', $senderId)->first();
                
                if (!$existingSender) {
                    // Create new sender ID record with all required fields
                    SenderID::create([
                        'user_id' => null, // System/admin sender ID (available to all users)
                        'sender_id' => $senderId,
                        'use_case' => $useCase,
                        'sample_messages' => $sampleContent,
                        'status' => $status === 'active' ? 'approved' : $status,
                        'beem_sender_id' => $beemId,
                        'reviewed_at' => $status === 'active' || $status === 'approved' ? now() : null,
                        'is_default' => false,
                    ]);
                    $syncedCount++;
                } else {
                    // Update existing sender ID status if different
                    $newStatus = $status === 'active' ? 'approved' : $status;
                    if ($existingSender->status !== $newStatus) {
                        $existingSender->update([
                            'status' => $newStatus,
                            'beem_sender_id' => $beemId ?? $existingSender->beem_sender_id,
                        ]);
                        $syncedCount++;
                    }
                }
            }
        }
        
        return $syncedCount;
    }
    
    /**
     * Clear all sender IDs and sync fresh data from Beem API
     */
    public function clearAndSyncSenderIds()
    {
        try {
            // Delete all existing sender ID records
            $deletedCount = SenderID::count();
            SenderID::truncate();
            
            Log::info("Cleared {$deletedCount} sender ID records before fresh sync");
            
            $beemService = new BeemSmsService();
            
            // Fetch all sender IDs from Beem (API returns status in each item)
            $result = $beemService->getSenderNames();
            
            $syncedCount = 0;
            
            if ($result['success'] && isset($result['data'])) {
                // Process all sender IDs - extract status from each item
                $items = $result['data'];
                
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    
                    $senderId = $item['senderid'] ?? $item['sender_id'] ?? $item['sender'] ?? $item['name'] ?? null;
                    $sampleContent = $item['sample_content'] ?? $item['description'] ?? 'Synced from Beem API';
                    $useCase = $item['use_case'] ?? 'Synced from Beem reseller account';
                    $beemId = $item['id'] ?? null;
                    $itemStatus = $item['status'] ?? 'pending';
                    
                    // Map Beem status to local status (active -> approved)
                    $localStatus = ($itemStatus === 'active') ? 'approved' : $itemStatus;
                    
                    if ($senderId) {
                        SenderID::create([
                            'user_id' => null,
                            'sender_id' => $senderId,
                            'use_case' => $useCase,
                            'sample_messages' => $sampleContent,
                            'status' => $localStatus,
                            'beem_sender_id' => $beemId,
                            'reviewed_at' => ($localStatus === 'approved') ? now() : null,
                            'is_default' => false,
                        ]);
                        $syncedCount++;
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "Cleared {$deletedCount} old records. Synchronized {$syncedCount} sender IDs from Beem API",
                    'deleted_count' => $deletedCount,
                    'synced_count' => $syncedCount
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch sender IDs: ' . ($result['error'] ?? 'Unknown error'),
                    'details' => $result['details'] ?? null
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Clear and sync sender IDs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear and sync sender IDs: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Request a new sender ID via Beem API
     */
    public function requestSenderId(Request $request)
    {
        $request->validate([
            'senderid' => 'required|string|max:11',
            'sample_content' => 'required|string|min:15'
        ]);
        
        try {
            $beemService = new BeemSmsService();
            
            $result = $beemService->requestSenderName(
                $request->senderid,
                $request->sample_content
            );
            
            if ($result['success']) {
                // Also save to local database for tracking
                SenderID::create([
                    'user_id' => null, // Admin requested
                    'sender_id' => $request->senderid,
                    'status' => 'pending',
                    'purpose' => 'Admin requested via Beem API',
                    'sample_content' => $request->sample_content,
                    'beem_id' => $result['data']['id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                return redirect()->back()->with('success', 
                    'Sender ID "' . $request->senderid . '" has been requested successfully and is now pending approval from Beem Africa.'
                );
            } else {
                return redirect()->back()->with('error', 
                    'Failed to request sender ID: ' . $result['error']
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Sender ID request error: ' . $e->getMessage());
            return redirect()->back()->with('error', 
                'Failed to request sender ID: ' . $e->getMessage()
            );
        }
    }

    /**
     * Sync SMS balance with Beem API
     */
    public function syncSmsBalance(Request $request)
    {
        $beemService = new BeemSmsService();
        $result = $beemService->syncAdminBalance();

        // If the request expects JSON (AJAX), return a JSON response
        if ($request->expectsJson()) {
            if ($result['success']) {
                $setting = Setting::where('key', 'admin_sms_balance')->first();
                return response()->json([
                    'success' => true,
                    'balance' => $result['balance'] ?? null,
                    'sms_credits' => $result['sms_credits'] ?? null,
                    'currency' => $result['currency'] ?? 'TZS',
                    'last_sync' => $setting && $setting->balance_last_synced ? $setting->balance_last_synced->toISOString() : null,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to sync SMS balance',
            ], 400);
        }

        // Fallback to redirect for non-AJAX requests
        if ($result['success']) {
            return redirect()->back()->with('success', 'SMS balance synchronized successfully! Current balance: ' . number_format($result['sms_credits']) . ' SMS credits');
        }

        return redirect()->back()->with('error', 'Failed to sync SMS balance: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    /**
     * Manually update SMS balance
     */
    public function updateSmsBalance(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0'
        ]);
        
        try {
            $balance = $request->balance;
            $smsCredits = floor($balance / 30); // Convert TZS to SMS credits at 30 TZS per SMS
            
            // Update admin SMS balance in settings
            Setting::updateOrCreate(
                ['key' => 'admin_sms_balance'],
                [
                    'value' => $smsCredits,
                    'admin_sms_balance' => $smsCredits,
                    'balance_last_synced' => now()
                ]
            );
            
            return redirect()->back()->with('success', 'SMS balance updated successfully! Current balance: ' . number_format($smsCredits) . ' SMS credits (TZS ' . number_format($balance) . ')');
            
        } catch (\Exception $e) {
            Log::error('Manual SMS balance update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update SMS balance: ' . $e->getMessage());
        }
    }

    /**
     * JSON: Live Beem balance (no local update)
     */
    public function getBeemLiveBalance()
    {
        $beemService = new BeemSmsService();
        $live = $beemService->getBalance();
        if ($live['success']) {
            return response()->json([
                'success' => true,
                'balance' => $live['balance'],
                'currency' => $live['currency'] ?? 'TZS',
                'sms_credits' => $live['sms_credits'],
                'endpoint' => $live['endpoint_used'] ?? null,
            ]);
        }
        return response()->json([
            'success' => false,
            'error' => $live['error'] ?? 'Unknown error'
        ], 400);
    }

    /**
     * Show SMS transactions
     */
    public function smsTransactions(Request $request)
    {
        $query = SmsTransaction::with(['user', 'admin']);
        
        // Filter by type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total_transactions' => SmsTransaction::count(),
            'total_purchases' => SmsTransaction::where('type', 'purchase')->sum('amount'),
            'total_deductions' => SmsTransaction::where('type', 'deduction')->sum('amount'),
            'total_credits' => SmsTransaction::where('type', 'credit')->sum('amount'),
        ];
        
        $users = User::where('role', 'user')->orderBy('name')->get();
        
        return view('admin.sms-transactions', compact('transactions', 'stats', 'users'));
    }

    /**
     * Manually credit SMS to user
     */
    public function creditSmsToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255'
        ]);
        
        $beemService = new BeemSmsService();
        $result = $beemService->processSMSPurchase(
            $request->user_id,
            $request->amount,
            'manual_admin_credit_' . time()
        );
        
        if ($result['success']) {
            return redirect()->back()->with('success', 'SMS credits added successfully! User new balance: ' . number_format($result['user_new_balance']) . ' SMS');
        } else {
            return redirect()->back()->with('error', 'Failed to credit SMS: ' . $result['error']);
        }
    }

    /**
     * Get SMS balance status for AJAX
     */
    public function getSmsBalanceStatus()
    {
        $beemService = new BeemSmsService();
        $balanceSummary = $beemService->getBalanceSummary();
        
        return response()->json([
            'admin_balance' => $balanceSummary['admin_balance'],
            'total_user_balance' => $balanceSummary['total_user_balance'],
            // Align with BeemSmsService::getBalanceSummary which uses 'last_synced'
            'last_sync' => $balanceSummary['last_synced'],
            'formatted_admin_balance' => number_format($balanceSummary['admin_balance']) . ' SMS',
            'formatted_user_balance' => number_format($balanceSummary['total_user_balance']) . ' SMS'
        ]);
    }



    /**
     * Synchronize balance from Beem dashboard
     */
    public function syncBeemBalance(Request $request)
    {
        try {
            $beemService = new BeemSmsService();
            $force = $request->boolean('force', false);
            
            $result = $beemService->syncBalance($force);
            
            if ($result['success']) {
                if (isset($result['balance'])) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Balance synchronized successfully from Beem dashboard!',
                        'balance' => $result['balance'],
                        'formatted_balance' => number_format($result['balance']) . ' SMS',
                        'sync_time' => $result['sync_time']->format('Y-m-d H:i:s')
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'last_sync' => isset($result['last_sync']) ? $result['last_sync']->format('Y-m-d H:i:s') : null
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance synchronization failed: ' . $result['error']
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configure Beem dashboard credentials
     */
    public function configureBeemDashboard(Request $request)
    {
        $request->validate([
            'dashboard_email' => 'required|email',
            'dashboard_password' => 'required|string|min:6'
        ]);
        
        try {
            // Store encrypted credentials
            Setting::updateOrCreate(
                ['key' => 'beem_dashboard_email'],
                ['value' => $request->dashboard_email]
            );
            
            Setting::updateOrCreate(
                ['key' => 'beem_dashboard_password'],
                ['value' => encrypt($request->dashboard_password)]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Beem dashboard credentials configured successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save credentials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Beem dashboard configuration status
     */
    public function getBeemDashboardStatus()
    {
        $emailSetting = Setting::where('key', 'beem_dashboard_email')->first();
        $passwordSetting = Setting::where('key', 'beem_dashboard_password')->first();
        $lastSyncSetting = Setting::where('key', 'beem_balance_last_sync')->first();
        
        $isConfigured = $emailSetting && $passwordSetting && $emailSetting->value && $passwordSetting->value;
        
        return response()->json([
            'configured' => $isConfigured,
            'email' => $isConfigured ? $emailSetting->value : null,
            'last_sync' => $lastSyncSetting ? $lastSyncSetting->value : null,
            'has_credentials' => $isConfigured
        ]);
    }
}
