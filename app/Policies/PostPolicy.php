<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * All roles can create posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admins/super-admins can update any post; editors can only update their own.
     */
    public function update(User $user, Post $post): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return $post->user_id === $user->id;
    }

    /**
     * Delete follows the same rules as update.
     */
    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}
