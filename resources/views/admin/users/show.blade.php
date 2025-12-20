@extends('layouts.admin-modern-dashboard')

@section('title', 'User Details - ' . $user->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">User Details</h1>
                <p class="text-gray-600">Manage {{ $user->name }}'s account and SMS credits</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user text-blue-600 mr-2"></i>
                    User Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <h4 class="text-xl font-semibold text-gray-900">{{ $user->name }}</h4>
                            @if($user->isAdmin())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-crown mr-1"></i>Administrator
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <p class="text-gray-900">{{ $user->phone }}</p>
                        </div>
                        @if($user->email)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <p class="text-gray-900">{{ $user->email }}</p>
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                            <p class="text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</p>
                            <p class="text-sm text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS Credits Management -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-sms text-purple-600 mr-2"></i>
                    SMS Credits Management
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Add Credits -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <h4 class="font-medium text-green-800 mb-3 flex items-center">
                            <i class="fas fa-plus-circle mr-2"></i>Add Credits
                        </h4>
                        <form action="{{ route('admin.users.add-credits', $user) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="add_credits" class="block text-sm font-medium text-gray-700 mb-1">Credits to Add</label>
                                <input type="number" name="amount" id="add_credits" min="1" max="10000" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div class="mb-3">
                                <label for="add_reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <input type="text" name="reason" id="add_reason" placeholder="e.g., Bonus credits" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Add Credits
                            </button>
                        </form>
                    </div>

                    <!-- Deduct Credits -->
                    <div class="bg-red-50 rounded-lg p-4">
                        <h4 class="font-medium text-red-800 mb-3 flex items-center">
                            <i class="fas fa-minus-circle mr-2"></i>Deduct Credits
                        </h4>
                        <form action="{{ route('admin.users.deduct-credits', $user) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="deduct_credits" class="block text-sm font-medium text-gray-700 mb-1">Credits to Deduct</label>
                                <input type="number" name="amount" id="deduct_credits" min="1" max="{{ $user->sms_credits }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="mb-3">
                                <label for="deduct_reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <input type="text" name="reason" id="deduct_reason" placeholder="e.g., Refund processed" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                                <i class="fas fa-minus mr-2"></i>Deduct Credits
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Campaigns -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-paper-plane text-blue-600 mr-2"></i>
                    Recent Campaigns
                </h3>
                @if($user->campaigns->count() > 0)
                    <div class="space-y-3">
                        @foreach($user->campaigns->take(5) as $campaign)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $campaign->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $campaign->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($campaign->status === 'sent') bg-green-100 text-green-800
                                        @elseif($campaign->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                    <p class="text-sm text-gray-500 mt-1">{{ number_format($campaign->total_recipients) }} recipients</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">No campaigns found</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-green-600 mr-2"></i>
                    Quick Stats
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">SMS Credits</p>
                            <p class="text-2xl font-bold text-purple-600">{{ number_format($user->sms_credits) }}</p>
                        </div>
                        <i class="fas fa-sms text-purple-600 text-2xl"></i>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Total Campaigns</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $user->campaigns->count() }}</p>
                        </div>
                        <i class="fas fa-paper-plane text-blue-600 text-2xl"></i>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Sender IDs</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $user->senderIDs->count() }}</p>
                        </div>
                        <i class="fas fa-shield-alt text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            @if(!$user->isAdmin())
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cog text-gray-600 mr-2"></i>
                        Account Actions
                    </h3>
                    <div class="space-y-3">
                        <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full {{ $user->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-md transition-colors">
                                <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-2"></i>
                                {{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history text-gray-600 mr-2"></i>
                    Recent Activity
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-plus text-blue-500"></i>
                        <span class="text-gray-600">Registered {{ $user->created_at->diffForHumans() }}</span>
                    </div>
                    @if($user->campaigns->count() > 0)
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-paper-plane text-green-500"></i>
                            @php
                                $latestCampaign = $user->campaigns->sortByDesc('created_at')->first();
                            @endphp
                            <span class="text-gray-600">Last campaign {{ $latestCampaign && $latestCampaign->created_at ? $latestCampaign->created_at->diffForHumans() : 'N/A' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-focus on credit input fields when forms are visible
document.addEventListener('DOMContentLoaded', function() {
    // Add validation for deduct credits
    const deductForm = document.querySelector('form[action*="deduct-credits"]');
    const deductInput = document.getElementById('deduct_credits');
    const maxCredits = {{ $user->sms_credits }};
    
    if (deductForm && deductInput) {
        deductInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value > maxCredits) {
                this.value = maxCredits;
            }
        });
    }
});
</script>
@endpush
