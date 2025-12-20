@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin Reports')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-chart-line text-blue-600 mr-3"></i>
                        Admin Reports Dashboard
                    </h1>
                    <p class="text-gray-600">Comprehensive analytics and insights across all system activities</p>
                </div>
                
                <!-- Date Range Filter -->
                <div class="mt-4 md:mt-0">
                    <form method="GET" class="flex flex-col sm:flex-row gap-2">
                        <input type="date" name="start_date" value="{{ $startDate }}" 
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <input type="date" name="end_date" value="{{ $endDate }}" 
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($reports['overview']['total_users']) }}</p>
                        <p class="text-sm text-green-600">+{{ $reports['overview']['new_users'] }} this period</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Campaigns</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($reports['overview']['total_campaigns']) }}</p>
                        <p class="text-sm text-green-600">+{{ $reports['overview']['campaigns_period'] }} this period</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-bullhorn text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Messages Sent</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($reports['overview']['total_messages']) }}</p>
                        <p class="text-sm text-green-600">+{{ number_format($reports['overview']['messages_period']) }} this period</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-sms text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($reports['overview']['total_revenue']) }} TZS</p>
                        <p class="text-sm text-green-600">+{{ number_format($reports['overview']['revenue_period']) }} TZS this period</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-download text-blue-600 mr-2"></i>
                Export Reports
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('admin.reports.export', ['type' => 'overview', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i>Overview
                </a>
                <a href="{{ route('admin.reports.export', ['type' => 'users', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="{{ route('admin.reports.export', ['type' => 'campaigns', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-bullhorn mr-2"></i>Campaigns
                </a>
                <a href="{{ route('admin.reports.export', ['type' => 'payments', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="flex items-center justify-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-money-bill mr-2"></i>Payments
                </a>
            </div>
        </div>

        <!-- User Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Top Users by Campaigns -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                    Top Users by Campaigns
                </h3>
                <div class="space-y-3">
                    @foreach($reports['user_activities']['top_campaign_users']->take(5) as $user)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-sm text-gray-600">{{ $user->phone_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-blue-600">{{ $user->campaigns_count }}</p>
                            <p class="text-xs text-gray-500">campaigns</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Users by Messages -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-trophy text-green-500 mr-2"></i>
                    Top Users by Messages
                </h3>
                <div class="space-y-3">
                    @foreach($reports['user_activities']['top_message_users']->take(5) as $user)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-sm text-gray-600">{{ $user->phone_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">{{ number_format($user->sms_messages_count) }}</p>
                            <p class="text-xs text-gray-500">messages</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Campaign Analytics -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                Campaign Analytics
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($reports['campaign_analytics']['total_campaigns']) }}</p>
                    <p class="text-sm text-gray-600">Total Campaigns</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($reports['campaign_analytics']['total_sent']) }}</p>
                    <p class="text-sm text-gray-600">Messages Sent</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600">{{ number_format($reports['campaign_analytics']['total_delivered']) }}</p>
                    <p class="text-sm text-gray-600">Messages Delivered</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($reports['campaign_analytics']['avg_delivery_rate'], 1) }}%</p>
                    <p class="text-sm text-gray-600">Avg Delivery Rate</p>
                </div>
            </div>

            <!-- Campaign Status Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Campaign Status Distribution</h4>
                    <div class="space-y-2">
                        @foreach($reports['campaign_analytics']['status_breakdown'] as $status => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 capitalize">{{ $status }}</span>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Top Performing Campaigns</h4>
                    <div class="space-y-2">
                        @foreach($reports['campaign_analytics']['top_campaigns']->take(5) as $campaign)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 truncate">{{ Str::limit($campaign->name, 20) }}</span>
                            <span class="font-medium text-green-600">{{ number_format($campaign->delivered_count) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                Financial Summary
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ number_format($reports['financial_summary']['payment_stats']['total_revenue']) }} TZS</p>
                    <p class="text-sm text-gray-600">Total Revenue</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($reports['financial_summary']['payment_stats']['completed_payments']) }}</p>
                    <p class="text-sm text-gray-600">Completed Payments</p>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($reports['financial_summary']['payment_stats']['total_credits_sold']) }}</p>
                    <p class="text-sm text-gray-600">Credits Sold</p>
                </div>
            </div>

            <!-- Top Paying Users -->
            <div>
                <h4 class="font-medium text-gray-900 mb-3">Top Paying Users</h4>
                <div class="space-y-3">
                    @foreach($reports['financial_summary']['top_paying_users']->take(5) as $user)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-sm text-gray-600">{{ $user->phone_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">{{ number_format($user->payments_sum_amount ?? 0) }} TZS</p>
                            <p class="text-xs text-gray-500">total spent</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- System Health & Sender IDs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- System Health -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-heartbeat text-red-500 mr-2"></i>
                    System Health
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Message Delivery Rate</span>
                        <span class="font-bold text-green-600">{{ number_format($reports['system_health']['delivery_stats']['delivery_rate'], 1) }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Total Messages</span>
                        <span class="font-medium">{{ number_format($reports['system_health']['delivery_stats']['total_messages']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Failed Messages</span>
                        <span class="font-medium text-red-600">{{ number_format($reports['system_health']['delivery_stats']['failed_messages']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">System Uptime</span>
                        <span class="font-bold text-green-600">{{ $reports['system_health']['uptime'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Sender ID Analytics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-id-card text-blue-500 mr-2"></i>
                    Sender ID Analytics
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Total Applications</span>
                        <span class="font-medium">{{ $reports['sender_id_analytics']['total_applications'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Approval Rate</span>
                        <span class="font-bold text-green-600">{{ number_format($reports['sender_id_analytics']['approval_rate'], 1) }}%</span>
                    </div>
                    @foreach($reports['sender_id_analytics']['status_breakdown'] as $status => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 capitalize">{{ $status }}</span>
                        <span class="font-medium">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-clock text-blue-600 mr-2"></i>
                Recent System Activities
            </h3>
            <div class="space-y-4">
                @foreach($reports['recent_activities'] as $activity)
                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        @if($activity['type'] === 'user_registration')
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                            </div>
                        @elseif($activity['type'] === 'campaign_created')
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-bullhorn text-green-600 text-sm"></i>
                            </div>
                        @elseif($activity['type'] === 'payment')
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-money-bill text-yellow-600 text-sm"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900">{{ $activity['description'] }}</p>
                        <p class="text-xs text-gray-500">{{ $activity['timestamp']->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
