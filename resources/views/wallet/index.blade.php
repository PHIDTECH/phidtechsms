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
                   class="inline-flex items-center gap-2 rounded-full bg-green-500 text-white font-semibold px-6 py-3 shadow-xl shadow-green-900/20 transition-all hover:bg-green-600 hover:-translate-y-0.5">
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
    </div>

    <!-- Payment History Section -->
    <div class="grid gap-6">
        <div class="rounded-3xl bg-white shadow-lg border border-gray-100 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-5 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-credit-card text-green-500"></i>
                        Payment History
                    </h3>
                    <p class="text-sm text-gray-500">Track your SMS purchases and payment status.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                @if(isset($payments) && $payments->count())
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">SMS Credits</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($payments as $payment)
                                @php
                                    $statusClasses = [
                                        'completed' => 'bg-emerald-100 text-emerald-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'failed' => 'bg-rose-100 text-rose-700',
                                        'cancelled' => 'bg-gray-100 text-gray-700',
                                    ];
                                    $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                        {{ $payment->reference }}
                                        <div class="text-xs text-gray-500">{{ ucfirst($payment->payment_method ?? 'N/A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-800">
                                        {{ number_format($payment->amount, 0) }} {{ $payment->currency ?? 'TZS' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-800 font-semibold">
                                        {{ number_format($payment->credits ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $payment->created_at?->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="px-6 py-12 text-center space-y-3">
                        <div class="text-4xl text-gray-300">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <p class="text-gray-600 font-medium">No payments yet</p>
                        <p class="text-sm text-gray-500">Your payment history will appear here after your first purchase.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

