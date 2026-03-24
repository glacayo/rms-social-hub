<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Models\PostPage;
use App\Modules\Publisher\MediaValidator;
use App\Modules\Publisher\PostStateMachine;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublisherController extends Controller
{
    public function __construct(
        private readonly MediaValidator $mediaValidator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        // List view — paginated
        $posts = Post::with(['user:id,name', 'pages:id,page_name'])
            ->when($user->isEditor(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(20);

        // Calendar data — scheduled and published posts
        $calendarPosts = Post::with('pages:id,page_name')
            ->when($user->isEditor(), fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [Post::STATUS_SCHEDULED, Post::STATUS_PUBLISHED])
            ->whereNotNull('scheduled_at')
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => Str::limit($post->content, 40),
                'start' => $post->scheduled_at->toISOString(),
                'color' => match ($post->status) {
                    Post::STATUS_PUBLISHED => '#16a34a',
                    Post::STATUS_SCHEDULED => '#2563eb',
                    default => '#6b7280',
                },
                'extendedProps' => [
                    'status' => $post->status,
                    'post_type' => $post->post_type,
                    'pages' => $post->pages->pluck('page_name'),
                ],
            ]);

        return Inertia::render('Publisher/Index', [
            'posts' => $posts,
            'calendarPosts' => $calendarPosts,
        ]);
    }

    public function create(): Response
    {
        $pages = auth()->user()->visiblePages()->map(fn ($page) => [
            'id' => $page->id,
            'page_id' => $page->page_id,
            'page_name' => $page->page_name,
            'status' => $page->token_status,
        ]);

        return Inertia::render('Publisher/Create', [
            'pages' => $pages,
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        // Handle media upload
        $mediaPaths = [];
        $mediaType = $validated['media_type'];

        if ($request->hasFile('media') && $mediaType !== 'none') {
            $file = $request->file('media');
            $errors = $this->mediaValidator->validate($file, $validated['post_type'], $mediaType);

            if (! empty($errors)) {
                return back()->withErrors(['media' => $errors])->withInput();
            }

            $path = $file->store('posts/media', 'public');
            $mediaPaths = [$path];
        }

        // Create post
        $post = Post::create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'media_paths' => $mediaPaths ?: null,
            'media_type' => $mediaType,
            'post_type' => $validated['post_type'],
            'status' => Post::STATUS_DRAFT,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        // Attach pages
        foreach ($validated['page_ids'] as $pageId) {
            PostPage::create([
                'post_id' => $post->id,
                'page_id' => $pageId,
                'status' => PostPage::STATUS_PENDING,
            ]);
        }

        $this->auditLogger->log('post.created', user: auth()->user(), postId: $post->id);

        // If scheduled_at is set, transition to scheduled
        if (! empty($validated['scheduled_at'])) {
            PostStateMachine::for($post)->schedule(new \DateTime($validated['scheduled_at']));
            $this->auditLogger->log('post.scheduled', user: auth()->user(), postId: $post->id, metadata: ['scheduled_at' => $validated['scheduled_at']]);
        }

        return redirect()->route('publisher.index')
            ->with('success', 'Post creado exitosamente.');
    }
}
