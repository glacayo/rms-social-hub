<?php

namespace App\Http\Controllers;

use App\Modules\Facebook\PageRepository;
use App\Modules\Facebook\Services\OAuthService;
use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public function __construct(
        private readonly OAuthService $oauthService,
        private readonly PageRepository $pageRepository,
    ) {}

    public function redirect()
    {
        return redirect($this->oauthService->getAuthUrl());
    }

    public function callback(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        try {
            $result = $this->oauthService->handleCallback($request->get('code'));

            // Store pages (T08 handles persistence)
            foreach ($result['pages'] as $pageDTO) {
                $this->pageRepository->saveFromDTO($pageDTO, $result['token'], $request->user());
            }

            return redirect()->route('admin.pages.index')
                ->with('success', 'Páginas vinculadas exitosamente.');

        } catch (\RuntimeException $e) {
            return redirect()->route('admin.pages.index')
                ->with('error', 'Error al conectar con Facebook: ' . $e->getMessage());
        }
    }
}
