@extends('layouts.modern-dashboard')

@section('title', 'Create SMS Campaign')

@section('styles')
<style>
.template-card:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.template-card.selected {
    border-color: #667eea;
    background: #f8f9ff;
}

.recipient-option:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.recipient-option.selected {
    border-color: #667eea;
    background: #f8f9ff;
}

.schedule-option:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.schedule-option.selected {
    border-color: #667eea;
    background: #f8f9ff;
}

.group-selector {
    margin-top: 1rem;
    display: none;
}

.group-selector.show {
    display: block;
}

.phone-input {
    margin-top: 1rem;
    display: none;
}

.phone-input.show {
    display: block;
}

.schedule-datetime {
    margin-top: 1rem;
    display: none;
}

.schedule-datetime.show {
    display: block;
}
</style>
@endsection

@section('content')
<div class="bg-white py-3">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
        
        <!-- Debug Info -->
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4" style="display: none;" id="debug-info">
            <strong>Debug Info:</strong>
            <div>User: {{ auth()->check() ? auth()->user()->name : 'Not logged in' }}</div>
            <div>SMS Credits: {{ auth()->check() ? auth()->user()->sms_credits : 'N/A' }}</div>
        </div>
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                <div class="font-semibold mb-2">There were issues with your submission:</div>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-lg">
                    <i class="fas fa-plus-circle text-white text-xl"></i>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">Create SMS Campaign</h1>
            </div>
            <a href="{{ route('campaigns.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow">
                <i class="fas fa-arrow-left mr-2"></i> Back to Campaigns
            </a>
        </div>

        <form method="POST" action="{{ route('campaigns.store') }}" id="campaignForm">
            @csrf
            <div class="max-w-3xl">
                <div>
                    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                        <!-- Campaign Details -->
                        <div id="step-1" class="p-3 border-b border-gray-100">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Campaign Details</h3>
                            </div>
                            <p class="text-gray-600 mb-3">Give your campaign a name and choose how you want to send it.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Campaign Name *</label>
                                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" id="name" name="name" value="{{ old('name') }}" placeholder="Enter campaign name" required>
                                    @error('name')
                                        <div class="text-red-500 text-sm mt-1 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="sender_id" class="block text-sm font-semibold text-gray-700 mb-2">Sender ID *</label>
                                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" id="sender_id" name="sender_id" required>
                                        <option value="">Select Sender ID</option>
                                        @foreach($senderIds as $senderId)
                                            <option value="{{ $senderId->id }}" {{ old('sender_id') == $senderId->id ? 'selected' : '' }}>
                                                {{ $senderId->sender_id }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sender_id')
                                        <div class="text-red-500 text-sm mt-1 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    @if($senderIds->isEmpty())
                                        <div class="text-amber-600 text-sm mt-2 flex items-center p-3 bg-amber-50 rounded-lg">
                                            <i class="fas fa-exclamation-triangle mr-2"></i> No approved sender IDs found. 
                                            <a href="#" class="text-blue-600 hover:text-blue-800 underline ml-1">Apply for a sender ID</a>
                                        </div>
                                @endif
                            </div>
                            <div class="mt-4 flex items-center justify-end">
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg" onclick="showStep(2)">Next</button>
                            </div>
                        </div>
                    </div>

                        <!-- Message Composition -->
                        <div id="step-2" class="p-3 border-b border-gray-100" style="display:none">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <i class="fas fa-edit text-green-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Message Composition</h3>
                            </div>
                            <p class="text-gray-600 mb-3">Compose your message or select from your saved templates.</p>
                            <div class="mb-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Message Source</label>
                                <div class="flex items-center gap-6">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="message_source" value="custom" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" checked>
                                        <span class="text-gray-800">Custom</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="message_source" value="template" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="text-gray-800">Template</span>
                                    </label>
                                </div>
                            </div>
                            @if($templates->isNotEmpty())
                                <div id="template-select-container" class="mb-4" style="display: none;">
                                    <label for="message_template_select" class="block text-sm font-semibold text-gray-700 mb-2">Select Template</label>
                                    <select id="message_template_select" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="">Choose a template</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            
                            @if($templates->isNotEmpty())
                                <div class="mb-3">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Quick Templates</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($templates as $template)
                                            <div class="template-card border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200 hover:shadow-md" data-template-id="{{ $template->id }}">
                                                <div class="font-semibold text-gray-900 mb-2">{{ $template->name }}</div>
                                                <div class="text-gray-600 text-sm italic mb-2">{{ Str::limit($template->content, 80) }}</div>
                                                @if($template->variables)
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($template->variables as $variable)
                                                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-md">{{ $variable }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <div class="relative">
                                <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Message Content *</label>
                                <textarea class="w-full px-3 py-2 pr-24 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none" id="message" name="message" rows="5" 
                                          placeholder="Type your message here..." required>{{ old('message') }}</textarea>
                                <div class="absolute bottom-3 right-3 bg-white px-2 py-1 border border-gray-200 rounded-lg text-xs text-gray-600">
                                    <span id="char-count">0</span>/1600 characters
                                </div>
                                @error('message')
                                    <div class="text-red-500 text-sm mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                                    <div class="flex items-center text-sm text-blue-700">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        This message will be sent as <span id="sms-parts" class="font-semibold">1</span> SMS part(s) per recipient.
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <button type="button" class="px-4 py-2 border rounded-lg" onclick="showStep(1)">Back</button>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg" onclick="showStep(3)">Next</button>
                            </div>
                        </div>

                        <!-- Recipients -->
                        <div id="step-3" class="p-3 border-b border-gray-100" style="display:none">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <i class="fas fa-users text-purple-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Recipients</h3>
                            </div>
                            <p class="text-gray-600 mb-3">Choose who will receive your message.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div class="recipient-option border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200" data-type="individual">
                                    <div class="flex items-start space-x-4">
                                        <input type="radio" name="recipient_type" value="individual" id="individual_phones" class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" {{ old('recipient_type', 'individual') === 'individual' ? 'checked' : '' }}>
                                        <label for="individual_phones" class="flex-1 cursor-pointer">
                                            <div class="font-semibold text-gray-900 mb-1">Individual Phone Numbers</div>
                                            <div class="text-gray-600 text-sm">Enter specific phone numbers (one per line)</div>
                                        </label>
                                    </div>
                                    <div class="phone-input mt-3 pl-8">
                                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none" name="recipient_phones" id="recipient_phones" rows="4" 
                                                  placeholder="255712345678&#10;255787654321&#10;255698765432">{{ old('recipient_phones') }}</textarea>
                                        <div class="mt-2 text-sm text-gray-600">
                                            Enter phone numbers starting with 255 (e.g., 255712345678), one per line.
                                            <span id="phone-count" class="font-semibold text-blue-600">0</span> valid numbers detected.
                                        </div>
                                    </div>
                                </div>

                                <div class="recipient-option border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200" data-type="groups">
                                    <div class="flex items-start space-x-4">
                                        <input type="radio" name="recipient_type" value="groups" id="group_recipients" class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" {{ old('recipient_type') === 'groups' ? 'checked' : '' }}>
                                        <label for="group_recipients" class="flex-1 cursor-pointer">
                                            <div class="font-semibold text-gray-900 mb-1">Contact Group</div>
                                            <div class="text-gray-600 text-sm">Select a contact group to send to</div>
                                        </label>
                                    </div>
                                    <div class="group-selector mt-3 pl-8">
                                        <label for="recipient_groups_select" class="block text-sm font-semibold text-gray-700 mb-2">Select Group *</label>
                                        <select id="recipient_groups_select" name="recipient_groups[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                            <option value="">Choose a group</option>
                                            @foreach($contactGroups as $group)
                                                <option value="{{ $group->id }}" data-count="{{ $group->active_contacts_count }}" {{ collect(old('recipient_groups', []))->contains($group->id) ? 'selected' : '' }}>
                                                    {{ $group->name }} ({{ number_format($group->active_contacts_count) }} contacts)
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="mt-2 text-sm text-gray-600">
                                            Selected group recipients: <span id="group-recipient-count" class="font-semibold text-blue-600">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('recipient_type')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            
                            @error('recipient_groups')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('recipient_phones')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('recipients')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            @error('balance')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div class="mt-4 flex items-center justify-between">
                                <button type="button" class="px-4 py-2 border rounded-lg" onclick="showStep(2)">Back</button>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg" onclick="showStep(4)">Next</button>
                            </div>
                        </div>

                        <!-- Scheduling -->
                        <div id="step-4" class="p-3" style="display:none">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="p-2 bg-orange-100 rounded-lg">
                                    <i class="fas fa-clock text-orange-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Scheduling</h3>
                            </div>
                            <p class="text-gray-600 mb-3">Choose when to send your campaign.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div class="schedule-option border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200" data-type="now">
                                    <div class="flex items-start space-x-4">
                                        <input type="radio" name="schedule_type" value="now" id="send_now" class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" {{ old('schedule_type', 'now') === 'now' ? 'checked' : '' }}>
                                        <label for="send_now" class="flex-1 cursor-pointer">
                                            <div class="font-semibold text-gray-900 mb-1">Send Now</div>
                                            <div class="text-gray-600 text-sm">Send the campaign immediately after creation</div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="schedule-option border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200" data-type="later">
                                    <div class="flex items-start space-x-4">
                                        <input type="radio" name="schedule_type" value="later" id="send_later" class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" {{ old('schedule_type') === 'later' ? 'checked' : '' }}>
                                        <label for="send_later" class="flex-1 cursor-pointer">
                                            <div class="font-semibold text-gray-900 mb-1">Schedule for Later</div>
                                            <div class="text-gray-600 text-sm">Choose a specific date and time to send</div>
                                        </label>
                                    </div>
                                    <div class="schedule-datetime mt-3 pl-8">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label for="scheduled_date" class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                                                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" id="scheduled_date" name="scheduled_date" 
                                                       value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d') }}">
                                            </div>
                                            <div>
                                                <label for="scheduled_time" class="block text-sm font-semibold text-gray-700 mb-2">Time</label>
                                                <input type="time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" id="scheduled_time" name="scheduled_time" 
                                                       value="{{ old('scheduled_time') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @error('schedule_at')
                                <div class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div class="mt-6 flex items-center justify-between">
                                <button type="button" class="px-4 py-2 border rounded-lg" onclick="showStep(3)">Back</button>
                                <button type="submit" name="submit_action" value="send" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg shadow transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Send SMS</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                </div>
         </div>
     </form>
 </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.showStep = function(step){
        const sections = ['step-1','step-2','step-3','step-4'];
        sections.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.display = (id === 'step-' + step) ? '' : 'none';
        });
        setMessageSourceUI();
        updateCharacterCount();
        updateCostCalculator();
    };
    const currentBalance = {{ auth()->user()->sms_credits }};
    const messageSourceRadios = document.querySelectorAll('input[name="message_source"]');
    const templateSelectContainer = document.getElementById('template-select-container');
    const templateSelect = document.getElementById('message_template_select');

    function setMessageSourceUI() {
        const selected = document.querySelector('input[name="message_source"]:checked')?.value || 'custom';
        if (templateSelectContainer) {
            templateSelectContainer.style.display = selected === 'template' ? 'block' : 'none';
        }
    }

    messageSourceRadios.forEach(r => r.addEventListener('change', () => {
        setMessageSourceUI();
        // When switching to template without a selection, keep content as-is
        updateCharacterCount();
        updateCostCalculator();
    }));

    if (templateSelect) {
        templateSelect.addEventListener('change', async function() {
            const templateId = this.value;
            if (!templateId) return;
            try {
                const res = await fetch(`/campaigns/template/${templateId}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                const content = (data && (data.content || (data.data && data.data.content))) || '';
                const messageContent = document.getElementById('message');
                if (messageContent) {
                    messageContent.value = content;
                    updateCharacterCount();
                    updateCostCalculator();
                }
            } catch (e) {
                console.error('Failed to load template', e);
            }
        });
    }

    // Template selection
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', async function() {
            document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            const templateId = this.dataset.templateId;
            try {
                const res = await fetch(`/campaigns/template/${templateId}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                const content = (data && (data.content || (data.data && data.data.content))) || '';
                const messageContent = document.getElementById('message');
                messageContent.value = content;
                updateCharacterCount();
                updateCostCalculator();
            } catch (e) {
                console.error('Failed to load template', e);
            }
        });
    });

    // Recipient type selection
    document.querySelectorAll('.recipient-option').forEach(opt => {
        opt.addEventListener('click', function() {
            const type = this.dataset.type;
            const radio = document.querySelector(`input[name="recipient_type"][value="${type}"]`);
            if (radio) radio.checked = true;

            document.querySelectorAll('.phone-input').forEach(el => el.classList.remove('show'));
            document.querySelectorAll('.group-selector').forEach(el => el.classList.remove('show'));
            if (type === 'individual') {
                document.querySelectorAll('.phone-input').forEach(el => el.classList.add('show'));
            } else if (type === 'groups') {
                document.querySelectorAll('.group-selector').forEach(el => el.classList.add('show'));
            }
            updateRecipientCount();
        });
    });

    // Schedule type selection
    document.querySelectorAll('.schedule-option').forEach(opt => {
        opt.addEventListener('click', function() {
            const type = this.dataset.type;
            const radio = document.querySelector(`input[name="schedule_type"][value="${type}"]`);
            if (radio) radio.checked = true;

            document.querySelectorAll('.schedule-datetime').forEach(el => el.classList.remove('show'));
            if (type === 'later') {
                document.querySelectorAll('.schedule-datetime').forEach(el => el.classList.add('show'));
            }
        });
    });

    // Character counting
    const messageContentEl = document.getElementById('message');
    if (messageContentEl) {
        messageContentEl.addEventListener('input', updateCharacterCount);
    }

    function updateCharacterCount() {
        const content = (document.getElementById('message')?.value || '');
        const length = content.length;
        const smsPartsCount = Math.max(1, Math.ceil(length / 160));

        const charCountEl = document.getElementById('char-count');
        if (charCountEl) charCountEl.textContent = String(length);
        const smsPartsEl = document.getElementById('sms-parts');
        if (smsPartsEl) smsPartsEl.textContent = String(smsPartsCount);
        const costPerRecipientEl = document.getElementById('cost-per-recipient');
        if (costPerRecipientEl) costPerRecipientEl.textContent = String(smsPartsCount * 30);
        const smsPartsDisplayEl = document.getElementById('sms-parts-display');
        if (smsPartsDisplayEl) smsPartsDisplayEl.textContent = String(smsPartsCount);

        updateCostCalculator();
    }

    // Phone number counting
    const recipientPhonesEl = document.getElementById('recipient_phones');
    if (recipientPhonesEl) {
        recipientPhonesEl.addEventListener('input', function() {
            const phones = (this.value || '').split('\n').filter(phone => phone.trim().match(/^255\d{9}$/));
            const phoneCountEl = document.getElementById('phone-count');
            if (phoneCountEl) phoneCountEl.textContent = String(phones.length);
            updateRecipientCount();
        });
    }

    function updateRecipientCount() {
        const recipientTypeRadio = document.querySelector('input[name="recipient_type"]:checked');
        const recipientType = recipientTypeRadio ? recipientTypeRadio.value : null;
        let count = 0;

        if (recipientType === 'individual') {
            const phones = (document.getElementById('recipient_phones')?.value || '').split('\n').filter(phone => phone.trim().match(/^255\d{9}$/));
            count = phones.length;
        } else if (recipientType === 'groups') {
            const selectEl = document.getElementById('recipient_groups_select');
            const selectedOption = selectEl ? selectEl.options[selectEl.selectedIndex] : null;
            const groupCount = selectedOption ? parseInt(selectedOption.getAttribute('data-count') || '0') : 0;
            count = isNaN(groupCount) ? 0 : groupCount;
            const groupCountEl = document.getElementById('group-recipient-count');
            if (groupCountEl) groupCountEl.textContent = String(count);
        }

        const recipientCountEl = document.getElementById('recipient-count');
        if (recipientCountEl) recipientCountEl.textContent = String(count);
        updateCostCalculator();
    }

    function updateCostCalculator() {
        console.log('updateCostCalculator called');
        console.log('currentBalance:', currentBalance);
        
        const recipientCount = parseInt(document.getElementById('recipient-count')?.textContent || '0') || 0;
        const smsPartsPerMessage = parseInt(document.getElementById('sms-parts-display')?.textContent || '1') || 1;
        const totalSmsParts = recipientCount * smsPartsPerMessage;
        const totalCost = totalSmsParts * 30;
        const creditsAfter = currentBalance - totalSmsParts;
        
        console.log('recipientCount:', recipientCount);
        console.log('smsPartsPerMessage:', smsPartsPerMessage);
        console.log('totalSmsParts:', totalSmsParts);
        console.log('totalCost:', totalCost);
        console.log('creditsAfter:', creditsAfter);

        const totalSmsPartsEl = document.getElementById('total-sms-parts');
        if (totalSmsPartsEl) totalSmsPartsEl.textContent = String(totalSmsParts);
        const totalCostEl = document.getElementById('total-cost');
        if (totalCostEl) totalCostEl.textContent = `${totalSmsParts.toLocaleString()} SMS`;
        const balanceAfterEl = document.getElementById('balance-after');
        if (balanceAfterEl) balanceAfterEl.textContent = `${creditsAfter.toLocaleString()} SMS`;

        const warningEl = document.getElementById('balance-warning');
        const errorEl = document.getElementById('balance-error');
        const draftBtn = document.getElementById('create-campaign-btn');
        const sendBtn = document.getElementById('send-campaign-btn');

        if (warningEl) warningEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        if (draftBtn) draftBtn.disabled = false;
        if (sendBtn) sendBtn.disabled = false;

        const insufficientBalance = totalSmsParts > currentBalance;
        const noRecipients = recipientCount === 0;
        if (insufficientBalance) {
            if (errorEl) errorEl.style.display = 'block';
            if (draftBtn) draftBtn.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
        } else if (creditsAfter < (currentBalance * 0.2)) {
            if (warningEl) warningEl.style.display = 'block';
        }
        if (noRecipients && sendBtn) sendBtn.disabled = true;
    }

    // Form submission
    const formEl = document.getElementById('campaignForm');
    if (formEl) {
        // Store the original action to prevent modification
        const originalAction = formEl.action.toString();
        console.log('Original form action stored:', originalAction);
        
        formEl.addEventListener('submit', function(e) {
            console.log('Form submission started');
            console.log('Submit event:', e);
            console.log('Submitter:', e.submitter);
            console.log('Submitter type:', e.submitter?.type);
            console.log('Submitter tagName:', e.submitter?.tagName);
            console.log('Submitter value:', e.submitter?.value);
            console.log('Submitter name:', e.submitter?.name);
            console.log('Submitter id:', e.submitter?.id);
            console.log('Current form action:', formEl.action);
            console.log('Original form action:', originalAction);
            
            // Ensure the action hasn't been modified
            const currentAction = formEl.action.toString();
            if (currentAction !== originalAction) {
                console.warn('Form action was modified! Resetting to original.');
                console.warn('Current action:', currentAction);
                console.warn('Original action:', originalAction);
                formEl.action = originalAction;
            }
            
            // Check if form is valid
            if (!formEl.checkValidity()) {
                console.log('Form validation failed');
                // Let the browser show validation messages
                return;
            }
            
            // Additional validation for required fields
            const name = document.getElementById('name')?.value.trim();
            const message = document.getElementById('message')?.value.trim();
            const senderId = document.getElementById('sender_id')?.value;
            const recipientType = document.querySelector('input[name="recipient_type"]:checked')?.value;
            
            console.log('Validation check:');
            console.log('Name:', name);
            console.log('Message:', message);
            console.log('Sender ID:', senderId);
            console.log('Recipient Type:', recipientType);
            
            if (!name || !message || !senderId) {
                e.preventDefault();
                alert('Please fill in all required fields: Campaign Name, Sender ID, and Message');
                return;
            }
            
            // Check recipient validation
            if (recipientType === 'individual') {
                const phones = document.getElementById('recipient_phones')?.value.trim();
                if (!phones) {
                    e.preventDefault();
                    alert('Please enter at least one phone number');
                    return;
                }
            } else if (recipientType === 'groups') {
                const groups = document.getElementById('recipient_groups_select')?.value;
                if (!groups) {
                    e.preventDefault();
                    alert('Please select at least one contact group');
                    return;
                }
            }
            
            const scheduledDate = document.getElementById('scheduled_date')?.value || '';
            const scheduledTime = document.getElementById('scheduled_time')?.value || '';

            const scheduleType = document.querySelector('input[name="schedule_type"]:checked')?.value;
            if (scheduleType === 'later') {
                if (!scheduledDate || !scheduledTime) {
                    e.preventDefault();
                    alert('Please select both date and time for scheduled sending.');
                    return;
                }

                const scheduledAt = `${scheduledDate} ${scheduledTime}`;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'schedule_at';
                hidden.value = scheduledAt;
                formEl.appendChild(hidden);
            }
            
            console.log('Form submission proceeding - all validation passed');
            console.log('Submitting with submitter:', e.submitter);
            console.log('Submitting with submitter value:', e.submitter?.value);
            console.log('Form action before submission:', formEl.action);
            
            // Log the final form data
            const finalFormData = new FormData(formEl);
            console.log('Final form data:');
            for (let [key, value] of finalFormData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            // Allow the form to submit normally
            console.log('Form will now submit normally...');
        });
    }

    // Initialize
        console.log('Current balance:', currentBalance);
        if (typeof currentBalance === 'undefined' || currentBalance === null) {
            console.error('currentBalance is not properly defined');
            alert('Error: Unable to load your SMS credits. Please refresh the page.');
            document.getElementById('debug-info').style.display = 'block';
        } else {
            console.log('Initialization successful');
            document.getElementById('debug-info').style.display = 'none';
        }
        
        // Debug: Check the expected route URL
        console.log('Expected campaigns.store route:', '{{ route("campaigns.store") }}');
        
        // Monitor form action for corruption
        setInterval(function() {
            const form = document.getElementById('campaignForm');
            if (form) {
                const currentAction = form.action.toString();
                if (currentAction.includes('[object')) {
                    console.error('FORM ACTION CORRUPTED:', currentAction);
                    console.error('This happened at:', new Date().toISOString());
                }
            }
        }, 1000); // Check every second
        
        // Add debugging for all radio buttons to see if they corrupt the form action
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                const form = document.getElementById('campaignForm');
                const beforeAction = form.action.toString();
                setTimeout(function() {
                    const afterAction = form.action.toString();
                    if (afterAction !== beforeAction) {
                        console.log('Form action changed by radio button:');
                        console.log('Before:', beforeAction);
                        console.log('After:', afterAction);
                        console.log('Radio button name:', e.target.name);
                        console.log('Radio button value:', e.target.value);
                    }
                }, 100);
            }
        });
        
        updateCharacterCount();
        updateRecipientCount();
        setMessageSourceUI();

        // Set initial states
        const initialRecipientType = document.querySelector('input[name="recipient_type"]:checked')?.value;
        if (initialRecipientType === 'groups') {
            document.querySelectorAll('.group-selector').forEach(el => el.classList.add('show'));
        } else if (initialRecipientType === 'individual') {
            document.querySelectorAll('.phone-input').forEach(el => el.classList.add('show'));
        }

        const initialScheduleType = document.querySelector('input[name="schedule_type"]:checked')?.value;
        if (initialScheduleType === 'later') {
            document.querySelectorAll('.schedule-datetime').forEach(el => el.classList.add('show'));
        }
    });

    
</script>
@endsection
