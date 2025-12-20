@extends('layouts.modern-dashboard')

@section('title', 'Sender ID Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    @if(session('success') || session('warning'))
        @php
            $type = session('success') ? 'success' : 'warning';
            $message = session('success') ?: session('warning');
            $bg = $type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800';
            $icon = $type === 'success' ? 'check-circle' : 'exclamation-circle';
        @endphp
        <div class="mb-6">
            <div class="border {{ $bg }} rounded-xl px-5 py-4 flex items-start space-x-3">
                <span class="mt-1">
                    <i class="fas fa-{{ $icon }}"></i>
                </span>
                <div>
                    <p class="font-semibold">{{ $message }}</p>
                    @if($type === 'warning')
                        <p class="text-sm opacity-80">We saved the application locally; please review the note below and retry sending to Beem if needed.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('sender-ids.index') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $senderID->sender_name }}</h1>
                <p class="text-gray-600 mt-1">Sender ID Application Details</p>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 h-16 w-16">
                        <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-xl font-bold text-blue-600">{{ substr($senderID->sender_name, 0, 2) }}</span>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $senderID->sender_name }}</h2>
                        <p class="text-gray-600">{{ $senderID->business_name }}</p>
                        <div class="flex items-center space-x-4 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $senderID->status_badge_class }}">
                                {{ $senderID->status_label }}
                            </span>
                            <span class="text-sm text-gray-500">Ref: {{ $senderID->reference_number }}</span>
                        </div>
                    </div>
                </div>
                
                @if($senderID->status === 'approved')
                <div class="text-right">
                    <div class="text-green-600 font-medium">Ready to Use</div>
                    <div class="text-sm text-gray-500">Approved {{ $senderID->approved_at->diffForHumans() }}</div>
                </div>
                @elseif($senderID->status === 'pending')
                <div class="text-right">
                    <div class="text-yellow-600 font-medium">Under Review</div>
                    <div class="text-sm text-gray-500">Applied {{ $senderID->application_date->diffForHumans() }}</div>
                </div>
                @elseif($senderID->status === 'rejected')
                <div class="text-right">
                    <div class="text-red-600 font-medium">Rejected</div>
                    <div class="text-sm text-gray-500">{{ $senderID->rejected_at->diffForHumans() }}</div>
                </div>
                @endif
            </div>
            
            @if($senderID->status === 'rejected' && $senderID->rejection_reason)
            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Rejection Reason</h3>
                        <div class="mt-2 text-sm text-red-700">
                            {{ $senderID->rejection_reason }}
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            @if($senderID->admin_notes)
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Admin Notes</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            {{ $senderID->admin_notes }}
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Application Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Application Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sender Name</label>
                    <p class="text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded border">{{ $senderID->sender_name }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                    <p class="text-gray-900 capitalize">{{ str_replace('_', ' ', $senderID->business_type) }}</p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Use Case</label>
                    <p class="text-gray-900 bg-gray-50 px-3 py-2 rounded border">{{ $senderID->use_case }}</p>
                </div>
            </div>
        </div>

        <!-- Business Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Business Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                    <p class="text-gray-900">{{ $senderID->business_name }}</p>
                </div>
                
                @if($senderID->business_registration)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
                    <p class="text-gray-900 font-mono">{{ $senderID->business_registration }}</p>
                </div>
                @endif
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Address</label>
                    <p class="text-gray-900">{{ $senderID->business_address }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <p class="text-gray-900">{{ $senderID->contact_person }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <p class="text-gray-900 font-mono">{{ $senderID->contact_phone }}</p>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Submitted Documents</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($senderID->business_license_path)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900">Business License</h4>
                            <p class="text-sm text-gray-500">{{ basename($senderID->business_license_path) }}</p>
                        </div>
                        <a href="{{ Storage::url($senderID->business_license_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                @endif
                
                @if($senderID->id_document_path)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900">ID Document</h4>
                            <p class="text-sm text-gray-500">{{ basename($senderID->id_document_path) }}</p>
                        </div>
                        <a href="{{ Storage::url($senderID->id_document_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                @endif
            </div>
            
            @if($senderID->additional_documents_paths && count($senderID->additional_documents_paths) > 0)
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Additional Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($senderID->additional_documents_paths as $document)
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center space-x-2">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 truncate">{{ basename($document) }}</p>
                            </div>
                            <a href="{{ Storage::url($document) }}" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Application Timeline</h3>
            
            <div class="flow-root">
                <ul class="-mb-8">
                    <li>
                        <div class="relative pb-8">
                            @if($senderID->status !== 'pending')
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">Application submitted on <time datetime="{{ $senderID->application_date->toISOString() }}">{{ $senderID->application_date->format('M j, Y \a\t g:i A') }}</time></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    @if($senderID->status === 'approved')
                    <li>
                        <div class="relative pb-8">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">Application approved on <time datetime="{{ $senderID->approved_at->toISOString() }}">{{ $senderID->approved_at->format('M j, Y \a\t g:i A') }}</time></p>
                                        <p class="text-sm text-green-600 font-medium">Your sender ID is now ready to use!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @elseif($senderID->status === 'rejected')
                    <li>
                        <div class="relative pb-8">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">Application rejected on <time datetime="{{ $senderID->rejected_at->toISOString() }}">{{ $senderID->rejected_at->format('M j, Y \a\t g:i A') }}</time></p>
                                        <p class="text-sm text-red-600 font-medium">Please review the rejection reason above</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @else
                    <li>
                        <div class="relative pb-8">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">Application is currently under review</p>
                                        <p class="text-sm text-yellow-600 font-medium">We'll notify you once the review is complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('sender-ids.index') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                ← Back to Sender IDs
            </a>
            
            @if($senderID->status === 'approved')
            <a href="{{ route('campaigns.create', ['sender_id' => $senderID->id]) }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Use in Campaign
            </a>
            @elseif($senderID->status === 'rejected')
            <a href="{{ route('sender-ids.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Submit New Application
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
