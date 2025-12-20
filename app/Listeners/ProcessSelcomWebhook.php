<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class ProcessSelcomWebhook
{
    /**
     * Handle the event dispatched by the Selcom package.
     *
     * @param \Bryceandy\Selcom\Events\CheckoutWebhookReceived $event
     */
    public function handle($event): void
    {
        try {
            $orderId = $event->orderId;

            $row = DB::table('selcom_payments')
                ->where('order_id', $orderId)
                ->first();

            if (!$row) {
                Log::warning('Selcom webhook row not found', ['order_id' => $orderId]);
                return;
            }

            // Our Payment reference is saved as Selcom transid
            $payment = Payment::where('reference', $row->transid)->first();

            if (!$payment) {
                Log::warning('Local payment not found for Selcom transid', [
                    'order_id' => $orderId,
                    'transid' => $row->transid,
                ]);
                return;
            }

            $status = strtoupper((string) ($row->payment_status ?? ''));

            if (in_array($status, ['COMPLETED', 'SUCCESS'])) {
                $payment->update([
                    'status' => 'completed',
                    'gateway_transaction_id' => $row->transid,
                    'completed_at' => now(),
                    'webhook_data' => [
                        'order_id' => $orderId,
                        'payment_status' => $status,
                    ],
                ]);

                // Credit SMS to user using BeemSmsService
                $beemService = new \App\Services\BeemSmsService();
                $result = $beemService->processSMSPurchase(
                    $payment->user_id,
                    $payment->credits,
                    'Payment completed - Order: ' . $orderId,
                    $payment->reference
                );

                if (!($result['success'] ?? false)) {
                    Log::error('SMS crediting failed after Selcom completion', [
                        'payment_id' => $payment->id,
                        'error' => $result['error'] ?? 'unknown',
                    ]);
                }

                return;
            }

            if (in_array($status, ['FAILED', 'CANCELLED'])) {
                $payment->update([
                    'status' => 'failed',
                    'gateway_transaction_id' => $row->transid,
                    'webhook_data' => [
                        'order_id' => $orderId,
                        'payment_status' => $status,
                    ],
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Error processing Selcom webhook event', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}

