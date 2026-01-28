@extends('layouts.admin-modern-dashboard')

@section('page-title', 'Manage Sender ID Applications')

@section('styles')
@endsection

@section('content')
<div class="animate-fade-in-up">
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="bg-[#6144f2] text-white rounded-2xl p-8 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2"><i class="fas fa-shield-alt mr-3"></i> Sender ID Applications</h1>
                <p class="text-red-100 mb-0">Review and manage sender ID applications from users</p>
            </div>
            <div class="flex-shrink-0">
                <button class="bg-white text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200" onclick="refreshPage()">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <p class="text-3xl font-bold text-gray-800 mb-2">{{ number_format($stats['total']) }}</p>
            <p class="text-gray-600 text-sm">Total Applications</p>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <p class="text-3xl font-bold text-yellow-600 mb-2">{{ number_format($stats['pending']) }}</p>
            <p class="text-gray-600 text-sm">Pending Review</p>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <p class="text-3xl font-bold text-green-600 mb-2">{{ number_format($stats['approved']) }}</p>
            <p class="text-gray-600 text-sm">Approved</p>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
            <p class="text-3xl font-bold text-red-600 mb-2">{{ number_format($stats['rejected']) }}</p>
            <p class="text-gray-600 text-sm">Rejected</p>
        </div>
    </div>

    <!-- Beem Africa Sender IDs -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-cloud mr-2 text-[#6144f2]"></i>
                Beem Africa Sender IDs
            </h2>
            <div class="flex gap-3">
                <button onclick="syncBeemSenderIds()" class="bg-[#6144f2] hover:bg-[#5a3de8] text-white px-4 py-2 rounded-lg text-sm transition-colors flex items-center" id="sync-beem-btn">
                    <i class="fas fa-sync-alt mr-2"></i> Sync from Beem
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Approved Sender IDs from Beem -->
            <div class="border border-gray-200 rounded-lg">
                <div class="bg-green-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-green-800 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Approved on Beem ({{ count($beemSenderApproved ?? []) }})
                    </h3>
                </div>
                <div class="p-4">
                    @if(!empty($beemSenderApproved))
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($beemSenderApproved as $sender)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $sender['name'] ?? 'N/A' }}</span>
                                        @if(isset($sender['raw']['sample_content']))
                                            <p class="text-xs text-gray-600 mt-1">{{ Str::limit($sender['raw']['sample_content'], 50) }}</p>
                                        @endif
                                    </div>
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                        {{ ucfirst($sender['status'] ?? 'approved') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No approved sender IDs found on Beem</p>
                        @if(!empty($approvedError))
                            <p class="text-red-600 text-sm text-center">Error: {{ $approvedError }}</p>
                        @endif
                    @endif
                </div>
            </div>
            
            <!-- Pending Sender IDs from Beem -->
            <div class="border border-gray-200 rounded-lg">
                <div class="bg-yellow-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-yellow-800 flex items-center">
                        <i class="fas fa-hourglass-half mr-2"></i>
                        Pending on Beem ({{ count($beemSenderPending ?? []) }})
                    </h3>
                </div>
                <div class="p-4">
                    @if(!empty($beemSenderPending))
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($beemSenderPending as $sender)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $sender['name'] ?? 'N/A' }}</span>
                                        @if(isset($sender['raw']['sample_content']))
                                            <p class="text-xs text-gray-600 mt-1">{{ Str::limit($sender['raw']['sample_content'], 50) }}</p>
                                        @endif
                                    </div>
                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                                        {{ ucfirst($sender['status'] ?? 'pending') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No pending sender IDs found on Beem</p>
                        @if(!empty($pendingError))
                            <p class="text-red-600 text-sm text-center">Error: {{ $pendingError }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
        <form method="GET" action="{{ route('admin.sender-ids.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label for="business_type" class="block text-sm font-medium text-gray-700 mb-2">Business Type</label>
                    <select name="business_type" id="business_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        @foreach(App\Models\SenderID::getBusinessTypes() as $type)
                            <option value="{{ $type }}" {{ request('business_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" id="search" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Search by sender name, business, or reference..." 
                           value="{{ request('search') }}">
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-search mr-2"></i> Filter
                    </button>
                    <a href="{{ route('admin.sender-ids.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h5 class="text-lg font-semibold text-gray-800 mb-0">Applications ({{ $applications->total() }})</h5>
            <div>
                <button class="bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-200" onclick="toggleBulkActions()">
                    <i class="fas fa-tasks mr-2"></i> Bulk Actions
                </button>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-gray-50 p-4 border-b border-gray-200 hidden" id="bulkActions">
            <form id="bulkForm" method="POST" action="{{ route('admin.sender-ids.bulk-action') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Select Action</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject Selected</option>
                        </select>
                    </div>
                    <div>
                        <input type="text" name="rejection_reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Rejection reason (required for reject)">
                    </div>
                    <div>
                        <input type="text" name="admin_notes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Admin notes (optional)">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                            <i class="fas fa-check mr-2"></i> Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($applications->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="w-12 px-6 py-3 text-left">
                                <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($applications as $application)
                        <tr class="hover:bg-gray-50 {{ $application->status === 'pending' ? 'border-l-4 border-yellow-400' : '' }}">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="applications[]" value="{{ $application->id }}" 
                                       class="application-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ $application->status !== 'pending' ? 'disabled' : '' }}>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $application->sender_name }}</div>
                                <div class="text-sm text-gray-500">{{ $application->purpose }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ $application->business_name }}</div>
                                <div class="text-sm text-gray-500">{{ $application->business_type }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($application->user)
                                    <div class="text-gray-900">{{ $application->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $application->user->phone }}</div>
                                @else
                                    <div class="text-gray-500 italic">System-wide</div>
                                    <div class="text-xs text-gray-400">Available to all users</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($application->status === 'pending')
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        {{ $application->statusLabel }}
                                    </span>
                                @elseif($application->status === 'approved')
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ $application->statusLabel }}
                                    </span>
                                @else
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ $application->statusLabel }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ $application->application_date->format('M d, Y') }}</div>
                                <div class="text-sm text-gray-500">{{ $application->application_date->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">{{ $application->reference_number }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.sender-ids.show', $application) }}" 
                                       class="bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-2 py-1 rounded text-sm transition-colors duration-200">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($application->status === 'pending')
                                        <button type="button" class="bg-green-50 hover:bg-green-100 text-green-600 border border-green-200 px-2 py-1 rounded text-sm transition-colors duration-200" 
                                                onclick="quickApprove({{ $application->id }})">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-2 py-1 rounded text-sm transition-colors duration-200" 
                                                onclick="quickReject({{ $application->id }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3">
                {{ $applications->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="mb-4">
                    <i class="fas fa-inbox text-6xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Applications Found</h3>
                <p class="text-gray-500">There are no sender ID applications matching your criteria.</p>
            </div>
        @endif

        <!-- Pagination -->
        @if($applications->hasPages())
            <div class="flex justify-center mt-6">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Quick Action Modals -->
<div id="quickApproveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form id="quickApproveForm" method="POST">
            @csrf
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Quick Approve</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeQuickApproveModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="mb-3">
                        <label for="approve_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" id="approve_notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors duration-200" onclick="closeQuickApproveModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors duration-200">
                        <i class="fas fa-check mr-2"></i> Approve
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="quickRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form id="quickRejectForm" method="POST">
            @csrf
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Quick Reject</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeQuickRejectModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="mb-3">
                        <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea name="rejection_reason" id="reject_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" 
                                  placeholder="Explain why this application is being rejected..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reject_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" id="reject_notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2" 
                                  placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors duration-200" onclick="closeQuickRejectModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i> Reject
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
function refreshPage() {
    window.location.reload();
}

function toggleBulkActions() {
    const bulkActions = document.getElementById('bulkActions');
    bulkActions.classList.toggle('hidden');
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.application-checkbox:not([disabled])');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActionsVisibility();
}

function updateBulkActionsVisibility() {
    const checkedBoxes = document.querySelectorAll('.application-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
}

// Handle individual checkbox changes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.application-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsVisibility);
    });
    
    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        const approveModal = document.getElementById('quickApproveModal');
        const rejectModal = document.getElementById('quickRejectModal');
        
        if (event.target === approveModal) {
            closeQuickApproveModal();
        }
        if (event.target === rejectModal) {
            closeQuickRejectModal();
        }
    });
});

function quickApprove(applicationId) {
    const form = document.getElementById('quickApproveForm');
    form.action = `/admin/sender-ids/${applicationId}/approve`;
    
    document.getElementById('quickApproveModal').classList.remove('hidden');
}

function quickReject(applicationId) {
    const form = document.getElementById('quickRejectForm');
    form.action = `/admin/sender-ids/${applicationId}/reject`;
    
    document.getElementById('quickRejectModal').classList.remove('hidden');
}

function closeQuickApproveModal() {
    document.getElementById('quickApproveModal').classList.add('hidden');
}

function closeQuickRejectModal() {
    document.getElementById('quickRejectModal').classList.add('hidden');
}

// Handle bulk form submission
document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.application-checkbox:checked');
    
    if (selectedCheckboxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one application.');
        return;
    }
    
    // Add selected application IDs to the form
    selectedCheckboxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'applications[]';
        input.value = checkbox.value;
        this.appendChild(input);
    });
    
    const action = this.querySelector('[name="action"]').value;
    if (action === 'reject' && !this.querySelector('[name="rejection_reason"]').value) {
        e.preventDefault();
        alert('Rejection reason is required when rejecting applications.');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} ${selectedCheckboxes.length} application(s)?`)) {
        e.preventDefault();
    }
});

// Auto-refresh functionality
setInterval(function() {
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn && !refreshBtn.disabled) {
        // Add subtle indication of auto-refresh
        refreshBtn.classList.add('animate-pulse');
        setTimeout(() => {
            refreshBtn.classList.remove('animate-pulse');
        }, 1000);
    }
}, 30000); // Refresh every 30 seconds

// Beem Sender ID Functions
function syncBeemSenderIds() {
    const btn = document.getElementById('sync-beem-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Syncing...';
    btn.disabled = true;
    
    fetch('{{ route('admin.sync-sender-ids') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Sender IDs synchronized successfully from Beem Africa!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('Failed to sync sender IDs: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred while syncing sender IDs', 'error');
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Sync from Beem';
        btn.disabled = false;
    });
}

function clearAndSyncBeemSenderIds() {
    if (!confirm('This will DELETE ALL existing sender ID applications and sync fresh data from Beem. Are you sure?')) {
        return;
    }
    
    const btn = document.getElementById('clear-sync-beem-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Clearing & Syncing...';
    btn.disabled = true;
    
    fetch('{{ route('admin.clear-and-sync-sender-ids') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('Failed: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Clear & Sync Fresh';
        btn.disabled = false;
    });
}

function showRequestSenderForm() {
    document.getElementById('requestSenderModal').classList.remove('hidden');
    document.getElementById('senderid').focus();
}

function hideRequestSenderForm() {
    document.getElementById('requestSenderModal').classList.add('hidden');
    document.getElementById('requestSenderForm').reset();
    document.getElementById('charCount').textContent = '0';
}

function updateCharCount() {
    const textarea = document.getElementById('sample_content');
    const charCount = document.getElementById('charCount');
    charCount.textContent = textarea.value.length;
    
    if (textarea.value.length < 15) {
        charCount.className = 'text-red-600 font-medium';
    } else {
        charCount.className = 'text-green-600 font-medium';
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'
            } mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('requestSenderModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRequestSenderForm();
        }
    });
});
</script>

<!-- Request Sender ID Modal -->
<div id="requestSenderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Request New Sender ID</h3>
            <button onclick="hideRequestSenderForm()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-800 mb-2">Sender ID Guidelines:</h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Maximum 11 characters</li>
                <li>• Should relate to your business/service</li>
                <li>• Only space, hyphen (-) and dot (.) allowed</li>
                <li>• Don't mimic established brands</li>
            </ul>
        </div>
        
        <form action="{{ route('admin.request-sender-id') }}" method="POST" id="requestSenderForm">
            @csrf
            <div class="mb-4">
                <label for="senderid" class="block text-sm font-medium text-gray-700 mb-2">
                    Sender ID <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="senderid" 
                       name="senderid" 
                       maxlength="11"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#6144f2] focus:border-transparent" 
                       placeholder="e.g., Phidtech"
                       required>
            </div>
            
            <div class="mb-4">
                <label for="sample_content" class="block text-sm font-medium text-gray-700 mb-2">
                    Sample Content <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-500">(min. 15 characters)</span>
                </label>
                <textarea id="sample_content" 
                          name="sample_content" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#6144f2] focus:border-transparent" 
                          placeholder="Describe what this sender ID will be used for..."
                          oninput="updateCharCount()"
                          required></textarea>
                <div class="text-xs text-gray-500 mt-1">
                    Characters: <span id="charCount" class="text-red-600 font-medium">0</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" 
                        onclick="hideRequestSenderForm()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-[#6144f2] text-white rounded-md hover:bg-[#5a3de8] transition-colors">
                    Request Sender ID
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
