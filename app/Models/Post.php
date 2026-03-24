<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'media_paths',
        'media_type',
        'post_type',
        'status',
        'scheduled_at',
        'published_at',
        'failed_reason',
        'retry_count',
    ];

    protected $casts = [
        'media_paths' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_SENDING = 'sending';

    const STATUS_PUBLISHED = 'published';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postPages(): HasMany
    {
        return $this->hasMany(PostPage::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(FacebookPage::class, 'post_pages', 'post_id', 'page_id')
            ->withPivot(['status', 'facebook_post_id', 'published_at', 'failed_reason']);
    }

    // Helpers
    public function isRetryable(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retry_count < 3;
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}
