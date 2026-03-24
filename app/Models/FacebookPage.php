<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class FacebookPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_id',
        'page_name',
        'access_token',
        'token_expires_at',
        'token_status',
        'linked_by_user_id',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = ['access_token']; // never expose in JSON responses

    // Encrypt on set
    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    // Decrypt on get
    public function getAccessTokenAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    // Scope: pages expiring within 7 days
    public function scopeExpiringSoon($query)
    {
        return $query->where('token_expires_at', '<=', now()->addDays(7))
            ->where('token_status', '!=', 'expired');
    }

    // Scope: only active pages
    public function scopeActive($query)
    {
        return $query->where('token_status', '!=', 'expired')->whereNull('deleted_at');
    }

    /**
     * Mark this page's token as 'expiring' (called before a refresh attempt).
     * Only transitions from 'active' — avoids overwriting 'expired' or already 'expiring'.
     */
    public function markAsExpiring(): void
    {
        if ($this->token_status === 'active') {
            $this->update(['token_status' => 'expiring']);
        }
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by_user_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_page_permissions', 'page_id', 'user_id')
            ->withPivot('assigned_by');
    }
}
