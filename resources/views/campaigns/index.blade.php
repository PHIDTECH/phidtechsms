@extends('layouts.modern-dashboard')

@section('page-title', 'SMS Campaigns')

@section('content')
<div class="max-w-7xl mx-auto px-4 pb-10 animate-fade-in-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white grid place-items-center"><i class="fas fa-bullhorn"></i></div>
            <h1 class="text-2xl font-bold text-gray-800">SMS Campaigns</h1>
        </div>
        <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 font-semibold">
            <i class="fas fa-plus"></i> Create New SMS
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-4 shadow">
            <div class="text-sm opacity-90">Total</div>
            <div class="text-2xl font-bold">{{ $stats['total_campaigns'] }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-sm text-gray-500">Active</div>
            <div class="text-2xl font-bold text-gray-800">{{ $stats['active_campaigns'] }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-sm text-gray-500">Completed</div>
            <div class="text-2xl font-bold text-gray-800">{{ $stats['completed_campaigns'] }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-sm text-gray-500">Sent</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_messages_sent']) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-sm text-gray-500">Delivery</div>
            <div class="text-2xl font-bold text-gray-800">{{ $stats['delivery_rate'] }}%</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-sm text-gray-500">Credits</div>
            <div class="text-2xl font-bold text-indigo-600">{{ number_format(auth()->user()->sms_credits, 0) }} SMS</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6">
        @php $statuses = ['' => 'All','draft' => 'Drafts','scheduled' => 'Scheduled','sending' => 'Sending','completed' => 'Completed','failed' => 'Failed']; @endphp
        @foreach($statuses as $key => $label)
            @php $active = request('status') === ($key === '' ? null : $key); @endphp
            <a href="{{ $key === '' ? route('campaigns.index') : route('campaigns.index', ['status' => $key]) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full text-sm {{ $active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Campaigns list -->
    @forelse($campaigns as $campaign)
        <div class="rounded-2xl bg-white border border-gray-100 shadow p-5 mb-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $campaign->name }}</h3>
                    <div class="text-sm text-gray-500 mt-1">
                        <i class="far fa-calendar-alt mr-1"></i> {{ $campaign->created_at->format('M d, Y H:i') }}
            @if($campaign->schedule_at)
            <span class="mx-2">•</span> <i class="far fa-clock mr-1"></i> {{ $campaign->schedule_at->format('M d, Y H:i') }}
            @endif
                        <span class="mx-2">•</span> <i class="far fa-id-card mr-1"></i> Sender: {{ $campaign->sender_id }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @class([
                            'bg-gray-100 text-gray-700' => $campaign->status === 'draft',
                            'bg-yellow-100 text-yellow-800' => $campaign->status === 'scheduled',
                            'bg-blue-100 text-blue-800' => $campaign->status === 'sending',
                            'bg-green-100 text-green-800' => $campaign->status === 'completed',
                            'bg-red-100 text-red-800' => $campaign->status === 'failed',
                        ])
                    ">{{ ucfirst($campaign->status) }}</span>
                    <a href="{{ route('campaigns.show', $campaign->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-eye"></i> View
                    </a>
                    @if(in_array($campaign->status, ['draft','failed']))
                        <form method="POST" action="{{ route('campaigns.destroy', $campaign->id) }}" onsubmit="return confirm('Delete this campaign?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 text-red-600 px-3 py-1.5 text-sm hover:bg-red-50">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="mt-3 text-sm text-gray-600">
                <span class="font-semibold">Message:</span>
            {{ Str::limit($campaign->message, 140) }}
            </div>

            @if(in_array($campaign->status, ['sending','completed']))
                <div class="mt-4">
                    <div class="h-2 rounded bg-gray-200 overflow-hidden">
                        <div class="h-2 bg-green-500" style="width: {{ $campaign->progress }}%"></div>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">Progress: {{ $campaign->progress }}%</div>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                <div>
                    <div class="text-xs text-gray-500">Recipients</div>
            <div class="text-base font-semibold text-gray-900">{{ number_format($campaign->estimated_recipients) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">SMS Parts</div>
            <div class="text-base font-semibold text-gray-900">{{ max(1, ceil(($campaign->estimated_parts ?? 0) / max(1, ($campaign->estimated_recipients ?? 1)))) }}</div>
                </div>
                @php $rate = (int) config('services.sms.cost_per_part', 30); @endphp
                <div>
                    <div class="text-xs text-gray-500">Estimated SMS</div>
                    <div class="text-base font-semibold text-gray-900">{{ number_format(floor(($campaign->estimated_cost ?? 0) / max($rate,1))) }} SMS</div>
                </div>
                @if($campaign->status === 'completed')
                    <div>
                        <div class="text-xs text-gray-500">Delivered</div>
                        <div class="text-base font-semibold text-gray-900">{{ $campaign->delivery_rate }}%</div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center rounded-2xl border border-gray-100 bg-white p-12 shadow">
            <div class="text-4xl text-indigo-600 mb-2"><i class="fas fa-bullhorn"></i></div>
            <h3 class="text-xl font-bold text-gray-800 mb-1">No Campaigns Yet</h3>
            <p class="text-gray-600 mb-4">Create your first SMS campaign to start reaching your audience.</p>
            <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 font-semibold">
                <i class="fas fa-plus"></i> Create Your First Campaign
            </a>
        </div>
    @endforelse

    @if($campaigns->hasPages())
        <div class="mt-6">{{ $campaigns->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Auto-refresh when a campaign is in sending state
  const hasSending = Array.from(document.querySelectorAll('span')).some(s => s.textContent.trim() === 'Sending');
  if (hasSending) setTimeout(() => location.reload(), 30000);
});
</script>
@endsection

