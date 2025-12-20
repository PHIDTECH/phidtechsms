<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Campaign;
use App\Models\SmsMessage;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function deliveryReport(Request $request)
    {
        $request->validate([
            'dest_addr' => 'required|string',
            'request_id' => 'required|string',
        ]);
        $user = Auth::user();
        $dest = preg_replace('/\s+/', '', $request->get('dest_addr'));
        $plusDest = str_starts_with($dest, '+') ? $dest : ('+' . ltrim($dest, '+'));
        $requestId = trim($request->get('request_id'));
        $service = new \App\Services\BeemSmsService();
        $result = $service->getDeliveryReport($dest, $requestId);
        if (!is_array($result)) {
            return response()->json(['success' => false, 'error' => 'Unable to fetch delivery report', 'details' => $result], 502);
        }
        $updated = 0;
        foreach ($result as $item) {
            $status = strtoupper($item['status'] ?? '');
            $local = match ($status) {
                'DELIVERED' => 'delivered',
                'PENDING' => 'queued',
                'UNDELIVERED' => 'failed',
                default => null,
            };
            if (!$local) { continue; }
            $msg = SmsMessage::where('user_id', $user->id)
                ->where(function($q) use ($dest, $plusDest) {
                    $q->where('phone', $plusDest)->orWhere('phone', $dest);
                })
                ->when($requestId, function($q) use ($requestId) {
                    $q->where(function($qq) use ($requestId) {
                        $qq->where('beem_message_id', $requestId)->orWhereNull('beem_message_id');
                    });
                })
                ->orderByDesc('created_at')
                ->first();
            if ($msg) {
                if ($local === 'delivered') {
                    $msg->markAsDelivered($item);
                } elseif ($local === 'failed') {
                    $msg->markAsFailed('UNDELIVERED', $item);
                } else {
                    $msg->update(['status' => 'queued', 'dlr_payload' => $item]);
                }
                $updated++;
            }
        }
        return response()->json(['success' => true, 'updated' => $updated, 'reports' => $result]);
    }

    /**
     * Display the reports dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get date range from request or default to last 30 days
        $startDate = request('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = request('end_date', Carbon::now()->format('Y-m-d'));
        
        $reports = [
            'campaign_performance' => $this->getCampaignPerformance($user->id, $startDate, $endDate),
            'delivery_analytics' => $this->getDeliveryAnalytics($user->id, $startDate, $endDate),
            'financial_summary' => $this->getFinancialSummary($user->id, $startDate, $endDate),
            'usage_trends' => $this->getUsageTrends($user->id, $startDate, $endDate),
            'stuck_messages' => $this->getStuckMessages($user->id),
        ];
        
        return view('reports.index', compact('reports', 'startDate', 'endDate'));
    }

    /**
     * Get campaign performance metrics
     */
    private function getCampaignPerformance($userId, $startDate, $endDate)
    {
        $campaigns = Campaign::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['smsMessages'])
            ->get();

        $totalCampaigns = $campaigns->count();
        $completedCampaigns = $campaigns->where('status', 'completed')->count();
        $totalRecipients = $campaigns->sum('estimated_recipients');
        $totalSent = $campaigns->sum('sent_count');
        $totalDelivered = $campaigns->sum('delivered_count');
        $totalFailed = $campaigns->sum('failed_count');
        $avgDeliveryRate = $totalSent > 0 ? ($totalDelivered / $totalSent) * 100 : 0;

        return [
            'total_campaigns' => $totalCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'completion_rate' => $totalCampaigns > 0 ? ($completedCampaigns / $totalCampaigns) * 100 : 0,
            'total_recipients' => $totalRecipients,
            'total_sent' => $totalSent,
            'total_delivered' => $totalDelivered,
            'total_failed' => $totalFailed,
            'avg_delivery_rate' => $avgDeliveryRate,
            'top_campaigns' => $campaigns->sortByDesc('delivered_count')->take(5),
        ];
    }

    /**
     * Get delivery analytics
     */
    private function getDeliveryAnalytics($userId, $startDate, $endDate)
    {
        $messages = SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $statusBreakdown = $messages->groupBy('status')->map->count();
        
        // Daily delivery trends
        $dailyTrends = SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'status_breakdown' => $statusBreakdown,
            'daily_trends' => $dailyTrends,
            'total_messages' => $messages->count(),
            'delivery_rate' => $messages->count() > 0 ? ($messages->where('status', 'delivered')->count() / $messages->count()) * 100 : 0,
        ];
    }

    /**
     * Get financial summary
     */
    private function getFinancialSummary($userId, $startDate, $endDate)
    {
        $transactions = WalletTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $topups = $transactions->where('type', 'topup')->sum('amount');
        $smsSpend = abs($transactions->where('type', 'sms_cost')->sum('amount'));
        $refunds = $transactions->where('type', 'refund')->sum('amount');
        
        // Daily spending trends
        $dailySpending = WalletTransaction::where('user_id', $userId)
            ->where('type', 'sms_cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('ABS(SUM(amount)) as spent')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_topups' => $topups,
            'total_spent' => $smsSpend,
            'total_refunds' => $refunds,
            'net_spend' => $smsSpend - $topups - $refunds,
            'daily_spending' => $dailySpending,
            'avg_daily_spend' => $dailySpending->avg('spent') ?? 0,
        ];
    }

    /**
     * Get usage trends
     */
    private function getUsageTrends($userId, $startDate, $endDate)
    {
        // Monthly usage comparison
        $currentMonth = Carbon::parse($endDate)->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonth();
        
        $currentMonthSms = SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$currentMonth, $currentMonth->copy()->endOfMonth()])
            ->count();
            
        $previousMonthSms = SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$previousMonth, $previousMonth->copy()->endOfMonth()])
            ->count();
            
        $growthRate = $previousMonthSms > 0 ? (($currentMonthSms - $previousMonthSms) / $previousMonthSms) * 100 : 0;
        
        // Peak usage hours (DB-specific extraction of hour from timestamp)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $hourExpr = "HOUR(created_at)";
        } elseif ($driver === 'pgsql') {
            $hourExpr = "EXTRACT(HOUR FROM created_at)";
        } elseif ($driver === 'sqlsrv') {
            $hourExpr = "DATEPART(hour, created_at)";
        } else {
            $hourExpr = "strftime('%H', created_at)"; // SQLite and others
        }

        $hourlyUsage = SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw($hourExpr . ' as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'current_month_sms' => $currentMonthSms,
            'previous_month_sms' => $previousMonthSms,
            'growth_rate' => $growthRate,
            'peak_hours' => $hourlyUsage->take(3),
            'hourly_distribution' => $hourlyUsage,
        ];
    }

    /**
     * Export reports to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'campaigns');
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $filename = "report_{$type}_" . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() use ($user, $type, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            switch ($type) {
                case 'campaigns':
                    $this->exportCampaigns($file, $user->id, $startDate, $endDate);
                    break;
                case 'messages':
                    $this->exportMessages($file, $user->id, $startDate, $endDate);
                    break;
                case 'transactions':
                    $this->exportTransactions($file, $user->id, $startDate, $endDate);
                    break;
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function exportCampaigns($file, $userId, $startDate, $endDate)
    {
        fputcsv($file, ['Campaign Name', 'Status', 'Recipients', 'Sent', 'Delivered', 'Failed', 'Delivery Rate', 'Cost', 'Created At']);
        
        Campaign::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->chunk(100, function($campaigns) use ($file) {
                foreach ($campaigns as $campaign) {
                    $deliveryRate = $campaign->sent_count > 0 ? ($campaign->delivered_count / $campaign->sent_count) * 100 : 0;
                    fputcsv($file, [
                        $campaign->name,
                        $campaign->status,
                        $campaign->estimated_recipients,
                        $campaign->sent_count,
                        $campaign->delivered_count,
                        $campaign->failed_count,
                        number_format($deliveryRate, 2) . '%',
                        'TZS ' . number_format($campaign->estimated_cost, 0),
                        $campaign->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }
    
    private function exportMessages($file, $userId, $startDate, $endDate)
    {
        fputcsv($file, ['Phone Number', 'Message', 'Status', 'Campaign', 'Sender ID', 'Parts', 'Cost', 'Sent At', 'Delivered At']);
        
        SmsMessage::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['campaign'])
            ->chunk(100, function($messages) use ($file) {
                foreach ($messages as $message) {
                    fputcsv($file, [
                        $message->phone_number,
                        substr($message->message_content, 0, 50) . '...',
                        $message->status,
                        $message->campaign->name ?? 'N/A',
                        $message->sender_id,
                        $message->parts,
                        'TZS ' . number_format($message->cost, 0),
                        $message->created_at->format('Y-m-d H:i:s'),
                        $message->delivered_at ? $message->delivered_at->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }
            });
    }
    
    private function exportTransactions($file, $userId, $startDate, $endDate)
    {
        fputcsv($file, ['Type', 'Description', 'Amount', 'Balance After', 'Status', 'Created At']);
        
        WalletTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->chunk(100, function($transactions) use ($file) {
                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        ucfirst($transaction->type),
                        $transaction->description,
                        'TZS ' . number_format($transaction->amount, 0),
                        'TZS ' . number_format($transaction->balance_after, 0),
                        ucfirst($transaction->status),
                        $transaction->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    /**
     * Get messages stuck in 'sent' status for more than 30 minutes
     */
    private function getStuckMessages($userId)
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);
        
        $stuckMessages = SmsMessage::whereHas('campaign', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'sent')
            ->where('sent_at', '<', $thirtyMinutesAgo)
            ->with(['campaign'])
            ->orderBy('sent_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'count' => $stuckMessages->count(),
            'messages' => $stuckMessages,
        ];
    }

    /**
     * Display the modern reports dashboard
     */
    public function modern()
    {
        $user = Auth::user();
        
        // Get date range from request or default to last 7 days
        $startDate = request('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
        $endDate = request('end_date', Carbon::now()->format('Y-m-d'));
        
        $reports = [
            'campaign_performance' => $this->getCampaignPerformance($user->id, $startDate, $endDate),
            'delivery_analytics' => $this->getDeliveryAnalytics($user->id, $startDate, $endDate),
            'financial_summary' => $this->getFinancialSummary($user->id, $startDate, $endDate),
            'usage_trends' => $this->getUsageTrends($user->id, $startDate, $endDate),
            'stuck_messages' => $this->getStuckMessages($user->id),
        ];
        
        return view('reports.modern', compact('reports', 'startDate', 'endDate'));
    }
}
