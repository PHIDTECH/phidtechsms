@extends('layouts.modern-dashboard')

@section('page-title', 'Payment Cancelled')

@section('content')
<div class="max-w-3xl mx-auto px-4 pb-10 animate-fade-in-up">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-pink-600 px-6 py-5">
            <h2 class="text-xl sm:text-2xl font-bold text-white">Payment Cancelled</h2>
            <p class="text-pink-100">Your payment was not completed.</p>
        </div>

        <div class="p-6 space-y-4">
            @if(isset($payment) && $payment)
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Reference</dt>
                            <dd class="font-semibold text-gray-800">{{ $payment->reference }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-semibold text-red-700">{{ ucfirst($payment->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-gray-800">TZS {{ number_format($payment->amount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">SMS Credits</dt>
                            <dd class="font-semibold text-gray-800">{{ number_format($payment->credits) }}</dd>
                        </div>
                    </dl>
                </div>
                <p class="text-sm text-gray-600">You can retry the payment anytime.</p>
            @else
                <p class="text-gray-700">Your payment was cancelled or failed.</p>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('wallet.topup') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-3">
                    <i class="fas fa-redo"></i>
                    Try Again
                </a>
                <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-gray-800 hover:bg-gray-900 text-white font-semibold px-5 py-3">
                    <i class="fas fa-receipt"></i>
                    View Payment History
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

