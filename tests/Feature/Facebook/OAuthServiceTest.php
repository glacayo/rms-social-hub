<?php

namespace Tests\Feature\Facebook;

use App\Modules\Facebook\DTOs\TokenDTO;
use App\Modules\Facebook\Services\OAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private OAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OAuthService(
            appId: 'test-app-id',
            appSecret: 'test-app-secret',
            redirectUri: 'http://localhost/facebook/callback',
        );
    }

    public function test_get_auth_url_contains_required_params(): void
    {
        $url = $this->service->getAuthUrl();

        $this->assertStringContainsString('client_id=test-app-id', $url);
        $this->assertStringContainsString('pages_manage_posts', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function test_handle_callback_exchanges_code_for_token(): void
    {
        Http::fake([
            // Short-lived token exchange
            'https://graph.facebook.com/v21.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-token'], 200)
                ->push(['access_token' => 'long-lived-token', 'expires_in' => 5184000], 200),
            // Pages fetch
            'https://graph.facebook.com/v21.0/me/accounts*' => Http::response([
                'data' => [
                    ['id' => 'page-1', 'name' => 'Test Page', 'access_token' => 'page-token', 'category' => 'Business'],
                ],
            ], 200),
        ]);

        $result = $this->service->handleCallback('test-code');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertInstanceOf(TokenDTO::class, $result['token']);
        $this->assertEquals('long-lived-token', $result['token']->token);
        $this->assertCount(1, $result['pages']);
        $this->assertEquals('Test Page', $result['pages'][0]->pageName);
    }

    public function test_handle_callback_throws_on_api_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['error' => 'invalid_code'], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->handleCallback('invalid-code');
    }
}
