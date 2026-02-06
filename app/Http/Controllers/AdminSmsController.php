<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SenderID;
use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\WalletTransaction;
use App\Services\SmsService;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminSmsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Send Message Dashboard
     */
    public function index()
    {
        // Get SMS statistics
        $stats = [
            'sms_balance' => $this->getAdminSmsBalance(),
            'delivered_this_month' => SmsMessage::whereMonth('created_at', now()->month)
                ->where('status', 'delivered')
                ->count(),
            'sent_this_month' => SmsMessage::whereMonth('created_at', now()->month)
                ->whereIn('status', ['sent', 'delivered'])
                ->count(),
            'failed_this_month' => SmsMessage::whereMonth('created_at', now()->month)
                ->where('status', 'failed')
                ->count(),
        ];

        // Calculate delivery rate
        $totalSent = $stats['sent_this_month'] + $stats['failed_this_month'];
        $stats['delivery_rate'] = $totalSent > 0 
            ? round(($stats['delivered_this_month'] / $totalSent) * 100, 1) 
            : 0;

        // For admin, always provide PHIDTECH as the default sender ID
        $senderIds = collect([
            (object)[
                'id' => 0,
                'sender_id' => 'PHIDTECH',
                'sender_name' => 'PHIDTECH',
                'status' => 'approved'
            ]
        ]);

        // PHIDTECH is always available for admin
        $hasSenderId = true;

        // Get recent messages sent by admin
        $recentMessages = SmsMessage::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get users for selection
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('admin.sms.index', compact('stats', 'senderIds', 'hasSenderId', 'recentMessages', 'users'));
    }

    /**
     * Show compose message form
     */
    public function compose()
    {
        // For admin, always provide PHIDTECH as the default sender ID
        // This is hardcoded since PHIDTECH is registered in Beem for admin use
        $senderIds = collect([
            (object)[
                'id' => 0,
                'sender_id' => 'PHIDTECH',
                'sender_name' => 'PHIDTECH',
                'status' => 'approved'
            ]
        ]);

        // Get users for selection
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        // Get contact groups (if any exist) with contact counts
        $contactGroups = ContactGroup::withCount('contacts')->orderBy('name')->get();

        return view('admin.sms.compose', compact('senderIds', 'users', 'contactGroups'));
    }

    /**
     * Send SMS message
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_type' => 'required|in:quick,group',
            'sender_id' => 'required|string',
            'message' => 'required|string|max:918',
            // For quick SMS
            'recipients' => 'required_if:message_type,quick|nullable|string',
            'selected_users' => 'nullable|array',
            'selected_users.*' => 'exists:users,id',
            // For group SMS
            'contact_group_id' => 'required_if:message_type,group|nullable|exists:contact_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $messageType = $request->message_type;
        $senderId = $request->sender_id;
        $message = $request->message;

        // Collect all phone numbers
        $phoneNumbers = [];

        if ($messageType === 'quick') {
            // Parse manually entered numbers
            if ($request->filled('recipients')) {
                $rawNumbers = preg_split('/[\s,;\n]+/', $request->recipients);
                foreach ($rawNumbers as $number) {
                    $number = trim($number);
                    if (!empty($number)) {
                        $phoneNumbers[] = $this->normalizePhoneNumber($number);
                    }
                }
            }

            // Add selected users' phone numbers
            if ($request->filled('selected_users')) {
                $selectedUsers = User::whereIn('id', $request->selected_users)->get();
                foreach ($selectedUsers as $selectedUser) {
                    if ($selectedUser->phone) {
                        $phoneNumbers[] = $this->normalizePhoneNumber($selectedUser->phone);
                    }
                }
            }
        } else {
            // Group SMS - get contacts from group
            $contactGroup = ContactGroup::find($request->contact_group_id);
            if ($contactGroup) {
                $contacts = Contact::where('contact_group_id', $contactGroup->id)->get();
                foreach ($contacts as $contact) {
                    if ($contact->phone) {
                        $phoneNumbers[] = $this->normalizePhoneNumber($contact->phone);
                    }
                }
            }
        }

        // Remove duplicates
        $phoneNumbers = array_unique($phoneNumbers);

        if (empty($phoneNumbers)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid phone numbers provided'
            ], 400);
        }

        // Calculate cost
        $smsService = new SmsService();
        $parts = $smsService->calculateSmsParts($message);
        $totalCost = $parts * count($phoneNumbers);

        // Check admin SMS balance
        $adminBalance = $this->getAdminSmsBalance();
        if ($adminBalance < $totalCost) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient SMS balance. Required: {$totalCost}, Available: {$adminBalance}"
            ], 400);
        }

        try {
            // Create campaign record
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => 'Admin SMS - ' . now()->format('Y-m-d H:i:s'),
                'mode' => $messageType === 'quick' ? 'quick' : 'group',
                'sender_id' => $senderId,
                'message' => $message,
                'schedule_at' => null,
                'audience_config' => json_encode(['phones' => $phoneNumbers]),
                'status' => 'processing',
                'estimated_recipients' => count($phoneNumbers),
                'estimated_parts' => $parts * count($phoneNumbers),
                'estimated_cost' => $totalCost * 30 // 30 TZS per SMS
            ]);

            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            // Send SMS to each recipient
            if (count($phoneNumbers) === 1) {
                // Single SMS
                $result = $smsService->sendSms($phoneNumbers[0], $message, $senderId);
                
                $smsMessage = SmsMessage::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'phone' => $phoneNumbers[0],
                    'message_content' => $message,
                    'sender_id' => $senderId,
                    'parts_count' => $parts,
                    'cost' => $parts * 30,
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'sent_at' => $result['success'] ? now() : null,
                    'failure_reason' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
                    'beem_message_id' => $result['message_id'] ?? null
                ]);

                if ($result['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $errors[] = $result['error'] ?? 'Unknown error';
                }
            } else {
                // Bulk SMS
                $result = $smsService->sendBulkSms($phoneNumbers, $message, $senderId);

                foreach ($phoneNumbers as $phone) {
                    SmsMessage::create([
                        'campaign_id' => $campaign->id,
                        'user_id' => $user->id,
                        'phone' => $phone,
                        'message_content' => $message,
                        'sender_id' => $senderId,
                        'parts_count' => $parts,
                        'cost' => $parts * 30,
                        'status' => $result['success'] ? 'sent' : 'failed',
                        'sent_at' => $result['success'] ? now() : null,
                        'failure_reason' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
                        'beem_message_id' => $result['message_id'] ?? null
                    ]);
                }

                if ($result['success']) {
                    $sentCount = count($phoneNumbers);
                } else {
                    $failedCount = count($phoneNumbers);
                    $errors[] = $result['error'] ?? 'Unknown error';
                }
            }

            // Update campaign status
            $campaign->update([
                'status' => $failedCount === 0 ? 'completed' : ($sentCount === 0 ? 'failed' : 'completed'),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'completed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "SMS sent successfully! Sent: {$sentCount}, Failed: {$failedCount}",
                'data' => [
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'total' => count($phoneNumbers),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin SMS send error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admin SMS balance from Beem
     */
    private function getAdminSmsBalance()
    {
        try {
            $beemService = new BeemSmsService();
            $result = $beemService->getBalance();
            
            if ($result['success']) {
                return $result['sms_credits'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get Beem balance: ' . $e->getMessage());
        }
        
        return 0;
    }

    /**
     * Normalize phone number to Tanzania format
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove any non-digit characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove + if present
        $phone = ltrim($phone, '+');
        
        // If starts with 0, replace with 255
        if (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        // If doesn't start with 255, add it
        if (substr($phone, 0, 3) !== '255') {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }

    /**
     * Get users for AJAX selection
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('search', '');
        
        $users = User::where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json($users);
    }

    /**
     * Get message history
     */
    public function history(Request $request)
    {
        $messages = SmsMessage::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.sms.history', compact('messages'));
    }
}
