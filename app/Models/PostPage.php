<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'page_id',
        'status',
        'facebook_post_id',
        'published_at',
        'failed_reason',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_PUBLISHED = 'published';

    const STATUS_FAILED = 'failed';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
