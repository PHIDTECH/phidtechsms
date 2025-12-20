@extends('layouts.modern-dashboard')

@section('title', 'Import Contacts')
@section('page-title', 'Import Contacts')

@section('styles')
<style>
    [x-cloak] {
        display: none !important;
    }

    .step-container {
        transition: transform 0.5s ease-in-out;
    }

    .step {
        min-width: 100%;
        flex-shrink: 0;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <h1 class="text-2xl font-semibold text-gray-800">Contact Import Wizard</h1>
            <p class="text-sm text-gray-600 mt-1">Follow the steps to import your contacts from a file.</p>
        </div>

        <div class="p-6">
            <!-- Step Progress Indicator -->
            <ol id="step-indicator" class="mb-6 grid grid-cols-4 gap-3">
                <li class="step-item flex items-center gap-2" data-step="1">
                    <span class="h-6 w-6 rounded-full bg-blue-600 text-white grid place-items-center text-xs font-bold">1</span>
                    <span class="text-xs font-medium text-gray-700">Upload</span>
                </li>
                <li class="step-item flex items-center gap-2" data-step="2">
                    <span class="h-6 w-6 rounded-full bg-gray-300 text-gray-700 grid place-items-center text-xs font-bold">2</span>
                    <span class="text-xs font-medium text-gray-700">Map</span>
                </li>
                <li class="step-item flex items-center gap-2" data-step="3">
                    <span class="h-6 w-6 rounded-full bg-gray-300 text-gray-700 grid place-items-center text-xs font-bold">3</span>
                    <span class="text-xs font-medium text-gray-700">Select Group</span>
                </li>
                <li class="step-item flex items-center gap-2" data-step="4">
                    <span class="h-6 w-6 rounded-full bg-gray-300 text-gray-700 grid place-items-center text-xs font-bold">4</span>
                    <span class="text-xs font-medium text-gray-700">Summary</span>
                </li>
            </ol>
            <div class="overflow-hidden">
                <div id="step-container" class="flex transition-transform duration-500 ease-in-out">

                    <!-- Step 1: Upload File -->
                    <div id="step-1" class="step w-full p-6">
                        <h2 class="text-lg font-medium text-gray-900">Step 1: Upload File</h2>
                        <p class="text-sm text-gray-500 mt-1">Select a CSV, XLS, or XLSX file to upload.</p>

                        <div id="upload-form-container" class="mt-6">
                            <form id="upload-form" action="{{ route('contacts.import.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div id="dropzone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-green-400 transition">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                                <span>Upload a file</span>
                                                <input id="file-upload" name="file" type="file" class="sr-only">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">CSV, XLS, XLSX up to 10MB</p>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div id="file-details" class="hidden mt-6 p-4 bg-gray-50 rounded-md border">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900" id="file-name"></p>
                                    <p class="text-xs text-gray-500" id="file-size"></p>
                                </div>
                                <button id="remove-file-btn" class="text-red-600 hover:text-red-800">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Map Columns -->
                    <div id="step-2" class="step w-full p-6">
                        <h2 class="text-lg font-medium text-gray-900">Step 2: Map Columns</h2>
                        <p class="text-sm text-gray-500 mt-1">Map your file columns to the correct contact fields.</p>

                        <div class="mt-4">
                            <div class="flex items-center">
                                <input id="use-headers-toggle" type="checkbox" class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500" checked>
                                <label for="use-headers-toggle" class="ml-2 block text-sm text-gray-900">
                                    First row is a header
                                </label>
                            </div>
                        </div>

                        <div id="mapping-container" class="mt-6" x-data="importMapping" x-cloak>
                            <div id="mappingTableWrapper" class="overflow-x-auto" :class="{ 'hidden': !showMapping }">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <template x-for="header in headers" :key="header">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" x-text="header"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <template x-for="header in headers" :key="header">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md"
                                                            @change="updateMapping(header, $event.target.value)"
                                                            :value="map[header]">
                                                        <template x-for="option in mappingOptions" :key="option.value">
                                                            <option :value="option.value" x-text="option.label"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                            </template>
                                        </tr>
                                        <template x-for="(row, rowIndex) in rows" :key="rowIndex">
                                            <tr>
                                                <template x-for="header in headers" :key="header">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="row[header]"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button id="step-2-back-btn" class="btn btn-secondary">Back</button>
                            <button id="validate-mapping-btn" class="btn btn-primary">Validate & Proceed</button>
                        </div>
                    </div>

                    <!-- Step 3: Select Group & Review -->
                    <div id="step-3" class="step w-full p-6">
                        <h2 class="text-lg font-medium text-gray-900">Step 3: Select Group & Review</h2>
                        <p class="text-sm text-gray-500 mt-1">Choose a contact group and review the import details.</p>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="group-select" class="block text-sm font-medium text-gray-700">Contact Group</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <select id="group-select" class="focus:ring-green-500 focus:border-green-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300">
                                        <option value="">Select a group</option>
                                    </select>
                                    <button id="open-group-modal-btn" type="button" class="-ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>New</span>
                                    </button>
                                </div>
                                <div id="group-details-card" class="mt-3 p-3 bg-gray-50 rounded-md border hidden"></div>
                            </div>
                        </div>

                        <div id="validation-results-container" class="mt-8">
                            <!-- Validation results will be injected here -->
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button id="step-3-back-btn" class="btn btn-secondary">Back</button>
                            <button id="step-3-continue-btn" class="btn btn-primary" disabled>Review & Confirm</button>
                        </div>
                    </div>

                    <!-- Step 4: Import Summary -->
                    <div id="step-4" class="step w-full p-6">
                        <h2 class="text-lg font-medium text-gray-900">Step 4: Import Summary</h2>
                        <p class="text-sm text-gray-500 mt-1">Review the final details before starting the import.</p>

                        <div class="mt-6 bg-gray-50 p-6 rounded-lg border">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div class="md:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Contact Group</dt>
                                    <dd id="summary-group-name" class="mt-1 text-lg font-semibold text-gray-900"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Valid Rows</dt>
                                    <dd id="summary-valid" class="mt-1 text-lg font-semibold text-gray-900"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Selected for Import</dt>
                                    <dd id="summary-selected" class="mt-1 text-lg font-semibold text-green-600"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Invalid Rows</dt>
                                    <dd id="summary-invalid" class="mt-1 text-lg font-semibold text-red-600"></dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Field Mapping</dt>
                                    <dd id="summary-mapping" class="mt-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-x-4 gap-y-2"></dd>
                                </div>
                            </dl>
                        </div>

                        <div id="import-progress-container" class="mt-8">
                            <div class="flex justify-between mb-1">
                                <span id="progress-status-label" class="text-base font-medium text-gray-700">Ready to import</span>
                                <span class="text-sm font-medium text-gray-700"><span id="progress-percentage">0</span>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="progress-bar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                            <div class="mt-2 flex justify-between text-sm text-gray-600">
                                <div>Successful: <span id="progress-success" class="font-medium">0</span></div>
                                <div>Failed: <span id="progress-failed" class="font-medium">0</span></div>
                            </div>
                        </div>

                        <div id="import-success-summary" class="hidden mt-6 p-4 bg-green-50 text-green-700 rounded-lg">
                            <h3 class="font-semibold">Import Complete!</h3>
                            <div id="import-summary-details" class="text-sm mt-2"></div>
                            <button id="download-errors-btn" class="hidden mt-3 text-sm font-semibold text-red-600 hover:underline">
                                Download Error Report
                            </button>
                        </div>

                        <div class="mt-8 flex justify-between items-center">
                            <button id="step-4-back-btn" class="btn btn-secondary">Back</button>
                            <div>
                                <button id="start-import-btn" class="btn btn-primary">Start Import</button>
                                <button id="start-new-import-btn" class="btn btn-outline-secondary ml-2 hidden">Start New Import</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div id="group-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-lg font-medium text-gray-900">Create New Contact Group</h3>
            <button id="close-group-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="create-group-form" class="mt-4">
            <div>
                <label for="new-group-name" class="block text-sm font-medium text-gray-700">Group Name</label>
                <input type="text" id="new-group-name" name="newGroupName" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div class="mt-4">
                <label for="new-group-description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                <textarea id="new-group-description" name="newGroupDescription" rows="3" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button id="cancel-group-create" type="button" class="btn btn-secondary">Cancel</button>
                <button id="save-group-btn" type="submit" class="btn btn-primary">Save Group</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const elements = {
        stepContainer: document.getElementById('step-container'),
        steps: document.querySelectorAll('.step'),
        
        // Step 1
        uploadFormContainer: document.getElementById('upload-form-container'),
        uploadForm: document.getElementById('upload-form'),
        submitUploadBtn: document.getElementById('submit-upload-btn'),
        dropzone: document.getElementById('dropzone'),
        fileInput: document.getElementById('file-upload'),
        fileDetails: document.getElementById('file-details'),
        fileName: document.getElementById('file-name'),
        fileSize: document.getElementById('file-size'),
        removeFileBtn: document.getElementById('remove-file-btn'),
        browseBtn: document.getElementById('browse-btn'),

        // Step 2
        useHeadersToggle: document.getElementById('use-headers-toggle'),
        mappingContainer: document.getElementById('mapping-container'),
        validateMappingBtn: document.getElementById('validate-mapping-btn'),
        step2BackBtn: document.getElementById('step-2-back-btn'),

        // Step 3
        groupSelect: document.getElementById('group-select'),
        openGroupModalBtn: document.getElementById('open-group-modal-btn'),
        groupDetailsCard: document.getElementById('group-details-card'),
        validationResultsContainer: document.getElementById('validation-results-container'),
        step3BackBtn: document.getElementById('step-3-back-btn'),
        step3ContinueBtn: document.getElementById('step-3-continue-btn'),

        // Step 4
        summaryGroupName: document.getElementById('summary-group-name'),
        summaryValid: document.getElementById('summary-valid'),
        summarySelected: document.getElementById('summary-selected'),
        summaryInvalid: document.getElementById('summary-invalid'),
        summaryMapping: document.getElementById('summary-mapping'),
        importProgressContainer: document.getElementById('import-progress-container'),
        progressStatusLabel: document.getElementById('progress-status-label'),
        progressPercentage: document.getElementById('progress-percentage'),
        progressBar: document.getElementById('progress-bar'),
        progressSuccess: document.getElementById('progress-success'),
        progressFailed: document.getElementById('progress-failed'),
        importSuccessSummary: document.getElementById('import-success-summary'),
        importSummaryDetails: document.getElementById('import-summary-details'),
        downloadErrorsBtn: document.getElementById('download-errors-btn'),
        step4BackBtn: document.getElementById('step-4-back-btn'),
        startImportBtn: document.getElementById('start-import-btn'),
        startNewImportBtn: document.getElementById('start-new-import-btn'),

        // Group Modal
        groupModal: document.getElementById('group-modal'),
        closeGroupModal: document.getElementById('close-group-modal'),
        createGroupForm: document.getElementById('create-group-form'),
        cancelGroupCreate: document.getElementById('cancel-group-create'),
        saveGroupBtn: document.getElementById('save-group-btn'),

        // Dynamic elements
        rowsHeaderCheckbox: null,
        selectAllRowsBtn: null,
        clearAllRowsBtn: null,
        selectPageRowsBtn: null,
        clearPageRowsBtn: null,
        prevRowsPageBtn: null,
        nextRowsPageBtn: null,
    };

    const state = {
        currentStep: 1,
        importId: null,
        selectedFile: null,
        useHeaders: true,
        columnMapping: {},
        groups: [],
        selectedGroupId: null,
        validationSummary: { valid: 0, invalid: 0, preview_valid: [], preview_invalid: [] },
        rowsPagination: { currentPage: 1, lastPage: 1, total: 0 },
        selectionMode: 'all', // 'all' or 'manual'
        selectedRows: new Set(),
        deselectedRows: new Set(),
        errorReportUrl: null,
    };
    window.state = state; // Expose for Alpine bridge

    const fieldLabels = {
        phone: 'Phone',
        additional_phone: 'Additional Phone',
        title: 'Title',
        first_name: 'First Name',
        last_name: 'Last Name',
        gender: 'Gender',
        dob: 'Date of Birth',
        email: 'Email',
        address: 'Address',
        city: 'City',
        country: 'Country',
        area: 'Area',
    };

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    function showToast(message, type = 'info') {
        // Implement a toast notification system if you have one
        console.log(`[Toast-${type}]: ${message}`);
        alert(message);
    }

    function updateStepCounts() {
        const indicator = document.getElementById('step-indicator');
        if (!indicator) return;
        const items = indicator.querySelectorAll('.step-item');
        items.forEach(item => {
            const step = Number(item.dataset.step || 0);
            const badge = item.querySelector('span');
            const label = item.querySelector('span + span');
            if (!badge || !label) return;
            if (step < state.currentStep) {
                badge.className = 'h-6 w-6 rounded-full bg-green-600 text-white grid place-items-center text-xs font-bold';
                label.className = 'text-xs font-medium text-gray-700';
            } else if (step === state.currentStep) {
                badge.className = 'h-6 w-6 rounded-full bg-blue-600 text-white grid place-items-center text-xs font-bold';
                label.className = 'text-xs font-semibold text-blue-700';
            } else {
                badge.className = 'h-6 w-6 rounded-full bg-gray-300 text-gray-700 grid place-items-center text-xs font-bold';
                label.className = 'text-xs font-medium text-gray-700';
            }
        });
    }

    function goToStep(stepNumber) {
        state.currentStep = stepNumber;
        const offset = -(stepNumber - 1) * 100;
        elements.stepContainer.style.transform = `translateX(${offset}%)`;
        updateStepCounts();
    }

    function resetFileSelection() {
        elements.fileInput.value = '';
        state.selectedFile = null;
        elements.fileDetails.classList.add('hidden');
        elements.uploadFormContainer.classList.remove('hidden');
        
        // Reset mapping component
        const mappingComponent = getMappingComponent();
        if (mappingComponent) {
            mappingComponent.applyPreview({ headers: [], rows: [] });
        }
    }

    async function handleFileChosen(file) {
        if (!file) return;
        state.selectedFile = file;
        elements.fileName.textContent = file.name;
        elements.fileSize.textContent = formatFileSize(file.size);
        elements.fileDetails.classList.remove('hidden');
        elements.uploadFormContainer.classList.add('hidden');

        // Submit the form via AJAX instead of regular form submission
        const formData = new FormData(elements.uploadForm);
        formData.append('file', file);
        // Ensure backend receives a canonical boolean value ('1' or '0')
        formData.append('use_headers', state.useHeaders ? '1' : '0');

        try {
            const response = await fetch('{{ route('contacts.import.upload') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const json = await response.json();
            if (!response.ok || !json.success) {
                let msg = json.message;
                if (!msg && json.errors) {
                    try {
                        msg = Object.values(json.errors).flat().join(' ');
                    } catch (_) {}
                }
                throw new Error(msg || 'Upload failed.');
            }

            // Handle successful upload
            state.importId = json.data.import_id;
            state.useHeaders = json.data.use_headers;

            // Prepare and broadcast preview payload for Alpine mapping component
            const previewPayload = {
                headers: json.data.headers,
                rows: json.data.rows
            };
            // Save globally for race conditions and fire an event for listeners
            window.__importPreview = previewPayload;
            try {
                window.dispatchEvent(new CustomEvent('import:preview-ready', { detail: previewPayload }));
            } catch (_) {}

            // Update mapping component directly if available
            const mappingComponent = getMappingComponent();
            if (mappingComponent && typeof mappingComponent.applyPreview === 'function') {
                mappingComponent.applyPreview(previewPayload);
            }

            // Move to step 2
            goToStep(2);
            
        } catch (error) {
            console.error('Upload error:', error);
            showToast(error.message || 'Upload failed. Please try again.', 'error');
            // Reset file selection
            resetFileSelection();
        }
    }

    function getMappingComponent() {
        const el = document.getElementById('mapping-container');
        // Return the Alpine component's data object, not the internal instance
        return el && el.__x ? el.__x.$data : null;
    }

    function handleMappingChange(mapping) {
        state.columnMapping = mapping;
    }

    async function validateMapping() {
        const mappingComponent = getMappingComponent();
        if (mappingComponent) {
            state.columnMapping = mappingComponent.getMapping();
        }

        if (Object.values(state.columnMapping).every(v => v === '')) {
            showToast('Please map at least one column.', 'warning');
            return;
        }
        if (!Object.values(state.columnMapping).includes('phone')) {
            showToast('The "Phone" field is required for mapping.', 'warning');
            return;
        }

        elements.validateMappingBtn.disabled = true;
        elements.validateMappingBtn.classList.add('opacity-70');

        // Transform header->field map to field->header map expected by backend
        const serverMapping = {};
        Object.entries(state.columnMapping).forEach(([header, field]) => {
            if (field && field.length) {
                serverMapping[field] = header;
            }
        });

        // Keep state mapping consistent with server format for summaries and display
        state.columnMapping = serverMapping;

        const payload = {
            import_id: state.importId,
            column_mapping: serverMapping,
        };

        try {
            const response = await fetch('{{ route('contacts.import.mapping') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                let msg = json.message;
                if (!msg && json.errors) {
                    try {
                        msg = Object.values(json.errors).flat().join(' ');
                    } catch (_) {}
                }
                throw new Error(msg || 'Validation failed.');
            }

            state.validationSummary = json.data.summary;
            renderValidationResults(json.data);
            await fetchContactGroups();
            goToStep(3);
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Failed to validate mapping.', 'error');
        } finally {
            elements.validateMappingBtn.disabled = false;
            elements.validateMappingBtn.classList.remove('opacity-70');
        }
    }

    function renderValidationResults(data) {
        const { summary, preview_valid, preview_invalid } = data;
        let html = `
            <div class="bg-gray-50 p-4 rounded-lg border">
                <h3 class="text-md font-semibold text-gray-800">Validation Summary</h3>
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-lg font-bold text-green-600">${formatNumber(summary.valid)}</div>
                        <div class="text-sm text-gray-600">Valid Rows</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-red-600">${formatNumber(summary.invalid)}</div>
                        <div class="text-sm text-gray-600">Invalid Rows</div>
                    </div>
                </div>
            </div>
        `;

        if (summary.valid > 0) {
            html += `
                <div class="mt-6">
                    <div class="flex justify-between items-center">
                        <h4 class="text-md font-semibold text-gray-800">Review Valid Rows (${formatNumber(summary.valid)})</h4>
                        <div class="flex items-center space-x-2 text-sm">
                            <div id="selection-mode-all" class="hidden">
                                <span>All ${formatNumber(summary.valid)} rows selected.</span>
                                <button id="clear-all-rows-btn" class="text-green-600 hover:underline">Clear selection</button>
                            </div>
                            <div id="selection-mode-manual">
                                <button id="select-all-rows-btn" class="text-green-600 hover:underline">Select all ${formatNumber(summary.valid)} rows</button>
                            </div>
                        </div>
                    </div>
                    <div id="validated-rows-table" class="mt-2 flow-root">
                        <!-- Rows will be fetched and rendered here -->
                    </div>
                </div>
            `;
        }

        elements.validationResultsContainer.innerHTML = html;
        
        // Re-bind dynamic elements
        elements.selectAllRowsBtn = document.getElementById('select-all-rows-btn');
        elements.clearAllRowsBtn = document.getElementById('clear-all-rows-btn');

        if (summary.valid > 0) {
            fetchValidatedRows(1);
        }
        updateStep3ContinueState();
    }

    function getSelectedRowCount() {
        if (state.selectionMode === 'all') {
            return state.validationSummary.valid - state.deselectedRows.size;
        }
        return state.selectedRows.size;
    }

    function updateSelectionCount() {
        const countEl = document.getElementById('selection-count');
        if (countEl) {
            countEl.textContent = formatNumber(getSelectedRowCount());
        }
    }

    function setSelectionMode(mode) {
        state.selectionMode = mode;
        state.selectedRows.clear();
        state.deselectedRows.clear();

        const modeAll = document.getElementById('selection-mode-all');
        const modeManual = document.getElementById('selection-mode-manual');

        if (mode === 'all') {
            modeAll.classList.remove('hidden');
            modeManual.classList.add('hidden');
        } else {
            modeAll.classList.add('hidden');
            modeManual.classList.remove('hidden');
        }
        
        updateSelectionCount();
        updateTableSelectionVisuals();
    }

    function selectRowsOnPage(isSelected) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => {
            const rowId = parseInt(cb.dataset.rowId, 10);
            if (isSelected) {
                if (state.selectionMode === 'all') {
                    state.deselectedRows.delete(rowId);
                } else {
                    state.selectedRows.add(rowId);
                }
            } else {
                if (state.selectionMode === 'all') {
                    state.deselectedRows.add(rowId);
                } else {
                    state.selectedRows.delete(rowId);
                }
            }
        });
        updateSelectionCount();
        updateTableSelectionVisuals();
    }

    function updateTableSelectionVisuals() {
        const headerCheckbox = document.getElementById('rows-header-checkbox');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        let allChecked = true;
        let anyChecked = false;

        checkboxes.forEach(cb => {
            const rowId = parseInt(cb.dataset.rowId, 10);
            let isChecked;
            if (state.selectionMode === 'all') {
                isChecked = !state.deselectedRows.has(rowId);
            } else {
                isChecked = state.selectedRows.has(rowId);
            }
            cb.checked = isChecked;
            if (!isChecked) allChecked = false;
            if (isChecked) anyChecked = true;
        });

        if (headerCheckbox) {
            headerCheckbox.checked = allChecked;
            headerCheckbox.indeterminate = anyChecked && !allChecked;
        }
    }

    async function fetchValidatedRows(page) {
        if (!state.importId) return;

        const tableContainer = document.getElementById('validated-rows-table');
        tableContainer.innerHTML = '<div class="text-center p-4">Loading...</div>';

        try {
            const response = await fetch(`{{ url('contacts/import') }}/${state.importId}/rows?page=${page}`);
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Failed to load rows.');
            }

            const { rows, pagination } = json.data;

            const buildRow = (r) => {
                const name = r.name || r.phone || '';
                const email = r.email || '';
                const city = r.city || '';
                const phone = r.phone || '';
                const rowId = r.row_number || 0;
                return `
                    <tr class="bg-white">
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                            <input type="checkbox" class="row-checkbox" data-row-id="${rowId}">
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">${name}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">${phone}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">${email}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">${city}</td>
                    </tr>
                `;
            };

            const rowsHtml = rows && rows.length
                ? rows.map(buildRow).join('')
                : `<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No rows found.</td></tr>`;

            const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
            const nextDisabled = pagination.current_page >= pagination.last_page ? 'disabled' : '';

            const html = `
                <div class="overflow-x-auto rounded border">
                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b">
                        <div class="flex items-center space-x-2">
                            <input id="rows-header-checkbox" type="checkbox" class="h-4 w-4">
                            <label for="rows-header-checkbox" class="text-sm text-gray-700">Select page</label>
                            <button id="select-page-rows-btn" class="ml-2 text-green-600 text-sm hover:underline">Select all on page</button>
                            <button id="clear-page-rows-btn" class="text-green-600 text-sm hover:underline">Clear page selection</button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button id="prev-rows-page-btn" class="px-2 py-1 text-sm border rounded ${prevDisabled ? 'opacity-50 cursor-not-allowed' : ''}" ${prevDisabled}>Prev</button>
                            <span class="text-xs text-gray-600">Page ${pagination.current_page} of ${pagination.last_page}</span>
                            <button id="next-rows-page-btn" class="px-2 py-1 text-sm border rounded ${nextDisabled ? 'opacity-50 cursor-not-allowed' : ''}" ${nextDisabled}>Next</button>
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            ${rowsHtml}
                        </tbody>
                    </table>
                    <div id="pagination-data" class="hidden" data-current-page="${pagination.current_page}" data-last-page="${pagination.last_page}" data-total="${pagination.total}"></div>
                </div>
            `;

            tableContainer.innerHTML = html;

            // Re-bind dynamic elements after new content is loaded
            elements.rowsHeaderCheckbox = document.getElementById('rows-header-checkbox');
            elements.selectPageRowsBtn = document.getElementById('select-page-rows-btn');
            elements.clearPageRowsBtn = document.getElementById('clear-page-rows-btn');
            elements.prevRowsPageBtn = document.getElementById('prev-rows-page-btn');
            elements.nextRowsPageBtn = document.getElementById('next-rows-page-btn');

            // Add event listeners
            if (elements.rowsHeaderCheckbox) {
                elements.rowsHeaderCheckbox.addEventListener('change', e => selectRowsOnPage(e.target.checked));
            }
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', e => {
                    const rowId = parseInt(e.target.dataset.rowId, 10);
                    if (e.target.checked) {
                        if (state.selectionMode === 'all') {
                            state.deselectedRows.delete(rowId);
                        } else {
                            state.selectedRows.add(rowId);
                        }
                    } else {
                        if (state.selectionMode === 'all') {
                            state.deselectedRows.add(rowId);
                        } else {
                            state.selectedRows.delete(rowId);
                        }
                    }
                    updateSelectionCount();
                    updateTableSelectionVisuals();
                });
            });
            
            if (elements.prevRowsPageBtn) {
                elements.prevRowsPageBtn.addEventListener('click', () => fetchValidatedRows(state.rowsPagination.currentPage - 1));
            }
            if (elements.nextRowsPageBtn) {
                elements.nextRowsPageBtn.addEventListener('click', () => fetchValidatedRows(state.rowsPagination.currentPage + 1));
            }

            // Update pagination state from data attributes on a placeholder element
            const paginationEl = document.getElementById('pagination-data');
            if (paginationEl) {
                state.rowsPagination.currentPage = parseInt(paginationEl.dataset.currentPage, 10);
                state.rowsPagination.lastPage = parseInt(paginationEl.dataset.lastPage, 10);
                state.rowsPagination.total = parseInt(paginationEl.dataset.total, 10);
            }

            updateSelectionCount();
            updateTableSelectionVisuals();

        } catch (error) {
            console.error('Failed to fetch validated rows:', error);
            tableContainer.innerHTML = '<div class="text-center p-4 text-red-600">Failed to load rows.</div>';
        }
    }

    function updateStep3ContinueState() {
        const isGroupSelected = !!state.selectedGroupId;
        const hasValidRows = state.validationSummary.valid > 0;
        elements.step3ContinueBtn.disabled = !(isGroupSelected && hasValidRows);
    }

    async function fetchContactGroups() {
        try {
            const response = await fetch('{{ route('contacts.import.groups') }}');
            const json = await response.json();
            if (!response.ok) throw new Error(json.message || 'Failed to fetch groups.');
            state.groups = json.data || [];
            renderGroupOptions();
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Could not load contact groups.', 'error');
        }
    }

    function renderGroupOptions() {
        elements.groupSelect.innerHTML = '<option value="">Select a group</option>';
        state.groups.forEach(group => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = group.name;
            elements.groupSelect.appendChild(option);
        });
        updateStep3ContinueState();
    }

    function renderGroupDetails(groupId) {
        const group = state.groups.find(g => String(g.id) === String(groupId));
        if (!group) {
            elements.groupDetailsCard.classList.add('hidden');
            elements.groupDetailsCard.innerHTML = '';
            updateStep3ContinueState();
            return;
        }
        elements.groupDetailsCard.innerHTML = `
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <div class="text-sm font-semibold text-gray-800">${group.name}</div>
                    <div class="text-xs text-gray-500">${group.description || 'No description provided.'}</div>
                </div>
                <div class="text-xs text-gray-500">
                    ${formatNumber(group.contacts_count || 0)} existing contacts
                </div>
            </div>
        `;
        elements.groupDetailsCard.classList.remove('hidden');
        updateStep3ContinueState();
    }

    async function handleGroupCreate(event) {
        event.preventDefault();
        const formData = new FormData(elements.createGroupForm);
        const payload = {
            name: formData.get('newGroupName'),
            description: formData.get('newGroupDescription'),
        };

        if (!payload.name) {
            showToast('Group name is required.', 'warning');
            return;
        }

        elements.saveGroupBtn.disabled = true;
        elements.saveGroupBtn.classList.add('opacity-70');

        try {
            const response = await fetch('{{ route('contacts.import.groups.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Failed to create group.');
            }

            if (json.data) {
                state.groups.push(json.data);
                renderGroupOptions();
                state.selectedGroupId = json.data.id;
                elements.groupSelect.value = state.selectedGroupId;
                renderGroupDetails(state.selectedGroupId);
                elements.step3ContinueBtn.disabled = false;
            }

            elements.groupModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            elements.createGroupForm.reset();
            showToast('Contact group created successfully.');
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Failed to create group.', 'error');
        } finally {
            elements.saveGroupBtn.disabled = false;
            elements.saveGroupBtn.classList.remove('opacity-70');
        }
    }

    function updateSummarySection() {
        const group = state.groups.find(g => String(g.id) === String(state.selectedGroupId));
        elements.summaryGroupName.textContent = group ? group.name : '--';
        elements.summaryValid.textContent = formatNumber(state.validationSummary.valid);
        elements.summarySelected.textContent = formatNumber(getSelectedRowCount());
        elements.summaryInvalid.textContent = formatNumber(state.validationSummary.invalid);

        const mappingEntries = Object.entries(state.columnMapping).map(([field, column]) => {
            return `<div>
                <dt class="text-xs font-semibold text-gray-500">${fieldLabels[field] || field}</dt>
                <dd class="text-sm text-gray-700">${column}</dd>
            </div>`;
        }).join('');
        elements.summaryMapping.innerHTML = mappingEntries || '<div class="text-sm text-gray-500">No fields mapped.</div>';

        elements.progressBar.style.width = '0%';
        elements.progressStatusLabel.textContent = 'Ready to import';
        elements.progressSuccess.textContent = '0';
        elements.progressFailed.textContent = '0';
        elements.downloadErrorsBtn.classList.add('hidden');
        elements.importSuccessSummary.classList.add('hidden');
    }

    async function startImport() {
        if (!state.importId || !state.selectedGroupId) {
            showToast('Missing import information.', 'error');
            return;
        }

        const selectedCount = getSelectedRowCount();
        if (selectedCount <= 0) {
            showToast('Select at least one row before importing.', 'warning');
            return;
        }

        const payload = {
            import_id: state.importId,
            contact_group_id: state.selectedGroupId,
            selection_mode: state.selectionMode,
        };

        if (state.selectionMode === 'all') {
            payload.excluded_rows = Array.from(state.deselectedRows);
        } else {
            payload.selected_rows = Array.from(state.selectedRows);
        }

        elements.startImportBtn.disabled = true;
        elements.startImportBtn.classList.add('opacity-70');
        elements.progressStatusLabel.textContent = 'Importing contacts...';
        elements.progressBar.style.width = '25%';

        try {
            const response = await fetch('{{ route('contacts.import.process') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Import failed.');
            }

            const result = json.data || {};
            const successful = result.successful || 0;
            const failed = result.failed || 0;
            const importedSelected = result.selected ?? selectedCount;

            elements.progressSuccess.textContent = formatNumber(successful);
            elements.progressFailed.textContent = formatNumber(failed);
            elements.progressBar.style.width = '100%';
            elements.progressStatusLabel.textContent = 'Import completed.';

            if (failed > 0) {
                state.errorReportUrl = `{{ url('contacts/import') }}/${state.importId}/errors`;
                elements.downloadErrorsBtn.classList.remove('hidden');
            } else {
                elements.downloadErrorsBtn.classList.add('hidden');
            }

            if (result.summary) {
                elements.importSummaryDetails.innerHTML = `
                    <div>Total rows: ${formatNumber(result.summary.total_rows || 0)}</div>
                    <div>Selected: ${formatNumber(importedSelected)}</div>
                    <div>Successful: ${formatNumber(successful)}</div>
                    <div>Failed: ${formatNumber(failed)}</div>
                `;
            }

            if (elements.summarySelected) {
                elements.summarySelected.textContent = formatNumber(importedSelected);
            }

            elements.importSuccessSummary.classList.remove('hidden');
            showToast('Contacts imported successfully.');
        } catch (error) {
            console.error(error);
            elements.progressStatusLabel.textContent = 'Import failed.';
            showToast(error.message || 'Import failed. Please try again.', 'error');
        } finally {
            elements.startImportBtn.disabled = false;
            elements.startImportBtn.classList.remove('opacity-70');
        }
    }

    function resetWizard() {
        resetFileSelection();
        state.groups = [];
        state.selectedGroupId = null;
        state.importId = null;
        state.validationSummary = { valid: 0, invalid: 0, preview_valid: [], preview_invalid: [] };
        renderGroupOptions();
        renderGroupDetails(null);
        goToStep(1);
        showToast('Import wizard reset.');
    }

    function init() {
        goToStep(1);
        updateStepCounts();

        elements.dropzone.addEventListener('click', () => elements.fileInput.click());
        if (elements.browseBtn) {
            elements.browseBtn.addEventListener('click', () => elements.fileInput.click());
        }

        elements.dropzone.addEventListener('dragover', event => {
            event.preventDefault();
            elements.dropzone.classList.add('border-green-400', 'bg-green-50');
        });
        elements.dropzone.addEventListener('dragleave', () => {
            elements.dropzone.classList.remove('border-green-400', 'bg-green-50');
        });
        elements.dropzone.addEventListener('drop', event => {
            event.preventDefault();
            elements.dropzone.classList.remove('border-green-400', 'bg-green-50');
            const file = event.dataTransfer.files?.[0];
            if (file) {
                handleFileChosen(file);
            }
        });

        elements.fileInput.addEventListener('change', event => {
            const file = event.target.files?.[0];
            if (file) {
                handleFileChosen(file);
            }
        });

        elements.removeFileBtn.addEventListener('click', resetFileSelection);
        elements.useHeadersToggle.addEventListener('change', event => {
            state.useHeaders = event.target.checked;
        });
        
        elements.step2BackBtn.addEventListener('click', () => goToStep(1));
        elements.step3BackBtn.addEventListener('click', () => goToStep(2));
        elements.step4BackBtn.addEventListener('click', () => goToStep(3));

        if (elements.rowsHeaderCheckbox) {
            elements.rowsHeaderCheckbox.addEventListener('change', event => {
                selectRowsOnPage(event.target.checked);
            });
        }
        if (elements.selectAllRowsBtn) {
            elements.selectAllRowsBtn.addEventListener('click', () => setSelectionMode('all'));
        }
        if (elements.clearAllRowsBtn) {
            elements.clearAllRowsBtn.addEventListener('click', () => setSelectionMode('manual'));
        }
        if (elements.selectPageRowsBtn) {
            elements.selectPageRowsBtn.addEventListener('click', () => selectRowsOnPage(true));
        }
        if (elements.clearPageRowsBtn) {
            elements.clearPageRowsBtn.addEventListener('click', () => selectRowsOnPage(false));
        }
        if (elements.prevRowsPageBtn) {
            elements.prevRowsPageBtn.addEventListener('click', () => {
                if (state.rowsPagination.currentPage > 1) {
                    fetchValidatedRows(state.rowsPagination.currentPage - 1);
                }
            });
        }
        if (elements.nextRowsPageBtn) {
            elements.nextRowsPageBtn.addEventListener('click', () => {
                if (state.rowsPagination.currentPage < state.rowsPagination.lastPage) {
                    fetchValidatedRows(state.rowsPagination.currentPage + 1);
                }
            });
        }

        elements.openGroupModalBtn.addEventListener('click', () => {
            elements.groupModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => document.getElementById('newGroupName').focus(), 100);
        });
        elements.closeGroupModal.addEventListener('click', () => {
            elements.groupModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            elements.createGroupForm.reset();
        });
        elements.cancelGroupCreate.addEventListener('click', () => {
            elements.groupModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            elements.createGroupForm.reset();
        });

        elements.validateMappingBtn.addEventListener('click', validateMapping);
        elements.groupSelect.addEventListener('change', event => {
            state.selectedGroupId = event.target.value || null;
            renderGroupDetails(state.selectedGroupId);
            updateStep3ContinueState();
        });
        elements.step3ContinueBtn.addEventListener('click', () => {
            updateSummarySection();
            goToStep(4);
        });
        elements.startImportBtn.addEventListener('click', startImport);
        elements.downloadErrorsBtn.addEventListener('click', () => {
            if (state.errorReportUrl) {
                window.open(state.errorReportUrl, '_blank');
            }
        });
        elements.startNewImportBtn.addEventListener('click', resetWizard);
        elements.createGroupForm.addEventListener('submit', handleGroupCreate);
    }

    init();
})();

