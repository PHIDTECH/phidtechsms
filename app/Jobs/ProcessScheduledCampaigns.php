<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Http\Controllers\CampaignController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessScheduledCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing scheduled campaigns job started');
        
        // Get campaigns that are scheduled and due to be sent
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
            
        Log::info('Found ' . $campaigns->count() . ' campaigns to process');
        
        foreach ($campaigns as $campaign) {
            try {
                Log::info('Processing campaign: ' . $campaign->name, ['campaign_id' => $campaign->id]);
                
                // Update campaign status to queued
                $campaign->update(['status' => 'queued']);
                
                // Process the campaign using the controller method
                $controller = new CampaignController();
                $reflection = new \ReflectionClass($controller);
                $method = $reflection->getMethod('processCampaign');
                $method->setAccessible(true);
                $method->invoke($controller, $campaign);
                
                Log::info('Campaign processed successfully', ['campaign_id' => $campaign->id]);
                
            } catch (\Exception $e) {
                Log::error('Failed to process scheduled campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Mark campaign as failed
                $campaign->update([
                    'status' => 'failed',
                    'failure_reason' => 'Processing error: ' . $e->getMessage()
                ]);
            }
        }
        
        Log::info('Processing scheduled campaigns job completed');
    }
}