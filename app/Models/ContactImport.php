<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactImport extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'contact_group_id',
        'filename',
        'original_filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'successful_imports',
        'failed_imports',
        'status',
        'error_message',
        'column_mapping',
        'validation_errors',
        'started_at',
        'completed_at',
        'metadata'
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'validation_errors' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the import.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact group for the import.
     */
    public function contactGroup()
    {
        return $this->belongsTo(ContactGroup::class);
    }

    /**
     * Scope a query to only include pending imports.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include processing imports.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope a query to only include completed imports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed imports.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Mark import as started.
     */
    public function markAsStarted()
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now()
        ]);
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted($successfulImports = 0, $failedImports = 0)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'successful_imports' => $successfulImports,
            'failed_imports' => $failedImports,
            'completed_at' => now()
        ]);
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress($processedRows)
    {
        $this->update([
            'processed_rows' => $processedRows
        ]);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Check if import is in progress.
     */
    public function isInProgress()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if import is completed.
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if import failed.
     */
    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get error report file path.
     */
    public function getErrorReportPath()
    {
        if (!$this->failed_imports || $this->failed_imports == 0) {
            return null;
        }

        $directory = storage_path('app/contact-imports/errors');
        return $directory . '/' . $this->id . '_errors.csv';
    }

    /**
     * Get summary for display.
     */
    public function getSummary()
    {
        return [
            'total_rows' => $this->total_rows,
            'successful_imports' => $this->successful_imports,
            'failed_imports' => $this->failed_imports,
            'progress_percentage' => $this->progress_percentage,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'duration' => $this->started_at && $this->completed_at 
                ? $this->started_at->diffForHumans($this->completed_at, true)
                : null
        ];
    }
}