<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'content',
        'description',
        'beem_template_id',
        'variables',
        'category',
        'is_active',
        'usage_count'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns this template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if template is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Replace variables in template content
     */
    public function replaceVariables(array $data): string
    {
        $content = $this->content;
        
        foreach ($this->variables ?? [] as $variable) {
            $placeholder = '{' . $variable . '}';
            $value = $data[$variable] ?? $placeholder;
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }

    /**
     * Get template variables from content
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Get variables for this template (from stored field or content)
     */
    public function getVariables(): array
    {
        if (is_array($this->variables) && count($this->variables) > 0) {
            return $this->variables;
        }
        // Fallback: extract from current content
        return $this->extractVariables();
    }

    /**
     * Update template and extract variables
     */
    public function updateContent(string $content): void
    {
        $this->update([
            'content' => $content,
            'variables' => $this->extractVariablesFromContent($content)
        ]);
    }

    /**
     * Extract variables from given content
     */
    private function extractVariablesFromContent(string $content): array
    {
        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Get character count of template content
     */
    public function getCharacterCount(): int
    {
        return mb_strlen($this->content);
    }

    /**
     * Calculate SMS parts for template content
     */
    public function calculateParts(): int
    {
        $length = $this->getCharacterCount();
        
        if ($length <= 160) {
            return 1;
        }
        
        return ceil($length / 153);
    }
}
