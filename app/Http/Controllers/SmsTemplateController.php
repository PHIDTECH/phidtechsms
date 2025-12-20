<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SmsTemplateController extends Controller
{
    /**
     * Display a listing of SMS templates
     */
    public function index(Request $request)
    {
        $query = SmsTemplate::where('user_id', Auth::id());
        
        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }
        
        // Search by name or content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        $templates = $query->orderBy('created_at', 'desc')->paginate(12);
        
        // Get statistics
        $stats = [
            'total' => SmsTemplate::where('user_id', Auth::id())->count(),
            'active' => SmsTemplate::where('user_id', Auth::id())->where('is_active', true)->count(),
            'inactive' => SmsTemplate::where('user_id', Auth::id())->where('is_active', false)->count(),
            'most_used' => SmsTemplate::where('user_id', Auth::id())->orderBy('usage_count', 'desc')->first()
        ];
        
        return view('sms-templates.index', compact('templates', 'stats'));
    }
    
    /**
     * Show the form for creating a new SMS template
     */
    public function create()
    {
        return view('sms-templates.create');
    }
    
    /**
     * Store a newly created SMS template
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:otp,transactional,marketing,reminders',
            'content' => 'required|string|max:1000',
            'description' => 'nullable|string|max:500'
        ]);
        
        $variables = [];
        preg_match_all('/\{([^}]+)\}/', $request->content, $matches);
        $variables = array_unique($matches[1] ?? []);

        $beemResponse = null;
        try {
            $beem = new \App\Services\BeemSmsService();
            $beemResponse = $beem->createSmsTemplate($request->name, $request->content);
        } catch (\Throwable $e) {
            $beemResponse = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $remoteSuccess = $beemResponse['success'] ?? false;
        $remoteError = $remoteSuccess ? null : ($beemResponse['error'] ?? 'Failed to create template on Beem');
        $remoteId = $remoteSuccess
            ? ($beemResponse['id']
                ?? data_get($beemResponse, 'data.id')
                ?? data_get($beemResponse, 'data.template_id'))
            : null;

        try {
            DB::beginTransaction();

            $template = SmsTemplate::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'category' => $request->category,
                'content' => $request->content,
                'description' => $request->description,
                'beem_template_id' => $remoteId,
                'variables' => $variables,
                'is_active' => true
            ]);

            AuditLog::logAction(Auth::id(), 'template_created', 'SMS template created: ' . $template->name, [
                'template_id' => $template->id,
                'category' => $template->category,
                'variables_count' => count($variables),
                'beem_synced' => $remoteSuccess,
                'beem_error' => $remoteSuccess ? null : $remoteError
            ]);

            DB::commit();

            $flashKey = $remoteSuccess ? 'success' : 'warning';
            $flashMessage = $remoteSuccess
                ? 'SMS template created successfully!'
                : 'Template saved locally, but sync to Beem failed: ' . $remoteError;

            return redirect()->route('sms-templates.index')->with($flashKey, $flashMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create template: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Display the specified SMS template
     */
    public function show(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to template');
        }
        
        // Pass as 'template' to match Blade expectations
        return view('sms-templates.show', ['template' => $smsTemplate]);
    }
    
    /**
     * Show the form for editing the specified SMS template
     */
    public function edit(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to template');
        }
        
        // Pass as 'template' to match Blade expectations
        return view('sms-templates.edit', ['template' => $smsTemplate]);
    }
    
    /**
     * Update the specified SMS template
     */
    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to template');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:otp,transactional,marketing,reminders',
            'content' => 'required|string|max:1000',
            'description' => 'nullable|string|max:500'
        ]);
        
        try {
            DB::beginTransaction();

            // Extract variables from content
            preg_match_all('/\{([^}]+)\}/', $request->content, $matches);
            $variables = array_unique($matches[1] ?? []);

            // Sync to Beem: update if we have external id, else create
            $beem = new \App\Services\BeemSmsService();
            if ($smsTemplate->beem_template_id) {
                $remote = $beem->updateSmsTemplate($smsTemplate->beem_template_id, $request->name, $request->content);
                if (!($remote['success'] ?? false)) {
                    throw new \Exception($remote['error'] ?? 'Failed to update template on Beem');
                }
            } else {
                $remote = $beem->createSmsTemplate($request->name, $request->content);
                if (!($remote['success'] ?? false)) {
                    throw new \Exception($remote['error'] ?? 'Failed to create template on Beem');
                }
                $smsTemplate->beem_template_id = $remote['id'] ?? $smsTemplate->beem_template_id;
            }

            $smsTemplate->update([
                'name' => $request->name,
                'category' => $request->category,
                'content' => $request->content,
                'beem_template_id' => $smsTemplate->beem_template_id,
                'description' => $request->description,
                'variables' => $variables
            ]);
            
            // Log template update
            AuditLog::logAction(Auth::id(), 'template_updated', 'SMS template updated: ' . $smsTemplate->name, [
                'template_id' => $smsTemplate->id,
                'category' => $smsTemplate->category,
                'variables_count' => count($variables)
            ]);
            
            DB::commit();
            
            return redirect()->route('sms-templates.index')
                ->with('success', 'SMS template updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update template: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Remove the specified SMS template
     */
    public function destroy(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to template');
        }
        
        try {
            DB::beginTransaction();

            // Delete on Beem first if linked
            if ($smsTemplate->beem_template_id) {
                $beem = new \App\Services\BeemSmsService();
                $beem->deleteSmsTemplate($smsTemplate->beem_template_id); // ignore failure; proceed to delete local
            }

            $templateName = $smsTemplate->name;
            $smsTemplate->delete();
            
            // Log template deletion
            AuditLog::logAction(Auth::id(), 'template_deleted', 'SMS template deleted: ' . $templateName, [
                'template_id' => $smsTemplate->id
            ]);
            
            DB::commit();
            
            return redirect()->route('sms-templates.index')
                ->with('success', 'SMS template deleted successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete template: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Toggle template status (active/inactive)
     */
    public function toggleStatus(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            // If non-AJAX form submit, redirect back with error
            if (!request()->wantsJson()) {
                return redirect()->back()->withErrors(['error' => 'Unauthorized']);
            }
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        try {
            $smsTemplate->update([
                'is_active' => !$smsTemplate->is_active
            ]);
            
            $status = $smsTemplate->is_active ? 'activated' : 'deactivated';
            
            // Log status change
            AuditLog::logAction(Auth::id(), 'template_status_changed', "SMS template {$status}: " . $smsTemplate->name, [
                'template_id' => $smsTemplate->id,
                'new_status' => $smsTemplate->is_active
            ]);
            
            // If non-AJAX form submit, redirect back with flash
            if (!request()->wantsJson()) {
                return redirect()->back()->with('success', "Template {$status} successfully!");
            }
            
            return response()->json([
                'success' => true,
                'message' => "Template {$status} successfully!",
                'is_active' => $smsTemplate->is_active
            ]);
            
        } catch (\Exception $e) {
            if (!request()->wantsJson()) {
                return redirect()->back()->withErrors(['error' => 'Failed to update template status: ' . $e->getMessage()]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get template content for AJAX requests
     */
    public function getTemplate(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Increment usage count
        $smsTemplate->incrementUsage();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $smsTemplate->id,
                'name' => $smsTemplate->name,
                'content' => $smsTemplate->content,
                'variables' => $smsTemplate->variables,
                'character_count' => $smsTemplate->getCharacterCount(),
                'sms_parts' => $smsTemplate->calculateParts()
            ]
        ]);
    }
    
    /**
     * Duplicate an existing template
     */
    public function duplicate(SmsTemplate $smsTemplate)
    {
        // Ensure user owns this template
        if ($smsTemplate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to template');
        }
        
        try {
            DB::beginTransaction();
            
            $newTemplate = SmsTemplate::create([
                'user_id' => Auth::id(),
                'name' => $smsTemplate->name . ' (Copy)',
                'category' => $smsTemplate->category,
                'content' => $smsTemplate->content,
                'description' => $smsTemplate->description,
                'variables' => $smsTemplate->variables,
                'is_active' => true
            ]);
            
            // Log template duplication
            AuditLog::logAction(Auth::id(), 'template_duplicated', 'SMS template duplicated: ' . $newTemplate->name, [
                'original_template_id' => $smsTemplate->id,
                'new_template_id' => $newTemplate->id
            ]);
            
            DB::commit();
            
            return redirect()->route('sms-templates.edit', $newTemplate)
                ->with('success', 'Template duplicated successfully! You can now edit the copy.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to duplicate template: ' . $e->getMessage()]);
        }
    }
}
