<?php

namespace App\Jobs;

use App\Models\FacebookPage;
use App\Modules\Facebook\Services\TokenManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('token-refresh');
    }

    public function handle(TokenManager $tokenManager): void
    {
        $pages = FacebookPage::expiringSoon()->get();

        Log::info('RefreshTokenJob: checking tokens', ['count' => $pages->count()]);

        foreach ($pages as $page) {
            $page->markAsExpiring(); // mark status before refresh attempt
            try {
                $tokenManager->refresh($page);
            } catch (\Throwable $e) {
                Log::error('RefreshTokenJob: failed for page', [
                    'page_id' => $page->id,
                    'page_name' => $page->page_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
