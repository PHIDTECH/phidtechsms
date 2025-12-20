<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactImport;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;
use Carbon\Carbon;

class ContactImportController extends Controller
{
    protected $beemService;

    public function __construct(BeemSmsService $beemService)
    {
        $this->beemService = $beemService;
        $this->middleware('auth');
        $this->middleware('can:manage-contact-groups')->only(['createContactGroup']);
    }

    /**
     * Display the import page.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ensure the user has a default contact group to import into
        $defaultGroup = ContactGroup::getDefaultForUser($user->id);
        if (!$defaultGroup) {
            ContactGroup::createDefaultForUser($user->id);
        }
        
        // Get user's active contact groups for selection
        $contactGroups = ContactGroup::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // Get recent imports
        $recentImports = ContactImport::where('user_id', $user->id)
            ->with('contactGroup')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('contacts.import.index', compact('recentImports', 'contactGroups'));
    }

    public function showSheet()
    {
        return view('contacts.import.import_sheet');
    }

    /**
     * Handle file upload and create import job.
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'use_headers' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'import';
            $filename = time() . '_' . $baseName;
            $extension = $file->getClientOriginalExtension();
            $storedName = trim($filename . ($extension ? '.' . $extension : ''), '.');
            $path = $file->storeAs('imports', $storedName);

            $useHeaders = $request->boolean('use_headers', true);

            // Parse the file and get preview data
            $absolutePath = Storage::disk('local')->path($path);
            $data = $this->parseFile($absolutePath, $useHeaders);
            
            if (empty($data)) {
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is empty or could not be parsed.'
                ], 422);
            }

            $firstRow = reset($data);
            if (!is_array($firstRow) || empty($firstRow)) {
                Log::warning('Contact import upload: unable to detect headers', [
                    'user_id' => Auth::id(),
                    'file' => $originalName,
                    'first_row' => $firstRow,
                ]);
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to detect column headers from the uploaded file.',
                ], 422);
            }

            Log::debug('Contact import upload parsed data', [
                'user_id' => Auth::id(),
                'file' => $originalName,
                'headers' => array_keys($firstRow),
                'first_row' => $firstRow,
                'total_rows' => count($data),
            ]);

            // Create import job
            $import = ContactImport::create([
                'user_id' => Auth::id(),
                'filename' => $originalName,
                'original_filename' => $originalName,
                'file_path' => $path,
                'total_rows' => count($data),
                'status' => 'pending',
                'metadata' => [
                    'use_headers' => $useHeaders
                ]
            ]);

            $previewData = [
                'import_id' => $import->id,
                'filename' => $originalName,
                'total_rows' => count($data),
                'headers' => array_map(static fn ($header) => (string) $header, array_keys($firstRow)),
                'rows' => array_slice($data, 0, 10),
                'use_headers' => $useHeaders
            ];

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $previewData
                ]);
            }

            // Store preview data in session and redirect for non-AJAX requests
            return redirect()->route('contacts.import.sheet')->with('import_preview', $previewData);

        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error parsing file: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Process column mapping.
     */
    public function processMapping(Request $request)
    {
        // Increase execution time for large files
        set_time_limit(300); // 5 minutes
        
        $validator = Validator::make($request->all(), [
            'import_id' => 'required|exists:contact_imports,id',
            'column_mapping' => 'required|array',
            'column_mapping.phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $import = ContactImport::findOrFail($request->import_id);
        // Proceed for any authenticated user to validate phone-only imports
        // Log cross-account mapping for audit
        if ($import->user_id !== Auth::id()) {
            Log::warning('Contact import mapping by non-owner', [
                'import_id' => $import->id,
                'owner_id' => $import->user_id,
                'actor_id' => Auth::id(),
            ]);
        }

        try {
            $metadata = $import->metadata ?? [];
            $useHeaders = $metadata['use_headers'] ?? true;

            // Parse full file for validation
            $absolutePath = Storage::disk('local')->path($import->file_path);
            $allData = $this->parseFile($absolutePath, $useHeaders);
            
            // Validate and normalize data
            $validationResult = $this->validateAndNormalizeData($allData, $request->column_mapping);
            $validatedDataPath = $this->storeValidatedDataset($import, $validationResult['valid_data']);
            $metadata = array_merge($metadata, [
                'use_headers' => $useHeaders,
                'validated_data_path' => $validatedDataPath,
                'valid_total' => $validationResult['valid_count'],
                'invalid_total' => $validationResult['invalid_count'],
            ]);
            
            // Update import with mapping and validation results
            $import->update([
                'column_mapping' => $request->column_mapping,
                'validation_errors' => $validationResult['errors'],
                'successful_imports' => $validationResult['valid_count'],
                'failed_imports' => $validationResult['invalid_count'],
                'metadata' => $metadata
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'valid_count' => $validationResult['valid_count'],
                    'invalid_count' => $validationResult['invalid_count'],
                    'summary' => [
                        'valid' => $validationResult['valid_count'],
                        'invalid' => $validationResult['invalid_count'],
                    ],
                    'preview_valid' => array_slice($validationResult['preview_valid'], 0, 25),
                    'preview_invalid' => array_slice($validationResult['errors'], 0, 25),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Mapping processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing mapping: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get available contact groups for selection.
     */
    public function getContactGroups(Request $request)
    {
        $user = Auth::user();
        
        $groups = ContactGroup::where('user_id', $user->id)
            ->where('is_active', true)
            ->withCount('contacts')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'beem_address_book_id', 'is_default', 'color']);

        return response()->json([
            'success' => true,
            'data' => $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'beem_address_book_id' => $group->beem_address_book_id,
                    'is_default' => $group->is_default,
                    'color' => $group->color,
                    'contacts_count' => $group->contacts_count ?? 0,
                ];
            })
        ]);
    }

    /**
     * Return validated rows for an import so the user can pick which ones to keep.
     */
    public function getValidatedRows(Request $request, ContactImport $import)
    {
        if ($import->user_id !== Auth::id()) {
            Log::warning('Validated rows requested by non-owner', [
                'import_id' => $import->id,
                'owner_id' => $import->user_id,
                'actor_id' => Auth::id(),
            ]);
        }

        if (empty($import->column_mapping)) {
            return response()->json([
                'success' => false,
                'message' => 'Mapping has not been configured for this import.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 25);

        $validData = $this->loadValidatedData($import);
        $total = count($validData);

        $lastPage = max((int) ceil($total / $perPage), 1);
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($validData, $offset, $perPage);

        $rows = array_map(function (array $row) {
            return [
                'row_number' => $row['row_number'] ?? null,
                'name' => $row['display_name'] ?? null,
                'phone' => $row['normalized_phone'] ?? $row['mob_no'] ?? null,
                'additional_phone' => $row['mob_no2'] ?? null,
                'email' => $row['email'] ?? null,
                'city' => $row['city'] ?? null,
                'country' => $row['country'] ?? null,
                'gender' => $row['gender'] ?? null,
                'birth_date' => $row['birth_date'] ?? null,
                'raw' => $row['raw'] ?? [],
            ];
        }, $pageRows);

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                ],
            ],
        ]);
    }

