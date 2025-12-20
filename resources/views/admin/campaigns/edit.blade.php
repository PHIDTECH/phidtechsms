@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin - Edit Campaign')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <nav class="text-sm text-gray-500 mb-2">
                        <a href="{{ route('admin.campaigns.index') }}" class="hover:text-purple-600">Campaigns</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:text-purple-600">{{ $campaign->name }}</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-900">Edit</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-edit text-purple-600 mr-3"></i>
                        Edit Campaign
                    </h1>
                    <p class="text-gray-600">Modify campaign settings and add admin notes</p>
                </div>
                <div class="flex space-x-3 mt-4 md:mt-0">
                    <a href="{{ route('admin.campaigns.show', $campaign) }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Details
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Campaign Information -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Campaign Name</label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $campaign->name) }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-2">Sender ID</label>
                                    <input type="text" id="sender_id" name="sender_id" value="{{ old('sender_id', $campaign->sender_id) }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="Leave empty for default">
                                </div>
                                
                                @if($campaign->status === 'draft' || $campaign->status === 'pending')
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select id="status" name="status" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="draft" {{ $campaign->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="pending" {{ $campaign->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        @if($campaign->status === 'cancelled')
                                            <option value="cancelled" selected>Cancelled</option>
                                        @endif
                                    </select>
                                </div>
                                @else
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <p class="text-sm text-gray-900 py-2">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'sending' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                        <span class="text-xs text-gray-500 ml-2">(Cannot be changed)</span>
                                    </p>
                                </div>
                                @endif
                                
                                @if($campaign->status === 'draft' || $campaign->status === 'pending')
                                <div>
                                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">Schedule Date & Time</label>
                                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" 
                                           value="{{ old('scheduled_at', $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to send immediately when approved</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Message Content</h3>
                            
                            @if(in_array($campaign->status, ['draft', 'pending']))
                                <div>
                                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message Text</label>
                                    <textarea id="message" name="message" rows="6" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                              required>{{ old('message', $campaign->message) }}</textarea>
                                    <div class="mt-2 flex justify-between text-sm text-gray-500">
                                        <span id="char-count">{{ strlen($campaign->message) }} characters</span>
                                        <span id="sms-parts">{{ ceil(strlen($campaign->message) / 160) }} SMS parts</span>
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $campaign->message }}</p>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Character Count:</span>
                                        <span class="font-medium">{{ strlen($campaign->message) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">SMS Parts:</span>
                                        <span class="font-medium">{{ ceil(strlen($campaign->message) / 160) }}</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Message content cannot be changed after campaign has started</p>
                            @endif
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Notes</h3>
                            <div>
                                <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Internal Notes</label>
                                <textarea id="admin_notes" name="admin_notes" rows="4" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                          placeholder="Add internal notes about this campaign...">{{ old('admin_notes', $campaign->admin_notes) }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">These notes are only visible to administrators</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Campaign Details -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Details</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Owner:</span>
                                    <span class="font-medium text-gray-900">{{ $campaign->user->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium text-gray-900">{{ $campaign->user->phone }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Recipients:</span>
                                    <span class="font-medium text-gray-900">{{ number_format($campaign->estimated_recipients) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Estimated Cost:</span>
                                    <span class="font-medium text-gray-900">TZS {{ number_format($campaign->estimated_cost) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="font-medium text-gray-900">{{ $campaign->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Statistics -->
                    @if($campaign->status !== 'draft')
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Sent:</span>
                                    <span class="text-sm font-medium text-blue-900">{{ number_format($campaign->sent_count) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Delivered:</span>
                                    <span class="text-sm font-medium text-green-900">{{ number_format($campaign->delivered_count) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Failed:</span>
                                    <span class="text-sm font-medium text-red-900">{{ number_format($campaign->failed_count) }}</span>
                                </div>
                                @if($campaign->actual_cost)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Actual Cost:</span>
                                    <span class="text-sm font-medium text-gray-900">TZS {{ number_format($campaign->actual_cost) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                            <div class="space-y-3">
                                <button type="submit" 
                                        class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                                
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" 
                                   class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 text-center block">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const smsPartsCount = document.getElementById('sms-parts');
    
    if (messageTextarea && charCount && smsPartsCount) {
        function updateCounts() {
            const length = messageTextarea.value.length;
            const parts = Math.ceil(length / 160) || 1;
            
            charCount.textContent = length + ' characters';
            smsPartsCount.textContent = parts + ' SMS parts';
        }
        
        messageTextarea.addEventListener('input', updateCounts);
    }
});
</script>
@endsection