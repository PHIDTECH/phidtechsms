@extends('layouts.modern-dashboard')

@section('page-title', 'Buy SMS')

@section('content')
<div class="max-w-7xl mx-auto px-4 pb-10 space-y-8 animate-fade-in-up">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-3xl bg-gradient-to-r from-indigo-500 via-purple-600 to-purple-700 text-indigo-50 shadow-xl p-6 sm:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="uppercase tracking-wide text-xs text-indigo-100 mb-2">SMS Credits Balance</p>
                    <p class="text-4xl sm:text-5xl font-bold mb-3 text-white">
                        {{ number_format($user->sms_credits) }}
                        <span class="ml-1 text-base font-medium text-white/80">SMS</span>
                    </p>
                    <p class="text-indigo-100">Ready to use for your next SMS campaign.</p>
                </div>
                <a href="{{ route('wallet.topup') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-white text-indigo-600 font-semibold px-6 py-3 shadow-xl shadow-indigo-900/10 transition-all hover:bg-indigo-50 hover:text-indigo-700 hover:-translate-y-0.5">
                    <i class="fas fa-plus-circle"></i>
                    Buy SMS Credits
                </a>
            </div>
            @php $rate = (int) config('services.sms.cost_per_part', 30); @endphp
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3">
                    <span class="h-10 w-10 flex items-center justify-center rounded-full bg-white/20">
                        <i class="fas fa-chart-line text-white"></i>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-indigo-100">Net Change This Month</p>
                        <p class="text-lg font-semibold">{{ number_format(floor(($monthlyStats['net_change'] ?? 0) / max($rate,1))) }} SMS</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3">
                    <span class="h-10 w-10 flex items-center justify-center rounded-full bg-white/20">
                        <i class="fas fa-receipt text-white"></i>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-indigo-100">SMS Top-ups This Month</p>
                        <p class="text-lg font-semibold">{{ number_format(floor(($monthlyStats['total_topups'] ?? 0) / max($rate,1))) }} SMS</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white shadow-xl border border-gray-100 p-6 space-y-5">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Overview</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1">This Month</h3>
            </div>
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="h-10 w-10 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <i class="fas fa-arrow-up"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800">SMS Top-ups</p>
                            <p class="text-xs text-gray-500">SMS credits purchased</p>
                        </div>
                    </div>
                    <p class="text-lg font-semibold text-emerald-600">{{ number_format(floor(($monthlyStats['total_topups'] ?? 0) / max($rate,1))) }} SMS</p>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="h-10 w-10 flex items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <i class="fas fa-arrow-down"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800">SMS Used</p>
                            <p class="text-xs text-gray-500">SMS sent this month</p>
                        </div>
                    </div>
                    <p class="text-lg font-semibold text-rose-600">{{ number_format(floor(($monthlyStats['total_spent'] ?? 0) / max($rate,1))) }} SMS</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-3xl bg-white shadow-lg border border-gray-100 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-5 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-history text-indigo-500"></i>
                        Recent Transactions
                    </h3>
                    <p class="text-sm text-gray-500">Latest activity from Buy SMS.</p>
                </div>
                <a href="{{ route('wallet.transactions') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                    View all
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-100">
                @php
                    $statusStyles = [
                        'completed' => 'bg-emerald-100 text-emerald-700',
                        'pending' => 'bg-amber-100 text-amber-700',
                        'failed' => 'bg-rose-100 text-rose-700',
                        'cancelled' => 'bg-gray-100 text-gray-700',
                    ];
                @endphp
                @forelse($recentTransactions as $transaction)
                    @php
                        $isCredit = $transaction->type === 'credit';
                        $chipClass = $isCredit ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600';
                        $icon = $isCredit ? 'fas fa-arrow-down' : 'fas fa-arrow-up';
                        $sign = $isCredit ? '+' : '-';
                        $statusClass = $statusStyles[$transaction->status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="h-12 w-12 flex items-center justify-center rounded-2xl {{ $chipClass }}">
                                <i class="{{ $icon }}"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $transaction->description }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $transaction->created_at->format('M d, Y H:i') }}
                                    @if($transaction->reference)
                                        • Ref: {{ $transaction->reference }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    <div class="text-right">
                            @php $smsAmount = floor(($transaction->amount ?? 0) / max($rate,1)); @endphp
                            <p class="text-sm font-semibold {{ $isCredit ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $sign }}{{ number_format($smsAmount) }} SMS
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusClass }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center space-y-3">
                        <div class="text-4xl text-gray-300">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <p class="text-gray-600 font-medium">No transactions yet</p>
                        <p class="text-sm text-gray-500">Start by topping up your SMS credits.</p>
                        <a href="{{ route('wallet.topup') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                            <i class="fas fa-plus-circle"></i>
                            Make your first top-up
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl bg-white shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-calculator text-indigo-500"></i>
                SMS Cost Calculator
            </h3>
            <p class="text-sm text-gray-500 mt-1">Estimate how much a campaign will cost.</p>

            <form id="costCalculator" class="mt-5 space-y-5">
                <div class="space-y-2">
                    <label for="message" class="text-sm font-medium text-gray-700">Message content</label>
                    <textarea id="message" rows="3" placeholder="Type your SMS message here..."
                        class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm text-gray-800"></textarea>
                    <p class="text-xs text-gray-500">Characters: <span id="charCount" class="font-semibold text-gray-700">0</span> / 160</p>
                </div>
                <div class="space-y-2">
                    <label for="recipients" class="text-sm font-medium text-gray-700">Number of recipients</label>
                    <input id="recipients" type="number" min="1" value="1"
                        class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm text-gray-800" />
                </div>
                <div id="costResult" class="hidden rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                    <p class="font-semibold">Estimated cost</p>
                    <div id="costBreakdown" class="mt-2 space-y-1"></div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('message');
    const recipientsInput = document.getElementById('recipients');
    const charCount = document.getElementById('charCount');
    const costResult = document.getElementById('costResult');
    const costBreakdown = document.getElementById('costBreakdown');

    const updateCharCount = () => {
        const length = messageInput.value.length;
        charCount.textContent = length;
        charCount.classList.toggle('text-rose-500', length > 160);
    };

    const calculateCost = () => {
        const message = messageInput.value;
        const recipients = parseInt(recipientsInput.value, 10) || 1;

        if (!message.length) {
            costResult.classList.add('hidden');
            return;
        }

        fetch('{{ route('wallet.calculate-cost') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                message: message,
                recipient_count: recipients
            })
        })
        .then(response => response.ok ? response.json() : Promise.reject())
        .then(data => {
            costBreakdown.innerHTML = `
                <div>Message length: <strong>${data.message_length}</strong> characters</div>
                <div>Parts per SMS: <strong>${data.parts_per_sms}</strong></div>
                <div>Recipients: <strong>${data.recipient_count}</strong></div>
                <div>Total parts: <strong>${data.total_parts}</strong></div>
                <div class="pt-2 text-base font-semibold">${data.formatted_cost}</div>
            `;
            costResult.classList.remove('hidden');
        })
        .catch(() => {
            costResult.classList.add('hidden');
        });
    };

    messageInput.addEventListener('input', () => {
        updateCharCount();
        calculateCost();
    });

    recipientsInput.addEventListener('input', calculateCost);

    updateCharCount();
});
</script>
@endsection
