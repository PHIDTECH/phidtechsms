@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin Dashboard')

@section('content')
<div class="animate-fade-in-up">
    <!-- Key Performance Indicators - Simplified -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- SMS Balance Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">SMS Balance</p>
                    <p id="dashboard-admin-balance" class="text-3xl font-bold text-gray-900">{{ number_format($beemBalance['admin_balance'] ?? 0) }}</p>
                    <a href="{{ route('wallet.topup') }}" class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                        Buy More SMS →
                    </a>
                </div>
                <div class="p-4 bg-blue-100 rounded-2xl">
                    <i class="fas fa-envelope text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Users Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_users'] ?? 0) }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        +{{ number_format($stats['new_users'] ?? 0) }} this week
                    </p>
                </div>
                <div class="p-4 bg-green-100 rounded-2xl">
                    <i class="fas fa-users text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Campaigns Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Campaigns</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_campaigns'] ?? 0) }}</p>
                </div>
                <div class="p-4 bg-purple-100 rounded-2xl">
                    <i class="fas fa-bullhorn text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Delivery Rate Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Delivery Rate</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $deliveryStats['delivered_percentage'] ?? 0 }}%</p>
                </div>
                <div class="p-4 bg-orange-100 rounded-2xl">
                    <i class="fas fa-chart-line text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Send SMS -->
        <a href="{{ route('admin.sms.compose') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
                    <i class="fas fa-comment-dots text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Send SMS</h3>
                    <p class="text-sm text-gray-600">Send messages to users</p>
                </div>
            </div>
        </a>

        <!-- Manage Users -->
        <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Manage Users</h3>
                    <p class="text-sm text-gray-600">View and manage all users</p>
                </div>
            </div>
        </a>

        <!-- Sender IDs -->
        <a href="{{ route('admin.sender-ids.index') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
                    <i class="fas fa-id-card text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Sender IDs</h3>
                    <p class="text-sm text-gray-600">Manage sender names</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Pending Items Alert -->
    @if(($stats['pending_sender_ids'] ?? 0) > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-8">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
            <div>
                <h4 class="font-semibold text-yellow-800">Pending Approvals</h4>
                <p class="text-sm text-yellow-700">You have {{ $stats['pending_sender_ids'] }} Sender ID requests waiting for approval. <a href="{{ route('admin.sender-ids.index') }}" class="underline font-medium">Review now</a></p>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Users Section -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-users text-blue-500 mr-2"></i>
                    Recent Users
                </h3>
                <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentUsers ?? [] as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-xs font-semibold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">{{ $user->name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->phone ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php $isActive = $user->is_active ?? true; @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $isActive ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at ? $user->created_at->diffForHumans() : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center">
                            <i class="fas fa-users text-gray-300 text-3xl mb-3"></i>
                            <p class="text-gray-500">No recent users</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
