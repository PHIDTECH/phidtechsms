@extends('layouts.modern-dashboard')

@section('title', 'SMS Credits History')

@section('styles')
<style>
/* Tailwind-first tweaks: keep minimal custom tokens for statuses */
.status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.status-completed { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-failed { background: #f8d7da; color: #721c24; }
</style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            <i class="fas fa-history mr-2 text-[#6144f2]"></i>
            SMS Credits History
        </h1>
        <a href="{{ route('wallet.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-[#6144f2] text-[#6144f2] hover:bg-[#6144f2] hover:text-white transition">
            <i class="fas fa-arrow-left mr-2"></i> Back to SMS Credits
        </a>
    </div>

    <!-- Summary -->
    <div class="rounded-xl bg-white border border-gray-200 p-4 sm:p-6 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-xl font-bold text-gray-900">{{ $transactions->total() }}</p>
                <p class="text-sm text-gray-500">Total Transactions</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold text-gray-900">{{ number_format($transactions->where('type', 'credit')->where('status', 'completed')->sum('sms_credits')) }} SMS</p>
                <p class="text-sm text-gray-500">Total SMS Purchased</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold text-gray-900">{{ number_format($transactions->where('type', 'debit')->where('status', 'completed')->sum('sms_credits')) }} SMS</p>
                <p class="text-sm text-gray-500">Total SMS Used</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold text-gray-900">{{ number_format(auth()->user()->sms_credits) }} SMS</p>
                <p class="text-sm text-gray-500">Current Credits</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl bg-white border border-gray-200 p-4 sm:p-6 mb-6">
        <form method="GET" action="{{ route('wallet.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                <select id="type" name="type" class="w-full rounded-lg border-gray-300 focus:border-[#6144f2] focus:ring-[#6144f2]">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>Credits (SMS Purchases)</option>
                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>Debits (SMS Usage)</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border-gray-300 focus:border-[#6144f2] focus:ring-[#6144f2]">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 focus:border-[#6144f2] focus:ring-[#6144f2]">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 focus:border-[#6144f2] focus:ring-[#6144f2]">
            </div>
            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#6144f2] text-white shadow hover:bg-[#5336f0] transition">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="{{ route('wallet.transactions') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions -->
    <div>
        @forelse($transactions as $transaction)
            <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-6 mb-4">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-2 sm:col-span-1">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-base mr-2 {{ $transaction->type === 'credit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            @if($transaction->type === 'credit')
                                <i class="fas fa-plus"></i>
                            @else
                                <i class="fas fa-minus"></i>
                            @endif
                        </div>
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <div class="font-semibold text-gray-900">{{ $transaction->description }}</div>
                        <div class="text-xs text-gray-500">{{ $transaction->created_at->format('M d, Y H:i:s') }}</div>
                        @if($transaction->reference)
                            <div class="text-xs text-gray-500">Ref: {{ $transaction->reference }}</div>
                        @endif
                    </div>
                    <div class="col-span-6 sm:col-span-2 text-gray-700 text-sm">
                        @if($transaction->payment_method)
                            <div class=""><i class="fas fa-credit-card mr-1"></i> {{ strtoupper($transaction->payment_method) }}</div>
                        @endif
                        @if($transaction->campaign_id)
                            <div class="text-gray-500"><i class="fas fa-bullhorn mr-1"></i> Campaign #{{ $transaction->campaign_id }}</div>
                        @endif
                    </div>
                    <div class="col-span-6 sm:col-span-2 text-center">
                        <span class="status-badge status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                    </div>
                    <div class="col-span-10 sm:col-span-2 text-right">
                        <div class="text-lg font-bold {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->sms_credits ?? 0) }} SMS
                        </div>
                        <div class="text-xs text-gray-500">Cost: TZS {{ number_format($transaction->amount, 2) }}</div>
                        <div class="text-xs text-gray-500">Balance: {{ number_format($transaction->balance_after) }} SMS</div>
                    </div>
                    <div class="col-span-2 sm:col-span-1 text-right">
                        @if($transaction->metadata)
                            <button class="inline-flex items-center px-3 py-2 rounded-lg border border-[#6144f2] text-[#6144f2] hover:bg-[#6144f2] hover:text-white transition"
                                    data-modal-target="detailsModal{{ $transaction->id }}">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        @endif
                    </div>
                </div>

                @if($transaction->metadata)
                <!-- Tailwind Modal -->
                <div id="detailsModal{{ $transaction->id }}" class="fixed inset-0 z-50 hidden" aria-hidden="true">
                    <div class="absolute inset-0 bg-black/40"></div>
                    <div class="relative max-w-lg mx-auto mt-24 bg-white rounded-xl shadow-xl">
                        <div class="flex items-center justify-between px-4 py-3 border-b">
                            <h5 class="text-lg font-semibold text-gray-900">Transaction Details</h5>
                            <button type="button" class="p-2 rounded hover:bg-gray-100" data-close-modal>
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="p-4">
                            <dl class="grid grid-cols-3 gap-2 text-sm">
                                <dt class="text-gray-600">Reference:</dt>
                                <dd class="col-span-2 text-gray-900">{{ $transaction->reference }}</dd>

                                <dt class="text-gray-600">Amount:</dt>
                                <dd class="col-span-2 text-gray-900">TZS {{ number_format($transaction->amount, 2) }}</dd>

                                <dt class="text-gray-600">Balance Before:</dt>
                                <dd class="col-span-2 text-gray-900">TZS {{ number_format($transaction->balance_before, 2) }}</dd>

                                <dt class="text-gray-600">Balance After:</dt>
                                <dd class="col-span-2 text-gray-900">TZS {{ number_format($transaction->balance_after, 2) }}</dd>

                                @if($transaction->payment_reference)
                                    <dt class="text-gray-600">Payment Ref:</dt>
                                    <dd class="col-span-2 text-gray-900">{{ $transaction->payment_reference }}</dd>
                                @endif

                                @if($transaction->processed_at)
                                    <dt class="text-gray-600">Processed At:</dt>
                                    <dd class="col-span-2 text-gray-900">{{ $transaction->processed_at->format('M d, Y H:i:s') }}</dd>
                                @endif
                            </dl>

                            @if($transaction->metadata)
                                <h6 class="mt-4 font-semibold text-gray-900">Additional Details:</h6>
                                <pre class="bg-gray-50 p-3 rounded border text-xs text-gray-800">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                        <div class="px-4 py-3 border-t flex justify-end">
                            <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100" data-close-modal>Close</button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12">
                <i class="fas fa-receipt text-gray-400 text-5xl"></i>
                <h4 class="text-gray-500 mt-3 text-lg">No Transactions Found</h4>
                <p class="text-gray-500">No transactions match your current filters.</p>
                <a href="{{ route('wallet.topup') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#6144f2] text-white hover:bg-[#5336f0] transition">
                    <i class="fas fa-plus mr-2"></i> Make Your First Top-up
                </a>
            </div>
        @endforelse

        @if($transactions->hasPages())
            <div class="mt-4">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
// Vanilla JS for inputs and modal toggling
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  const from = document.getElementById('date_from');
  const to = document.getElementById('date_to');
  if (from) from.max = today;
  if (to) to.max = today;

  const validate = () => {
    if (from && to && from.value && to.value && from.value > to.value) {
      alert('From date cannot be later than To date');
      to.value = '';
    }
  };
  if (from) from.addEventListener('change', validate);
  if (to) to.addEventListener('change', validate);

  // Modal open buttons
  document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-modal-target');
      const m = document.getElementById(id);
      if (m) m.classList.remove('hidden');
    });
  });
  // Modal close buttons
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('[id^="detailsModal"]');
      if (modal) modal.classList.add('hidden');
    });
  });
  // Close on overlay click
  document.querySelectorAll('[id^="detailsModal"]').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.add('hidden');
    });
  });
});
</script>
@endsection
