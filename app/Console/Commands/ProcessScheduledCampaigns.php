<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledCampaigns as ProcessScheduledCampaignsJob;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled SMS campaigns that are due to be sent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to process scheduled campaigns...');
        
        // Dispatch the job
        ProcessScheduledCampaignsJob::dispatch();
        
        $this->info('Scheduled campaigns processing job has been dispatched.');
        
        return 0;
    }
}