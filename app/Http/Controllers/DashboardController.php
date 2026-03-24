<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Post stats
        $postQuery = Post::query()
            ->when($user->isEditor(), fn ($q) => $q->where('user_id', $user->id));

        $stats = [
            'draft' => (clone $postQuery)->where('status', Post::STATUS_DRAFT)->count(),
            'scheduled' => (clone $postQuery)->where('status', Post::STATUS_SCHEDULED)->count(),
            'published' => (clone $postQuery)->where('status', Post::STATUS_PUBLISHED)->count(),
            'failed' => (clone $postQuery)->where('status', Post::STATUS_FAILED)->count(),
        ];

        // Upcoming scheduled posts (next 7 days)
        $upcoming = (clone $postQuery)
            ->where('status', Post::STATUS_SCHEDULED)
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->with('pages:id,page_name')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'preview' => \Illuminate\Support\Str::limit($post->content, 60),
                'scheduled_at' => $post->scheduled_at->toISOString(),
                'post_type' => $post->post_type,
                'pages' => $post->pages->pluck('page_name'),
            ]);

        // Token health (admin/super-admin only)
        $tokenHealth = null;
        if (! $user->isEditor()) {
            $tokenHealth = [
                'active' => FacebookPage::where('token_status', 'active')->count(),
                'expiring' => FacebookPage::where('token_status', 'expiring')->count(),
                'expired' => FacebookPage::where('token_status', 'expired')->count(),
            ];
        }

        // Recent failures (last 5 failed posts)
        $recentFailures = (clone $postQuery)
            ->where('status', Post::STATUS_FAILED)
            ->with('pages:id,page_name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'preview' => \Illuminate\Support\Str::limit($post->content, 60),
                'failed_reason' => $post->failed_reason,
                'retry_count' => $post->retry_count,
                'updated_at' => $post->updated_at->toISOString(),
            ]);

        // Unread notifications count
        $unreadCount = $user->unreadNotifications()->count();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'tokenHealth' => $tokenHealth,
            'recentFailures' => $recentFailures,
            'unreadCount' => $unreadCount,
        ]);
    }
}
