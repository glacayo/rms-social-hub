<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Modules\Facebook\PageRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {}

    public function index(): Response
    {
        $pages = FacebookPage::active()
            ->with('linkedBy:id,name,email')
            ->orderBy('page_name')
            ->get()
            ->map(fn($page) => [
                'id'               => $page->id,
                'page_id'          => $page->page_id,
                'page_name'        => $page->page_name,
                'token_status'     => $page->token_status,
                'token_expires_at' => $page->token_expires_at?->toISOString(),
                'linked_by'        => $page->linkedBy?->only('id', 'name', 'email'),
                'created_at'       => $page->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function destroy(FacebookPage $page): \Illuminate\Http\RedirectResponse
    {
        $this->pageRepository->delete($page);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Página desvinculada correctamente.');
    }
}
