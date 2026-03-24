<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function log(
        string $action,
        ?User $user = null,
        ?int $pageId = null,
        ?int $postId = null,
        array $metadata = []
    ): AuditLog {
        return AuditLog::create([
            'action'   => $action,
            'user_id'  => $user?->id,
            'page_id'  => $pageId,
            'post_id'  => $postId,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }
}
