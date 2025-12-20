<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\SmsMessage;
use App\Models\Campaign;
use Carbon\Carbon;

class WebhookController extends Controller
{
    /**
     * Handle Beem delivery report webhook
     */
    public function beemDeliveryReport(Request $request)
    {
        try {
            // Log the incoming webhook for debugging
            Log::info('Beem DLR Webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'ip' => $request->ip(),
            ]);

            // Validate the webhook payload
            $validated = $request->validate([
                'request_id' => 'required|string',
                'recipient' => 'required|string',
                'status' => 'required|string',
                'message_id' => 'nullable|string',
                'timestamp' => 'nullable|string',
                'error_code' => 'nullable|string',
                'error_message' => 'nullable|string',
            ]);

            // Find the SMS message by request_id or message_id from Beem
            $smsMessage = SmsMessage::where('id', $validated['request_id'])
                ->orWhere('beem_message_id', $validated['message_id'])
                ->orWhere('beem_message_id', $validated['request_id'])
                ->first();

            if (!$smsMessage) {
                Log::warning('SMS message not found for DLR', [
                    'request_id' => $validated['request_id'],
                    'recipient' => $validated['recipient']
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'SMS message not found'
                ], 404);
            }

            // Map Beem status to our internal status
            $internalStatus = $this->mapBeemStatus($validated['status']);
            
            // Update the SMS message status
            $smsMessage->update([
                'status' => $internalStatus,
                'delivered_at' => $internalStatus === 'delivered' ? now() : null,
                'failed_at' => in_array($internalStatus, ['failed', 'rejected']) ? now() : null,
                'delivery_report' => json_encode($validated),
                'error_code' => $validated['error_code'] ?? null,
                'error_message' => $validated['error_message'] ?? null,
            ]);

            // Update campaign statistics
            $this->updateCampaignStats($smsMessage->campaign_id);

            Log::info('DLR processed successfully', [
                'message_id' => $smsMessage->id,
                'status' => $internalStatus,
                'recipient' => $validated['recipient']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Delivery report processed'
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing Beem DLR webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle Selcom payment webhook (already implemented in PaymentController)
     * This is a placeholder for consistency
     */
    public function selcomPayment(Request $request)
    {
        // Redirect to PaymentController webhook method
        return app(PaymentController::class)->webhook($request);
    }

    /**
     * Map Beem delivery status to our internal status
     */
    private function mapBeemStatus(string $beemStatus): string
    {
        $statusMap = [
            // Successful delivery
            'delivered' => 'delivered',
            'DELIVERED' => 'delivered',
            'success' => 'delivered',
            'SUCCESS' => 'delivered',
            
            // Failed delivery
            'failed' => 'failed',
            'FAILED' => 'failed',
            'undelivered' => 'failed',
            'UNDELIVERED' => 'failed',
            'expired' => 'failed',
            'EXPIRED' => 'failed',
            
            // Rejected by network
            'rejected' => 'rejected',
            'REJECTED' => 'rejected',
            'invalid' => 'rejected',
            'INVALID' => 'rejected',
            
            // Pending/Processing
            'pending' => 'sent',
            'PENDING' => 'sent',
            'sent' => 'sent',
            'SENT' => 'sent',
            'accepted' => 'sent',
            'ACCEPTED' => 'sent',
        ];

        return $statusMap[strtolower($beemStatus)] ?? 'sent';
    }

    /**
     * Update campaign statistics based on message status changes
     */
    private function updateCampaignStats(int $campaignId): void
    {
        try {
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                return;
            }

            // Get message statistics for this campaign
            $stats = DB::table('sms_messages')
                ->where('campaign_id', $campaignId)
                ->select(
                    DB::raw('COUNT(*) as total_messages'),
                    DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                    DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
                    DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count'),
                    DB::raw('SUM(CASE WHEN status IN ("sent", "pending") THEN 1 ELSE 0 END) as pending_count')
                )
                ->first();

            // Calculate delivery rate
            $deliveryRate = $stats->total_messages > 0 
                ? round(($stats->delivered_count / $stats->total_messages) * 100, 2)
                : 0;

            // Update campaign with new statistics
            $campaign->update([
                'total_recipients' => $stats->total_messages,
                'delivered_count' => $stats->delivered_count,
                'failed_count' => $stats->failed_count + $stats->rejected_count,
                'delivery_rate' => $deliveryRate,
            ]);

            // Update campaign status if all messages are processed
            if ($stats->pending_count == 0 && $campaign->status === 'sending') {
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error updating campaign stats', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generic webhook endpoint for testing
     */
    public function test(Request $request)
    {
        Log::info('Test webhook received', [
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Test webhook received',
            'timestamp' => now()->toISOString(),
            'data' => $request->all()
        ]);
    }

    /**
     * Health check endpoint for webhook monitoring
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => 'RodLine SMS Webhooks',
            'version' => '1.0.0'
        ]);
    }
}