    /**
     * Create new contact group with Beem address book.
     */
    public function createContactGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            // Create address book in Beem
            $beemResponse = $this->beemService->createAddressBook(
                $request->name,
                $request->description
            );

            $beemAddressBookId = null;
            if (is_array($beemResponse) && ($beemResponse['success'] ?? false)) {
                $beemAddressBookId = $beemResponse['data']['id'] ?? null;
            }

            // Create local contact group
            $group = ContactGroup::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'color' => '#3B82F6',
                'beem_address_book_id' => $beemAddressBookId,
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $group
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating contact group: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating contact group: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Process the import.
     */
    public function processImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_id' => 'required|exists:contact_imports,id',
            'contact_group_id' => 'required|exists:contact_groups,id',
            'selection_mode' => 'nullable|in:all,manual',
            'selected_rows' => 'nullable|array',
            'selected_rows.*' => 'integer|min:1',
            'excluded_rows' => 'nullable|array',
            'excluded_rows.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $import = ContactImport::findOrFail($request->import_id);
        $contactGroup = ContactGroup::findOrFail($request->contact_group_id);
        
        // Verify ownership
        if ($import->user_id !== Auth::id() || $contactGroup->user_id !== Auth::id()) {
            Log::warning('Process import initiated by non-owner', [
                'import_id' => $import->id,
                'owner_id' => $import->user_id,
                'group_owner' => $contactGroup->user_id,
                'actor_id' => Auth::id(),
            ]);
        }

