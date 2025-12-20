@extends('layouts.modern-dashboard')

@section('title', 'Apply for Sender ID')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('sender-ids.index') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Apply for Sender ID</h1>
                <p class="text-gray-600 mt-1">Submit your application for a custom SMS sender identity</p>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('sender-ids.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Sender ID Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Sender ID Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Sender Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="sender_name" 
                               name="sender_name" 
                               value="{{ old('sender_name') }}"
                               maxlength="11"
                               pattern="[A-Za-z0-9]+"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sender_name') border-red-500 @enderror"
                               placeholder="e.g., MYCOMPANY"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Max 11 characters, letters and numbers only</p>
                        @error('sender_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="sender_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="sender_description" 
                                  name="sender_description" 
                                  rows="3"
                                  maxlength="170"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sender_description') border-red-500 @enderror"
                                  placeholder="Describe how you plan to use this sender ID"
                                  required>{{ old('sender_description') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Use 15-170 characters (<span id="char-count">0</span>/170)</p>
                        @error('sender_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Company Name
                        </label>
                        <input type="text" 
                               id="business_name" 
                               name="business_name" 
                               value="{{ old('business_name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('business_name') border-red-500 @enderror"
                               placeholder="Your company name">
                        @error('business_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="company_url" class="block text-sm font-medium text-gray-700 mb-2">
                            Company URL
                        </label>
                        <input type="url" 
                               id="company_url" 
                               name="company_url" 
                               value="{{ old('company_url') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('company_url') border-red-500 @enderror"
                               placeholder="https://example.com">
                        @error('company_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="use_case_category" class="block text-sm font-medium text-gray-700 mb-2">
                            Use Case <span class="text-red-500">*</span>
                        </label>
                        <select id="use_case_category" 
                                name="use_case_category" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('use_case_category') border-red-500 @enderror"
                                required>
                            <option value="">Select use case</option>
                            <option value="marketing" {{ old('use_case_category') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                            <option value="transactional" {{ old('use_case_category') === 'transactional' ? 'selected' : '' }}>Transactional</option>
                            <option value="otp" {{ old('use_case_category') === 'otp' ? 'selected' : '' }}>OTP</option>
                            <option value="alerts" {{ old('use_case_category') === 'alerts' ? 'selected' : '' }}>Alerts</option>
                            <option value="notifications" {{ old('use_case_category') === 'notifications' ? 'selected' : '' }}>Notifications</option>
                            <option value="two_way" {{ old('use_case_category') === 'two_way' ? 'selected' : '' }}>Two-way</option>
                            <option value="other" {{ old('use_case_category') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('use_case_category')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6">
                <a href="{{ route('sender-ids.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Back
                </a>
                
                <div class="flex space-x-3">
                    <button type="button" 
                            onclick="clearForm()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Clear
                    </button>
                    
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Submit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Character counter for description
document.getElementById('sender_description').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('char-count').textContent = charCount;
    
    if (charCount > 170) {
        document.getElementById('char-count').classList.add('text-red-500');
    } else {
        document.getElementById('char-count').classList.remove('text-red-500');
    }
});

// Clear form function
function clearForm() {
    if (confirm('Are you sure you want to clear all fields?')) {
        document.querySelector('form').reset();
        document.getElementById('char-count').textContent = '0';
    }
}

// Initialize character count on page load
document.addEventListener('DOMContentLoaded', function() {
    const description = document.getElementById('sender_description');
    document.getElementById('char-count').textContent = description.value.length;
});
</script>
@endsection
