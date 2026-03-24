<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * This table is immutable — no updated_at column.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'page_id',
        'post_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Override save() to prevent updates — this table is append-only.
     *
     * @throws \RuntimeException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('AuditLog records are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
