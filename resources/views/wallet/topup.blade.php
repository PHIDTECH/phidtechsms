@extends('layouts.modern-dashboard')

@section('page-title', 'Buy SMS Credits')

@section('content')
<div class="max-w-5xl mx-auto px-4 pb-8 animate-fade-in-up">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-5">
            <h2 class="text-xl sm:text-2xl font-bold text-white">Buy SMS Credits</h2>
            <p class="text-indigo-100">Pay securely with Selcom</p>
        </div>

        @if ($errors->any())
            <div class="px-6 pt-6">
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('payments.create') }}" id="topupForm" class="p-6">
            @csrf
            <input type="hidden" name="amount" id="amountHidden" value="">
            <input type="hidden" name="payment_method" value="selcom">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Presets</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @php $presets = [500,1000,2000,5000,10000,20000]; @endphp
                            @foreach ($presets as $p)
                            <button type="button" data-preset="{{ $p }}" class="preset-card rounded-xl border border-gray-200 bg-white p-4 text-center hover:border-indigo-500 hover:bg-indigo-50 transition">
                                <div class="text-lg font-bold text-gray-800">{{ number_format($p) }}</div>
                                <div class="text-xs text-gray-500">SMS</div>
                            </button>
                            @endforeach
                            <button type="button" data-preset="custom" class="preset-card rounded-xl border border-gray-200 bg-white p-4 text-center hover:border-indigo-500 hover:bg-indigo-50 transition">
                                <div class="text-lg font-bold text-gray-800">Custom</div>
                                <div class="text-xs text-gray-500">Enter amount</div>
                            </button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="sms_credits" class="block text-sm font-semibold text-gray-700 mb-2">SMS Credits</label>
                        <input id="sms_credits" name="sms_credits" type="number" min="100" max="50000" step="10" placeholder="e.g. 1,000" required
                               class="w-full rounded-xl border border-[#6144f2] focus:border-[#6144f2] focus:ring-[#6144f2] text-lg p-3" />
                        <p class="text-xs text-gray-500 mt-1">Minimum 100, maximum 50,000. Step 10.</p>
                    </div>

                    
                </div>

                <!-- Step 2 & 3: Payment + Billing -->
                <div class="md:sticky md:top-4">
                    <div id="summaryCard" class="rounded-xl border border-gray-100 bg-gray-50 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total Cost</span>
                            <span id="summaryTotal" class="text-lg font-bold text-indigo-600">TZS 0</span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-gray-600">You will receive</span>
                            <span id="summaryCredits" class="text-lg font-bold text-emerald-600">0 SMS</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Payment Method</label>
                        <div class="space-y-3" id="pmList">
                            <label class="pm-item rounded-xl border border-gray-200 p-3 cursor-pointer hover:border-indigo-500 block">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="payment_method" value="selcom" class="text-indigo-600 focus:ring-indigo-500" checked required>
                                    <div>
                                        <div class="font-semibold text-gray-800">Selcom</div>
                                        <div class="text-xs text-gray-500">Cards & Mobile Money</div>
                                    </div>
                                </div>
                                <img src="{{ asset('selcom (4).png') }}" alt="Selcom" class="mt-3 w-full h-auto max-h-24 object-contain bg-white rounded-md">
                            </label>
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3 text-sm text-gray-600">
                            <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                            Secure payment. You’ll be redirected to complete the transaction.
                        </div>

                        <div class="mt-5">
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Billing Phone (255XXXXXXXXX)</label>
                            <input id="phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]+" placeholder="255XXXXXXXXX" required
                                   value="{{ old('phone', auth()->user()->phone_number ?? '') }}"
                                   class="w-full rounded-xl border border-[#6144f2] focus:border-[#6144f2] focus:ring-[#6144f2] text-lg p-3" />
                            <p class="text-xs text-gray-500 mt-1">Use your mobile wallet number with country code.</p>
                        </div>

                        <div class="mt-5 flex items-center justify-end">
                            <button id="submitBtn" type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-3 disabled:opacity-60 disabled:cursor-not-allowed">
                                <i class="fas fa-lock"></i>
                                Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const costPerSms = {{ (int) config('services.sms.cost_per_part', 30) }};
  const presets = Array.from(document.querySelectorAll('.preset-card'));
  const input = document.getElementById('sms_credits');
  const submitBtn = document.getElementById('submitBtn');
  const summaryTotal = document.getElementById('summaryTotal');
  const summaryCredits = document.getElementById('summaryCredits');
  const phoneInput = document.getElementById('phone');

  const setSelected = (btn) => {
    presets.forEach(b => b.classList.remove('ring-2','ring-indigo-500','bg-indigo-50'));
    if (btn) btn.classList.add('ring-2','ring-indigo-500','bg-indigo-50');
  };

  presets.forEach(btn => {
    btn.addEventListener('click', () => {
      const v = btn.dataset.preset;
      setSelected(btn);
      if (v === 'custom') {
        input.value = '';
        input.focus();
      } else {
        input.value = v;
        updateCost();
      }
    });
  });

  function updateCost() {
    const count = parseInt(input.value || '0', 10);
    const phoneValid = phoneInput && /^255\d{9}$/.test(phoneInput.value || '');
    if (count >= 100 && count <= 50000) {
      const cost = count * costPerSms;
      submitBtn.disabled = !phoneValid;
      // Update hidden amount for payments.create
      const amountHidden = document.getElementById('amountHidden');
      if (amountHidden) amountHidden.value = cost;
      if (summaryTotal) summaryTotal.textContent = 'TZS ' + cost.toLocaleString();
      if (summaryCredits) summaryCredits.textContent = `${count.toLocaleString()} SMS`;
    } else {
      submitBtn.disabled = true;
      const amountHidden = document.getElementById('amountHidden');
      if (amountHidden) amountHidden.value = '';
      if (summaryTotal) summaryTotal.textContent = 'TZS 0';
      if (summaryCredits) summaryCredits.textContent = '0 SMS';
    }
  }
  if (phoneInput) {
    phoneInput.addEventListener('input', updateCost);
  }

  input.addEventListener('input', () => {
    setSelected(null);
    updateCost();
  });

  document.getElementById('topupForm').addEventListener('submit', (e) => {
    updateCost();
    if (submitBtn.disabled) {
      e.preventDefault();
      alert('Please enter between 100 and 50,000 SMS credits.');
      return;
    }
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
  });

  // initial
  updateCost();
});
</script>
@endsection
