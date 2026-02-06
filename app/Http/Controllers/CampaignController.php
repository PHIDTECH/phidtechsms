<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\SmsTemplate;
use App\Models\SenderID;
use App\Models\SmsMessage;
use App\Models\WalletTransaction;
use App\Models\AuditLog;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\ContactGroup;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * Display campaigns dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        $campaigns = Campaign::where('user_id', $user->id)
            ->with(['smsMessages'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $stats = [
            'total_campaigns' => Campaign::where('user_id', $user->id)->count(),
            'active_campaigns' => Campaign::where('user_id', $user->id)->whereIn('status', ['sending', 'scheduled'])->count(),
            'completed_campaigns' => Campaign::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total_messages_sent' => SmsMessage::where('user_id', $user->id)->where('status', 'sent')->count(),
            'delivery_rate' => $this->calculateDeliveryRate($user->id),
        ];
        
        return view('campaigns.index', compact('campaigns', 'stats'));
    }
    
    /**
     * Show campaign creation form
     */
    public function create()
    {
        $user = auth()->user();
        
        $templates = SmsTemplate::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $senderIds = SenderID::where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('sender_id')
            ->get();
        
        $contactGroups = ContactGroup::where('user_id', $user->id)
            ->withCount(['contacts as active_contacts_count' => function($q){
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
        
        $totalActiveContacts = Contact::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        
        return view('campaigns.create', compact('templates', 'senderIds', 'contactGroups', 'totalActiveContacts'));
    }
    
    /**
     * Store new campaign
     */
    public function store(Request $request)
    {
        $action = $request->input('action', $request->input('submit_action'));
        $payload = $request->all();
        $payload['action'] = $action;

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:1600',
            'sender_id' => 'required|exists:sender_ids,id',
            'recipient_type' => 'required|in:all,groups,individual',
            'recipient_groups' => ['exclude_unless:recipient_type,groups', 'array'],
            'recipient_groups.*' => [
                'exclude_unless:recipient_type,groups',
                'integer',
                Rule::exists('contact_groups', 'id')->where(function($q){
                    $q->where('user_id', auth()->id());
                })
            ],
            'recipient_phones' => ['exclude_unless:recipient_type,individual', 'string'],
            'schedule_type' => 'required|in:now,later',
            'schedule_at' => 'required_if:schedule_type,later|date|after:now',
            'action' => 'required|in:create,send',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $user = auth()->user();
        
        // Verify sender ID belongs to user and is approved
        $senderIdRecord = SenderID::where('id', $request->sender_id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();
        
        if (!$senderIdRecord) {
            return back()->withErrors(['sender_id' => 'Invalid or unapproved sender ID'])->withInput();
        }

        $smsService = new SmsService();
        if (!$smsService->validateSenderId($senderIdRecord->sender_id)) {
            return back()->withErrors(['sender_id' => 'Sender ID must be 3–11 alphanumeric characters'])->withInput();
        }
        
        // Get recipients based on type
        $recipients = $this->getRecipients($request, $user->id);
        
        if (empty($recipients)) {
            return back()->withErrors(['recipients' => 'No valid recipients found'])->withInput();
        }
        
        // Calculate cost
        $messageLength = strlen($request->message);
        $smsPartsPerMessage = max(1, ceil($messageLength / 160));
        $totalRecipients = count($recipients);
        $totalSmsParts = $smsPartsPerMessage * $totalRecipients;
        $estimatedCost = $totalSmsParts * 30; // TZS 30 per SMS part
        
        // Check if user has sufficient SMS credits
        if ($user->sms_credits < $totalSmsParts) {
            return back()->withErrors(['balance' => 'Insufficient SMS credits. Required: ' . number_format($totalSmsParts) . ' SMS credits'])->withInput();
        }
        
        DB::beginTransaction();
        
        try {
            // Determine campaign status based on action and schedule
            $status = 'draft';
            if ($action === 'send') {
                $status = $request->schedule_type === 'now' ? 'queued' : 'scheduled';
            }

            // Create campaign aligned to Campaign model fields
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'mode' => 'standard',
                'sender_id' => $senderIdRecord->sender_id, // store human-readable sender name
                'message' => $request->message,
                'schedule_at' => $request->schedule_type === 'later' ? $request->schedule_at : null,
                'audience_config' => ['type' => $request->recipient_type],
                'status' => $status,
                'estimated_recipients' => $totalRecipients,
                'estimated_parts' => $totalSmsParts,
                'estimated_cost' => $estimatedCost,
                'started_at' => null,
                'completed_at' => null,
                'failure_reason' => null,
            ]);
            
            // Create SMS messages for each recipient (aligned to SmsMessage fields)
            $smsMessages = [];
            foreach ($recipients as $phone) {
                $smsMessages[] = [
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'message_content' => $request->message,
                    'sender_id' => $senderIdRecord->sender_id,
                    'parts_count' => $smsPartsPerMessage,
                    'cost' => $smsPartsPerMessage * 30,
                    'status' => 'queued',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            SmsMessage::insert($smsMessages);
            
            // If sending now, process immediately (only for send action)
            if ($action === 'send' && $request->schedule_type === 'now') {
                $this->processCampaign($campaign);
            }
            
            // Log campaign creation
            $actionText = $action === 'send' ? 'created and sent' : 'created as draft';
            AuditLog::logCampaign(
                $user->id,
                'campaign_created',
                $campaign->id,
                [
                    'recipient_count' => $totalRecipients,
                    'estimated_cost' => $estimatedCost,
                    'action' => $request->action,
                ]
            );
            
            DB::commit();
            
            $successMessage = $action === 'send' ? 'Campaign created and sent successfully!' : 'Campaign created as draft successfully!';
            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create campaign: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Show campaign details
     */
    public function show(Campaign $campaign)
    {
        // Ensure campaign belongs to authenticated user
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }
        
        $campaign->load(['smsMessages', 'walletTransactions']);
        
        $messageStats = [
            'queued' => $campaign->smsMessages->where('status', 'queued')->count(),
            'sent' => $campaign->smsMessages->where('status', 'sent')->count(),
            'delivered' => $campaign->smsMessages->where('status', 'delivered')->count(),
            'failed' => $campaign->smsMessages->where('status', 'failed')->count(),
        ];
        
        return view('campaigns.show', compact('campaign', 'messageStats'));
    }
    
    /**
     * Process campaign (charge wallet and send messages)
     */
    private function processCampaign(Campaign $campaign)
    {
        $user = $campaign->user;
        $smsService = new SmsService();
        if (!$smsService->canSend()) {
            $campaign->update(['status' => 'failed', 'failure_reason' => 'Beem API credentials are missing or invalid']);
            return;
        }
        
        // Determine total SMS parts from campaign or messages
        $totalSmsParts = $campaign->estimated_parts ?? $campaign->smsMessages()->sum('parts_count');
        
        // Charge wallet using SMS credits (total SMS parts)
        $charged = WalletController::chargeSms($user, (int) $totalSmsParts, $campaign->id);
        
        if (!$charged) {
            $campaign->update(['status' => 'failed', 'failure_reason' => 'Insufficient SMS credits']);
            return;
        }
        
        // Update campaign status
        $campaign->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);
        
        // Send SMS messages via Beem API
        $this->sendCampaignMessages($campaign);
    }
    
    /**
     * Send campaign messages via Beem API
     */
    private function sendCampaignMessages(Campaign $campaign)
    {
        $smsService = new SmsService();
        $messages = $campaign->smsMessages()->where('status', 'queued')->get();
        
        // Group messages by content and sender for bulk sending
        $messageGroups = $messages->groupBy(function ($message) {
            return $message->sender_id . '|' . $message->message_content;
        });
        
        foreach ($messageGroups as $group) {
            $firstMessage = $group->first();
            $recipients = $group->pluck('phone')->toArray();
            
            // Send bulk SMS
            $result = $smsService->sendBulkSms(
                $recipients,
                $firstMessage->message_content,
                $firstMessage->sender_id
            );
            
            if ($result['success']) {
                // Update all messages in this group as sent
                foreach ($group as $message) {
                    $message->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'beem_message_id' => $result['message_id'] ?? null,
                    ]);
                }
            } else {
                // Mark all messages in this group as failed
                foreach ($group as $message) {
                    $reason = isset($result['error_code']) ? ($result['error'] . ' (' . $result['error_code'] . ')') : ($result['error'] ?? 'Unknown error');
                    $message->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_reason' => $reason,
                    ]);
                }
            }
        }
        
        // Update campaign statistics
        $this->updateCampaignStatistics($campaign);
    }
    
    /**
     * Update campaign statistics after sending
     */
    private function updateCampaignStatistics(Campaign $campaign)
    {
        $totalMessages = $campaign->smsMessages()->count();
        $sentMessages = $campaign->smsMessages()->where('status', 'sent')->count();
        $failedMessages = $campaign->smsMessages()->whereIn('status', ['failed', 'rejected'])->count();
        $pendingMessages = $campaign->smsMessages()->where('status', 'queued')->count();
        
        $campaign->update([
            'sent_count' => $sentMessages,
            'failed_count' => $failedMessages,
        ]);
        
        // If all messages are processed, mark campaign as completed
        if ($pendingMessages == 0) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
    
    /**
     * Get recipients based on request type
     */
    private function getRecipients(Request $request, $userId)
    {
        $recipients = [];
        
        switch ($request->recipient_type) {
            case 'all':
                $recipients = Contact::where('user_id', $userId)
                    ->where('is_active', true)
                    ->pluck('phone')
                    ->toArray();
                break;
                
            case 'groups':
                $groups = (array) ($request->recipient_groups ?? []);
                $recipients = Contact::where('user_id', $userId)
                    ->where('is_active', true)
                    ->whereIn('contact_group_id', $groups)
                    ->pluck('phone')
                    ->toArray();
                break;
                
            case 'individual':
                $phones = preg_split("/\r\n|\n|\r/", $request->recipient_phones ?? '');
                $recipients = array_map('trim', $phones);
                $recipients = array_filter($recipients); // Remove empty lines
                
                // Validate phone numbers (255XXXXXXXXX format for Tanzania)
                $recipients = array_filter($recipients, function($phone) {
                    return preg_match('/^255\d{9}$/', $phone);
                });
                break;
        }
        
        return array_unique($recipients);
    }
    
    /**
     * Calculate delivery rate for user
     */
    private function calculateDeliveryRate($userId)
    {
        $totalSent = SmsMessage::where('user_id', $userId)->whereIn('status', ['sent', 'delivered'])->count();
        $totalDelivered = SmsMessage::where('user_id', $userId)->where('status', 'delivered')->count();
        
        if ($totalSent === 0) {
            return 0;
        }
        
        return round(($totalDelivered / $totalSent) * 100, 2);
    }
    
    /**
     * Get campaign templates
     */
    public function getTemplate($templateId)
    {
        $template = SmsTemplate::where('id', $templateId)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'content' => $template->content,
            'variables' => $template->variables,
            'character_count' => mb_strlen($template->content),
            'sms_parts' => $template->calculateParts(),
        ]);
    }
    
    /**
     * Calculate SMS cost
     */
    public function calculateCost(Request $request)
    {
        $messageLength = strlen($request->message_content ?? '');
        $recipientCount = (int) ($request->recipient_count ?? 0);
        
        $smsPartsPerMessage = max(1, ceil($messageLength / 160));
        $totalSmsParts = $smsPartsPerMessage * $recipientCount;
        $totalCost = $totalSmsParts * 30; // TZS 30 per SMS part
        
        return response()->json([
            'message_length' => $messageLength,
            'sms_parts_per_message' => $smsPartsPerMessage,
            'recipient_count' => $recipientCount,
            'total_sms_parts' => $totalSmsParts,
            'cost_per_sms_part' => 30,
            'total_cost' => $totalCost,
            'formatted_cost' => 'TZS ' . number_format($totalCost, 2),
        ]);
    }
    
    /**
     * Delete campaign
     */
    public function destroy(Campaign $campaign)
    {
        // Ensure campaign belongs to authenticated user
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Only allow deletion of draft or failed campaigns
        if (!in_array($campaign->status, ['draft', 'failed'])) {
            return back()->withErrors(['error' => 'Cannot delete campaigns that are not in draft or failed status']);
        }
        
        DB::beginTransaction();
        
        try {
            // Delete related SMS messages
            $campaign->smsMessages()->delete();
            
            // Log campaign deletion
            AuditLog::logCampaign(auth()->id(), 'campaign_deleted', $campaign->id, [
                'campaign_name' => $campaign->name,
            ]);
            
            // Delete campaign
            $campaign->delete();
            
            DB::commit();
            
            return redirect()->route('campaigns.index')
                ->with('success', 'Campaign deleted successfully!');
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete campaign: ' . $e->getMessage()]);
        }
    }
}
