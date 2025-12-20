@extends('layouts.modern-dashboard')

@section('page-title', 'Modern Reports')

@section('styles')
<style>
    .modern-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .stats-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
    }
    
    .bg-gradient-purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .bg-gradient-green {
        background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    }
    
    .bg-gradient-red {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    }
    
    .bg-gradient-blue {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    }
</style>
@endsection

@section('content')
                <!-- Page Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">SMS Reports & Analytics</h2>
                    
                    <!-- Time Period Selector and Export -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 90 days</option>
                                <option>Custom Range</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2">
                            <i class="fas fa-download"></i>
                            <span>Export Report</span>
                        </button>
                    </div>
                </div>

                <!-- Analytics Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Delivery Analytics -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Delivery Analytics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Messages Sent</span>
                                <span class="text-xl font-semibold text-gray-900">{{ number_format($reports['delivery_analytics']['total_messages'] ?? 12847) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Successfully Delivered</span>
                                <span class="text-xl font-semibold text-green-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['delivered'] ?? 12654) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Failed Deliveries</span>
                                <span class="text-xl font-semibold text-red-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['failed'] ?? 193) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Average Delivery Time</span>
                                <span class="text-xl font-semibold text-gray-900">{{ $reports['delivery_analytics']['avg_delivery_time'] ?? '2.3s' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Performance -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Performance</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Messages Sent</span>
                                <span class="text-xl font-semibold text-gray-900">{{ number_format($reports['campaign_performance']['total_sent'] ?? 12847) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Successfully Delivered</span>
                                <span class="text-xl font-semibold text-green-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['delivered'] ?? 12654) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Failed Deliveries</span>
                                <span class="text-xl font-semibold text-red-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['failed'] ?? 193) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Average Delivery Time</span>
                                <span class="text-xl font-semibold text-gray-900">{{ $reports['delivery_analytics']['avg_delivery_time'] ?? '2.3s' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Analytics -->
                @if(isset($reports['campaign_performance']['top_campaigns']) && $reports['campaign_performance']['top_campaigns']->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Campaigns</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-medium text-gray-600">Campaign Name</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-600">Messages Sent</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-600">Delivered</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-600">Delivery Rate</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-600">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports['campaign_performance']['top_campaigns'] as $campaign)
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $campaign->name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ number_format($campaign->sent_count) }}</td>
                                    <td class="py-3 px-4 text-green-600">{{ number_format($campaign->delivered_count) }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $campaign->delivery_rate >= 90 ? 'bg-green-100 text-green-800' : ($campaign->delivery_rate >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ number_format($campaign->delivery_rate, 1) }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Usage Trends -->
                @if(isset($reports['usage_trends']))
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Trends</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($reports['usage_trends']['current_month_sms'] ?? 0) }}</div>
                            <div class="text-sm text-gray-600">This Month</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($reports['usage_trends']['previous_month_sms'] ?? 0) }}</div>
                            <div class="text-sm text-gray-600">Previous Month</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold {{ ($reports['usage_trends']['growth_rate'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ ($reports['usage_trends']['growth_rate'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($reports['usage_trends']['growth_rate'] ?? 0, 1) }}%
                            </div>
                            <div class="text-sm text-gray-600">Growth Rate</div>
                        </div>
                    </div>
                </div>
                @endif</div>
            </div>
        </div>
    </div>
</div>
@endsection
