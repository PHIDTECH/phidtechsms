@extends('layouts.admin-modern-dashboard')

@section('title', 'API Settings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">API Settings</h1>
        <p class="text-gray-600">Configure Beem SMS and Selcom payment API credentials</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('warning') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Beem SMS Settings -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Beem SMS Configuration</h2>
                        <p class="text-gray-600">Configure your Beem SMS API credentials for sending messages</p>
                    </div>
                    @if($beemStatus)
                        <div class="flex items-center">
                            @if($beemStatus['success'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Connected
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    Error
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            
            <form action="{{ route('admin.settings.beem.update') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="beem_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="beem_api_key" name="api_key" 
                               value="{{ old('api_key', $beemSettings['api_key'] ? str_repeat('*', 20) : '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your Beem SMS API Key"
                               {{ $beemSettings['api_key'] ? 'placeholder=API Key is configured (hidden for security)' : 'required' }}>
                        @error('api_key')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="beem_secret_key" class="block text-sm font-medium text-gray-700 mb-2">
                            Secret Key <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="beem_secret_key" name="secret_key" 
                               value="{{ old('secret_key', $beemSettings['secret_key'] ? str_repeat('*', 20) : '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your Beem SMS Secret Key"
                               {{ $beemSettings['secret_key'] ? 'placeholder=Secret Key is configured (hidden for security)' : 'required' }}>
                        @error('secret_key')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="beem_base_url" class="block text-sm font-medium text-gray-700 mb-2">
                            Base URL
                        </label>
                        <input type="url" id="beem_base_url" name="base_url" 
                               value="{{ old('base_url', $beemSettings['base_url'] ?: 'https://apisms.beem.africa/v1') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://apisms.beem.africa/v1">
                        @error('base_url')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="beem_default_sender_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Default Sender ID
                        </label>
                        <input type="text" id="beem_default_sender_id" name="default_sender_id" 
                               value="{{ old('default_sender_id', $beemSettings['default_sender_id'] ?: 'RodLine') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="RodLine" maxlength="11">
                        @error('default_sender_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Maximum 11 characters</p>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Changes will be tested automatically upon saving
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Save Beem Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Selcom Payment Settings -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Selcom Payment Configuration</h2>
                <p class="text-gray-600">Configure your Selcom payment gateway credentials</p>
            </div>
            
            <form action="{{ route('admin.settings.selcom.update') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="selcom_vendor_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Vendor ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="selcom_vendor_id" name="vendor_id" 
                               value="{{ old('vendor_id', $selcomSettings['vendor_id']) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter your Selcom Vendor ID" required>
                        @error('vendor_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="selcom_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="selcom_api_key" name="api_key" 
                               value="{{ old('api_key', $selcomSettings['api_key'] ? str_repeat('*', 20) : '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter your Selcom API Key"
                               {{ $selcomSettings['api_key'] ? 'placeholder=API Key is configured (hidden for security)' : 'required' }}>
                        @error('api_key')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="selcom_secret_key" class="block text-sm font-medium text-gray-700 mb-2">
                            Secret Key <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="selcom_secret_key" name="secret_key" 
                               value="{{ old('secret_key', $selcomSettings['secret_key'] ? str_repeat('*', 20) : '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter your Selcom Secret Key"
                               {{ $selcomSettings['secret_key'] ? 'placeholder=Secret Key is configured (hidden for security)' : 'required' }}>
                        @error('secret_key')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="selcom_base_url" class="block text-sm font-medium text-gray-700 mb-2">
                            Base URL
                        </label>
                        <input type="url" id="selcom_base_url" name="base_url" 
                               value="{{ old('base_url', $selcomSettings['base_url'] ?: 'https://apigw.selcommobile.com') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="https://apigw.selcommobile.com">
                        @error('base_url')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-shield-alt mr-1"></i>
                        All credentials are encrypted in database
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Save Selcom Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Sync Section -->
    @if($beemSettings['api_key'] && $beemSettings['secret_key'])
    <div class="mt-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Balance Synchronization</h3>
                    <p class="text-gray-600">Sync your Beem SMS account balance to keep track of available credits</p>
                </div>
                <button onclick="syncBalance()" id="sync-balance-btn" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Sync Balance Now
                </button>
            </div>
            
            <div id="balance-info" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Current Balance</p>
                    <p class="text-2xl font-bold text-gray-900" id="current-balance">Loading...</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">SMS Credits</p>
                    <p class="text-2xl font-bold text-gray-900" id="sms-credits">Loading...</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Last Sync</p>
                    <p class="text-sm text-gray-900" id="last-sync">Loading...</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
// Load balance on page load
document.addEventListener('DOMContentLoaded', function() {
    @if($beemSettings['api_key'] && $beemSettings['secret_key'])
        loadBalance();
    @endif
});

function loadBalance() {
    fetch('{{ route("admin.sms.beem-live-balance") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBalanceDisplay(data);
            } else {
                document.getElementById('current-balance').textContent = 'Error';
                document.getElementById('sms-credits').textContent = 'Error';
                document.getElementById('last-sync').textContent = 'Failed to load';
            }
        })
        .catch(error => {
            console.error('Error loading balance:', error);
        });
}

function syncBalance() {
    const syncBtn = document.getElementById('sync-balance-btn');
    
    // Show loading state
    syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Syncing...';
    syncBtn.disabled = true;
    
    fetch('{{ route("admin.sms.sync-balance") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateBalanceDisplay(data);
            showNotification('Balance synchronized successfully!', 'success');
        } else {
            showNotification('Failed to sync balance: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred while syncing balance', 'error');
    })
    .finally(() => {
        // Reset button state
        syncBtn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Sync Balance Now';
        syncBtn.disabled = false;
    });
}

function updateBalanceDisplay(data) {
    document.getElementById('current-balance').textContent = `TZS ${new Intl.NumberFormat().format(data.balance || 0)}`;
    document.getElementById('sms-credits').textContent = new Intl.NumberFormat().format(data.sms_credits || 0);
    
    if (data.last_sync) {
        const lastSync = new Date(data.last_sync);
        document.getElementById('last-sync').textContent = lastSync.toLocaleString();
    } else {
        document.getElementById('last-sync').textContent = 'Never';
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'
            } mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endsection