// Alpine.js component for reactive mapping table
(function registerImportMappingComponent() {
    const componentDef = () => ({
        showMapping: false,
        headers: [],
        rows: [],
        map: {},
        mappingOptions: [
            { value: '', label: 'Ignore Column' },
            { value: 'phone', label: 'Phone (required)' },
            { value: 'additional_phone', label: 'Additional Phone' },
            { value: 'title', label: 'Title' },
            { value: 'first_name', label: 'First Name' },
            { value: 'last_name', label: 'Last Name' },
            { value: 'gender', label: 'Gender' },
            { value: 'dob', label: 'Date of Birth (yyyy-mm-dd)' },
            { value: 'email', label: 'Email' },
            { value: 'address', label: 'Address' },
            { value: 'city', label: 'City' },
            { value: 'country', label: 'Country' },
            { value: 'area', label: 'Area' },
        ],
        applyPreview(payload) {
            const { headers = [], rows = [] } = payload || {};
            this.headers = Array.isArray(headers) ? headers : [];
            this.rows = Array.isArray(rows) ? rows.slice(0, 5) : [];
            this.showMapping = this.headers.length > 0;
            // Initialize mapping with empty values
            this.map = {};
            this.headers.forEach(header => {
                this.map[header] = '';
            });
            // Sync to global state if available
            if (!window.state) window.state = {};
            window.state.columnMapping = { ...this.map };
            
            // Manually control visibility of mapping table wrapper
            const wrapper = document.getElementById('mappingTableWrapper');
            if (wrapper) {
                if (this.showMapping) {
                    wrapper.classList.remove('hidden');
                } else {
                    wrapper.classList.add('hidden');
                }
            }
            
            console.log('Alpine: Applied preview', {
                showMapping: this.showMapping,
                headers: this.headers,
                rows: this.rows.length,
                map: this.map
            });
        },

        init() {
            // If a preview payload already exists (race), hydrate immediately
            if (window.__importPreview) {
                this.applyPreview(window.__importPreview);
            }
            // Listen for preview data from upload success
            window.addEventListener('import:preview-ready', (e) => {
                console.log('Alpine: Received preview-ready event', e.detail);
                this.applyPreview(e.detail);
            });
        },

        updateMapping(header, value) {
            this.map[header] = value;
            console.log('Alpine: Mapping updated', { header, value, map: this.map });
            
            // Update the global state for compatibility with existing code
            if (window.state && window.state.columnMapping) {
                window.state.columnMapping[header] = value;
            }
        },

        getMapping() {
            return this.map;
        }
    });

    if (window.Alpine && typeof window.Alpine.data === 'function') {
        window.Alpine.data('importMapping', componentDef);
    } else {
        document.addEventListener('alpine:init', () => {
            Alpine.data('importMapping', componentDef);
        });
    }
})();
// Fail-safe: if Alpine fails to initialize, uncloak elements so the user sees fallback UI
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (!window.Alpine) {
            const comp = document.getElementById('importMappingComponent');
            if (comp) {
                comp.querySelectorAll('[x-cloak]')?.forEach(el => el.removeAttribute('x-cloak'));
            }
        }
    }, 600);
});
</script>
@endsection
