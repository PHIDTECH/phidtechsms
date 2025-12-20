<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsMessage;
use App\Services\BeemSmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckSmsDeliveryReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:check-delivery-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check delivery status of sent SMS messages from Beem API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SMS delivery report check...');

        // Get messages that are 'sent' and were created more than 5 minutes ago
        // We limit to messages sent in the last 48 hours to avoid checking very old messages forever
        $messages = SmsMessage::where('status', 'sent')
            ->whereNotNull('beem_message_id')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->where('created_at', '>=', Carbon::now()->subHours(48))
            ->get();

        $count = $messages->count();
        $this->info("Found {$count} messages to check.");

        if ($count === 0) {
            return;
        }

        $beemService = new BeemSmsService();
        $updated = 0;

        foreach ($messages as $message) {
            $this->line("Checking message ID: {$message->id} (Beem ID: {$message->beem_message_id})");

            // Beem API requires dest_addr (phone) and request_id (beem_message_id)
            // Ensure phone number format matches what Beem expects (usually without +)
            $phone = str_replace('+', '', $message->phone);
            
            $result = $beemService->getDeliveryReport($phone, $message->beem_message_id);

            if ($result['success'] && !empty($result['data'])) {
                // API returns an array of results, usually one item
                $report = $result['data'][0] ?? null;

                if ($report && isset($report['status'])) {
                    $status = strtoupper($report['status']);
                    $this->info("  - Status from API: {$status}");

                    if ($status === 'DELIVERED') {
                        $message->markAsDelivered($report);
                        $updated++;
                        $this->info("  - Marked as DELIVERED");
                    } elseif ($status === 'UNDELIVERED' || $status === 'FAILED' || $status === 'REJECTED') {
                        $reason = $report['failure_reason'] ?? 'Delivery failed';
                        $message->markAsFailed($reason, $report);
                        $updated++;
                        $this->error("  - Marked as FAILED");
                    } else {
                        // PENDING or other statuses, just update payload if needed
                        $message->update(['dlr_payload' => $report]);
                    }
                }
            } else {
                $this->warn("  - Failed to fetch report: " . ($result['error'] ?? 'Unknown error'));
            }
            
            // Sleep briefly to avoid rate limiting if processing many messages
            usleep(200000); // 0.2 seconds
        }

        $this->info("Completed. Updated {$updated} messages.");
    }
}
