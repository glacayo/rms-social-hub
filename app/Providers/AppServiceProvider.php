<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\Facebook\Contracts\FacebookApiClientInterface::class,
            function ($app) {
                return new \App\Modules\Facebook\Services\FacebookApiClient(
                    appId: config('services.facebook.app_id'),
                    appSecret: config('services.facebook.app_secret'),
                );
            }
        );

        $this->app->bind(
            \App\Modules\Facebook\Services\OAuthService::class,
            function ($app) {
                return new \App\Modules\Facebook\Services\OAuthService(
                    appId: config('services.facebook.app_id'),
                    appSecret: config('services.facebook.app_secret'),
                    redirectUri: config('services.facebook.redirect_uri'),
                );
            }
        );

        $this->app->singleton(\App\Services\AuditLogger::class);
        $this->app->bind(\App\Modules\Facebook\Services\TokenManager::class);
        $this->app->bind(\App\Modules\Publisher\PublishService::class);
        $this->app->bind(\App\Modules\Publisher\RetryPolicy::class);
        $this->app->bind(\App\Modules\Publisher\SchedulerService::class);
        $this->app->singleton(\App\Modules\Publisher\MediaValidator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(\App\Models\FacebookPage::class, \App\Policies\PagePolicy::class);
        Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
    }
}
