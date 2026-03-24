<?php

namespace App\Modules\Publisher;

use App\Jobs\PublishPostJob;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    /**
     * Find all posts due for publishing and dispatch their jobs.
     * Called every minute by the Laravel scheduler.
     */
    public function dispatchDuePosts(): int
    {
        $posts = Post::scheduled()->with('postPages')->get();

        $dispatched = 0;

        foreach ($posts as $post) {
            // Guard: must have at least one target page
            if ($post->postPages->isEmpty()) {
                Log::warning('SchedulerService: post has no target pages, cancelling', [
                    'post_id' => $post->id,
                ]);
                PostStateMachine::for($post)->cancel();

                continue;
            }

            PublishPostJob::dispatch($post)->onQueue('publishing');
            $dispatched++;

            Log::info('SchedulerService: dispatched post', [
                'post_id' => $post->id,
                'pages_count' => $post->postPages->count(),
                'scheduled_at' => $post->scheduled_at,
            ]);
        }

        return $dispatched;
    }
}
