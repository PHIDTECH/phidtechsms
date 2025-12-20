@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin Dashboard')

@section('content')
<div class="animate-fade-in-up">
    <!-- Welcome Header -->
    <div class="mb-8">
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                    <p class="text-purple-100">Here's what's happening with your SMS platform today.</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- SMS Balance Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">SMS Balance</p>
                    <p id="dashboard-admin-balance" class="text-2xl font-bold text-gray-900">{{ number_format($beemBalance['admin_balance'] ?? 0) }}</p>
                    <p class="text-xs text-blue-600 mt-1">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Last sync: <span id="dashboard-last-sync">{{ $beemBalance['last_sync'] ? \Carbon\Carbon::parse($beemBalance['last_sync'])->diffForHumans() : 'Never' }}</span>
                        <button id="dashboard-sync-btn" class="ml-2 text-blue-600 hover:underline">Sync now</button>
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-sms text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Users Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_users'] ?? 0) }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>
                        {{ number_format($stats['new_users'] ?? 0) }} new this week
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-users text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Campaigns Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Campaigns</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_campaigns'] ?? 0) }}</p>
                    <p class="text-xs text-purple-600 mt-1">
                        <i class="fas fa-bullhorn mr-1"></i>
                        All time campaigns
                    </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-bullhorn text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- SMS Sent Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">SMS Sent</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_messages'] ?? 0) }}</p>
                    <p class="text-xs text-orange-600 mt-1">
                        <i class="fas fa-paper-plane mr-1"></i>
                        Total messages sent
                    </p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-paper-plane text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Sender IDs Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Approved Sender IDs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['approved_sender_ids'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ number_format($stats['pending_sender_ids'] ?? 0) }} pending approval
                    </p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-id-card text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Users Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_users'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100, 1) : 0 }}% of total users
                    </p>
                </div>
                <div class="p-3 bg-teal-100 rounded-full">
                    <i class="fas fa-user-check text-teal-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900">TSh {{ number_format($stats['total_revenue'] ?? 0, 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        From wallet transactions
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-dollar-sign text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Delivery Rate Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Delivery Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $deliveryStats['delivered_percentage'] ?? 0 }}%</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $deliveryStats['failed_percentage'] ?? 0 }}% failed
                    </p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-full">
                    <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- SMS Trends Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">SMS Trends (Last 7 Days)</h3>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-1"></div>
                        Sent
                    </span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                        Delivered
                    </span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="smsChart"></canvas>
            </div>
        </div>

        <!-- Delivery Status Pie Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Message Status Distribution</h3>
                <i class="fas fa-chart-pie text-gray-400"></i>
            </div>
            <div class="h-64">
                <canvas id="deliveryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.users.index') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <div class="p-2 bg-blue-500 rounded-lg mr-3">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Manage Users</p>
                    <p class="text-sm text-gray-600">View all users</p>
                </div>
            </a>
            
            <a href="{{ route('admin.campaigns.index') }}" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                <div class="p-2 bg-purple-500 rounded-lg mr-3">
                    <i class="fas fa-bullhorn text-white"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">View Campaigns</p>
                    <p class="text-sm text-gray-600">Monitor campaigns</p>
                </div>
            </a>
            
            <a href="{{ route('admin.sender-ids.index') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                <div class="p-2 bg-green-500 rounded-lg mr-3">
                    <i class="fas fa-id-card text-white"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Sender IDs</p>
                    <p class="text-sm text-gray-600">Approve requests</p>
                </div>
            </a>
            
            <a href="{{ route('admin.settings.index') }}" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                <div class="p-2 bg-orange-500 rounded-lg mr-3">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Settings</p>
                    <p class="text-sm text-gray-600">System config</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Users -->
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
            
            <div class="p-6">
                @forelse($recentUsers ?? [] as $user)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        @php $isActive = $user->is_active ?? true; @endphp
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $isActive ? 'Active' : 'Inactive' }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ $user->created_at ? $user->created_at->diffForHumans() : 'N/A' }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-users text-gray-300 text-3xl mb-3"></i>
                    <p class="text-gray-500">No recent users</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="bg-white rounded-xl shadow-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-bullhorn text-purple-500 mr-2"></i>
                        Recent Campaigns
                    </h3>
                    <a href="{{ route('admin.campaigns.index') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                @forelse($recentCampaigns ?? [] as $campaign)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">{{ $campaign->name ?? 'Campaign' }}</p>
                            @php
                                $status = $campaign->status ?? 'pending';
                                $statusColors = [
                                    'completed' => 'bg-green-100 text-green-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'scheduled' => 'bg-yellow-100 text-yellow-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-gray-100 text-gray-800'
                                ];
                                $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <p class="text-xs text-gray-500">{{ $campaign->user->name ?? 'Unknown User' }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($campaign->total_recipients ?? 0) }} recipients</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $campaign->created_at ? $campaign->created_at->diffForHumans() : 'N/A' }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-bullhorn text-gray-300 text-3xl mb-3"></i>
                    <p class="text-gray-500">No recent campaigns</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Chart.js and Custom Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // SMS Trends Chart
    const smsCtx = document.getElementById('smsChart');
    if (smsCtx) {
        const weeklyData = @json($weeklyData ?? []);
        const labels = weeklyData.map(item => item.date);
        const sentData = weeklyData.map(item => item.sent);
        const deliveredData = weeklyData.map(item => item.delivered);

        new Chart(smsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'SMS Sent',
                    data: sentData,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'SMS Delivered',
                    data: deliveredData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Delivery Status Pie Chart
    const deliveryCtx = document.getElementById('deliveryChart');
    if (deliveryCtx) {
        const deliveryStats = @json($deliveryStats ?? []);
        
        new Chart(deliveryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Delivered', 'Failed', 'Pending'],
                datasets: [{
                    data: [
                        deliveryStats.delivered || 0,
                        deliveryStats.failed || 0,
                        deliveryStats.pending || 0
                    ],
                    backgroundColor: [
                        '#10B981',
                        '#EF4444',
                        '#F59E0B'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>

<!-- Custom Styles -->
<style>
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.transition-shadow {
    transition: box-shadow 0.3s ease;
}

.transition-colors {
    transition: background-color 0.3s ease, color 0.3s ease;
}
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('dashboard-sync-btn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.textContent = 'Syncing...';
        fetch('{{ route("admin.sms.sync-balance") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                const nf = new Intl.NumberFormat();
                document.getElementById('dashboard-admin-balance').textContent = nf.format(data.sms_credits || 0);
                const lastSyncEl = document.getElementById('dashboard-last-sync');
                if (data.last_sync) {
                    const dt = new Date(data.last_sync);
                    lastSyncEl.textContent = dt.toLocaleString();
                } else {
                    lastSyncEl.textContent = 'Just now';
                }
            } else {
                alert('Failed to sync: ' + (data && data.error ? data.error : 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Sync error', err);
            alert('Network error during sync');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Sync now';
        });
    });
});
</script>
@endpush
