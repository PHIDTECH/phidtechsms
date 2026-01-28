<?php

namespace App\Http\Controllers;

use App\Models\SenderID;
use App\Models\User;
use App\Services\BeemSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminSenderIDController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display all sender ID applications
     */
    public function index(Request $request)
    {
        $query = SenderID::with('user');
        
        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Search by sender name, business name, or user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sender_name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%")
                               ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        $senderIds = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total' => SenderID::count(),
            'pending' => SenderID::where('status', 'pending')->count(),
            'approved' => SenderID::where('status', 'approved')->count(),
            'rejected' => SenderID::where('status', 'rejected')->count(),
        ];
        
        // Fetch sender names from Beem API
        $beemService = new BeemSmsService();
        
        $normalizeSenderItems = function ($result) {
            if (!($result['success'] ?? false)) {
                return [[], $result['error'] ?? ''];
            }
            $data = $result['data'] ?? [];
            
            $normalized = [];
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                $name = $item['senderid'] ?? $item['sender_id'] ?? $item['sender'] ?? $item['name'] ?? null;
                $status = $item['status'] ?? null;
                $created = $item['created'] ?? null;
                $normalized[] = [
                    'name' => $name,
                    'status' => $status,
                    'raw' => $item,
                    'created_at' => $created,
                ];
            }
            return [$normalized, null];
        };
        
        // Get all sender names first
         $allResult = $beemService->getSenderNames();
         [$allSenders, $allError] = $normalizeSenderItems($allResult);
         
         // Calculate real statistics from Beem data
         $beemStats = [
             'total' => count($allSenders),
             'active' => 0,
             'pending' => 0,
             'approved' => 0,
             'rejected' => 0
         ];
         
         foreach ($allSenders as $sender) {
             $status = strtolower($sender['status'] ?? 'unknown');
             
             switch ($status) {
                 case 'active':
                     $beemStats['active']++;
                     $beemStats['approved']++; // Active is considered approved
                     break;
                 case 'pending':
                     $beemStats['pending']++;
                     break;
                 case 'approved':
                     $beemStats['approved']++;
                     break;
                 case 'rejected':
                     $beemStats['rejected']++;
                     break;
                 case 'inprogress':
                 case 'in progress':
                     $beemStats['pending']++; // In progress is considered pending
                     break;
             }
         }
         
         // Update local stats with Beem data
         $stats['total'] = $beemStats['total'];
         $stats['pending'] = $beemStats['pending'];
         $stats['approved'] = $beemStats['approved'];
         $stats['rejected'] = $beemStats['rejected'];
         
         // Filter by status and reindex arrays
         $beemSenderApproved = array_values(array_filter($allSenders, function($sender) {
             $status = strtolower($sender['status'] ?? '');
             return $status === 'approved' || $status === 'active';
         }));
         
         $beemSenderPending = array_values(array_filter($allSenders, function($sender) {
             $status = strtolower($sender['status'] ?? '');
             return $status === 'pending' || $status === 'inprogress' || $status === 'in progress';
         }));
         
         $approvedError = $allError;
         $pendingError = $allError;

        return view('admin.sender-ids.index', compact(
            'senderIds', 
            'stats', 
            'beemSenderApproved', 
            'beemSenderPending', 
            'approvedError', 
            'pendingError'
        ))->with('applications', $senderIds);
    }

    /**
     * Show specific sender ID application
     */
    public function show(SenderID $senderID)
    {
        $senderID->load('user', 'reviewer');
        return view('admin.sender-ids.show', compact('senderID'));
    }

    /**
     * Approve sender ID application
     */
    public function approve(Request $request, SenderID $senderID)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $senderID->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        // Notify user via email
        $user = $senderID->user;
        if ($user && $user->email) {
            $subject = 'Sender ID Approved';
            $body = "Hello {$user->name},\n\nYour Sender ID '{$senderID->sender_name}' has been approved. You can start sending with it now.\n\nRegards, Phidtech SMS";
            try { Mail::raw($body, function ($m) use ($user, $subject) { $m->to($user->email)->subject($subject); }); } catch (\Throwable $e) {}
        }
        
        return redirect()->back()->with('success', 'Sender ID application approved successfully.');
    }

    /**
     * Reject sender ID application
     */
    public function reject(Request $request, SenderID $senderID)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $senderID->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);

        // Notify user via email
        $user = $senderID->user;
        if ($user && $user->email) {
            $subject = 'Sender ID Rejected';
            $reason = $request->admin_notes;
            $body = "Hello {$user->name},\n\nYour Sender ID '{$senderID->sender_name}' has been rejected. Reason: {$reason}.\nPlease review and re-apply if needed.\n\nRegards, Phidtech SMS";
            try { Mail::raw($body, function ($m) use ($user, $subject) { $m->to($user->email)->subject($subject); }); } catch (\Throwable $e) {}
        }
        
        return redirect()->back()->with('success', 'Sender ID application rejected.');
    }

    /**
     * Download document
     */
    public function downloadDocument(SenderID $senderID, $type)
    {
        $allowedTypes = ['business_license', 'tax_certificate', 'id_document'];
        
        if (!in_array($type, $allowedTypes)) {
            abort(404);
        }

        $filePath = $senderID->{$type};
        
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('public')->download($filePath);
    }

    /**
     * Download additional document
     */
    public function downloadAdditionalDocument(SenderID $senderID, $index)
    {
        $additionalDocs = $senderID->additional_documents ?? [];
        
        if (!isset($additionalDocs[$index])) {
            abort(404, 'Document not found.');
        }

        $filePath = $additionalDocs[$index];
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Document not found.');
        }

        return Storage::disk('public')->download($filePath);
    }

    /**
     * Assign an existing (Beem) sender name to a user account locally
     */
    public function assign(Request $request)
    {
        $request->validate([
            'sender_name' => 'required|string|max:11',
            'user_identifier' => 'required|string', // id, email or phone
        ]);

        // Find user by id, email or phone
        $user = User::query()
            ->when(is_numeric($request->user_identifier), function ($q) use ($request) {
                $q->where('id', (int) $request->user_identifier);
            }, function ($q) use ($request) {
                $q->where('email', $request->user_identifier)
                  ->orWhere('phone', $request->user_identifier);
            })
            ->first();

        if (!$user) {
            return back()->withErrors(['user_identifier' => 'User not found (use ID, email or phone).'])->withInput();
        }

        $name = preg_replace('/\s+/', ' ', trim($request->sender_name));

        // Create or update local SenderID as approved
        $sender = SenderID::updateOrCreate([
            'user_id' => $user->id,
            'sender_name' => $name,
        ], [
            'status' => 'approved',
            'business_type' => 'other',
            'use_case' => 'Imported from Beem',
            'approved_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', "Sender ID '{$sender->sender_name}' assigned to {$user->name}.");
    }
    /**
     * Bulk actions for sender IDs
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'sender_ids' => 'required|array|min:1',
            'sender_ids.*' => 'exists:sender_ids,id',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $senderIds = SenderID::whereIn('id', $request->sender_ids)
                            ->where('status', 'pending')
                            ->get();

        $count = 0;
        foreach ($senderIds as $senderID) {
            $senderID->update([
                'status' => $request->action === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);
            $user = $senderID->user;
            if ($user && $user->email) {
                $subject = $request->action === 'approve' ? 'Sender ID Approved' : 'Sender ID Rejected';
                $body = $request->action === 'approve'
                    ? "Hello {$user->name},\n\nYour Sender ID '{$senderID->sender_name}' has been approved.\n\nRegards, Phidtech SMS"
                    : "Hello {$user->name},\n\nYour Sender ID '{$senderID->sender_name}' has been rejected. Reason: {$request->admin_notes}.\n\nRegards, Phidtech SMS";
                try { Mail::raw($body, function ($m) use ($user, $subject) { $m->to($user->email)->subject($subject); }); } catch (\Throwable $e) {}
            }
            $count++;
        }

        $action = $request->action === 'approve' ? 'approved' : 'rejected';
        return redirect()->back()->with('success', "{$count} sender ID applications {$action} successfully.");
    }
}
