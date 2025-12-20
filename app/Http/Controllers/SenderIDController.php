<?php

namespace App\Http\Controllers;

use App\Models\SenderID;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\BeemSmsService;
use Illuminate\Support\Facades\Mail;

class SenderIDController extends Controller
{
    /**
     * Display a listing of sender IDs for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        $senderIds = SenderID::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('sender-ids.index', compact('senderIds'));
    }

    /**
     * Show the form for creating a new sender ID application
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check if user has pending applications
        $pendingCount = SenderID::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        return view('sender-ids.create', compact('pendingCount'));
    }

    /**
     * Store a newly created sender ID application
     */
    public function store(Request $request)
    {
        // Validate simplified Beem-style inputs
        $validated = $request->validate([
            'sender_name' => ['required','string','max:11','regex:/^[A-Za-z0-9 .-]+$/','unique:sender_ids,sender_name'],
            'sender_description' => 'required|string|min:15|max:170',
            'business_name' => 'nullable|string|max:255',
            'company_url' => 'nullable|url|max:255',
            'use_case_category' => 'required|in:marketing,transactional,otp,alerts,notifications,two_way,other',
        ]);

        $user = Auth::user();
        $beemService = new BeemSmsService();
        $senderName = preg_replace('/\s+/', ' ', trim($validated['sender_name']));

        // Check Beem naming rules before we do anything costly
        $senderValidation = $beemService->validateSenderName($senderName);
        if (!($senderValidation['valid'] ?? false)) {
            return back()->withErrors([
                'sender_name' => implode(' ', $senderValidation['errors'] ?? ['Invalid sender name.'])
            ])->withInput();
        }

        // Limit to at most 3 pending applications
        $pendingCount = SenderID::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingCount >= 3) {
            return back()->withErrors(['sender_name' => 'You can only have 3 pending applications at a time.']);
        }

        // Combine category and description for a clear use-case text
        $categoryLabels = [
            'marketing' => 'Marketing',
            'transactional' => 'Transactional',
            'otp' => 'OTP',
            'alerts' => 'Alerts',
            'notifications' => 'Notifications',
            'two_way' => 'Two-way',
            'other' => 'Other',
        ];
        $useCaseText = $categoryLabels[$validated['use_case_category']] . ': ' . $validated['sender_description'];

        $adminNotes = !empty($validated['company_url']) ? ('Company URL: ' . $validated['company_url']) : null;

