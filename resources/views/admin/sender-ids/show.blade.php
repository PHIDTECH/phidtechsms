@extends('layouts.admin-modern-dashboard')

@section('title', 'Review Sender ID Application')



@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="bg-gradient-to-br from-purple-600 to-purple-800 text-white rounded-2xl p-8 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="mb-4 lg:mb-0">
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-id-card mr-3"></i>{{ $senderID->sender_name }}
                </h1>
                <p class="text-purple-100 mb-3">{{ $senderID->business_name }} • {{ $senderID->business_type }}</p>
                @if($senderID->status === 'pending')
                    <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        {{ $senderID->statusLabel }}
                    </span>
                @elseif($senderID->status === 'approved')
                    <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        {{ $senderID->statusLabel }}
                    </span>
                @else
                    <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        {{ $senderID->statusLabel }}
                    </span>
                @endif
            </div>
            <div>
                <a href="{{ route('admin.sender-ids.index') }}" class="bg-white text-purple-600 hover:bg-gray-50 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Applications
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Application Details -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Application Details
                </h5>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Reference Number:</span>
                    <span class="text-right flex-1">
                        <code class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">{{ $senderID->reference_number }}</code>
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Sender Name:</span>
                    <span class="text-right flex-1 font-semibold text-gray-900">{{ $senderID->sender_name }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Purpose/Use Case:</span>
                    <span class="text-right flex-1">{{ $senderID->purpose }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Application Date:</span>
                    <span class="text-right flex-1">{{ $senderID->application_date->format('M d, Y H:i') }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="font-semibold text-gray-600 w-2/5">Status:</span>
                    <span class="text-right flex-1">
                        @if($senderID->status === 'pending')
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                {{ $senderID->statusLabel }}
                            </span>
                        @elseif($senderID->status === 'approved')
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                {{ $senderID->statusLabel }}
                            </span>
                        @else
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                {{ $senderID->statusLabel }}
                            </span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Business Information -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-building mr-2"></i>Business Information
                </h5>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Business Name:</span>
                    <span class="text-right flex-1">{{ $senderID->business_name }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Business Type:</span>
                    <span class="text-right flex-1">{{ $senderID->business_type }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Registration Number:</span>
                    <span class="text-right flex-1">{{ $senderID->business_registration ?? 'Not provided' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Business Address:</span>
                    <span class="text-right flex-1">{{ $senderID->business_address ?? 'Not provided' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Contact Person:</span>
                    <span class="text-right flex-1">{{ $senderID->contact_person ?? 'Not provided' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="font-semibold text-gray-600 w-2/5">Contact Phone:</span>
                    <span class="text-right flex-1">{{ $senderID->contact_phone ?? 'Not provided' }}</span>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-user mr-2"></i>Applicant Information
                </h5>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Name:</span>
                    <span class="text-right flex-1">{{ $senderID->user->name }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Email:</span>
                    <span class="text-right flex-1">{{ $senderID->user->email }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="font-semibold text-gray-600 w-2/5">Phone:</span>
                    <span class="text-right flex-1">{{ $senderID->user->phone }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="font-semibold text-gray-600 w-2/5">Account Status:</span>
                    <span class="text-right flex-1 space-x-2">
                        @if($senderID->user->phone_verified)
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Verified</span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">Unverified</span>
                        @endif
                        @if($senderID->user->is_active)
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Inactive</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Documents -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-file-alt mr-2"></i>Submitted Documents
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Business License -->
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-certificate text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <h6 class="font-semibold text-gray-800 mb-2">Business License</h6>
                            @if($senderID->business_license_path)
                                <a href="{{ route('admin.sender-ids.download-document', [$senderID, 'business_license']) }}" 
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors duration-200">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            @else
                                <span class="text-gray-500">Not provided</span>
                            @endif
                        </div>
                    </div>

                    <!-- ID Document -->
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-id-card text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h6 class="font-semibold text-gray-800 mb-2">ID Document</h6>
                            @if($senderID->id_document_path)
                                <a href="{{ route('admin.sender-ids.download-document', [$senderID, 'id_document']) }}" 
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors duration-200">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            @else
                                <span class="text-gray-500">Not provided</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Additional Documents -->
                @if($senderID->additional_documents_paths && count($senderID->additional_documents_paths) > 0)
                    <h6 class="text-base font-semibold text-gray-700 mt-6 mb-3">Additional Documents</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($senderID->additional_documents_paths as $index => $path)
                            <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-file text-purple-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h6 class="font-semibold text-gray-800 mb-2">Document {{ $index + 1 }}</h6>
                                    <a href="{{ route('admin.sender-ids.download-additional', [$senderID, $index]) }}" 
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors duration-200">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Review Notes -->
            @if($senderID->admin_notes || $senderID->rejection_reason)
                <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                    <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                        <i class="fas fa-sticky-note mr-2"></i>Review Notes
                    </h5>
                    
                    @if($senderID->rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-2"></i>
                                <div>
                                    <strong class="text-red-800">Rejection Reason:</strong>
                                    <p class="text-red-700 mt-1">{{ $senderID->rejection_reason }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($senderID->admin_notes)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                                <div>
                                    <strong class="text-blue-800">Admin Notes:</strong>
                                    <p class="text-blue-700 mt-1">{{ $senderID->admin_notes }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($senderID->reviewer)
                        <div class="text-sm text-gray-500 mt-4 pt-4 border-t border-gray-200">
                            <i class="fas fa-user-check mr-1"></i>
                            Reviewed by <span class="font-medium">{{ $senderID->reviewer->name }}</span> on 
                            {{ $senderID->approved_at ? $senderID->approved_at->format('M d, Y H:i') : $senderID->rejected_at->format('M d, Y H:i') }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- Timeline -->
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 hover:shadow-lg transition-shadow duration-300">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-history mr-2"></i>Application Timeline
                </h5>
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                    
                    <div class="relative flex items-start mb-6">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center z-10">
                            <i class="fas fa-paper-plane text-white text-sm"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h6 class="font-semibold text-gray-800">Application Submitted</h6>
                                    <p class="text-gray-600 text-sm mt-1">Sender ID application submitted by {{ $senderID->user->name }}</p>
                                </div>
                                <div class="text-sm text-gray-500 ml-4">
                                    {{ $senderID->application_date->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($senderID->approved_at)
                        <div class="relative flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center z-10">
                                <i class="fas fa-check text-white text-sm"></i>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h6 class="font-semibold text-green-800">Application Approved</h6>
                                        <p class="text-gray-600 text-sm mt-1">
                                            Approved by {{ $senderID->reviewer->name ?? 'System' }}
                                            @if($senderID->admin_notes)
                                                <br><em class="text-gray-500">"{{ $senderID->admin_notes }}"</em>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 ml-4">
                                        {{ $senderID->approved_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($senderID->rejected_at)
                        <div class="relative flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center z-10">
                                <i class="fas fa-times text-white text-sm"></i>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h6 class="font-semibold text-red-800">Application Rejected</h6>
                                        <p class="text-gray-600 text-sm mt-1">
                                            Rejected by {{ $senderID->reviewer->name ?? 'System' }}
                                            @if($senderID->rejection_reason)
                                                <br><em class="text-gray-500">"{{ $senderID->rejection_reason }}"</em>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 ml-4">
                                        {{ $senderID->rejected_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-gray-200 rounded-xl p-6 sticky top-6">
                <h5 class="text-lg font-semibold text-gray-700 border-b-2 border-gray-200 pb-2 mb-4">
                    <i class="fas fa-cogs mr-2"></i>Actions
                </h5>
                
                @if($senderID->status === 'pending')
                    <!-- Approve Form -->
                    <form method="POST" action="{{ route('admin.sender-ids.approve', $senderID) }}" class="mb-6">
                        @csrf
                        <div class="mb-4">
                            <label for="admin_notes_approve" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" id="admin_notes_approve" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" rows="3" 
                                      placeholder="Add notes about this approval..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center mb-4" 
                                onclick="return confirm('Are you sure you want to approve this sender ID application?')">
                            <i class="fas fa-check mr-2"></i> Approve Application
                        </button>
                    </form>

                    <!-- Reject Form -->
                    <form method="POST" action="{{ route('admin.sender-ids.reject', $senderID) }}">
                        @csrf
                        <div class="mb-4">
                            <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea name="rejection_reason" id="rejection_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" rows="3" 
                                      placeholder="Explain why this application is being rejected..." required></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="admin_notes_reject" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" id="admin_notes_reject" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" rows="2" 
                                      placeholder="Add additional notes..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center" 
                                onclick="return confirm('Are you sure you want to reject this sender ID application?')">
                            <i class="fas fa-times mr-2"></i> Reject Application
                        </button>
                    </form>
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                            <div>
                                <p class="text-blue-700">This application has already been {{ $senderID->status }}.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="border-t border-gray-200 pt-4">
                    <!-- Additional Actions -->
                    <div class="space-y-3">
                        <a href="{{ route('admin.sender-ids.index') }}" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i> View All Applications
                        </a>
                        
                        <a href="{{ route('admin.sender-ids.index', ['status' => 'pending']) }}" class="w-full bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-clock mr-2"></i> View Pending Applications
                        </a>
                    </div>
                </div>
            </div>
         </div>
         </div>
     </div>
@endsection
