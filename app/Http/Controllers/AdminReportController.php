<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Models\WalletTransaction;
use App\Models\Payment;
use App\Models\SenderID;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display comprehensive admin reports dashboard
     */
    public function index(Request $request)
    {
        // Get date range from request or default to last 30 days
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $reports = [
            'overview' => $this->getOverviewStats($startDate, $endDate),
            'user_activities' => $this->getUserActivities($startDate, $endDate),
            'campaign_analytics' => $this->getCampaignAnalytics($startDate, $endDate),
            'financial_summary' => $this->getFinancialSummary($startDate, $endDate),
            'sender_id_analytics' => $this->getSenderIdAnalytics($startDate, $endDate),
            'system_health' => $this->getSystemHealth($startDate, $endDate),
            'top_users' => $this->getTopUsers($startDate, $endDate),
            'recent_activities' => $this->getRecentActivities()
        ];
        
        return view('admin.reports.index', compact('reports', 'startDate', 'endDate'));
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($startDate, $endDate)
    {
        return [
            'total_users' => User::count(),
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_campaigns' => Campaign::count(),
            'campaigns_period' => Campaign::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_messages' => SmsMessage::count(),
            'messages_period' => SmsMessage::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'revenue_period' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'pending_sender_ids' => SenderID::where('status', 'pending')->count(),
        ];
    }

    /**
     * Get user activities breakdown
     */
    private function getUserActivities($startDate, $endDate)
    {
        // Most active users by campaigns
        $topCampaignUsers = User::withCount(['campaigns' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->orderBy('campaigns_count', 'desc')
        ->limit(10)
        ->get();

        // Most active users by messages sent
        $topMessageUsers = User::withCount(['smsMessages' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->orderBy('sms_messages_count', 'desc')
        ->limit(10)
        ->get();

        // User registration trends
        $userTrends = User::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as registrations')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // User activity by status
        $usersByStatus = User::select('is_active', DB::raw('COUNT(*) as count'))
            ->groupBy('is_active')
            ->get()
            ->pluck('count', 'is_active');

        return [
            'top_campaign_users' => $topCampaignUsers,
            'top_message_users' => $topMessageUsers,
            'registration_trends' => $userTrends,
            'users_by_status' => $usersByStatus,
        ];
    }

    /**
     * Get campaign analytics
     */
    private function getCampaignAnalytics($startDate, $endDate)
    {
        $campaigns = Campaign::whereBetween('created_at', [$startDate, $endDate])->get();
        
        $statusBreakdown = $campaigns->groupBy('status')->map->count();
        
        // Daily campaign trends
        $dailyTrends = Campaign::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top performing campaigns
        $topCampaigns = Campaign::whereBetween('created_at', [$startDate, $endDate])
            ->with('user:id,name')
            ->orderBy('delivered_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'status_breakdown' => $statusBreakdown,
            'daily_trends' => $dailyTrends,
            'top_campaigns' => $topCampaigns,
            'total_campaigns' => $campaigns->count(),
            'total_recipients' => $campaigns->sum('estimated_recipients'),
            'total_sent' => $campaigns->sum('sent_count'),
            'total_delivered' => $campaigns->sum('delivered_count'),
            'avg_delivery_rate' => $campaigns->sum('sent_count') > 0 ? 
                ($campaigns->sum('delivered_count') / $campaigns->sum('sent_count')) * 100 : 0,
        ];
    }

    /**
     * Get financial summary
     */
    private function getFinancialSummary($startDate, $endDate)
    {
        // Payment statistics
        $payments = Payment::whereBetween('created_at', [$startDate, $endDate]);
        
        $paymentStats = [
            'total_payments' => $payments->count(),
            'completed_payments' => $payments->where('status', 'completed')->count(),
            'pending_payments' => $payments->where('status', 'pending')->count(),
            'failed_payments' => $payments->where('status', 'failed')->count(),
            'total_revenue' => $payments->where('status', 'completed')->sum('amount'),
            'total_credits_sold' => $payments->where('status', 'completed')->sum('credits'),
        ];

        // Daily revenue trends
        $revenueTrends = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top paying users
        $topPayingUsers = User::withSum(['payments' => function($query) use ($startDate, $endDate) {
            $query->where('status', 'completed')
                  ->whereBetween('created_at', [$startDate, $endDate]);
        }], 'amount')
        ->orderBy('payments_sum_amount', 'desc')
        ->limit(10)
        ->get();

        return [
            'payment_stats' => $paymentStats,
            'revenue_trends' => $revenueTrends,
            'top_paying_users' => $topPayingUsers,
        ];
    }

    /**
     * Get sender ID analytics
     */
    private function getSenderIdAnalytics($startDate, $endDate)
    {
        $senderIds = SenderID::whereBetween('created_at', [$startDate, $endDate]);
        
        $statusBreakdown = SenderID::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $recentApplications = SenderID::with('user:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_applications' => $senderIds->count(),
            'status_breakdown' => $statusBreakdown,
            'recent_applications' => $recentApplications,
            'approval_rate' => SenderID::count() > 0 ? 
                (SenderID::where('status', 'approved')->count() / SenderID::count()) * 100 : 0,
        ];
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealth($startDate, $endDate)
    {
        // Message delivery rates
        $messages = SmsMessage::whereBetween('created_at', [$startDate, $endDate]);
        
        $deliveryStats = [
            'total_messages' => $messages->count(),
            'delivered_messages' => $messages->where('status', 'delivered')->count(),
            'failed_messages' => $messages->where('status', 'failed')->count(),
            'pending_messages' => $messages->where('status', 'pending')->count(),
        ];

        $deliveryStats['delivery_rate'] = $deliveryStats['total_messages'] > 0 ? 
            ($deliveryStats['delivered_messages'] / $deliveryStats['total_messages']) * 100 : 0;

        // System errors (if you have error logging)
        $errorCount = 0; // You can implement error tracking here

        return [
            'delivery_stats' => $deliveryStats,
            'error_count' => $errorCount,
            'uptime' => '99.9%', // You can implement actual uptime tracking
        ];
    }

    /**
     * Get top users by various metrics
     */
    private function getTopUsers($startDate, $endDate)
    {
        return [
            'by_campaigns' => User::withCount(['campaigns' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])->orderBy('campaigns_count', 'desc')->limit(5)->get(),
            
            'by_messages' => User::withCount(['smsMessages' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])->orderBy('sms_messages_count', 'desc')->limit(5)->get(),
            
            'by_revenue' => User::withSum(['payments' => function($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'amount')->orderBy('payments_sum_amount', 'desc')->limit(5)->get(),
        ];
    }

    /**
     * Get recent activities across the system
     */
    private function getRecentActivities()
    {
        $activities = collect();

        // Recent user registrations
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        foreach ($recentUsers as $user) {
            $activities->push([
                'type' => 'user_registration',
                'description' => "New user registered: {$user->name}",
                'timestamp' => $user->created_at,
                'user' => $user->name,
            ]);
        }

        // Recent campaigns
        $recentCampaigns = Campaign::with('user:id,name')->orderBy('created_at', 'desc')->limit(5)->get();
        foreach ($recentCampaigns as $campaign) {
            $activities->push([
                'type' => 'campaign_created',
                'description' => "Campaign '{$campaign->name}' created by {$campaign->user->name}",
                'timestamp' => $campaign->created_at,
                'user' => $campaign->user->name,
            ]);
        }

        // Recent payments
        $recentPayments = Payment::with('user:id,name')->orderBy('created_at', 'desc')->limit(5)->get();
        foreach ($recentPayments as $payment) {
            $activities->push([
                'type' => 'payment',
                'description' => "Payment of {$payment->amount} TZS by {$payment->user->name}",
                'timestamp' => $payment->created_at,
                'user' => $payment->user->name,
            ]);
        }

        return $activities->sortByDesc('timestamp')->take(15)->values();
    }

    /**
     * Export admin reports to CSV
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $filename = "admin_report_{$type}_" . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() use ($type, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            switch ($type) {
                case 'users':
                    $this->exportUsers($file, $startDate, $endDate);
                    break;
                case 'campaigns':
                    $this->exportCampaigns($file, $startDate, $endDate);
                    break;
                case 'payments':
                    $this->exportPayments($file, $startDate, $endDate);
                    break;
                case 'messages':
                    $this->exportMessages($file, $startDate, $endDate);
                    break;
                default:
                    $this->exportOverview($file, $startDate, $endDate);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    private function exportUsers($file, $startDate, $endDate)
    {
        fputcsv($file, ['Name', 'Phone', 'Email', 'Status', 'Campaigns', 'Messages Sent', 'Total Spent', 'Registered']);
        
        User::with(['campaigns', 'smsMessages', 'payments'])
            ->chunk(100, function($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->name,
                        $user->phone,
                        $user->email,
                        $user->is_active ? 'Active' : 'Inactive',
                        $user->campaigns->count(),
                        $user->smsMessages->count(),
                        $user->payments->where('status', 'completed')->sum('amount'),
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    private function exportCampaigns($file, $startDate, $endDate)
    {
        fputcsv($file, ['Campaign Name', 'User', 'Status', 'Recipients', 'Sent', 'Delivered', 'Failed', 'Created']);
        
        Campaign::with('user:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->chunk(100, function($campaigns) use ($file) {
                foreach ($campaigns as $campaign) {
                    fputcsv($file, [
                        $campaign->name,
                        $campaign->user->name,
                        $campaign->status,
                        $campaign->estimated_recipients,
                        $campaign->sent_count,
                        $campaign->delivered_count,
                        $campaign->failed_count,
                        $campaign->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    private function exportPayments($file, $startDate, $endDate)
    {
        fputcsv($file, ['Reference', 'User', 'Amount', 'Credits', 'Status', 'Gateway', 'Created']);
        
        Payment::with('user:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->chunk(100, function($payments) use ($file) {
                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->reference,
                        $payment->user->name,
                        $payment->amount,
                        $payment->credits,
                        $payment->status,
                        $payment->gateway,
                        $payment->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    private function exportMessages($file, $startDate, $endDate)
    {
        fputcsv($file, ['User', 'Campaign', 'Recipient', 'Status', 'Sender ID', 'Created']);
        
        SmsMessage::with(['user:id,name', 'campaign:id,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->chunk(100, function($messages) use ($file) {
                foreach ($messages as $message) {
                    fputcsv($file, [
                        $message->user->name,
                        $message->campaign->name ?? 'Quick SMS',
                        $message->recipient,
                        $message->status,
                        $message->sender_id,
                        $message->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    private function exportOverview($file, $startDate, $endDate)
    {
        $overview = $this->getOverviewStats($startDate, $endDate);
        
        fputcsv($file, ['Metric', 'Value']);
        foreach ($overview as $key => $value) {
            fputcsv($file, [ucwords(str_replace('_', ' ', $key)), $value]);
        }
    }
}