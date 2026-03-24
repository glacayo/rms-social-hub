<?php

namespace App\Policies;

use App\Models\FacebookPage;
use App\Models\User;

class PagePolicy
{
    /**
     * All authenticated users can view the page list (filtered by visiblePages).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admins and super-admins see all pages; editors only see their assigned pages.
     */
    public function view(User $user, FacebookPage $page): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return $user->assignedPages()->where('facebook_pages.id', $page->id)->exists();
    }

    /**
     * Publishing follows the same rules as viewing.
     */
    public function publish(User $user, FacebookPage $page): bool
    {
        return $this->view($user, $page);
    }

    /**
     * Only admins and super-admins can manage (create/edit/delete) pages.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }
}