        try {
            DB::beginTransaction();

            $senderID = SenderID::create([
                'user_id' => $user->id,
                // Legacy column kept in schema; mirror value to avoid NOT NULL errors
                'sender_id' => $senderName,
                'sender_name' => $senderName,
                'business_name' => $validated['business_name'] ?? null,
                'business_type' => 'other',
                'use_case' => $useCaseText,
                'sample_messages' => $validated['sender_description'],
                'target_countries' => ['TZ'],
                'attachment_path' => null,
                'status' => 'pending',
                'admin_notes' => $adminNotes,
            ]);

            DB::commit();

            Log::info('Sender ID application submitted (simplified)', [
                'user_id' => $user->id,
                'sender_id' => $senderID->id,
                'sender_name' => $senderID->sender_name
            ]);
            // Notify user: application received
            if (!empty($user->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::raw(
                        "Hello {$user->name},\n\nWe’ve received your Sender ID application '{$senderID->sender_name}'. You’ll be notified once it’s reviewed.\n\nRegards, RodLine SMS",
                        function ($m) use ($user) { $m->to($user->email)->subject('Sender ID Application Received'); }
                    );
                } catch (\Throwable $ex) {}
            }
            if (!empty($user->phone)) {
                try {
                    $beemSms = new BeemSmsService();
                    $beemSms->sendSms($user->phone, 'Your Sender ID application has been received and is under review.', config('services.beem.default_sender_id'));
                } catch (\Throwable $ex) {}
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sender ID application error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'An error occurred while submitting your application. Please try again.'])
                ->withInput();
        }

        // Attempt to push the request to Beem for verification
        $beemResponse = $beemService->requestSenderName($senderID->sender_name, $validated['sender_description']);

        $messageKey = 'success';
        $messageText = 'Sender ID application submitted successfully! Reference: ' . $senderID->reference_number;

        if ($beemResponse['success'] ?? false) {
            $beemData = $beemResponse['data'] ?? [];
            $beemId = data_get($beemData, 'data.id')
                ?? data_get($beemData, 'data.senderid')
                ?? data_get($beemData, 'id');

            $notes = collect([
                $adminNotes,
                'Beem request submitted on ' . now()->format('d M Y H:i')
            ])->filter()->implode(' | ');

            $senderID->update([
                'beem_sender_id' => $beemId,
                'admin_notes' => $notes ?: null,
            ]);
        } else {
            $errorDetail = $beemResponse['error'] ?? 'Unknown error from Beem API';
            $notes = collect([
                $adminNotes,
                'Beem request failed: ' . $errorDetail
            ])->filter()->implode(' | ');

            $senderID->update([
                'admin_notes' => $notes ?: null,
            ]);

            // Graceful UX: do not surface provider error; application is pending
            $messageKey = 'success';
            $messageText = 'Sender ID application submitted. We will notify you by email once reviewed.';
        }

        return redirect()->route('sender-ids.show', $senderID)->with($messageKey, $messageText);
    }

    /**
     * Display the specified sender ID application
     */
    public function show(SenderID $senderID)
    {
        // Ensure user can only view their own applications
        $user = Auth::user();
        $isOwner = $senderID->user_id === Auth::id();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        if (!$isOwner && !$isAdmin) {
            return redirect()->route('sender-ids.index')->with('warning', 'You can only view your own sender ID applications.');
        }

        return view('sender-ids.show', compact('senderID'));
    }

    /**
     * Download supporting document
     */
    public function downloadDocument(SenderID $senderID, $documentIndex)
    {
        // Ensure user can only download their own documents
        if ($senderID->user_id !== Auth::id()) {
            abort(403);
        }

        $documents = $senderID->supporting_documents ?? [];
        
        if (!isset($documents[$documentIndex])) {
            abort(404);
        }

        $document = $documents[$documentIndex];
        
        if (!Storage::disk('private')->exists($document['path'])) {
            abort(404);
        }

        return Storage::disk('private')->download(
            $document['path'], 
            $document['original_name']
        );
    }

    /**
     * Admin: Display all sender ID applications
     */
    public function adminIndex(Request $request)
    {
        // This would be protected by admin middleware
        $query = SenderID::with('user');
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by sender name or business name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sender_name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        
        $senderIds = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total' => SenderID::count(),
            'pending' => SenderID::where('status', 'pending')->count(),
            'approved' => SenderID::where('status', 'approved')->count(),
            'rejected' => SenderID::where('status', 'rejected')->count(),
        ];

        return view('admin.sender-ids.index', compact('senderIds', 'stats'));
    }

    /**
     * Admin: Update sender ID application status
     */
    public function updateStatus(Request $request, SenderID $senderID)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,suspended',
            'admin_notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'required_if:status,rejected|string|max:500'
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $senderID->status;
            
            $senderID->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
                'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id()
            ]);
            
            // Log status change
            Log::info('Sender ID status updated', [
                'sender_id' => $senderID->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'admin_id' => Auth::id()
            ]);
            
            DB::commit();

            $user = User::find($senderID->user_id);
            if ($user && !empty($user->email)) {
                try {
                    $statusLabel = ucfirst($request->status);
                    $html = '<h2>Sender ID Status Updated</h2>'
                        . '<p>Your Sender ID <strong>' . e($senderID->sender_name) . '</strong> is now <strong>' . e($statusLabel) . '</strong>.</p>'
                        . ($request->status === 'approved' ? '<p>You can now use it in your campaigns.</p>' : '')
                        . ($request->status === 'rejected' ? '<p>Reason: ' . e($request->rejection_reason) . '</p>' : '')
                        . '<p><a href="' . route('sender-ids.index') . '" style="display:inline-block;background:#111827;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">View Sender IDs</a></p>';
                    Mail::html($html, function ($m) use ($user, $statusLabel) {
                        $m->to($user->email)->subject('Sender ID ' . $statusLabel);
                    });
                } catch (\Throwable $e) {
                }
            }
            
            return back()->with('success', 'Sender ID status updated successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Sender ID status update error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'An error occurred while updating the status.']);
        }
    }

    /**
     * Get available sender IDs for campaigns (AJAX)
     */
    public function getAvailable()
    {
        $user = Auth::user();
        
        $senderIds = SenderID::where('user_id', $user->id)
            ->where('status', 'approved')
            ->select('id', 'sender_name', 'business_name')
            ->get();
            
        return response()->json($senderIds);
    }
}
