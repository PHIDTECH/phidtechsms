<?php

namespace App\Jobs;

use App\Models\ContactImport;
use App\Models\ContactGroup;
use App\Services\ContactImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessContactImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importId;
    protected $contactGroupId;
    protected $chunkData;
    protected $chunkNumber;
    protected $totalChunks;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($importId, $contactGroupId, array $chunkData, int $chunkNumber, int $totalChunks)
    {
        $this->importId = $importId;
        $this->contactGroupId = $contactGroupId;
        $this->chunkData = $chunkData;
        $this->chunkNumber = $chunkNumber;
        $this->totalChunks = $totalChunks;
    }

    /**
     * Execute the job.
     */
    public function handle(ContactImportService $importService)
    {
        try {
            Log::info("Processing chunk {$this->chunkNumber}/{$this->totalChunks} for import {$this->importId}");

            $import = ContactImport::findOrFail($this->importId);
            $contactGroup = ContactGroup::findOrFail($this->contactGroupId);

            // Verify the import is still in processing state
            if ($import->status !== 'processing') {
                Log::warning("Import {$this->importId} is no longer in processing state. Skipping chunk {$this->chunkNumber}");
                return;
            }

            $result = $importService->processChunk(
                $import,
                $contactGroup,
                $this->chunkData,
                $this->chunkNumber,
                $this->totalChunks
            );

            Log::info("Chunk {$this->chunkNumber}/{$this->totalChunks} completed for import {$this->importId}. " .
                     "Successful: {$result['successful']}, Failed: {$result['failed']}");

        } catch (\Exception $e) {
            Log::error("Chunk processing failed for import {$this->importId}, chunk {$this->chunkNumber}: " . $e->getMessage());
            
            // Mark the import as failed
            $import = ContactImport::find($this->importId);
            if ($import) {
                $import->update([
                    'status' => 'failed',
                    'error_message' => "Chunk {$this->chunkNumber} failed: " . $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job failed permanently for import {$this->importId}, chunk {$this->chunkNumber}: " . $exception->getMessage());

        // Mark the import as failed
        $import = ContactImport::find($this->importId);
        if ($import) {
            $import->update([
                'status' => 'failed',
                'error_message' => "Chunk {$this->chunkNumber} failed permanently: " . $exception->getMessage()
            ]);
        }
    }
}