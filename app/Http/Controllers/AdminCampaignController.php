<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display all campaigns for admin
     */
    public function index(Request $request)
    {
        $query = Campaign::with(['user:id,name,phone', 'smsMessages']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by campaign name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get statistics
        $stats = [
            'total_campaigns' => Campaign::count(),
            'active_campaigns' => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
            'completed_campaigns' => Campaign::where('status', 'completed')->count(),
            'failed_campaigns' => Campaign::where('status', 'failed')->count(),
            'total_messages' => SmsMessage::count(),
            'total_sent' => SmsMessage::where('status', 'sent')->count(),
            'total_delivered' => SmsMessage::where('status', 'delivered')->count(),
        ];

        // Get users for filter dropdown
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('admin.campaigns.index', compact('campaigns', 'stats', 'users'));
    }

    /**
     * Show campaign details for admin
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['user:id,name,phone,email', 'smsMessages', 'walletTransactions']);

        $messageStats = [
            'total' => $campaign->smsMessages->count(),
            'queued' => $campaign->smsMessages->where('status', 'queued')->count(),
            'sent' => $campaign->smsMessages->where('status', 'sent')->count(),
            'delivered' => $campaign->smsMessages->where('status', 'delivered')->count(),
            'failed' => $campaign->smsMessages->where('status', 'failed')->count(),
        ];

        // Get recent messages for this campaign
        $recentMessages = $campaign->smsMessages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.campaigns.show', compact('campaign', 'messageStats', 'recentMessages'));
    }

    /**
     * Show campaign edit form for admin
     */
    public function edit(Campaign $campaign)
    {
        $campaign->load(['user:id,name']);
        
        return view('admin.campaigns.edit', compact('campaign'));
    }

    /**
     * Update campaign (admin can modify status, notes, etc.)
     */
    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,sending,completed,failed,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $campaign->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Cancel a campaign (admin action)
     */
    public function cancel(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['queued', 'scheduled', 'sending'])) {
            return back()->withErrors(['error' => 'Cannot cancel campaign with status: ' . $campaign->status]);
        }

        $campaign->update([
            'status' => 'cancelled',
            'admin_notes' => 'Campaign cancelled by admin at ' . now()->format('Y-m-d H:i:s'),
        ]);

        // Update pending messages to failed
        $campaign->smsMessages()
            ->where('status', 'queued')
            ->update([
                'status' => 'failed',
                'failure_reason' => 'Campaign cancelled by admin',
            ]);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled successfully.');
    }

    /**
     * Delete a campaign (admin action)
     */
    public function destroy(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'failed', 'cancelled'])) {
            return back()->withErrors(['error' => 'Cannot delete campaign with status: ' . $campaign->status]);
        }

        DB::beginTransaction();
        
        try {
            // Delete related SMS messages
            $campaign->smsMessages()->delete();
            
            // Delete campaign
            $campaign->delete();
            
            DB::commit();
            
            return redirect()->route('admin.campaigns.index')
                ->with('success', 'Campaign deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete campaign: ' . $e->getMessage()]);
        }
    }
}