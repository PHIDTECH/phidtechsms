@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin Settings')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-cog text-purple-600 mr-3"></i>
                Settings
            </h1>
            <p class="text-gray-600">Manage system configuration and API settings</p>
        </div>

        <!-- Settings Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- API Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-plug text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">API Configuration</h3>
                            <p class="text-sm text-gray-500">SMS & Payment APIs</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Configure Beem SMS and Selcom payment API credentials for system integration.</p>
                    <a href="{{ route('admin.settings.api') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-arrow-right mr-2"></i>
                        Configure APIs
                    </a>
                </div>
            </div>

            <!-- System Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-server text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">System Settings</h3>
                            <p class="text-sm text-gray-500">General Configuration</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Manage general system settings, application preferences, and default configurations.</p>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>
                        Coming Soon
                    </button>
                </div>
            </div>

            <!-- Email Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-envelope text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Email Settings</h3>
                            <p class="text-sm text-gray-500">SMTP Configuration</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Configure email server settings for notifications and system communications.</p>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>
                        Coming Soon
                    </button>
                </div>
            </div>

            <!-- Security Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Security Settings</h3>
                            <p class="text-sm text-gray-500">Access & Authentication</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Manage security policies, authentication settings, and access controls.</p>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>
                        Coming Soon
                    </button>
                </div>
            </div>

            <!-- Backup Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-database text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Backup & Restore</h3>
                            <p class="text-sm text-gray-500">Data Management</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Configure automated backups and manage data restoration options.</p>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>
                        Coming Soon
                    </button>
                </div>
            </div>

            <!-- Notification Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-bell text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                            <p class="text-sm text-gray-500">Alert Configuration</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-4">Manage system notifications, alerts, and communication preferences.</p>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>
                        Coming Soon
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="mt-12">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Quick Actions</h2>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button id="quick-sync-balance-btn" class="flex items-center justify-center px-4 py-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Sync Beem Balance
                    </button>
                    <button class="flex items-center justify-center px-4 py-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors duration-200">
                        <i class="fas fa-check-circle mr-2"></i>
                        Test API Connections
                    </button>
                    <button class="flex items-center justify-center px-4 py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i>
                        Export Settings
                    </button>
                </div>
            </div>
        </div>

        <!-- System Status Section -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">System Status</h2>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900">Database</h3>
                        <p class="text-sm text-green-600">Connected</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900">SMS API</h3>
                        <p class="text-sm text-yellow-600">Check Required</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-credit-card text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900">Payment API</h3>
                        <p class="text-sm text-blue-600">Configured</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-server text-gray-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900">Server</h3>
                        <p class="text-sm text-gray-600">Online</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('quick-sync-balance-btn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...';
        fetch('{{ route("admin.sms.sync-balance") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                alert('Balance synchronized successfully.');
            } else {
                alert('Failed to sync: ' + (data && data.error ? data.error : 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Sync error', err);
            alert('Network error during sync');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Sync Beem Balance';
        });
    });
});
</script>
@endpush