        try {
            if (empty($import->column_mapping) || !is_array($import->column_mapping)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Column mapping has not been configured for this import.'
                ], 422);
            }

            $selectionMode = $request->input('selection_mode', 'all');
            $selectedRows = collect($request->input('selected_rows', []))
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->values()
                ->all();
            $excludedRows = collect($request->input('excluded_rows', []))
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->values()
                ->all();

            if ($selectionMode === 'manual' && empty($selectedRows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one row to import.',
                ], 422);
            }

            $validData = $this->loadValidatedData($import);
            $totalValid = count($validData);

            if ($totalValid === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid rows available for import.',
                ], 422);
            }

            $filteredData = $validData;
            if ($selectionMode === 'manual') {
                $allowed = array_flip($selectedRows);
                $filteredData = array_values(array_filter($validData, function ($row) use ($allowed) {
                    $rowNumber = $row['row_number'] ?? null;
                    return $rowNumber && isset($allowed[$rowNumber]);
                }));
            } elseif (!empty($excludedRows)) {
                $blocked = array_flip($excludedRows);
                $filteredData = array_values(array_filter($validData, function ($row) use ($blocked) {
                    $rowNumber = $row['row_number'] ?? null;
                    return !$rowNumber || !isset($blocked[$rowNumber]);
                }));
            }

            if (empty($filteredData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected rows were filtered out. Nothing to import.',
                ], 422);
            }

            $import->update([
                'contact_group_id' => $contactGroup->id,
                'status' => 'processing'
            ]);

            // Import contacts
            $importResult = $this->importContacts($filteredData, $contactGroup, $import);

            // Update import status
            $metadata = $import->metadata ?? [];
            $cachedPath = $metadata['validated_data_path'] ?? null;
            $metadata['last_selection'] = [
                'mode' => $selectionMode,
                'selected_rows' => $selectionMode === 'manual' ? $selectedRows : null,
                'excluded_rows' => $selectionMode === 'all' ? $excludedRows : null,
                'selected_count' => count($filteredData),
            ];

            if ($cachedPath && Storage::disk('local')->exists($cachedPath)) {
                Storage::disk('local')->delete($cachedPath);
                unset($metadata['validated_data_path']);
            }

            Storage::disk('local')->delete($import->file_path);

            $processedRows = ($metadata['valid_total'] ?? $totalValid) + ($metadata['invalid_total'] ?? 0);

            $import->update([
                'status' => 'completed',
                'successful_imports' => $importResult['successful'],
                'failed_imports' => $importResult['failed'],
                'validation_errors' => $importResult['errors'],
                'processed_rows' => $processedRows,
                'completed_at' => now(),
                'metadata' => $metadata,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'successful' => $importResult['successful'],
                    'failed' => $importResult['failed'],
                    'total' => $import->total_rows,
                    'selected' => count($filteredData),
                    'summary' => $import->getSummary()
                ]
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('Import processing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing import: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get current import status.
     */
    public function getImportStatus(ContactImport $import)
    {
        if ($import->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $import->status,
                'total_rows' => $import->total_rows,
                'processed_rows' => $import->processed_rows,
                'successful_imports' => $import->successful_imports,
                'failed_imports' => $import->failed_imports,
                'error_message' => $import->error_message,
                'completed_at' => optional($import->completed_at)->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Download error report CSV.
     */
    public function downloadErrorReport($importId)
    {
        $import = ContactImport::findOrFail($importId);
        
        // Verify ownership
        if ($import->user_id !== Auth::id()) {
            abort(403);
        }

        if (empty($import->validation_errors)) {
            abort(404, 'No errors found for this import.');
        }

        $csv = $this->generateErrorReportCsv($import->validation_errors);
        
        $filename = 'import_errors_' . $import->id . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Parse uploaded file (CSV or Excel).
     */
    private function parseFile($filePath, $useHeaders = true)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $data = [];

        try {
            if (strtolower($extension) === 'csv') {
                $csv = Reader::createFromPath($filePath, 'r');
                if ($useHeaders) {
                    $csv->setHeaderOffset(0);
                    $data = iterator_to_array($csv->getRecords());
                } else {
                    $records = iterator_to_array($csv->getRecords());
                    if (!empty($records)) {
                        $firstRow = is_array($records[0]) ? $records[0] : (array)$records[0];
                        $columnCount = count($firstRow);
                        $headers = [];
                        for ($i = 0; $i < $columnCount; $i++) {
                            $headers[] = 'column_' . ($i + 1);
                        }

                        foreach ($records as $row) {
                            $rowArray = is_array($row) ? $row : (array)$row;
                            if (!array_filter($rowArray)) {
                                continue;
                            }
                            $rowValues = array_values($rowArray);
                            $rowValues = array_pad($rowValues, count($headers), null);
                            $data[] = array_combine($headers, array_map(function ($value) {
                                return is_string($value) ? trim($value) : $value;
                            }, $rowValues));
                        }
                    }
                }
            } else {
                // Excel file
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                if (!empty($rows)) {
                    if ($useHeaders) {
                        $headers = array_map(function ($header) {
                            return is_string($header) ? trim($header) : $header;
                        }, array_shift($rows));
                    } else {
                        $columnCount = count($rows[0]);
                        $headers = [];
                        for ($i = 0; $i < $columnCount; $i++) {
                            $headers[] = 'column_' . ($i + 1);
                        }
                    }

                    foreach ($rows as $row) {
                        if (array_filter($row)) { // Skip empty rows
                            $rowValues = array_pad($row, count($headers), null);
                            $data[] = array_combine($headers, array_map(function ($value) {
                                return is_string($value) ? trim($value) : $value;
                            }, $rowValues));
                        }
                    }
                } elseif (!$useHeaders && empty($rows)) {
                    $data = [];
                }
            }
        } catch (\Exception $e) {
            Log::error('File parsing error: ' . $e->getMessage());
            throw $e;
        }

        if (empty($data)) {
            return [];
        }

        return array_values(array_map(function ($row) {
            return is_array($row) ? $row : (array) $row;
        }, $data));
    }

    /**
     * Cache validated dataset on disk for subsequent review and import steps.
     */
    private function storeValidatedDataset(ContactImport $import, array $validData): string
    {
        $metadata = $import->metadata ?? [];
        $disk = Storage::disk('local');

        if (!empty($metadata['validated_data_path']) && $disk->exists($metadata['validated_data_path'])) {
            $disk->delete($metadata['validated_data_path']);
        }

        $directory = 'contact-imports/cache';
        $disk->makeDirectory($directory);

        $fileName = 'import_' . $import->id . '_validated.json';
        $relativePath = $directory . '/' . $fileName;

        $disk->put($relativePath, json_encode($validData, JSON_PRETTY_PRINT));

        return $relativePath;
    }

    /**
     * Load cached validated dataset or regenerate it if needed.
     */
    private function loadValidatedData(ContactImport $import): array
    {
        $metadata = $import->metadata ?? [];
        $disk = Storage::disk('local');

        if (!empty($metadata['validated_data_path']) && $disk->exists($metadata['validated_data_path'])) {
            $json = $disk->get($metadata['validated_data_path']);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (empty($import->column_mapping)) {
            return [];
        }

        if (!$disk->exists($import->file_path)) {
            return [];
        }

        $useHeaders = $metadata['use_headers'] ?? true;
        $absolutePath = $disk->path($import->file_path);
        $allData = $this->parseFile($absolutePath, $useHeaders);
        $validationResult = $this->validateAndNormalizeData($allData, $import->column_mapping);

        $validatedDataPath = $this->storeValidatedDataset($import, $validationResult['valid_data']);
        $metadata = array_merge($metadata, [
            'validated_data_path' => $validatedDataPath,
            'valid_total' => $validationResult['valid_count'],
            'invalid_total' => $validationResult['invalid_count'],
        ]);

        $import->update([
            'metadata' => $metadata,
            'successful_imports' => $validationResult['valid_count'],
            'failed_imports' => $validationResult['invalid_count'],
            'validation_errors' => $validationResult['errors'],
        ]);

        return $validationResult['valid_data'];
    }

    /**
     * Validate and normalize contact data.
     */
    private function validateAndNormalizeData($data, $columnMapping)
    {
        // Increase memory limit for large datasets
        ini_set('memory_limit', '512M');
        
        $validData = [];
        $previewValid = [];
        $errors = [];
        $validCount = 0;
        $invalidCount = 0;
        $seenPhones = [];

        $fieldMap = [
            'phone' => 'mob_no',
            'additional_phone' => 'mob_no2',
            'title' => 'title',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'gender' => 'gender',
            'dob' => 'birth_date',
            'address' => 'address',
            'city' => 'city',
            'country' => 'country',
            'email' => 'email',
            'area' => 'area',
        ];

        // Process in chunks to avoid memory issues
        $chunkSize = 1000;
        $chunks = array_chunk($data, $chunkSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $index => $row) {
                $actualIndex = ($chunkIndex * $chunkSize) + $index;
                $rowNumber = $actualIndex + 2; // header offset
            $payload = [
                'mob_no' => null,
                'mob_no2' => null,
                'title' => null,
                'first_name' => null,
                'last_name' => null,
                'gender' => null,
                'birth_date' => null,
                'address' => null,
                'city' => null,
                'country' => null,
                'email' => null,
                'area' => null,
            ];
            $rowErrors = [];
            $rawPhone = null;

            foreach ($fieldMap as $mappingKey => $targetKey) {
                if (empty($columnMapping[$mappingKey])) {
                    continue;
                }
                $columnName = $columnMapping[$mappingKey];
                if (!array_key_exists($columnName, $row)) {
                    continue;
                }

                $value = is_string($row[$columnName]) ? trim($row[$columnName]) : $row[$columnName];

                if ($mappingKey === 'phone') {
                    $rawPhone = $value;
                }

                if ($value === '' || $value === null) {
                    $payload[$targetKey] = null;
                } else {
                    $payload[$targetKey] = $value;
                }
            }

            // Validate phone
            if (empty($payload['mob_no'])) {
                $rowErrors[] = 'Phone number is required';
            } else {
                $normalizedPhone = Contact::normalizePhoneNumber($payload['mob_no']);
                if (!$normalizedPhone) {
                    $rowErrors[] = 'Invalid phone number format';
                } else {
                    if (in_array($normalizedPhone, $seenPhones, true)) {
                        $rowErrors[] = 'Duplicate phone number in file';
                    }
                    $payload['mob_no'] = $normalizedPhone;
                }
            }

            // Normalize additional phone
            if (!empty($payload['mob_no2'])) {
                $normalizedSecondary = Contact::normalizePhoneNumber($payload['mob_no2']);
                $payload['mob_no2'] = $normalizedSecondary ?: null;
            }

            // Validate email
            if (!empty($payload['email']) && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email format';
            }

            // Normalize gender
            if (!empty($payload['gender'])) {
                $payload['gender'] = strtolower($payload['gender']);
            }

            // Validate DOB
            if (!empty($payload['birth_date'])) {
                try {
                    $payload['birth_date'] = Carbon::parse($payload['birth_date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $rowErrors[] = 'Invalid date of birth format';
                }
            }

            $displayNameParts = array_filter([
                $payload['title'],
                $payload['first_name'],
                $payload['last_name'],
            ]);
            $displayName = trim(implode(' ', $displayNameParts));

            if (empty($rowErrors) && !empty($payload['mob_no'])) {
                $seenPhones[] = $payload['mob_no'];
                $validCount++;

                $validEntry = array_merge($payload, [
                    'normalized_phone' => $payload['mob_no'],
                    'display_name' => $displayName ?: $payload['mob_no'],
                    'row_number' => $rowNumber,
                    'raw' => $row,
                ]);

                $validData[] = $validEntry;

                if (count($previewValid) < 25) {
                    $previewValid[] = [
                        'name' => $displayName ?: $payload['mob_no'],
                        'phone' => $payload['mob_no'],
                        'email' => $payload['email'],
                        'city' => $payload['city'],
                        'status' => 'ready',
                    ];
                }
            } else {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'reason' => implode(', ', $rowErrors),
                    'raw_phone' => $rawPhone,
                    'normalized_phone' => $payload['mob_no'] ?? null,
                    'data' => $row,
                ];
                $invalidCount++;
            }
        }
        } // Close chunk loop

        return [
            'valid_data' => $validData,
            'preview_valid' => $previewValid,
            'errors' => $errors,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
        ];
    }

    /**
     * Import validated contacts.
     */
    private function importContacts($validData, $contactGroup, $import)
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($validData as $index => $contactData) {
            try {
                DB::beginTransaction();

                $normalizedPhone = $contactData['normalized_phone'] ?? $contactData['mob_no'];

                // Check for duplicates in local database
                $existingContact = Contact::where('user_id', Auth::id())
                    ->where('phone', $normalizedPhone)
                    ->first();

                if ($existingContact) {
                    // Move/update existing contact into the selected group instead of failing
                    $displayName = $contactData['display_name'] ?? $normalizedPhone;

                    $existingMeta = is_array($existingContact->metadata) ? $existingContact->metadata : [];

                    $existingContact->update([
                        'contact_group_id' => $contactGroup->id,
                        'name' => $displayName,
                        'email' => $contactData['email'] ?? $existingContact->email,
                        'date_of_birth' => $contactData['birth_date'] ?? $existingContact->date_of_birth,
                        'metadata' => array_filter([
                            'title' => $contactData['title'] ?? ($existingMeta['title'] ?? null),
                            'first_name' => $contactData['first_name'] ?? ($existingMeta['first_name'] ?? null),
                            'last_name' => $contactData['last_name'] ?? ($existingMeta['last_name'] ?? null),
                            'gender' => $contactData['gender'] ?? ($existingMeta['gender'] ?? null),
                            'area' => $contactData['area'] ?? ($existingMeta['area'] ?? null),
                            'city' => $contactData['city'] ?? ($existingMeta['city'] ?? null),
                            'country' => $contactData['country'] ?? ($existingMeta['country'] ?? null),
                            'address' => $contactData['address'] ?? ($existingMeta['address'] ?? null),
                            'additional_phone' => $contactData['mob_no2'] ?? ($existingMeta['additional_phone'] ?? null),
                        ]),
                    ]);

                    Log::info('Import: updated existing contact and moved to group', [
                        'user_id' => Auth::id(),
                        'contact_id' => $existingContact->id,
                        'group_id' => $contactGroup->id,
                        'phone' => $normalizedPhone,
                    ]);

                    DB::commit();
                    $successful++;
                    continue;
                }

                $displayName = $contactData['display_name'] ?? $normalizedPhone;

                // Create contact locally
                $contact = Contact::create([
                    'user_id' => Auth::id(),
                    'contact_group_id' => $contactGroup->id,
                    'name' => $displayName,
                    'phone' => $normalizedPhone,
                    'email' => $contactData['email'] ?? null,
                    'date_of_birth' => $contactData['birth_date'] ?? null,
                    'metadata' => array_filter([
                        'title' => $contactData['title'] ?? null,
                        'first_name' => $contactData['first_name'] ?? null,
                        'last_name' => $contactData['last_name'] ?? null,
                        'gender' => $contactData['gender'] ?? null,
                        'area' => $contactData['area'] ?? null,
                        'city' => $contactData['city'] ?? null,
                        'country' => $contactData['country'] ?? null,
                        'address' => $contactData['address'] ?? null,
                        'additional_phone' => $contactData['mob_no2'] ?? null,
                    ]),
                ]);

                // Create contact in Beem if address book exists
                if ($contactGroup->beem_address_book_id) {
                    try {
                        $payload = [
                            'mob_no' => $contactData['mob_no'],
                            'mob_no2' => $contactData['mob_no2'] ?? '',
                            'title' => $contactData['title'] ?? '',
                            'fname' => $contactData['first_name'] ?? '',
                            'lname' => $contactData['last_name'] ?? '',
                            'gender' => $contactData['gender'] ?? '',
                            'birth_date' => $contactData['birth_date'] ?? '',
                            'area' => $contactData['area'] ?? '',
                            'city' => $contactData['city'] ?? '',
                            'country' => $contactData['country'] ?? '',
                            'email' => $contactData['email'] ?? '',
                            'address' => $contactData['address'] ?? '',
                            'addressbook_id' => [$contactGroup->beem_address_book_id],
                        ];

                        $beemContact = $this->beemService->createContact($payload);
                        $contact->update(['beem_contact_id' => $beemContact['id'] ?? null]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to create contact in Beem: ' . $e->getMessage());
                    }
                }

                DB::commit();
                $successful++;

            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'row_number' => $index + 2,
                    'reason' => 'Database error: ' . $e->getMessage(),
                    'phone' => $contactData['mob_no'] ?? ''
                ];
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Generate error report CSV.
     */
    private function generateErrorReportCsv($errors)
    {
        $csv = "Row Number,Reason,Raw Phone,Normalized Phone,Other Data\n";
        
        foreach ($errors as $error) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $error['row_number'],
                str_replace('"', '""', $error['reason']),
                str_replace('"', '""', $error['raw_phone'] ?? ''),
                str_replace('"', '""', $error['normalized_phone'] ?? ''),
                str_replace('"', '""', json_encode($error['data'] ?? []))
            );
        }
        
        return $csv;
    }
}
