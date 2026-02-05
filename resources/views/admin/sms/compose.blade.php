@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin - Compose Message')

@section('content')
<div class="animate-fade-in-up">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.sms.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-paper-plane text-purple-600 mr-3"></i>
                    Compose Message
                </h1>
                <p class="text-gray-600">Send Quick SMS or Group SMS</p>
            </div>
        </div>
    </div>

    <!-- Compose Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form id="smsForm">
            @csrf
            
            <!-- Message Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Message Type</label>
                <div class="flex space-x-4">
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all message-type-option border-purple-500 bg-purple-50" data-type="quick">
                        <input type="radio" name="message_type" value="quick" class="hidden" checked>
                        <div class="p-3 bg-purple-100 rounded-lg mr-3">
                            <i class="fas fa-bolt text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Quick SMS</p>
                            <p class="text-sm text-gray-600">Send to specific numbers or users</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all message-type-option border-gray-200" data-type="group">
                        <input type="radio" name="message_type" value="group" class="hidden">
                        <div class="p-3 bg-blue-100 rounded-lg mr-3">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Group SMS</p>
                            <p class="text-sm text-gray-600">Send to a contact group</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sender ID -->
            <div class="mb-6">
                <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-2">Sender ID</label>
                <select id="sender_id" name="sender_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Select Sender ID</option>
                    @foreach($senderIds as $senderId)
                        <option value="{{ $senderId->sender_name }}">{{ $senderId->sender_name }}</option>
                    @endforeach
                </select>
                @if($senderIds->isEmpty())
                    <p class="text-sm text-yellow-600 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        No approved sender IDs available. <a href="{{ route('admin.sender-ids.index') }}" class="underline">Request one</a>
                    </p>
                @endif
            </div>

            <!-- Quick SMS Recipients Section -->
            <div id="quickSmsSection">
                <!-- Select Users -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Users (Optional)</label>
                    <div class="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                        <div class="mb-3">
                            <input type="text" id="userSearch" placeholder="Search users..." 
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div id="userList" class="space-y-2">
                            @foreach($users as $user)
                            <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer user-item" data-name="{{ strtolower($user->name) }}" data-phone="{{ $user->phone }}">
                                <input type="checkbox" name="selected_users[]" value="{{ $user->id }}" 
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 mr-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->phone }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Selected: <span id="selectedCount">0</span> users</p>
                </div>

                <!-- Manual Phone Numbers -->
                <div class="mb-6">
                    <label for="recipients" class="block text-sm font-medium text-gray-700 mb-2">
                        Or Enter Phone Numbers Manually
                    </label>
                    <textarea id="recipients" name="recipients" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="Enter phone numbers (one per line or comma-separated)&#10;Example:&#10;+255712345678&#10;0712345678, 0713456789"></textarea>
                    <p class="text-sm text-gray-500 mt-2">Enter Tanzania phone numbers. Numbers will be automatically formatted.</p>
                </div>
            </div>

            <!-- Group SMS Section (Hidden by default) -->
            <div id="groupSmsSection" class="hidden">
                <div class="mb-6">
                    <label for="contact_group_id" class="block text-sm font-medium text-gray-700 mb-2">Contact Group</label>
                    <select id="contact_group_id" name="contact_group_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Contact Group</option>
                        @foreach($contactGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->contacts_count }} contacts)</option>
                        @endforeach
                    </select>
                    @if($contactGroups->isEmpty())
                        <p class="text-sm text-yellow-600 mt-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            No contact groups available. Create one in Contacts section first.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Message Content -->
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea id="message" name="message" rows="5" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                          placeholder="Type your message here..."></textarea>
                <div class="flex justify-between mt-2 text-sm text-gray-500">
                    <span>Characters: <span id="charCount">0</span>/918</span>
                    <span>SMS Parts: <span id="smsPartCount">0</span> | Estimated Cost: TZS <span id="estimatedCost">0</span></span>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.sms.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" id="sendBtn"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Message
                </button>
            </div>
        </form>
    </div>

    <!-- Success/Error Modal -->
    <div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div id="modalContent" class="text-center">
                <!-- Content will be inserted by JS -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('smsForm');
    const messageInput = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const smsPartCount = document.getElementById('smsPartCount');
    const estimatedCost = document.getElementById('estimatedCost');
    const selectedCount = document.getElementById('selectedCount');
    const userSearch = document.getElementById('userSearch');
    const messageTypeOptions = document.querySelectorAll('.message-type-option');
    const quickSmsSection = document.getElementById('quickSmsSection');
    const groupSmsSection = document.getElementById('groupSmsSection');
    const sendBtn = document.getElementById('sendBtn');
    const resultModal = document.getElementById('resultModal');
    const modalContent = document.getElementById('modalContent');

    // Message type toggle
    messageTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const type = this.dataset.type;
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            messageTypeOptions.forEach(opt => {
                opt.classList.remove('border-purple-500', 'bg-purple-50');
                opt.classList.add('border-gray-200');
            });
            this.classList.remove('border-gray-200');
            this.classList.add('border-purple-500', 'bg-purple-50');

            if (type === 'quick') {
                quickSmsSection.classList.remove('hidden');
                groupSmsSection.classList.add('hidden');
            } else {
                quickSmsSection.classList.add('hidden');
                groupSmsSection.classList.remove('hidden');
            }
            
            updateCost();
        });
    });

    // Character count and SMS parts calculation
    messageInput.addEventListener('input', updateCost);

    function calculateSmsParts(length) {
        if (length === 0) return 0;
        if (length <= 160) return 1;
        if (length <= 306) return 2;
        return Math.ceil(length / 153);
    }

    function getRecipientCount() {
        const messageType = document.querySelector('input[name="message_type"]:checked').value;
        
        if (messageType === 'quick') {
            // Count selected users
            const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked').length;
            
            // Count manual numbers
            const recipients = document.getElementById('recipients').value;
            const manualNumbers = recipients.split(/[\s,;\n]+/).filter(n => n.trim().length > 0).length;
            
            return selectedUsers + manualNumbers;
        } else {
            // For group, we'd need AJAX to get count - for now estimate 1
            return 1;
        }
    }

    function updateCost() {
        const length = messageInput.value.length;
        const parts = calculateSmsParts(length);
        const recipients = Math.max(1, getRecipientCount());
        const cost = parts * recipients * 30;

        charCount.textContent = length;
        smsPartCount.textContent = parts;
        estimatedCost.textContent = cost.toLocaleString();
    }

    // User search
    userSearch.addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.user-item').forEach(item => {
            const name = item.dataset.name;
            const phone = item.dataset.phone || '';
            if (name.includes(search) || phone.includes(search)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    // Update selected count
    document.querySelectorAll('input[name="selected_users[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const count = document.querySelectorAll('input[name="selected_users[]"]:checked').length;
            selectedCount.textContent = count;
            updateCost();
        });
    });

    // Recipients input change
    document.getElementById('recipients').addEventListener('input', updateCost);

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

        const formData = new FormData(form);

        try {
            const response = await fetch('{{ route("admin.sms.send") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showModal('success', data.message, data.data);
            } else {
                showModal('error', data.message, data.errors);
            }
        } catch (error) {
            showModal('error', 'An error occurred while sending the message.');
        }

        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Message';
    });

    function showModal(type, message, details = null) {
        let icon, iconColor, title;
        
        if (type === 'success') {
            icon = 'check-circle';
            iconColor = 'text-green-500';
            title = 'Message Sent!';
        } else {
            icon = 'times-circle';
            iconColor = 'text-red-500';
            title = 'Failed to Send';
        }

        let detailsHtml = '';
        if (details && type === 'success') {
            detailsHtml = `
                <div class="mt-4 p-4 bg-gray-50 rounded-lg text-left">
                    <p class="text-sm text-gray-600">Sent: <span class="font-semibold text-green-600">${details.sent || 0}</span></p>
                    <p class="text-sm text-gray-600">Failed: <span class="font-semibold text-red-600">${details.failed || 0}</span></p>
                    <p class="text-sm text-gray-600">Total: <span class="font-semibold">${details.total || 0}</span></p>
                </div>
            `;
        }

        modalContent.innerHTML = `
            <i class="fas fa-${icon} ${iconColor} text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">${title}</h3>
            <p class="text-gray-600 mb-4">${message}</p>
            ${detailsHtml}
            <button onclick="closeModal()" class="mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                ${type === 'success' ? 'Done' : 'Try Again'}
            </button>
        `;

        resultModal.classList.remove('hidden');
        resultModal.classList.add('flex');

        if (type === 'success') {
            // Reset form on success
            form.reset();
            selectedCount.textContent = '0';
            updateCost();
        }
    }

    window.closeModal = function() {
        resultModal.classList.add('hidden');
        resultModal.classList.remove('flex');
    };

    // Close modal on outside click
    resultModal.addEventListener('click', function(e) {
        if (e.target === resultModal) {
            closeModal();
        }
    });

    // Initial cost update
    updateCost();
});
</script>
@endpush
@endsection
