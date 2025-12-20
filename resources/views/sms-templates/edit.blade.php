@extends('layouts.modern-dashboard')

@section('title', 'Edit SMS Template')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit SMS Template</h1>
                <p class="text-gray-600 mt-1">Update your SMS message template</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('sms-templates.show', $template) }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View
                </a>
                <a href="{{ route('sms-templates.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Templates
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Template Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <form action="{{ route('sms-templates.update', $template) }}" method="POST" id="template-form">
                    @csrf
                    @method('PUT')
                    
                    <!-- Template Name -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}" required maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror" placeholder="Enter template name...">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div class="mb-6">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="category" name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category') border-red-500 @enderror">
                            <option value="">Select a category</option>
                            <option value="otp" {{ old('category', $template->category) === 'otp' ? 'selected' : '' }}>OTP (One-Time Password)</option>
                            <option value="transactional" {{ old('category', $template->category) === 'transactional' ? 'selected' : '' }}>Transactional</option>
                            <option value="marketing" {{ old('category', $template->category) === 'marketing' ? 'selected' : '' }}>Marketing</option>
                            <option value="reminders" {{ old('category', $template->category) === 'reminders' ? 'selected' : '' }}>Reminders</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="500" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="Optional description for this template...">{{ old('description', $template->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Characters: <span id="description-count">0</span>/500</p>
                    </div>

                    <!-- Message Content -->
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Message Content *</label>
                        <textarea id="content" name="content" rows="6" required maxlength="1000" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('content') border-red-500 @enderror" placeholder="Enter your SMS message content here...\n\nUse variables like {name}, {amount}, {code} for dynamic content.">{{ old('content', $template->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-2 flex justify-between text-sm text-gray-500">
                            <span>Characters: <span id="content-count">0</span>/1000</span>
                            <span>SMS Parts: <span id="sms-parts">1</span></span>
                        </div>
                    </div>

                    <!-- Variables Preview -->
                    <div class="mb-6" id="variables-section" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Detected Variables</label>
                        <div id="variables-list" class="flex flex-wrap gap-2"></div>
                        <p class="mt-1 text-sm text-gray-500">These variables will be replaced with actual values when sending SMS.</p>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Template is active</span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500">Inactive templates cannot be used for sending SMS.</p>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4">
                        <a href="{{ route('sms-templates.show', $template) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Template
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="lg:col-span-1">
            <!-- Template Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Info</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="text-gray-900">{{ $template->created_at->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated:</span>
                        <span class="text-gray-900">{{ $template->updated_at->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Usage Count:</span>
                        <span class="text-gray-900">{{ $template->usage_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Template Preview -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Live Preview</h3>
                <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                    <div class="text-sm text-gray-600 mb-2">SMS Preview:</div>
                    <div id="preview-content" class="text-sm text-gray-900 whitespace-pre-wrap">{{ $template->content }}</div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-lg font-semibold text-gray-900" id="preview-chars">{{ strlen($template->content) }}</div>
                        <div class="text-xs text-gray-500">Characters</div>
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-gray-900" id="preview-parts">{{ strlen($template->content) > 160 ? ceil(strlen($template->content) / 153) : 1 }}</div>
                        <div class="text-xs text-gray-500">SMS Parts</div>
                    </div>
                </div>
            </div>

            <!-- Variable Help -->
            <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Using Variables</h3>
                <div class="space-y-3 text-sm text-blue-800">
                    <div>
                        <strong>Syntax:</strong> Use curly braces like <code class="bg-blue-100 px-1 rounded">{variable_name}</code>
                    </div>
                    <div>
                        <strong>Examples:</strong>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li><code class="bg-blue-100 px-1 rounded">{name}</code> - Customer name</li>
                            <li><code class="bg-blue-100 px-1 rounded">{amount}</code> - Transaction amount</li>
                            <li><code class="bg-blue-100 px-1 rounded">{code}</code> - Verification code</li>
                            <li><code class="bg-blue-100 px-1 rounded">{date}</code> - Date/time</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('sms-templates.duplicate', $template) }}" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors text-center block">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Duplicate Template
                    </a>
                    <form action="{{ route('sms-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.')" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentTextarea = document.getElementById('content');
    const descriptionTextarea = document.getElementById('description');
    const contentCount = document.getElementById('content-count');
    const descriptionCount = document.getElementById('description-count');
    const smsPartsSpan = document.getElementById('sms-parts');
    const variablesSection = document.getElementById('variables-section');
    const variablesList = document.getElementById('variables-list');
    const previewContent = document.getElementById('preview-content');
    const previewChars = document.getElementById('preview-chars');
    const previewParts = document.getElementById('preview-parts');

    function updateCounts() {
        const content = contentTextarea.value;
        const description = descriptionTextarea.value;
        
        // Update character counts
        contentCount.textContent = content.length;
        descriptionCount.textContent = description.length;
        previewChars.textContent = content.length;
        
        // Calculate SMS parts
        let parts = 1;
        if (content.length > 160) {
            parts = Math.ceil(content.length / 153);
        }
        smsPartsSpan.textContent = parts;
        previewParts.textContent = parts;
        
        // Update preview
        previewContent.textContent = content || 'Enter message content to see preview...';
        
        // Extract and display variables
        const variableMatches = content.match(/\{([^}]+)\}/g);
        if (variableMatches && variableMatches.length > 0) {
            const uniqueVariables = [...new Set(variableMatches)];
            variablesList.innerHTML = uniqueVariables.map(variable => 
                `<span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">${variable}</span>`
            ).join('');
            variablesSection.style.display = 'block';
        } else {
            variablesSection.style.display = 'none';
        }
    }

    // Add event listeners
    contentTextarea.addEventListener('input', updateCounts);
    descriptionTextarea.addEventListener('input', updateCounts);
    
    // Initial update
    updateCounts();
});
</script>
@endsection
