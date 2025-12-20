@extends('layouts.modern-dashboard')

@section('title', 'Security Settings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Security Settings</h1>
                <p class="text-gray-600">Manage your password and account security</p>
            </div>
            <a href="{{ route('profile.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Profile
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Change Password -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-key text-red-600 mr-2"></i>
                    Change Password
                </h3>

                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password" id="current_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('current_password') border-red-500 @enderror">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="password" id="password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-600">Password must be at least 8 characters long and contain a mix of letters, numbers, and symbols.</p>
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors">
                            <i class="fas fa-shield-alt mr-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Information -->
        <div class="space-y-6">
            <!-- Account Security -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                    Account Security
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span class="text-green-700 font-medium">Account Active</span>
                        </div>
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Verified</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-600 mr-2"></i>
                            <span class="text-blue-700 font-medium">Email Verified</span>
                        </div>
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Active</span>
                    </div>

                    @if($user->phone_number)
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-phone text-purple-600 mr-2"></i>
                            <span class="text-purple-700 font-medium">Phone Verified</span>
                        </div>
                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">Active</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Security Tips -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                    Security Tips
                </h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                        <span>Use a strong, unique password for your account</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                        <span>Never share your login credentials with others</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                        <span>Log out from shared or public computers</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                        <span>Keep your contact information up to date</span>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-cog text-gray-600 mr-2"></i>
                    Account Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('profile.index') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-user text-blue-600 mr-3"></i>
                        <span class="text-blue-700 font-medium">Edit Profile</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-tachometer-alt text-green-600 mr-3"></i>
                        <span class="text-green-700 font-medium">Dashboard</span>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <i class="fas fa-wallet text-purple-600 mr-3"></i>
                        <span class="text-purple-700 font-medium">Buy SMS</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    
    if (passwordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value && passwordInput.value !== this.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value && this.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
    }
});
</script>
@endsection
