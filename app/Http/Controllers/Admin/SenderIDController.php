<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SenderID;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SenderIDController extends Controller
{
    /**
     * Display a listing of sender ID applications
     */
    public function index(Request $request)
    {
        $query = SenderID::with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by business type
        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        $applications = $query->paginate(20);

        // Get statistics
        $stats = [
            'total' => SenderID::count(),
            'pending' => SenderID::pending()->count(),
            'approved' => SenderID::approved()->count(),
            'rejected' => SenderID::rejected()->count(),
        ];

        return view('admin.sender-ids.index', compact('applications', 'stats'));
    }

    /**
     * Display the specified sender ID application
     */
    public function show(SenderID $senderID)
    {
        $senderID->load(['user', 'reviewer']);
        return view('admin.sender-ids.show', compact('senderID'));
    }

    /**
     * Approve a sender ID application
     */
    public function approve(Request $request, SenderID $senderID)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $senderID->approve(Auth::id(), $request->admin_notes);

        return redirect()->back()->with('success', 'Sender ID application approved successfully.');
    }

    /**
     * Reject a sender ID application
     */
    public function reject(Request $request, SenderID $senderID)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $senderID->reject(Auth::id(), $request->rejection_reason, $request->admin_notes);

        return redirect()->back()->with('success', 'Sender ID application rejected.');
    }

    /**
     * Download a document
     */
    public function downloadDocument(SenderID $senderID, $type)
    {
        $allowedTypes = ['business_license', 'id_document'];
        
        if (!in_array($type, $allowedTypes)) {
            abort(404);
        }

        $pathField = $type . '_path';
        $filePath = $senderID->$pathField;

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404);
        }

        return Storage::download($filePath);
    }

    /**
     * Download additional document
     */
    public function downloadAdditionalDocument(SenderID $senderID, $index)
    {
        $additionalDocs = $senderID->additional_documents_paths ?? [];
        
        if (!isset($additionalDocs[$index]) || !Storage::exists($additionalDocs[$index])) {
            abort(404);
        }

        return Storage::download($additionalDocs[$index]);
    }

    /**
     * Bulk actions for sender ID applications
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'applications' => 'required|array',
            'applications.*' => 'exists:sender_ids,id',
            'rejection_reason' => 'required_if:action,reject|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $applications = SenderID::whereIn('id', $request->applications)
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            if ($request->action === 'approve') {
                $application->approve(Auth::id(), $request->admin_notes);
            } else {
                $application->reject(Auth::id(), $request->rejection_reason, $request->admin_notes);
            }
            $count++;
        }

        $action = $request->action === 'approve' ? 'approved' : 'rejected';
        return redirect()->back()->with('success', "{$count} applications {$action} successfully.");
    }
}