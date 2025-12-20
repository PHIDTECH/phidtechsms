@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin - Campaign Details')

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
                        <span class="text-gray-900">{{ $campaign->name }}</span>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-bullhorn text-purple-600 mr-3"></i>
                        Campaign Details
                    </h1>
                    <p class="text-gray-600">View and manage campaign information</p>
                </div>
                <div class="flex space-x-3 mt-4 md:mt-0">
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>Edit Campaign
                    </a>
                    @if(in_array($campaign->status, ['pending', 'scheduled', 'sending']))
                        <form method="POST" action="{{ route('admin.campaigns.cancel', $campaign) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                    onclick="return confirm('Are you sure you want to cancel this campaign?')">
                                <i class="fas fa-stop mr-2"></i>Cancel Campaign
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Campaign Status Card -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Campaign Status</h2>
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
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </div>
                
                @if($campaign->status === 'sending' || $campaign->status === 'completed')
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        @php
                            $progress = $campaign->estimated_recipients > 0 ? ($campaign->sent_count / $campaign->estimated_recipients) * 100 : 0;
                        @endphp
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ number_format($campaign->sent_count) }} of {{ number_format($campaign->estimated_recipients) }} messages sent ({{ number_format($progress, 1) }}%)
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Campaign Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
                                <p class="text-sm text-gray-900">{{ $campaign->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Owner</label>
                                <p class="text-sm text-gray-900">{{ $campaign->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $campaign->user->phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sender ID</label>
                                <p class="text-sm text-gray-900">{{ $campaign->sender_id ?? 'Default' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Created Date</label>
                                <p class="text-sm text-gray-900">{{ $campaign->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            @if($campaign->scheduled_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date</label>
                                <p class="text-sm text-gray-900">{{ $campaign->scheduled_at->format('M d, Y H:i') }}</p>
                            </div>
                            @endif
                            @if($campaign->sent_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sent Date</label>
                                <p class="text-sm text-gray-900">{{ $campaign->sent_at->format('M d, Y H:i') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Message Content -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Message Content</h3>
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
                    </div>
                </div>

                <!-- Recipients -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recipients</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ number_format($campaign->estimated_recipients) }}</p>
                                <p class="text-sm text-gray-600">Total Recipients</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-900">{{ number_format($campaign->sent_count) }}</p>
                                <p class="text-sm text-gray-600">Sent</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-900">{{ number_format($campaign->delivered_count) }}</p>
                                <p class="text-sm text-gray-600">Delivered</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-red-900">{{ number_format($campaign->failed_count) }}</p>
                                <p class="text-sm text-gray-600">Failed</p>
                            </div>
                        </div>
                        
                        @if($campaign->contact_list_id)
                            <div class="bg-blue-50 rounded-lg p-4">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-list mr-2"></i>
                                    Using Contact List: <strong>{{ $campaign->contactList->name ?? 'Unknown' }}</strong>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                @if($campaign->admin_notes)
                <!-- Admin Notes -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Notes</h3>
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $campaign->admin_notes }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Cost Information -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cost Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Estimated Cost:</span>
                                <span class="text-sm font-medium text-gray-900">TZS {{ number_format($campaign->estimated_cost) }}</span>
                            </div>
                            @if($campaign->actual_cost)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Actual Cost:</span>
                                <span class="text-sm font-medium text-gray-900">TZS {{ number_format($campaign->actual_cost) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Cost per SMS:</span>
                                <span class="text-sm font-medium text-gray-900">TZS {{ number_format($campaign->cost_per_sms ?? 50) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaign Timeline -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Created</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            
                            @if($campaign->scheduled_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-2 h-2 bg-yellow-400 rounded-full mt-2"></div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Scheduled</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->scheduled_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($campaign->sent_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Started Sending</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->sent_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($campaign->completed_at)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-2 h-2 bg-green-400 rounded-full mt-2"></div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Completed</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->completed_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" 
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 text-center block">
                                <i class="fas fa-edit mr-2"></i>Edit Campaign
                            </a>
                            
                            @if(in_array($campaign->status, ['pending', 'scheduled', 'sending']))
                                <form method="POST" action="{{ route('admin.campaigns.cancel', $campaign) }}">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                            onclick="return confirm('Are you sure you want to cancel this campaign?')">
                                        <i class="fas fa-stop mr-2"></i>Cancel Campaign
                                    </button>
                                </form>
                            @endif
                            
                            @if(in_array($campaign->status, ['draft', 'failed', 'cancelled']))
                                <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                            onclick="return confirm('Are you sure you want to delete this campaign?')">
                                        <i class="fas fa-trash mr-2"></i>Delete Campaign
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection