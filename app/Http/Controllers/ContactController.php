<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactImport;
use App\Services\BeemSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    protected $beemService;

    public function __construct(BeemSmsService $beemService)
    {
        $this->beemService = $beemService;
        $this->middleware('auth');
        $this->middleware('can:view-contacts')->only(['index', 'show']);
        $this->middleware('can:manage-contacts')->only(['create', 'store', 'edit', 'update', 'destroy', 'bulkDelete']);
        $this->middleware('can:manage-contact-groups')->only(['createGroup', 'storeGroup', 'updateGroup', 'destroyGroup']);
    }

    /**
     * Display the contacts management page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $syncError = null;
        $addressBooks = [];
        $canSyncRemote = $user->isAdmin() || $user->isReseller();

        if ($canSyncRemote) {
            try {
                $remoteAddressBooks = $this->beemService->getAddressBooks();
                if (is_array($remoteAddressBooks)) {
                    $addressBooks = $this->syncBeemAddressBooks($remoteAddressBooks, $user->id, true);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch Beem address books: ' . $e->getMessage());
                $syncError = $e->getMessage();
                $addressBooks = $this->syncBeemAddressBooks([], $user->id);
            }
        } else {
            // For regular users, do not sync vendor-level Beem address books; show only local groups
            $addressBooks = $this->syncBeemAddressBooks([], $user->id, false);
        }

        $totalFromApi = array_sum(array_map(function ($book) {
            return $book['total_contacts'] ?? 0;
        }, $addressBooks));

        // Calculate statistics (fall back to local data for active/opt-out)
        $stats = [
            'total_contacts' => $canSyncRemote ? $totalFromApi : Contact::where('user_id', $user->id)->count(),
            'active_contacts' => Contact::where('user_id', $user->id)->where('opt_out', false)->count(),
            'opted_out_contacts' => Contact::where('user_id', $user->id)->where('opt_out', true)->count(),
            'total_groups' => count($addressBooks),
            'recent_imports' => ContactImport::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        // Do not auto-create a default group on first load; keep a clean slate.
        $defaultGroup = ContactGroup::getDefaultForUser($user->id);

        return view('contacts.index', [
            'addressBooks' => $addressBooks,
            'stats' => $stats,
            'syncError' => $syncError,
            'defaultAddressBookId' => $defaultGroup?->beem_address_book_id,
        ]);
    }

    /**
     * Store a new contact.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string',
            'email' => 'nullable|email|max:255',
            'contact_group_id' => 'required|exists:contact_groups,id',
            'date_of_birth' => 'nullable|date|before:today',
        ]);

        // Set default name if not provided
        if (empty($request->name)) {
            $request->merge(['name' => 'Contact']);
        }

        // Validate and normalize phone number
        $validation = Contact::validateImportData($request->all());
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors']
            ], 422);
        }

        // Check if contact already exists
        if (Contact::existsForUser($user->id, $request->phone)) {
            return response()->json([
                'success' => false,
                'errors' => ['Phone number already exists in your contacts']
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get contact group
            $contactGroup = ContactGroup::where('id', $request->contact_group_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Create contact in Beem if address book exists (gracefully handle failures)
            $beemContactId = null;
            if ($contactGroup->beem_address_book_id) {
                try {
                    $beemResponse = $this->beemService->createContact(
                        $contactGroup->beem_address_book_id,
                        $request->name,
                        $validation['normalized_phone'],
                        $request->email
                    );

                    if (is_array($beemResponse) && ($beemResponse['success'] ?? false)) {
                        $beemContactId = $beemResponse['data']['id'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::warning('Beem createContact failed, proceeding with local contact: ' . $e->getMessage());
                }
            }

            // Create local contact
            $contact = Contact::create([
                'name' => $request->name,
                'phone' => $validation['normalized_phone'],
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'user_id' => $user->id,
                'contact_group_id' => $request->contact_group_id,
                'beem_contact_id' => $beemContactId,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully',
                'contact' => $contact->load('contactGroup')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating contact: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to create contact. Please try again.']
            ], 500);
        }
    }

    /**
     * Update an existing contact.
     */
    public function update(Request $request, Contact $contact)
    {
        $user = Auth::user();

        // Ensure user owns the contact
        if ($contact->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized']
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'email' => 'nullable|email|max:255',
            'contact_group_id' => 'required|exists:contact_groups,id',
            'date_of_birth' => 'nullable|date|before:today',
        ]);

        // Validate and normalize phone number
        $validation = Contact::validateImportData($request->all());
        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors']
            ], 422);
        }

        // Check if phone number exists for another contact
        $existingContact = Contact::where('user_id', $user->id)
            ->where('phone', $validation['normalized_phone'])
            ->where('id', '!=', $contact->id)
            ->first();

        if ($existingContact) {
            return response()->json([
                'success' => false,
                'errors' => ['Phone number already exists in your contacts']
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update contact in Beem if it exists
            if ($contact->beem_contact_id) {
                $this->beemService->updateContact(
                    $contact->beem_contact_id,
                    $request->name,
                    $validation['normalized_phone'],
                    $request->email
                );
            }

            // Update local contact
            $contact->update([
                'name' => $request->name,
                'phone' => $validation['normalized_phone'],
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'contact_group_id' => $request->contact_group_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'contact' => $contact->load('contactGroup')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating contact: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to update contact. Please try again.']
            ], 500);
        }
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact)
    {
        $user = Auth::user();

        // Ensure user owns the contact
        if ($contact->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized']
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Delete contact from Beem if it exists
            if ($contact->beem_contact_id) {
                $this->beemService->deleteContact($contact->beem_contact_id);
            }

            // Delete local contact
            $contact->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting contact: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to delete contact. Please try again.']
            ], 500);
        }
    }

    /**
     * Store a new contact group.
     */
    public function storeGroup(Request $request)
    {
        // Increase execution time for this operation
        set_time_limit(120); // 2 minutes
        
        $user = Auth::user();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
        ]);

        DB::beginTransaction();
        try {
            $canSyncRemote = $user->isAdmin() || $user->isReseller();
            // Attempt to create address book in Beem for admin/reseller only
            $beemResponse = null;
            if ($canSyncRemote) {
                try {
                    $beemResponse = $this->beemService->createAddressBook(
                        $request->name,
                        $request->description
                    );
                } catch (\Exception $e) {
                    Log::warning('Beem createAddressBook failed, proceeding with local group: ' . $e->getMessage());
                }
            }

            // Gracefully handle Beem failures by allowing local group creation
            $addressBookData = is_array($beemResponse) ? ($beemResponse['data'] ?? $beemResponse) : [];
            $beemAddressBookId = data_get($addressBookData, 'id');

            // Ensure unique name per user by auto-suffixing if needed
            $baseName = data_get($addressBookData, 'name', $request->name);
            $finalName = trim((string) $baseName) !== '' ? trim($baseName) : 'Address Book ' . now()->format('YmdHis');
            $counter = 1;
            while (ContactGroup::where('user_id', $user->id)->where('name', $finalName)->exists()) {
                $finalName = $baseName . ' (' . $counter . ')';
                $counter++;
            }

            // Create local contact group
            $contactGroup = ContactGroup::create([
                'name' => $finalName,
                'description' => data_get($addressBookData, 'description', $request->description),
                'color' => $request->color,
                'user_id' => $user->id,
                'beem_address_book_id' => $beemAddressBookId,
                'is_default' => false,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact group created successfully',
                'group' => $this->formatAddressBook($addressBookData, $contactGroup)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating contact group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to create contact group. Please try again.']
            ], 500);
        }
    }

    /**
     * Update a contact group.
     */
    public function updateGroup(Request $request, ContactGroup $contactGroup)
    {
        $user = Auth::user();

        // Ensure user owns the contact group
        if ($contactGroup->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized']
            ], 403);
        }

        // Prevent updating default group name
        $rules = [
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
        ];

        if (!$contactGroup->is_default) {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('contact_groups')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })->ignore($contactGroup->id)
            ];
        }

        $request->validate($rules);

        try {
            $updateData = [
                'description' => $request->description,
                'color' => $request->color,
            ];

            if (!$contactGroup->is_default) {
                $updateData['name'] = $request->name;
            }

            if ($contactGroup->beem_address_book_id) {
                $payload = [];
                if (!$contactGroup->is_default && isset($updateData['name'])) {
                    $payload['name'] = $updateData['name'];
                }
                if (!is_null($request->description)) {
                    $payload['description'] = $request->description;
                }

                if (!empty($payload)) {
                    // Attempt to update in Beem, but continue locally if it fails
                    try {
                        $this->beemService->updateAddressBook($contactGroup->beem_address_book_id, $payload);
                    } catch (\Exception $e) {
                        Log::warning('Beem updateAddressBook failed, proceeding with local update: ' . $e->getMessage());
                    }
                }
            }

            $contactGroup->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Contact group updated successfully',
                'group' => $this->formatAddressBook([], $contactGroup)
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating contact group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to update contact group. Please try again.']
            ], 500);
        }
    }

    /**
     * Delete a contact group.
     */
    public function destroyGroup(ContactGroup $contactGroup)
    {
        $user = Auth::user();
        Log::info('DestroyGroup called', [
            'group_id' => $contactGroup->id,
            'group_user_id' => (int) $contactGroup->user_id,
            'auth_user_id' => (int) $user->id,
            'is_default' => (bool) $contactGroup->is_default,
            'beem_address_book_id' => $contactGroup->beem_address_book_id,
        ]);

        // If the bound group belongs to another user but references a Beem address book,
        // switch to (or create) the current user's local reference so they can manage it.
        if ((int) $contactGroup->user_id !== (int) $user->id && !empty($contactGroup->beem_address_book_id)) {
            $userGroup = ContactGroup::firstOrNew([
                'user_id' => $user->id,
                'beem_address_book_id' => $contactGroup->beem_address_book_id,
            ]);

            if (!$userGroup->exists) {
                $fallbackName = trim((string) $contactGroup->name);
                if ($fallbackName === '') {
                    $fallbackName = 'Address Book ' . substr($contactGroup->beem_address_book_id, -6);
                }
                $userGroup->name = $fallbackName;
                $userGroup->description = $contactGroup->description;
                $userGroup->color = $contactGroup->color ?: '#3B82F6';
                // Never treat a synced book as default when creating a local reference
                $userGroup->is_default = false;
                $userGroup->is_active = true;
                $userGroup->save();
            }

            Log::info('DestroyGroup switching to user-owned local reference', [
                'original_group_id' => $contactGroup->id,
                'new_group_id' => $userGroup->id,
                'auth_user_id' => (int) $user->id,
            ]);

            $contactGroup = $userGroup;
        }

        // If still not owned and there is no Beem book, create a lightweight local copy
        if ((int) $contactGroup->user_id !== (int) $user->id && empty($contactGroup->beem_address_book_id)) {
            $fallbackName = trim((string) $contactGroup->name);
            if ($fallbackName === '') {
                $fallbackName = 'Group ' . now()->format('YmdHis');
            }
            $localGroup = ContactGroup::firstOrCreate([
                'user_id' => $user->id,
                'name' => $fallbackName,
            ], [
                'description' => $contactGroup->description,
                'color' => $contactGroup->color ?: '#3B82F6',
                'is_default' => false,
                'is_active' => true,
            ]);

            Log::info('DestroyGroup created local copy for user', [
                'original_group_id' => $contactGroup->id,
                'new_group_id' => $localGroup->id,
                'auth_user_id' => (int) $user->id,
            ]);

            $contactGroup = $localGroup;
        }

        // Ensure user owns the contact group
        if ((int) $contactGroup->user_id !== (int) $user->id && !$user->isAdmin() && !$user->isReseller()) {
            Log::warning('DestroyGroup unauthorized', [
                'group_id' => $contactGroup->id,
                'group_user_id' => (int) $contactGroup->user_id,
                'auth_user_id' => (int) $user->id,
                'auth_role' => $user->role,
            ]);
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized']
            ], 403);
        }

        Log::info('DestroyGroup authorized', [
            'group_id' => $contactGroup->id,
            'auth_user_id' => (int) $user->id,
        ]);

        // Prevent deleting default group
        if ($contactGroup->is_default) {
            return response()->json([
                'success' => false,
                'errors' => ['Cannot delete the default contact group']
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Move contacts to default group
            $defaultGroup = ContactGroup::getDefaultForUser($user->id);
            if (!$defaultGroup) {
                $defaultGroup = ContactGroup::createDefaultForUser($user->id);
            }

            Contact::where('contact_group_id', $contactGroup->id)
                ->update(['contact_group_id' => $defaultGroup->id]);

            if ($contactGroup->beem_address_book_id) {
                try {
                    $this->beemService->deleteAddressBook($contactGroup->beem_address_book_id);
                } catch (\Exception $e) {
                    \Log::warning('Beem deleteAddressBook failed, hiding locally');
                }
                $contactGroup->update(['is_active' => false]);
            } else {
                $contactGroup->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact group deleted successfully. Contacts moved to default group.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting contact group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Failed to delete contact group. Please try again.']
            ], 500);
        }
    }

    /**
     * Provide Beem address books for the dashboard via AJAX.
     */
    public function listAddressBooks(Request $request)
    {
        $user = Auth::user();
        $search = trim($request->query('search', ''));
        $canSyncRemote = $user->isAdmin() || $user->isReseller();

        try {
            $remoteAddressBooks = $canSyncRemote ? $this->beemService->getAddressBooks() : [];
            $addressBooks = $this->syncBeemAddressBooks(
                is_array($remoteAddressBooks) ? $remoteAddressBooks : [],
                $user->id,
                $canSyncRemote
            );

            if ($search !== '') {
                $addressBooks = array_values(array_filter($addressBooks, function ($book) use ($search) {
                    $name = strtolower($book['name'] ?? '');
                    $description = strtolower($book['description'] ?? '');
                    $needles = strtolower($search);
                    return str_contains($name, $needles) || str_contains($description, $needles);
                }));
            }

            return response()->json([
                'success' => true,
                'data' => $addressBooks,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving Beem address books: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to load address books at the moment.',
            ], 502);
        }
    }

    /**
     * Fetch contacts for the requested address book from Beem.
     */
    public function fetchAddressBookContacts(Request $request, string $addressBookId)
    {
        $user = Auth::user();
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(1, (int) $request->query('per_page', 100)));
        $addressBookName = $request->query('name');

        // Local contacts mode: when frontend passes 'local' and a local_id
        if ($addressBookId === 'local') {
            $localId = (int) $request->query('local_id');
            if (!$localId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing local address book identifier.',
                ], 422);
            }

            $contactGroup = ContactGroup::where('id', $localId)
                ->where('user_id', $user->id)
                ->first();

            if (!$contactGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address book not found.',
                ], 404);
            }

            $query = Contact::where('user_id', $user->id)
                ->where('contact_group_id', $contactGroup->id)
                ->orderBy('id', 'desc');

            $total = (clone $query)->count();
            $contacts = $query->forPage($page, $perPage)->get()->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'additional_phone' => data_get($c->metadata, 'additional_phone'),
                    'email' => $c->email,
                    'gender' => data_get($c->metadata, 'gender'),
                    'birth_date' => optional($c->date_of_birth)->format('Y-m-d'),
                    'area' => data_get($c->metadata, 'area'),
                    'city' => data_get($c->metadata, 'city'),
                    'country' => data_get($c->metadata, 'country'),
                    'status' => $c->opt_out ? 'opted_out' : 'active',
                    'created_at' => optional($c->created_at)->toDateTimeString(),
                    'valid_phone' => !empty($c->phone),
                ];
            })->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'contacts' => $contacts,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'count' => count($contacts),
                        'total' => $total,
                    ],
                ],
            ]);
        }

        // Remote Beem mode
        try {
            $response = $this->beemService->getContacts($addressBookId, $page, $perPage, true);
            $contacts = $response['data'] ?? [];
            $pagination = $response['meta'] ?? [];

            $contactGroup = ContactGroup::firstOrNew([
                'user_id' => $user->id,
                'beem_address_book_id' => $addressBookId,
            ]);

            if (!$contactGroup->exists) {
                $contactGroup->name = $addressBookName ?: 'Address Book ' . substr($addressBookId, -6);
                $contactGroup->description = $request->query('description');
                $contactGroup->color = $contactGroup->color ?: '#3B82F6';
                $contactGroup->is_active = true;
                $contactGroup->save();
            }

            $formattedContacts = $this->syncContactsFromBeem($contacts, $contactGroup);

            return response()->json([
                'success' => true,
                'data' => [
                    'contacts' => $formattedContacts,
                    'pagination' => array_merge([
                        'page' => $page,
                        'per_page' => $perPage,
                        'count' => count($formattedContacts),
                    ], $pagination),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching contacts for address book {$addressBookId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to load contacts at the moment.',
            ], 502);
        }
    }

    /**
     * Export contacts for an address book directly from Beem.
     */
    public function exportAddressBookContacts($addressBookId)
    {
        $user = Auth::user();

        // Ensure the user has a local reference, create one if needed
        $contactGroup = ContactGroup::firstOrNew([
            'user_id' => $user->id,
            'beem_address_book_id' => $addressBookId,
        ]);

        if (!$contactGroup->exists) {
            $contactGroup->name = 'Address Book ' . substr($addressBookId, -6);
            $contactGroup->color = '#3B82F6';
            $contactGroup->is_active = true;
            $contactGroup->save();
        }

        try {
            $contacts = $this->fetchAllBeemContacts($addressBookId);
        } catch (\Exception $e) {
            Log::error("Error exporting contacts for address book {$addressBookId}: " . $e->getMessage());

            return redirect()->back()->withErrors([
                'export' => 'Unable to export contacts from Beem at the moment. Please try again shortly.',
            ]);
        }

        $filename = Str::slug($contactGroup->name ?? 'address-book') . '_contacts_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'First Name',
                'Last Name',
                'Phone',
                'Additional Phone',
                'Email',
                'Gender',
                'DOB',
                'Area',
                'City',
                'Country',
                'Status',
            ]);

            foreach ($contacts as $contact) {
                $status = data_get($contact, 'is_opted_out') ? 'opted_out' : 'active';
                fputcsv($handle, [
                    data_get($contact, 'first_name'),
                    data_get($contact, 'last_name'),
                    data_get($contact, 'mob_no'),
                    data_get($contact, 'mob_no2'),
                    data_get($contact, 'email'),
                    data_get($contact, 'gender'),
                    data_get($contact, 'birth_date'),
                    data_get($contact, 'area'),
                    data_get($contact, 'city'),
                    data_get($contact, 'country'),
                    $status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact)
    {
        // Ensure contact belongs to authenticated user
        if ($contact->user_id !== auth()->id()) {
            abort(403);
        }
        
        $contact->load('contactGroup');
        
        return view('contacts.show', compact('contact'));
    }

    /**
     * Export contacts to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        $query = Contact::where('user_id', $user->id)
            ->with('contactGroup');

        if ($request->has('group_id') && $request->group_id) {
            $query->where('contact_group_id', $request->group_id);
        }

        $contacts = $query->get();

        $filename = 'contacts_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($contacts) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Name',
                'Phone',
                'Email',
                'Date of Birth',
                'Contact Group',
                'Created At'
            ]);

            // Add contact data
            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->name,
                    $contact->phone,
                    $contact->email,
                    $contact->date_of_birth ? $contact->date_of_birth->format('Y-m-d') : '',
                    $contact->contactGroup->name ?? '',
                    $contact->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Sync remote Beem address books to local models and return formatted payload.
     */
    protected function syncBeemAddressBooks(array $remoteAddressBooks, int $userId, bool $includeBeemLinked = true): array
    {
        $formatted = [];
        $syncedBeemIds = [];
        $hiddenBeemIds = ContactGroup::where('user_id', $userId)
            ->whereNotNull('beem_address_book_id')
            ->where('is_active', false)
            ->pluck('beem_address_book_id')
            ->filter()
            ->toArray();

        foreach ($remoteAddressBooks as $remote) {
            $beemId = data_get($remote, 'id');
            if (!$beemId) {
                continue;
            }

            $syncedBeemIds[] = $beemId;
            if (in_array($beemId, $hiddenBeemIds, true)) {
                continue;
            }

            $contactGroup = ContactGroup::firstOrNew([
                'user_id' => $userId,
                'beem_address_book_id' => $beemId,
            ]);

            $contactGroup->name = data_get($remote, 'name', $contactGroup->name ?: 'Address Book');
            $contactGroup->description = data_get($remote, 'description', $contactGroup->description);
            $contactGroup->is_active = true;
            if (!$contactGroup->color) {
                $contactGroup->color = '#3B82F6';
            }
            if (!$contactGroup->exists) {
                $contactGroup->is_default = (bool) data_get($remote, 'is_default', false);
            }
            $contactGroup->save();

            $formatted[] = $this->formatAddressBook($remote, $contactGroup);
        }

        // Include local groups that may not exist remotely (default or legacy)
        $localGroups = ContactGroup::where('user_id', $userId)
            ->where('is_active', true)
            ->when(!$includeBeemLinked, function ($q) {
                $q->whereNull('beem_address_book_id');
            })
            ->get();
        foreach ($localGroups as $group) {
            if ($group->beem_address_book_id && in_array($group->beem_address_book_id, $syncedBeemIds, true)) {
                continue;
            }
            $formatted[] = $this->formatAddressBook([], $group);
        }

        usort($formatted, function ($a, $b) {
            $aDefault = $a['is_default'] ?? false;
            $bDefault = $b['is_default'] ?? false;
            if ($aDefault !== $bDefault) {
                return $aDefault ? -1 : 1;
            }
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return $formatted;
    }

    /**
     * Normalize address book payload for frontend consumption.
     */
    protected function formatAddressBook(array $remote, ?ContactGroup $contactGroup = null): array
    {
        $beemId = data_get($remote, 'id') ?? $contactGroup?->beem_address_book_id;
        $contactCount = data_get($remote, 'total_contacts')
            ?? data_get($remote, 'contacts_count')
            ?? data_get($remote, 'contacts')
            ?? ($contactGroup ? $contactGroup->contacts()->count() : 0);

        return [
            'id' => $beemId,
            'beem_id' => $beemId,
            'local_id' => $contactGroup?->id,
            'name' => data_get($remote, 'name', $contactGroup?->name),
            'description' => data_get($remote, 'description', $contactGroup?->description),
            'total_contacts' => is_numeric($contactCount) ? (int) $contactCount : 0,
            'color' => $contactGroup?->color ?? '#3B82F6',
            'is_default' => (bool) ($contactGroup?->is_default ?? false),
            'created_at' => data_get($remote, 'created_at'),
            'updated_at' => data_get($remote, 'updated_at'),
        ];
    }

    /**
     * Sync a batch of Beem contacts into local storage and return formatted array.
     */
    protected function syncContactsFromBeem(array $contacts, ContactGroup $contactGroup): array
    {
        $formatted = [];

        foreach ($contacts as $contact) {
            $rawPhone = data_get($contact, 'mob_no') ?? data_get($contact, 'phone');
            $normalizedPhone = $rawPhone ? Contact::normalizePhoneNumber($rawPhone) : null;

            $nameParts = array_filter([
                data_get($contact, 'title'),
                data_get($contact, 'first_name'),
                data_get($contact, 'last_name'),
            ]);
            $fullName = trim(implode(' ', $nameParts));

            if ($normalizedPhone) {
                $localContact = Contact::firstOrNew([
                    'user_id' => $contactGroup->user_id,
                    'phone' => $normalizedPhone,
                ]);

                $localContact->name = $fullName ?: $normalizedPhone;
                $localContact->email = data_get($contact, 'email');

                $birthDate = data_get($contact, 'birth_date');
                if ($birthDate) {
                    try {
                        $localContact->date_of_birth = Carbon::parse($birthDate)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Ignore invalid dates
                    }
                }

                $localContact->contact_group_id = $contactGroup->id;
                $localContact->beem_contact_id = data_get($contact, 'id', $localContact->beem_contact_id);
                $localContact->opt_out = (bool) data_get($contact, 'is_opted_out', false);
                $localContact->is_active = !$localContact->opt_out;

                $metadata = array_filter([
                    'gender' => data_get($contact, 'gender'),
                    'area' => data_get($contact, 'area'),
                    'city' => data_get($contact, 'city'),
                    'country' => data_get($contact, 'country'),
                    'additional_phone' => data_get($contact, 'mob_no2'),
                ], fn ($value) => !is_null($value) && $value !== '');

                $localContact->metadata = array_merge($localContact->metadata ?? [], $metadata);
                $localContact->save();
            }

            $formatted[] = [
                'id' => data_get($contact, 'id'),
                'name' => $fullName ?: ($normalizedPhone ?? $rawPhone),
                'phone' => $normalizedPhone ?? $rawPhone,
                'raw_phone' => $rawPhone,
                'additional_phone' => data_get($contact, 'mob_no2'),
                'email' => data_get($contact, 'email'),
                'gender' => data_get($contact, 'gender'),
                'birth_date' => data_get($contact, 'birth_date'),
                'area' => data_get($contact, 'area'),
                'city' => data_get($contact, 'city'),
                'country' => data_get($contact, 'country'),
                'status' => data_get($contact, 'is_opted_out') ? 'opted_out' : 'active',
                'created_at' => data_get($contact, 'created_at'),
                'valid_phone' => !is_null($normalizedPhone),
            ];
        }

        return $formatted;
    }

    /**
     * Retrieve all Beem contacts for an address book by paging through results.
     */
    protected function fetchAllBeemContacts(string $addressBookId): array
    {
        $allContacts = [];
        $page = 1;
        $limit = 200;

        do {
            $response = $this->beemService->getContacts($addressBookId, $page, $limit, true);
            $batch = $response['data'] ?? [];
            $allContacts = array_merge($allContacts, $batch);
            $page++;
        } while (count($batch) === $limit && !empty($batch));

        return $allContacts;
    }
}
