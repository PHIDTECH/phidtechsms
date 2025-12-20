@extends('layouts.modern-dashboard')

@section('page-title', 'Payment History')

@section('content')
<div class="max-w-6xl mx-auto px-4 pb-10 animate-fade-in-up">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Payment History</h2>
            <p class="text-gray-600">Track every SMS top-up - pending, paid, or cancelled.</p>
        </div>
        <a href="{{ route('wallet.topup') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-3">
            <i class="fas fa-plus-circle"></i>
            Buy SMS Credits
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="border-b border-gray-100 px-4 sm:px-6 py-4">
            @php
                $filters = [
                    'all' => 'All orders',
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'cancelled' => 'Cancelled',
                    'failed' => 'Failed',
                ];
            @endphp
            <div class="flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    @php
                        $active = $statusFilter === $key;
                        $count = $statusCounts[$key] ?? 0;
                        $url = $key === 'all'
                            ? route('payments.index')
                            : route('payments.index', ['status' => $key]);
                    @endphp
                    <a href="{{ $url }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition
                              {{ $active ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        <span>{{ $label }}</span>
                        <span class="text-xs font-semibold
                                     {{ $active ? 'bg-indigo-500 text-white' : 'bg-white text-gray-700' }}
                                     rounded-full px-2 py-0.5">
                            {{ $count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            @php $showUserColumn = $isAdmin ?? false; @endphp
            @if($payments->count())
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                            @if($showUserColumn)
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            @endif
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">SMS Credits</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                    {{ $payment->reference }}
                                    <div class="text-xs text-gray-500">{{ ucfirst($payment->payment_method) }}</div>
                                </td>
                                @if($showUserColumn)
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <div class="font-semibold">{{ $payment->user->name ?? 'Unknown user' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $payment->user->email ?? $payment->user->phone ?? 'No info' }}
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-sm text-right text-gray-800">
                                    {{ number_format($payment->amount, 0) }} {{ $payment->currency }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-800">
                                    {{ number_format($payment->credits) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $payment->status_badge_class }}">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $payment->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $payment->updated_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-12 text-center">
                    <div class="text-4xl mb-3">📭</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-1">No payments yet</h3>
                    <p class="text-gray-600 mb-4">Your top-up orders will appear here once you initiate a payment.</p>
                    <a href="{{ route('wallet.topup') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                        <i class="fas fa-shopping-cart"></i>
                        Buy SMS Credits
                    </a>
                </div>
            @endif
        </div>

        @if($payments->hasPages())
            <div class="px-4 sm:px-6 py-4 border-t border-gray-100">
                {{ $payments->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
