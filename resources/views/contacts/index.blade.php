@extends('layouts.modern-dashboard')

@section('page-title', 'Manage Contacts')

@section('content')
<div class="max-w-7xl mx-auto px-4 pb-16 animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white grid place-items-center shadow">
                <i class="fas fa-address-book text-lg"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Contacts</h1>
                <p class="text-sm text-gray-500">Create and manage your groups</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button id="refreshAddressBooksBtn"
                class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-rotate"></i>
                Refresh
            </button>
            <a href="{{ url('contacts/import') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 shadow-sm transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <i class="fas fa-file-import"></i>
                Import
            </a>
            @can('manage-contact-groups')
            <button id="addAddressBookBtn"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-plus"></i>
                Add New
            </button>
            @endcan
        </div>
    </div>

    @if($syncError)
        <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>
                <span>{{ $syncError }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white p-4 shadow">
            <div class="text-xs uppercase tracking-wide opacity-80">Total Contacts</div>
            <div id="statsTotalContacts" class="mt-2 text-2xl font-bold">{{ number_format($stats['total_contacts'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-xs uppercase tracking-wide text-gray-500">Active Contacts</div>
            <div id="statsActiveContacts" class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($stats['active_contacts'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-xs uppercase tracking-wide text-gray-500">Opted Out</div>
            <div id="statsOptedOutContacts" class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($stats['opted_out_contacts'] ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-white border border-gray-100 p-4 shadow">
            <div class="text-xs uppercase tracking-wide text-gray-500">Recent Imports</div>
            <div id="statsRecentImports" class="mt-2 text-2xl font-bold text-blue-600">{{ number_format($stats['recent_imports'] ?? 0) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow px-5 py-4 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
            <div class="flex-1">
                <label for="searchAddressBooks" class="block text-sm font-medium text-gray-600 mb-1">Search Groups</label>
                <div class="relative">
                    <input id="searchAddressBooks" type="text" placeholder="Search groups…"
                        class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            @can('manage-contact-groups')
            <div id="bulkActionsPanel" class="w-full lg:w-auto">
                <label for="bulkActionSelect" class="block text-sm font-medium text-gray-600 mb-1">Bulk Actions</label>
                <div class="flex items-center gap-3">
                    <select id="bulkActionSelect"
                        class="rounded-lg border border-gray-200 px-3 py-2	text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Action</option>
                        <option value="bulk-delete">Bulk Delete</option>
                    </select>
                    <button id="applyBulkBtn" type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60">
                        Apply
                    </button>
                </div>
                <p id="bulkSelectedLabel" class="mt-1 text-xs text-gray-500 hidden"></p>
            </div>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow">
        <!-- Desktop/Table view -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            @can('manage-contact-groups')
                            <input id="selectAllAddressBooks" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            @endcan
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Group Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Total Contacts</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="addressBooksTableBody" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                            Loading groups…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile/Card view -->
        <div class="md:hidden">
            @can('manage-contact-groups')
            <div class="flex items-center justify-between px-4 pt-4">
                <label class="flex items-center gap-2 text-xs text-gray-600">
                    <input id="selectAllAddressBooks" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Select All
                </label>
                <p id="bulkSelectedLabelMobile" class="text-xs text-gray-500 hidden"></p>
            </div>
            @endcan
            <div id="addressBooksCards" class="grid grid-cols-1 gap-3 px-4 pb-4">
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-center text-sm text-gray-500">Loading groups…</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="notificationContainer" class="fixed top-5 right-5 z-50 space-y-2 pointer-events-none"></div>

<!-- Address Book Modal -->
<div id="addressBookModal" class="fixed inset-0 z-40 hidden">
    <div class="absolute inset-0 bg-gray-900/60"></div>
    <div class="relative z-10 flex min-h-screen items-center justify-center px-4">
        <div class="w-[95%] sm:max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 id="addressBookModalTitle" class="text-lg font-semibold text-gray-900">Edit Contact</h3>
                <button id="closeAddressBookModal" class="text-gray-400 transition hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button type="button" id="tabContact" class="flex-1 px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-user-plus"></i> Contact
                </button>
                <button type="button" id="tabGroup" class="flex-1 px-4 py-3 text-sm font-medium text-teal-600 border-b-2 border-teal-500 flex items-center justify-center gap-2">
                    <i class="fas fa-users"></i> Group
                </button>
            </div>

            <div class="p-6">
                <!-- Info Banner -->
                <div class="mb-4 flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-700">
                    <i class="fas fa-info-circle"></i>
                    <span>Want to add many contacts? <a href="{{ url('contacts/import') }}" class="text-teal-600 hover:underline">Try importing them</a></span>
                </div>

                <!-- Contact Tab Content -->
                <div id="tabContactContent" class="hidden">
                    <form id="addContactForm" class="space-y-4">
                        <input type="hidden" id="contactGroupId" value="">
                        
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">What contact group would you like to add this to?</label>
                            <select id="contactGroupSelect" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Address Book(s) ...</option>
                                @foreach($addressBooks ?? [] as $book)
                                    <option value="{{ $book['local_id'] ?? $book['id'] ?? '' }}">{{ $book['name'] ?? 'Unnamed' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="contactPhone" class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
                            <input id="contactPhone" type="text" placeholder="255 (ex: 784845785)"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="contactFirstName" class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                            <input id="contactFirstName" type="text" placeholder="First Name"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="contactLastName" class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                            <input id="contactLastName" type="text" placeholder="Last Name"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="contactEmail" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <input id="contactEmail" type="email" placeholder="Email"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="submit" id="saveContactBtn"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Group Tab Content -->
                <div id="tabGroupContent">
                    <form id="addressBookForm" class="space-y-4">
                        <input type="hidden" id="addressBookMode" value="create">
                        <input type="hidden" id="addressBookLocalId">
                        <input type="hidden" id="addressBookBeemId">

                        <div>
                            <label for="addressBookName" class="mb-1 block text-sm font-medium text-gray-700">Contact Group Title</label>
                            <input id="addressBookName" type="text" maxlength="255" required placeholder="Default"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="addressBookDescription" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="addressBookDescription" rows="3" placeholder="Default group"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <input type="hidden" id="addressBookColor" value="#3B82F6">

                        <div class="flex items-center justify-end pt-2">
                            <button type="submit" id="saveAddressBookBtn"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-6 py-2 text-sm font-semibold text-white shadow transition hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contacts Modal -->
<div id="contactsModal" class="fixed inset-0 z-40 hidden">
    <div class="absolute inset-0 bg-gray-900/60"></div>
    <div class="relative z-10 flex min-h-screen items-center justify-center px-4">
        <div class="w-[95%] sm:max-w-4xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h3 id="contactsModalTitle" class="text-lg font-semibold text-gray-900">Group Contacts</h3>
                    <p id="contactsModalSubtitle" class="text-sm text-gray-500"></p>
                </div>
                <button id="contactsCloseBtn" class="text-gray-400 transition hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <div id="contactsLoadingState" class="flex items-center justify-center py-10 text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading contacts…
                </div>
                <div id="contactsEmptyState" class="hidden py-10 text-center text-sm text-gray-500">
                    <i class="fas fa-user-friends mb-2 text-3xl opacity-50"></i>
                    <p>No contacts found in this group.</p>
                </div>
                <div class="overflow-x-auto">
                    <table id="contactsTable" class="hidden min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Phone</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Location</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody id="contactsTableBody" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4 text-sm">
                <button id="contactsPrevBtn"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled>
                    <i class="fas fa-chevron-left text-xs"></i>
                    Previous
                </button>
                <div id="contactsPageIndicator" class="text-gray-500"></div>
                <button id="contactsNextBtn"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled>
                    Next
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const initialAddressBooks = @json($addressBooks ?? []);
    const statsData = @json($stats ?? []);
    const defaultAddressBookId = @json($defaultAddressBookId);
    const routes = {
        list: @json(route('contacts.address-books.index')),
        contacts: @json(route('contacts.address-books.contacts', ['addressBookId' => '__ADDRESS_BOOK__'])),
        export: @json(route('contacts.address-books.export', ['addressBookId' => '__ADDRESS_BOOK__'])),
        store: @json(route('contacts.groups.store')),
        update: @json(route('contacts.groups.update', ['group' => '__GROUP_ID__'])),
        destroy: @json(route('contacts.groups.destroy', ['group' => '__GROUP_ID__'])),
    };

    // Server-evaluated permissions to gate UI and actions
    const permissions = {
        export: @json(auth()->user()->can('export-contacts')),
        manageGroups: @json(auth()->user()->can('manage-contact-groups')),
    };

    const numberFormatter = new Intl.NumberFormat();

    const state = {
        items: Array.isArray(initialAddressBooks) ? initialAddressBooks : [],
        filtered: [],
        selected: new Set(),
        loading: false,
        viewing: {
            beemId: null,
            localId: null,
            name: '',
            description: '',
            page: 1,
            perPage: 100,
            total: null,
        },
    };

    const elements = {
        tableBody: document.getElementById('addressBooksTableBody'),
        cardContainer: document.getElementById('addressBooksCards'),
        selectAllCheckboxes: Array.from(document.querySelectorAll('#selectAllAddressBooks')),
        bulkActionSelect: document.getElementById('bulkActionSelect'),
        applyBulkBtn: document.getElementById('applyBulkBtn'),
        bulkSelectedLabel: document.querySelectorAll('#bulkSelectedLabel')[0],
        searchInput: document.getElementById('searchAddressBooks'),
        refreshBtn: document.getElementById('refreshAddressBooksBtn'),
        addBtn: document.getElementById('addAddressBookBtn'),
        notificationContainer: document.getElementById('notificationContainer'),
        stats: {
            total: document.getElementById('statsTotalContacts'),
            active: document.getElementById('statsActiveContacts'),
            optedOut: document.getElementById('statsOptedOutContacts'),
            groups: document.getElementById('statsGroupCount'),
            imports: document.getElementById('statsRecentImports'),
        },
        modal: {
            root: document.getElementById('addressBookModal'),
            title: document.getElementById('addressBookModalTitle'),
            form: document.getElementById('addressBookForm'),
            mode: document.getElementById('addressBookMode'),
            localId: document.getElementById('addressBookLocalId'),
            beemId: document.getElementById('addressBookBeemId'),
            name: document.getElementById('addressBookName'),
            description: document.getElementById('addressBookDescription'),
            color: document.getElementById('addressBookColor'),
            saveBtn: document.getElementById('saveAddressBookBtn'),
            cancelBtn: document.getElementById('cancelAddressBookBtn'),
            closeBtn: document.getElementById('closeAddressBookModal'),
        },
        contactsModal: {
            root: document.getElementById('contactsModal'),
            title: document.getElementById('contactsModalTitle'),
            subtitle: document.getElementById('contactsModalSubtitle'),
            table: document.getElementById('contactsTable'),
            body: document.getElementById('contactsTableBody'),
            loading: document.getElementById('contactsLoadingState'),
            empty: document.getElementById('contactsEmptyState'),
            prevBtn: document.getElementById('contactsPrevBtn'),
            nextBtn: document.getElementById('contactsNextBtn'),
            closeBtn: document.getElementById('contactsCloseBtn'),
            pageIndicator: document.getElementById('contactsPageIndicator'),
        },
    };

    function init() {
        state.filtered = [...state.items];
        updateStats();
        renderTable();
        bindEvents();

        // Disable UI controls when user cannot manage groups
        if (!permissions.manageGroups) {
            elements.selectAllCheckboxes.forEach(cb => { if (cb) cb.disabled = true; });
            if (elements.bulkActionSelect) elements.bulkActionSelect.disabled = true;
            if (elements.applyBulkBtn) elements.applyBulkBtn.disabled = true;
            if (elements.addBtn) elements.addBtn.disabled = true;
        }
    }

    function bindEvents() {
        elements.searchInput.addEventListener('input', () => {
            filterAddressBooks(elements.searchInput.value.trim().toLowerCase());
        });

        elements.selectAllCheckboxes.forEach(cb => cb.addEventListener('change', (event) => {
            toggleSelectAll(event.target.checked);
        }));

        if (permissions.manageGroups) {
            elements.applyBulkBtn.addEventListener('click', applyBulkAction);
        }
        elements.refreshBtn.addEventListener('click', () => refreshAddressBooks(true));
        if (permissions.manageGroups) {
            elements.addBtn.addEventListener('click', () => openAddressBookModal('create'));
        }

        elements.modal.closeBtn.addEventListener('click', closeAddressBookModal);
        elements.modal.form.addEventListener('submit', handleAddressBookSubmit);

        // Tab switching
        const tabContact = document.getElementById('tabContact');
        const tabGroup = document.getElementById('tabGroup');
        const tabContactContent = document.getElementById('tabContactContent');
        const tabGroupContent = document.getElementById('tabGroupContent');
        const addContactForm = document.getElementById('addContactForm');

        tabContact.addEventListener('click', () => {
            tabContact.classList.add('text-teal-600', 'border-teal-500');
            tabContact.classList.remove('text-gray-500', 'border-transparent');
            tabGroup.classList.remove('text-teal-600', 'border-teal-500');
            tabGroup.classList.add('text-gray-500', 'border-transparent');
            tabContactContent.classList.remove('hidden');
            tabGroupContent.classList.add('hidden');
        });

        tabGroup.addEventListener('click', () => {
            tabGroup.classList.add('text-teal-600', 'border-teal-500');
            tabGroup.classList.remove('text-gray-500', 'border-transparent');
            tabContact.classList.remove('text-teal-600', 'border-teal-500');
            tabContact.classList.add('text-gray-500', 'border-transparent');
            tabGroupContent.classList.remove('hidden');
            tabContactContent.classList.add('hidden');
        });

        // Add contact form submission
        if (addContactForm) {
            addContactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const groupId = document.getElementById('contactGroupSelect').value || document.getElementById('contactGroupId').value;
                const phone = document.getElementById('contactPhone').value.trim();
                const firstName = document.getElementById('contactFirstName').value.trim();
                const lastName = document.getElementById('contactLastName').value.trim();
                const email = document.getElementById('contactEmail').value.trim();
                const name = [firstName, lastName].filter(Boolean).join(' ') || 'Contact';

                if (!phone) {
                    showNotification('Phone number is required', 'error');
                    return;
                }

                if (!groupId) {
                    showNotification('Please select a contact group', 'error');
                    return;
                }

                try {
                    const response = await fetch('{{ route("contacts.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            contact_group_id: groupId,
                            phone: phone,
                            name: name,
                            email: email || null,
                        }),
                    });

                    const data = await response.json();
                    if (response.ok) {
                        showNotification('Contact added successfully', 'success');
                        addContactForm.reset();
                        closeAddressBookModal();
                        refreshAddressBooks(true);
                    } else {
                        showNotification(data.message || data.errors?.[0] || 'Failed to add contact', 'error');
                    }
                } catch (error) {
                    showNotification('Failed to add contact', 'error');
                }
            });
        }

        elements.contactsModal.closeBtn.addEventListener('click', closeContactsModal);
        elements.contactsModal.prevBtn.addEventListener('click', () => changeContactsPage(-1));
        elements.contactsModal.nextBtn.addEventListener('click', () => changeContactsPage(1));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (!elements.modal.root.classList.contains('hidden')) closeAddressBookModal();
                if (!elements.contactsModal.root.classList.contains('hidden')) closeContactsModal();
            }
        });

        [elements.modal.root, elements.contactsModal.root].forEach(modalEl => {
            modalEl.addEventListener('click', (event) => {
                if (event.target === modalEl) {
                    modalEl === elements.modal.root ? closeAddressBookModal() : closeContactsModal();
                }
            });
        });
    }

    function updateStats() {
        const totals = state.items.reduce((acc, item) => acc + (Number(item.total_contacts) || 0), 0);
        if (elements.stats.total) elements.stats.total.textContent = numberFormatter.format(totals);
        if (elements.stats.groups) elements.stats.groups.textContent = numberFormatter.format(state.items.length);
        if (elements.stats.active) elements.stats.active.textContent = numberFormatter.format(statsData.active_contacts ?? 0);
        if (elements.stats.optedOut) elements.stats.optedOut.textContent = numberFormatter.format(statsData.opted_out_contacts ?? 0);
        if (elements.stats.imports) elements.stats.imports.textContent = numberFormatter.format(statsData.recent_imports ?? 0);
    }

    function filterAddressBooks(searchTerm = '') {
        if (!searchTerm) {
            state.filtered = [...state.items];
        } else {
            state.filtered = state.items.filter(book => {
                const name = (book.name || '').toLowerCase();
                const description = (book.description || '').toLowerCase();
                return name.includes(searchTerm) || description.includes(searchTerm);
            });
        }
        renderTable();
    }

    function toggleSelectAll(checked) {
        if (!permissions.manageGroups) {
            return;
        }
        if (!state.filtered.length) {
            elements.selectAllCheckbox.checked = false;
            return;
        }

        state.filtered.forEach(book => {
            const localId = book.local_id;
            if (!localId || book.is_default) {
                return;
            }
            if (checked) {
                state.selected.add(String(localId));
            } else {
                state.selected.delete(String(localId));
            }
        });

        renderTable();
    }

    function renderTable() {
        if (!state.filtered.length) {
            if (elements.tableBody) {
                elements.tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                            <i class="fas fa-folder-open mb-2 text-3xl opacity-50"></i>
                            <div>No groups found.</div>
                            <div class="text-xs text-gray-400 mt-1">Create a group to get started.</div>
                        </td>
                    </tr>
                `;
            }
            if (elements.cardContainer) {
                elements.cardContainer.innerHTML = `
                    <div class="rounded-xl border border-gray-100 bg-white p-6 text-center">
                        <i class="fas fa-folder-open mb-2 text-3xl opacity-50"></i>
                        <div class="text-sm text-gray-500">No groups found.</div>
                        <div class="text-xs text-gray-400 mt-1">Create a group to get started.</div>
                    </div>`;
            }
            elements.selectAllCheckboxes.forEach(cb => { cb.checked = false; cb.indeterminate = false; });
            updateBulkControls();
            return;
        }

        const rows = state.filtered.map(book => {
            const beemId = book.beem_id || book.id || '';
            const localId = book.local_id ? String(book.local_id) : '';
            const isDefault = Boolean(book.is_default);
            const isSelected = localId && state.selected.has(localId);
            const totalContacts = numberFormatter.format(Number(book.total_contacts) || 0);
            const color = book.color || '#3B82F6';
            const description = book.description ? book.description : '<span class="text-gray-400">—</span>';

            const checkboxCell = isDefault || !localId || !permissions.manageGroups
                ? ''
                : `<input type="checkbox" class="book-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        data-local-id="${localId}"
                        data-beem-id="${beemId}"
                        ${isSelected ? 'checked' : ''}>`;

            const viewBtn = `
                    <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition"
                        data-action="view"
                        data-beem-id="${beemId}"
                        data-local-id="${localId}"
                        data-name="${escapeHtml(book.name || '')}"
                        data-description="${escapeHtml(book.description || '')}">
                        <i class="fas fa-eye"></i>
                        View
                    </button>`;
            const editBtn = !permissions.manageGroups ? '' : `
                    <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition"
                        data-action="edit"
                        data-beem-id="${beemId}"
                        data-local-id="${localId}">
                        <i class="fas fa-pen"></i>
                        Edit
                    </button>`;
            const deleteBtn = !permissions.manageGroups ? '' : `
                    <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition ${isDefault ? 'opacity-50 cursor-not-allowed' : ''}"
                        data-action="delete"
                        data-beem-id="${beemId}"
                        data-local-id="${localId}"
                        ${isDefault ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>`;
            const exportBtn = !permissions.export ? '' : `
                    <button class="inline-flex items-center gap-1 rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900 transition"
                        data-action="export"
                        data-beem-id="${beemId}">
                        <i class="fas fa-download"></i>
                        Export
                    </button>`;

            const actionButtons = `
                <div class="flex justify-end gap-2">
                    ${viewBtn}
                    ${editBtn}
                    ${deleteBtn}
                    ${exportBtn}
                </div>
            `;

            return `
                <tr class="hover:bg-gray-50 transition">
                    <td class="w-12 px-4 py-3 align-top">${checkboxCell}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: ${color};"></span>
                            <div>
                                <div class="font-semibold text-gray-800">${escapeHtml(book.name || 'Unnamed Group')}</div>
                                ${isDefault ? '<div class="text-xs text-blue-600 mt-1">Default Group</div>' : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-700">${totalContacts}</td>
                    <td class="px-4 py-3 text-gray-500">${description}</td>
                    <td class="px-4 py-3">${actionButtons}</td>
                </tr>
            `;
        }).join('');

        if (elements.tableBody) {
            elements.tableBody.innerHTML = rows;
            bindTableRowEvents();
        }

        renderCards();

        const selectable = state.filtered.filter(book => !book.is_default && book.local_id).map(book => String(book.local_id));
        const selected = selectable.filter(id => state.selected.has(id));
        const indeterminate = selected.length > 0 && selected.length < selectable.length;
        const checked = Boolean(selectable.length && selected.length === selectable.length);
        elements.selectAllCheckboxes.forEach(cb => { cb.indeterminate = indeterminate; cb.checked = checked; });

        updateBulkControls();
    }

    function bindTableRowEvents() {
        if (permissions.manageGroups) {
            elements.tableBody.querySelectorAll('.book-checkbox').forEach(cb => {
                cb.addEventListener('change', (event) => {
                    const localId = event.target.dataset.localId;
                    if (!localId) return;
                    if (event.target.checked) {
                        state.selected.add(localId);
                    } else {
                        state.selected.delete(localId);
                    }
                    updateBulkControls();
                    updateSelectAllState();
                });
            });
        }

        elements.tableBody.querySelectorAll('[data-action="view"]').forEach(button => {
            button.addEventListener('click', () => {
                openContactsModal({
                    beemId: button.dataset.beemId,
                    localId: button.dataset.localId,
                    name: button.dataset.name || '',
                    description: button.dataset.description || '',
                });
            });
        });

        if (permissions.manageGroups) {
            elements.tableBody.querySelectorAll('[data-action="edit"]').forEach(button => {
                button.addEventListener('click', () => {
                    const book = findAddressBook(button.dataset.beemId, button.dataset.localId);
                    if (book) {
                        openAddressBookModal('edit', book);
                    }
                });
            });
        }

        if (permissions.manageGroups) {
            elements.tableBody.querySelectorAll('[data-action="delete"]').forEach(button => {
                button.addEventListener('click', () => {
                    const book = findAddressBook(button.dataset.beemId, button.dataset.localId);
                    if (book) {
                        handleDeleteAddressBook(book);
                    }
                });
            });
        }

        if (permissions.export) {
            elements.tableBody.querySelectorAll('[data-action="export"]').forEach(button => {
                button.addEventListener('click', () => {
                    const beemId = button.dataset.beemId;
                    if (!beemId) return;
                    window.location.href = routes.export.replace('__ADDRESS_BOOK__', encodeURIComponent(beemId));
                });
            });
        }
    }

    function renderCards() {
        if (!elements.cardContainer) return;

        const cards = state.filtered.map(book => {
            const beemId = book.beem_id || book.id || '';
            const localId = book.local_id ? String(book.local_id) : '';
            const isDefault = Boolean(book.is_default);
            const isSelected = localId && state.selected.has(localId);
            const totalContacts = numberFormatter.format(Number(book.total_contacts) || 0);
            const color = book.color || '#3B82F6';
            const description = book.description ? escapeHtml(book.description) : '—';

            const checkbox = isDefault || !localId || !permissions.manageGroups ? '' : `
                <input type="checkbox" class="card-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    data-local-id="${localId}" data-beem-id="${beemId}" ${isSelected ? 'checked' : ''}>
            `;

            return `
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: ${color};"></span>
                            <div>
                                <div class="font-semibold text-gray-800">${escapeHtml(book.name || 'Unnamed Group')}</div>
                                ${isDefault ? '<div class="text-xs text-blue-600 mt-1">Default Group</div>' : ''}
                            </div>
                        </div>
                        ${checkbox}
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Total Contacts</div>
                            <div class="font-medium text-gray-800">${totalContacts}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Description</div>
                            <div class="text-gray-600">${description}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition"
                            data-action="view" data-beem-id="${beemId}" data-local-id="${localId}"
                            data-name="${escapeHtml(book.name || '')}" data-description="${escapeHtml(book.description || '')}">
                            <i class="fas fa-eye"></i> View
                        </button>
                        ${permissions.manageGroups ? `
                        <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition"
                            data-action="edit" data-beem-id="${beemId}" data-local-id="${localId}">
                            <i class="fas fa-pen"></i> Edit
                        </button>` : ''}
                        ${permissions.manageGroups ? `
                        <button class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition ${isDefault ? 'opacity-50 cursor-not-allowed' : ''}"
                            data-action="delete" data-beem-id="${beemId}" data-local-id="${localId}" ${isDefault ? 'disabled' : ''}>
                            <i class="fas fa-trash"></i> Delete
                        </button>` : ''}
                        ${permissions.export ? `
                        <button class="inline-flex items-center gap-1 rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900 transition"
                            data-action="export" data-beem-id="${beemId}">
                            <i class="fas fa-download"></i> Export
                        </button>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        elements.cardContainer.innerHTML = cards;
        bindCardEvents();
        updateSelectAllState();
    }

    function bindCardEvents() {
        if (permissions.manageGroups) {
            elements.cardContainer.querySelectorAll('.card-checkbox').forEach(cb => {
                cb.addEventListener('change', (event) => {
                    const localId = event.target.dataset.localId;
                    if (!localId) return;
                    if (event.target.checked) {
                        state.selected.add(localId);
                    } else {
                        state.selected.delete(localId);
                    }
                    updateBulkControls();
                    updateSelectAllState();
                });
            });
        }

        elements.cardContainer.querySelectorAll('[data-action="view"]').forEach(button => {
            button.addEventListener('click', () => {
                openContactsModal({
                    beemId: button.dataset.beemId,
                    localId: button.dataset.localId,
                    name: button.dataset.name || '',
                    description: button.dataset.description || '',
                });
            });
        });

        if (permissions.manageGroups) {
            elements.cardContainer.querySelectorAll('[data-action="edit"]').forEach(button => {
                button.addEventListener('click', () => {
                    const book = findAddressBook(button.dataset.beemId, button.dataset.localId);
                    if (book) {
                        openAddressBookModal('edit', book);
                    }
                });
            });
        }

        if (permissions.manageGroups) {
            elements.cardContainer.querySelectorAll('[data-action="delete"]').forEach(button => {
                button.addEventListener('click', () => {
                    const book = findAddressBook(button.dataset.beemId, button.dataset.localId);
                    if (book) {
                        handleDeleteAddressBook(book);
                    }
                });
            });
        }

        if (permissions.export) {
            elements.cardContainer.querySelectorAll('[data-action="export"]').forEach(button => {
                button.addEventListener('click', () => {
                    const beemId = button.dataset.beemId;
                    if (!beemId) return;
                    window.location.href = routes.export.replace('__ADDRESS_BOOK__', encodeURIComponent(beemId));
                });
            });
        }
    }

    function updateSelectAllState() {
        const selectable = state.filtered.filter(book => !book.is_default && book.local_id).map(book => String(book.local_id));
        const selected = selectable.filter(id => state.selected.has(id));
        const indeterminate = selected.length > 0 && selected.length < selectable.length;
        const checked = Boolean(selectable.length && selected.length === selectable.length);
        elements.selectAllCheckboxes.forEach(cb => { cb.indeterminate = indeterminate; cb.checked = checked; });
    }

    function updateBulkControls() {
        const selectedCount = state.selected.size;
        elements.applyBulkBtn.disabled = !selectedCount || !elements.bulkActionSelect.value;
        if (selectedCount) {
            elements.bulkSelectedLabel.textContent = `${selectedCount} group${selectedCount === 1 ? '' : 's'} selected`;
            elements.bulkSelectedLabel.classList.remove('hidden');
        } else {
            elements.bulkSelectedLabel.classList.add('hidden');
        }
    }

    function applyBulkAction() {
        const action = elements.bulkActionSelect.value;
        if (!action || !state.selected.size) {
            return;
        }

        if (!permissions.manageGroups) {
            showToast('You do not have permission to manage groups.', 'error');
            return;
        }

        if (action === 'bulk-delete') {
            const ids = Array.from(state.selected);
            const booksToDelete = ids.map(localId => state.items.find(book => String(book.local_id) === localId)).filter(Boolean);

            if (!booksToDelete.length) {
                showToast('Nothing selected to delete.', 'warning');
                return;
            }

            const confirmation = confirm(`Delete ${booksToDelete.length} group${booksToDelete.length === 1 ? '' : 's'}?`);
            if (!confirmation) {
                return;
            }

            (async () => {
                for (const book of booksToDelete) {
                    try {
                        await deleteAddressBook(book);
                    } catch (error) {
                        console.error(error);
                        showToast(error.message || 'Failed to delete group.', 'error');
                    }
                }
                await refreshAddressBooks(false);
                state.selected.clear();
                updateBulkControls();
            })();
        }
    }

    function openAddressBookModal(mode, book = null) {
        elements.modal.mode.value = mode;
        if (mode === 'edit' && book) {
            elements.modal.title.textContent = 'Edit Group';
            elements.modal.name.value = book.name || '';
            elements.modal.description.value = book.description || '';
            elements.modal.color.value = book.color || '#3B82F6';
            elements.modal.localId.value = book.local_id || '';
            elements.modal.beemId.value = book.beem_id || book.id || '';
        } else {
            elements.modal.title.textContent = 'Add Group';
            elements.modal.name.value = '';
            elements.modal.description.value = '';
            elements.modal.color.value = '#3B82F6';
            elements.modal.localId.value = '';
            elements.modal.beemId.value = '';
        }

        elements.modal.root.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => elements.modal.name.focus(), 100);
    }

    function closeAddressBookModal() {
        elements.modal.root.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        elements.modal.saveBtn.disabled = false;
    }

    async function handleAddressBookSubmit(event) {
        event.preventDefault();
        if (elements.modal.saveBtn.disabled) {
            return;
        }

        const mode = elements.modal.mode.value;
        const name = elements.modal.name.value.trim();
        const description = elements.modal.description.value.trim();
        const color = elements.modal.color.value || '#3B82F6';
        const localId = elements.modal.localId.value;

        if (!name) {
            elements.modal.name.focus();
            showToast('Please provide a group name.', 'warning');
            return;
        }

        try {
            elements.modal.saveBtn.disabled = true;
            const payload = {
                name,
                description: description || null,
                color,
            };

            let response;
            if (mode === 'edit' && localId) {
                const updateRoute = routes.update.replace('__GROUP_ID__', encodeURIComponent(localId));
                response = await fetch(updateRoute, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
            } else {
                response = await fetch(routes.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
            }

            // Robustly parse the response and surface useful validation errors
            let data;
            try {
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(text || 'Unexpected response from server.');
                }
            } catch (parseError) {
                throw new Error(parseError.message || 'Failed to parse server response.');
            }

            if (!response.ok || !data.success) {
                let message = data.message || 'Unable to save group.';
                // Laravel validation errors come as an object: { field: [msg] }
                if (data.errors) {
                    if (Array.isArray(data.errors)) {
                        message = data.errors[0] || message;
                    } else {
                        try {
                            const first = Object.values(data.errors).flat()[0];
                            if (first) message = first;
                        } catch (_) {}
                    }
                }
                throw new Error(message);
            }

            if (data.group) {
                upsertAddressBook(data.group);
                filterAddressBooks(elements.searchInput.value.trim().toLowerCase());
                updateStats();
            } else {
                await refreshAddressBooks(false);
            }

            closeAddressBookModal();
            showToast(mode === 'edit' ? 'Group updated.' : 'Group created.');
        } catch (error) {
            console.error(error);
            elements.modal.saveBtn.disabled = false;
            showToast(error.message || 'Failed to save group.', 'error');
        }
    }

    function upsertAddressBook(entry) {
        const beemId = entry.beem_id || entry.id;
        const index = state.items.findIndex(item => (item.beem_id || item.id) === beemId);
        if (index >= 0) {
            state.items[index] = entry;
        } else {
            state.items.push(entry);
        }
    }

    function findAddressBook(beemId, localId) {
        return state.items.find(item => (item.beem_id || item.id) === beemId || (localId && String(item.local_id) === String(localId)));
    }

    async function handleDeleteAddressBook(book) {
        if (book.is_default || (defaultAddressBookId && (book.beem_id || book.id) === defaultAddressBookId)) {
            showToast('The default group cannot be deleted.', 'warning');
            return;
        }

        if (!book.local_id) {
            showToast('Unable to resolve group locally.', 'error');
            return;
        }

        const confirmation = confirm(`Delete "${book.name}"? This action cannot be undone.`);
        if (!confirmation) {
            return;
        }

        try {
            await deleteAddressBook(book);
            await refreshAddressBooks(false);
            showToast(`Deleted "${book.name}".`);
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Failed to delete group.', 'error');
        }
    }

    async function deleteAddressBook(book) {
        const deleteRoute = routes.destroy.replace('__GROUP_ID__', encodeURIComponent(book.local_id));
        console.log('Deleting group', { book, deleteRoute });
        const response = await fetch(deleteRoute, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error((data.errors && data.errors[0]) || data.message || 'Unable to delete group.');
        }

        state.items = state.items.filter(item => item.local_id !== book.local_id);
        state.selected.delete(String(book.local_id));
        filterAddressBooks(elements.searchInput.value.trim().toLowerCase());
        updateStats();
    }

    async function refreshAddressBooks(showMessage = false) {
        if (state.loading) return;
        state.loading = true;
        elements.refreshBtn.classList.add('opacity-75');
        elements.refreshBtn.disabled = true;

        try {
            const response = await fetch(routes.list, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to load groups.');
            }

            state.items = Array.isArray(data.data) ? data.data : [];
            filterAddressBooks(elements.searchInput.value.trim().toLowerCase());
            updateStats();
            if (showMessage) {
                showToast('Groups refreshed.');
            }
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Failed to refresh groups.', 'error');
        } finally {
            state.loading = false;
            elements.refreshBtn.classList.remove('opacity-75');
            elements.refreshBtn.disabled = false;
        }
    }

    function openContactsModal({ beemId, localId, name, description }) {
        if (!beemId && !localId) {
            showToast('Unable to open contacts for this group.', 'error');
            return;
        }

        state.viewing = {
            beemId,
            localId,
            name,
            description,
            page: 1,
            perPage: 100,
            total: null,
        };

        elements.contactsModal.title.textContent = name || 'Group Contacts';
        elements.contactsModal.subtitle.textContent = description || '';
        elements.contactsModal.root.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        loadContactsPage();
    }

    function closeContactsModal() {
        elements.contactsModal.root.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        elements.contactsModal.body.innerHTML = '';
        elements.contactsModal.table.classList.add('hidden');
        elements.contactsModal.loading.classList.remove('hidden');
        elements.contactsModal.empty.classList.add('hidden');
    }

    function changeContactsPage(delta) {
        const newPage = state.viewing.page + delta;
        if (newPage < 1) return;
        state.viewing.page = newPage;
        loadContactsPage();
    }

    async function loadContactsPage() {
        elements.contactsModal.loading.classList.remove('hidden');
        elements.contactsModal.table.classList.add('hidden');
        elements.contactsModal.empty.classList.add('hidden');
        elements.contactsModal.prevBtn.disabled = true;
        elements.contactsModal.nextBtn.disabled = true;
        elements.contactsModal.pageIndicator.textContent = `Loading page ${state.viewing.page}…`;

        try {
            const addressBookParam = state.viewing.beemId || 'local';
            const contactsUrl = routes.contacts.replace('__ADDRESS_BOOK__', encodeURIComponent(addressBookParam));
            const url = new URL(contactsUrl, window.location.origin);
            url.searchParams.set('page', state.viewing.page);
            url.searchParams.set('per_page', state.viewing.perPage);
            if (state.viewing.localId) {
                url.searchParams.set('local_id', state.viewing.localId);
            }
            if (state.viewing.name) {
                url.searchParams.set('name', state.viewing.name);
            }
            if (state.viewing.description) {
                url.searchParams.set('description', state.viewing.description);
            }

            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to fetch contacts.');
            }

            const contacts = data.data?.contacts || [];
            renderContactsTable(contacts);

            const pagination = data.data?.pagination || {};
            const currentPage = Number(pagination.page || state.viewing.page);
            const perPage = Number(pagination.per_page || state.viewing.perPage);
            const count = Number(pagination.count || contacts.length);
            const total = Number(pagination.total || 0);

            state.viewing.page = currentPage;
            state.viewing.perPage = perPage;
            state.viewing.total = total || null;

            elements.contactsModal.pageIndicator.textContent = total
                ? `Page ${currentPage} · ${count} of ${total} contacts`
                : `Page ${currentPage} · ${count} contacts`;

            elements.contactsModal.prevBtn.disabled = currentPage <= 1;
            elements.contactsModal.nextBtn.disabled = contacts.length < perPage;
        } catch (error) {
            console.error(error);
            showToast(error.message || 'Failed to load contacts.', 'error');
            elements.contactsModal.pageIndicator.textContent = 'Unable to load contacts.';
            elements.contactsModal.empty.classList.remove('hidden');
        } finally {
            elements.contactsModal.loading.classList.add('hidden');
        }
    }

    function renderContactsTable(contacts) {
        if (!contacts.length) {
            elements.contactsModal.empty.classList.remove('hidden');
            elements.contactsModal.table.classList.add('hidden');
            elements.contactsModal.body.innerHTML = '';
            return;
        }

        const rows = contacts.map(contact => {
            const badge = contact.status === 'opted_out'
                ? '<span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"><i class="fas fa-ban text-[10px]"></i> Opted Out</span>'
                : '<span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"><i class="fas fa-check text-[10px]"></i> Active</span>';

            const locationParts = [contact.area, contact.city, contact.country].filter(Boolean);
            const location = locationParts.length ? escapeHtml(locationParts.join(', ')) : '<span class="text-gray-400">—</span>';

            const phone = contact.phone
                ? `<span class="${contact.valid_phone ? 'text-gray-800 font-medium' : 'text-red-600 font-medium'}">${escapeHtml(contact.phone)}</span>`
                : '<span class="text-gray-400">—</span>';

            return `
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800">${escapeHtml(contact.name || contact.phone || 'Unknown')}</div>
                        ${contact.email ? `<div class="text-xs text-gray-500 mt-1">${escapeHtml(contact.email)}</div>` : ''}
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        ${phone}
                        ${contact.additional_phone ? `<div class="text-xs text-gray-500 mt-1">Alt: ${escapeHtml(contact.additional_phone)}</div>` : ''}
                    </td>
                    <td class="px-4 py-3 text-gray-600">${contact.email ? escapeHtml(contact.email) : '<span class="text-gray-400">—</span>'}</td>
                    <td class="px-4 py-3 text-gray-600">${location}</td>
                    <td class="px-4 py-3">${badge}</td>
                </tr>
            `;
        }).join('');

        elements.contactsModal.body.innerHTML = rows;
        elements.contactsModal.table.classList.remove('hidden');
        elements.contactsModal.empty.classList.add('hidden');
    }

    function showToast(message, type = 'success') {
        if (!elements.notificationContainer) return;

        const wrapper = document.createElement('div');
        wrapper.className = `pointer-events-auto transform rounded-xl px-4 py-3 text-sm shadow-lg transition ${type === 'error' ? 'bg-red-600 text-white' : type === 'warning' ? 'bg-yellow-500 text-white' : 'bg-blue-600 text-white'}`;
        wrapper.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${type === 'error' ? 'fa-circle-exclamation' : type === 'warning' ? 'fa-triangle-exclamation' : 'fa-check'} text-sm"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;

        elements.notificationContainer.appendChild(wrapper);
        setTimeout(() => {
            wrapper.classList.add('opacity-0', 'translate-y-1');
            setTimeout(() => wrapper.remove(), 250);
        }, 3500);
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    init();
})();
</script>
@endsection
