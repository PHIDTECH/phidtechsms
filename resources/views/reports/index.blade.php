@extends('layouts.modern-dashboard')

@section('page-title', 'Reports')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Reports & Analytics</h1>
            <p class="text-gray-500">Comprehensive insights into your SMS campaigns and usage.</p>
        </div>
        <div class="relative">
            <button id="exportBtn" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                <i class="fas fa-download"></i>
                <span>Export</span>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                <a class="block px-4 py-2 hover:bg-gray-50" href="{{ route('reports.export', ['type' => 'campaigns', 'start_date' => $startDate, 'end_date' => $endDate]) }}">Campaigns CSV</a>
                <a class="block px-4 py-2 hover:bg-gray-50" href="{{ route('reports.export', ['type' => 'messages', 'start_date' => $startDate, 'end_date' => $endDate]) }}">Messages CSV</a>
                <a class="block px-4 py-2 hover:bg-gray-50" href="{{ route('reports.export', ['type' => 'transactions', 'start_date' => $startDate, 'end_date' => $endDate]) }}">Transactions CSV</a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="start_date" class="block text-sm text-gray-600">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm text-gray-600">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full md:w-auto px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow">
                    <i class="fas fa-filter mr-2"></i>Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Campaign Performance -->
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Campaign Performance</h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-semibold text-indigo-600">{{ number_format($reports['campaign_performance']['total_campaigns']) }}</div>
                    <div class="text-xs text-gray-500">Total Campaigns</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-green-600">{{ number_format($reports['campaign_performance']['completion_rate'], 1) }}%</div>
                    <div class="text-xs text-gray-500">Completion Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-blue-600">{{ number_format($reports['campaign_performance']['total_sent']) }}</div>
                    <div class="text-xs text-gray-500">Messages Sent</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-yellow-600">{{ number_format($reports['campaign_performance']['avg_delivery_rate'], 1) }}%</div>
                    <div class="text-xs text-gray-500">Avg Delivery Rate</div>
                </div>
            </div>

            @if($reports['campaign_performance']['top_campaigns']->count() > 0)
            <div class="mt-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Top Performing Campaigns</h4>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($reports['campaign_performance']['top_campaigns'] as $campaign)
                        <div class="border border-gray-100 rounded-lg p-4">
                            <div class="font-medium text-gray-800">{{ $campaign->name }}</div>
                            <div class="text-xs text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</div>
                            <div class="mt-2 flex gap-6 text-sm">
                                <div><span class="text-gray-500">Sent:</span> {{ number_format($campaign->sent_count) }}</div>
                                <div><span class="text-gray-500">Delivered:</span> {{ number_format($campaign->delivered_count) }}</div>
                                <div><span class="text-gray-500">Rate:</span> {{ number_format($campaign->delivery_rate, 1) }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Delivery Analytics -->
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Delivery Analytics</h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="text-center">
                    <div class="text-2xl font-semibold text-indigo-600">{{ number_format($reports['delivery_analytics']['delivery_rate'] ?? 0, 1) }}%</div>
                    <div class="text-xs text-gray-500">Overall Delivery Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-blue-600">{{ number_format($reports['delivery_analytics']['total_messages']) }}</div>
                    <div class="text-xs text-gray-500">Total Messages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-green-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['delivered'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Delivered</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-yellow-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['queued'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Queued</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-red-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['failed'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Failed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-gray-600">{{ number_format($reports['delivery_analytics']['status_breakdown']['unknown'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Unknown</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Trends -->
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Usage Trends</h3>
        </div>
        <div class="p-5">
            <div class="grid md:grid-cols-3 gap-4 mb-4">
                <div class="text-center">
                    <div class="text-xl font-semibold text-indigo-600">{{ number_format($reports['usage_trends']['current_month_sms']) }}</div>
                    <div class="text-xs text-gray-500">This Month</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-semibold text-blue-600">{{ number_format($reports['usage_trends']['previous_month_sms']) }}</div>
                    <div class="text-xs text-gray-500">Previous Month</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-semibold {{ $reports['usage_trends']['growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $reports['usage_trends']['growth_rate'] >= 0 ? '+' : '' }}{{ number_format($reports['usage_trends']['growth_rate'], 1) }}%</div>
                    <div class="text-xs text-gray-500">Growth Rate</div>
                </div>
            </div>

            @if($reports['usage_trends']['peak_hours']->count() > 0)
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Peak Usage Hours</h4>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($reports['usage_trends']['peak_hours'] as $index => $hour)
                <div class="text-center border border-gray-100 rounded-lg p-4">
                    <div class="text-lg font-medium text-yellow-600">{{ sprintf('%02d:00', $hour->hour) }}</div>
                    <div class="text-xs text-gray-500">{{ number_format($hour->count) }} messages</div>
                    <div class="inline-block mt-1 px-2 py-0.5 text-[10px] rounded bg-yellow-100 text-yellow-700">#{{ $index + 1 }} Peak</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Daily Trends Table -->
    @if($reports['delivery_analytics']['daily_trends']->count() > 0)
    <div class="bg-white rounded-xl shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Daily Message Trends</h3>
        </div>
        <div class="p-5 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600">
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Total Messages</th>
                        <th class="px-3 py-2">Delivered</th>
                        <th class="px-3 py-2">Failed</th>
                        <th class="px-3 py-2">Delivery Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($reports['delivery_analytics']['daily_trends'] as $trend)
                        @php $rate = $trend->total > 0 ? ($trend->delivered / $trend->total) * 100 : 0; @endphp
                        <tr>
                            <td class="px-3 py-2">{{ Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</td>
                            <td class="px-3 py-2">{{ number_format($trend->total) }}</td>
                            <td class="px-3 py-2 text-green-600">{{ number_format($trend->delivered) }}</td>
                            <td class="px-3 py-2 text-red-600">{{ number_format($trend->failed) }}</td>
                            <td class="px-3 py-2">
                                <div class="w-full bg-gray-100 rounded-full h-3">
                                    <div class="bg-green-500 h-3 rounded-full" style="width: {{ $rate }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ number_format($rate, 1) }}%</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Stuck Messages Section -->
    @if(isset($reports['stuck_messages']) && $reports['stuck_messages']['count'] > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Messages Stuck in 'Sent' Status
            </h3>
            <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-2.5 py-0.5 rounded">
                {{ $reports['stuck_messages']['count'] }} messages
            </span>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-1"></i>
                These messages have been in 'sent' status for more than 30 minutes and may need attention.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beem ID</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($reports['stuck_messages']['messages'] as $message)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $message->phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $message->campaign->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $message->sent_at ? $message->sent_at->format('M d, Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($message->sent_at)
                                {{ $message->sent_at->diffForHumans() }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $message->beem_message_id ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
  const btn = document.getElementById('exportBtn');
  const menu = document.getElementById('exportMenu');
  btn?.addEventListener('click', () => { menu.classList.toggle('hidden'); });
  document.addEventListener('click', (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add('hidden');
  });
</script>
@endsection
