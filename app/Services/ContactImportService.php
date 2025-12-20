<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactImport;
use App\Jobs\ProcessContactImportChunk;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;
use Carbon\Carbon;

class ContactImportService
{
    protected $beemService;
    protected $chunkSize = 1000; // Process 1000 contacts per chunk

    public function __construct(BeemSmsService $beemService)
    {
        $this->beemService = $beemService;
    }

    /**
     * Process large import files using chunked processing.
     */
    public function processLargeImport(ContactImport $import, ContactGroup $contactGroup)
    {
        try {
            $import->update(['status' => 'processing']);

            // Parse file and get all data
            $allData = $this->parseFile(storage_path('app/' . $import->file_path));
            
            if (empty($allData)) {
                throw new \Exception('No data found in file');
            }

            // Validate and normalize data
            $validationResult = $this->validateAndNormalizeData($allData, $import->column_mapping);
            
            // Update import with validation results
            $import->update([
                'validation_errors' => $validationResult['errors'],
                'successful_imports' => 0, // Will be updated as chunks complete
                'failed_imports' => $validationResult['invalid_count']
            ]);

            // If file has more than 10k rows, use chunked processing
            if (count($validationResult['valid_data']) > 10000) {
                $this->processInChunks($import, $contactGroup, $validationResult['valid_data']);
            } else {
                // Process directly for smaller files
                $this->processDirectly($import, $contactGroup, $validationResult['valid_data']);
            }

            return [
                'success' => true,
                'message' => 'Import processing started',
                'total_valid' => count($validationResult['valid_data']),
                'total_invalid' => $validationResult['invalid_count']
            ];

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('Import processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process import in chunks using queue jobs.
     */
    protected function processInChunks(ContactImport $import, ContactGroup $contactGroup, array $validData)
    {
        $chunks = array_chunk($validData, $this->chunkSize);
        $totalChunks = count($chunks);

        // Update import with chunk information
        $import->update([
            'metadata' => array_merge($import->metadata ?? [], [
                'total_chunks' => $totalChunks,
                'completed_chunks' => 0,
                'processing_method' => 'chunked'
            ])
        ]);

        // Dispatch chunk processing jobs
        foreach ($chunks as $chunkIndex => $chunk) {
            ProcessContactImportChunk::dispatch(
                $import->id,
                $contactGroup->id,
                $chunk,
                $chunkIndex + 1,
                $totalChunks
            )->onQueue('imports');
        }

        Log::info("Dispatched {$totalChunks} chunks for import {$import->id}");
    }

    /**
     * Process import directly for smaller files.
     */
    protected function processDirectly(ContactImport $import, ContactGroup $contactGroup, array $validData)
    {
        $importResult = $this->importContacts($validData, $contactGroup, $import);

        $import->update([
            'status' => 'completed',
            'successful_imports' => $importResult['successful'],
            'failed_imports' => $import->failed_imports + $importResult['failed'],
            'validation_errors' => array_merge($import->validation_errors ?? [], $importResult['errors']),
            'completed_at' => now(),
            'metadata' => array_merge($import->metadata ?? [], [
                'processing_method' => 'direct'
            ])
        ]);

        // Clean up file
        Storage::delete($import->file_path);

        Log::info("Direct import completed for import {$import->id}");
    }

    /**
     * Process a single chunk of contacts.
     */
    public function processChunk(ContactImport $import, ContactGroup $contactGroup, array $chunkData, int $chunkNumber, int $totalChunks)
    {
        try {
            DB::beginTransaction();

            $importResult = $this->importContacts($chunkData, $contactGroup, $import);

            // Update import progress
            $metadata = $import->metadata ?? [];
            $metadata['completed_chunks'] = ($metadata['completed_chunks'] ?? 0) + 1;
            
            $import->increment('successful_imports', $importResult['successful']);
            $import->increment('failed_imports', $importResult['failed']);
            
            // Merge validation errors
            $existingErrors = $import->validation_errors ?? [];
            $newErrors = array_merge($existingErrors, $importResult['errors']);
            
            $import->update([
                'validation_errors' => $newErrors,
                'metadata' => $metadata
            ]);

            // Check if all chunks are completed
            if ($metadata['completed_chunks'] >= $totalChunks) {
                $import->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                // Clean up file
                Storage::delete($import->file_path);
                
                Log::info("All chunks completed for import {$import->id}");
            }

            DB::commit();

            return [
                'success' => true,
                'chunk_number' => $chunkNumber,
                'successful' => $importResult['successful'],
                'failed' => $importResult['failed']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Chunk {$chunkNumber} failed for import {$import->id}: " . $e->getMessage());
            
            // Mark import as failed if chunk processing fails
            $import->update([
                'status' => 'failed',
                'error_message' => "Chunk {$chunkNumber} failed: " . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Parse uploaded file (CSV or Excel).
     */
    public function parseFile($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $data = [];

        try {
            if (strtolower($extension) === 'csv') {
                $csv = Reader::createFromPath($filePath, 'r');
                $csv->setHeaderOffset(0);
                $data = iterator_to_array($csv->getRecords());
            } else {
                // Excel file
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                if (!empty($rows)) {
                    $headers = array_shift($rows);
                    foreach ($rows as $row) {
                        if (array_filter($row)) { // Skip empty rows
                            $data[] = array_combine($headers, $row);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('File parsing error: ' . $e->getMessage());
            throw $e;
        }

        return $data;
    }

    /**
     * Validate and normalize contact data.
     */
    public function validateAndNormalizeData($data, $columnMapping)
    {
        $validData = [];
        $errors = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // Account for header row
            $contact = [];
            $rowErrors = [];

            // Map columns
            foreach ($columnMapping as $field => $column) {
                if (isset($row[$column])) {
                    $contact[$field] = trim($row[$column]);
                }
            }

            // Validate phone number (required)
            if (empty($contact['phone'])) {
                $rowErrors[] = 'Phone number is required';
            } else {
                try {
                    $normalizedPhone = Contact::normalizePhoneNumber($contact['phone']);
                    $contact['phone'] = $normalizedPhone;
                } catch (\Exception $e) {
                    $rowErrors[] = 'Invalid phone number format';
                }
            }

            // Validate email if provided
            if (!empty($contact['email']) && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email format';
            }

            // Validate date of birth if provided
            if (!empty($contact['date_of_birth'])) {
                try {
                    $contact['date_of_birth'] = Carbon::parse($contact['date_of_birth'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $rowErrors[] = 'Invalid date of birth format';
                }
            }

            if (empty($rowErrors)) {
                $validData[] = $contact;
                $validCount++;
            } else {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'reason' => implode(', ', $rowErrors),
                    'raw_phone' => $row[$columnMapping['phone']] ?? '',
                    'normalized_phone' => $contact['phone'] ?? '',
                    'data' => $row
                ];
                $invalidCount++;
            }
        }

        return [
            'valid_data' => $validData,
            'errors' => $errors,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount
        ];
    }

    /**
     * Import validated contacts.
     */
    public function importContacts($validData, $contactGroup, $import)
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($validData as $index => $contactData) {
            try {
                // Check for duplicates within the user's contacts
                $existingContact = Contact::where('user_id', $import->user_id)
                    ->where('phone', $contactData['phone'])
                    ->first();

                if ($existingContact) {
                    $errors[] = [
                        'row_number' => $index + 2,
                        'reason' => 'Duplicate phone number',
                        'phone' => $contactData['phone']
                    ];
                    $failed++;
                    continue;
                }

                // Create contact locally
                $contact = Contact::create([
                    'user_id' => $import->user_id,
                    'contact_group_id' => $contactGroup->id,
                    'name' => $contactData['name'] ?? '',
                    'phone' => $contactData['phone'],
                    'email' => $contactData['email'] ?? null,
                    'date_of_birth' => $contactData['date_of_birth'] ?? null,
                ]);

                // Create contact in Beem if address book exists
                if ($contactGroup->beem_address_book_id) {
                    try {
                        $beemContact = $this->beemService->createContact([
                            'address_book_id' => $contactGroup->beem_address_book_id,
                            'phone_number' => $contact->phone,
                            'first_name' => $contact->name,
                            'email' => $contact->email
                        ]);

                        $contact->update(['beem_contact_id' => $beemContact['id'] ?? null]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to create contact in Beem: ' . $e->getMessage());
                        // Don't fail the entire import for Beem API issues
                    }
                }

                $successful++;

            } catch (\Exception $e) {
                $errors[] = [
                    'row_number' => $index + 2,
                    'reason' => 'Database error: ' . $e->getMessage(),
                    'phone' => $contactData['phone'] ?? ''
                ];
                $failed++;
                Log::error('Contact import error: ' . $e->getMessage());
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Get import progress for a specific import.
     */
    public function getImportProgress(ContactImport $import)
    {
        $metadata = $import->metadata ?? [];
        
        if (isset($metadata['total_chunks'])) {
            $completedChunks = $metadata['completed_chunks'] ?? 0;
            $totalChunks = $metadata['total_chunks'];
            $progress = $totalChunks > 0 ? ($completedChunks / $totalChunks) * 100 : 0;
        } else {
            $progress = $import->status === 'completed' ? 100 : 
                       ($import->status === 'processing' ? 50 : 0);
        }

        return [
            'status' => $import->status,
            'progress' => round($progress, 2),
            'successful_imports' => $import->successful_imports,
            'failed_imports' => $import->failed_imports,
            'total_rows' => $import->total_rows,
            'completed_chunks' => $metadata['completed_chunks'] ?? 0,
            'total_chunks' => $metadata['total_chunks'] ?? 1,
            'processing_method' => $metadata['processing_method'] ?? 'direct'
        ];
    }

    /**
     * Cancel an ongoing import.
     */
    public function cancelImport(ContactImport $import)
    {
        if ($import->status !== 'processing') {
            throw new \Exception('Import is not in processing state');
        }

        // Update status to cancelled
        $import->update([
            'status' => 'cancelled',
            'error_message' => 'Import cancelled by user'
        ]);

        // Clean up file if it exists
        if (Storage::exists($import->file_path)) {
            Storage::delete($import->file_path);
        }

        Log::info("Import {$import->id} cancelled by user {$import->user_id}");

        return true;
    }

    /**
     * Generate comprehensive import summary.
     */
    public function generateImportSummary(ContactImport $import)
    {
        $summary = [
            'import_id' => $import->id,
            'filename' => $import->filename,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'successful_imports' => $import->successful_imports,
            'failed_imports' => $import->failed_imports,
            'validation_errors_count' => count($import->validation_errors ?? []),
            'contact_group' => $import->contactGroup ? [
                'id' => $import->contactGroup->id,
                'name' => $import->contactGroup->name
            ] : null,
            'created_at' => $import->created_at,
            'completed_at' => $import->completed_at,
            'processing_time' => $import->completed_at ? 
                $import->created_at->diffInSeconds($import->completed_at) : null,
            'metadata' => $import->metadata ?? []
        ];

        return $summary;
    }
}