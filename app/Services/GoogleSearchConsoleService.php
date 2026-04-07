<?php

namespace App\Services;

use App\Models\SearchEngineConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class GoogleSearchConsoleService
{
    protected const SEARCH_CONSOLE_SCOPE = 'https://www.googleapis.com/auth/webmasters';

    protected function provider()
    {
        return Socialite::driver('google')
            ->redirectUrl(route('search-engines.google.callback'))
            ->scopes([self::SEARCH_CONSOLE_SCOPE])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ]);
    }

    public function redirectResponse()
    {
        return $this->provider()->redirect();
    }

    public function connectFromCallback(User $user): SearchEngineConnection
    {
        $googleUser = $this->provider()->user();
        $existingConnection = SearchEngineConnection::query()
            ->where('user_id', $user->id)
            ->where('provider', 'google')
            ->first();

        return SearchEngineConnection::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => 'google',
            ],
            [
                'provider_user_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken ?: $existingConnection?->refresh_token,
                'token_expires_at' => $googleUser->expiresIn
                    ? now()->addSeconds((int) $googleUser->expiresIn)
                    : null,
                'connected_at' => now(),
                'last_synced_at' => now(),
                'meta' => [
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                ],
            ]
        );
    }

    public function listSites(SearchEngineConnection $connection): array
    {
        $response = $this->authorizedRequest($connection)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        $response->throw();

        $connection->forceFill(['last_synced_at' => now()])->save();

        return collect($response->json('siteEntry', []))
            ->filter(fn ($site) => ($site['permissionLevel'] ?? null) !== 'siteUnverifiedUser')
            ->map(fn ($site) => [
                'site_url' => $site['siteUrl'] ?? '',
                'permission_level' => $site['permissionLevel'] ?? '',
            ])
            ->values()
            ->all();
    }

    public function submitSitemap(SearchEngineConnection $connection, string $siteProperty, string $sitemapUrl): array
    {
        $response = $this->authorizedRequest($connection)
            ->put(
                'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteProperty) . '/sitemaps/' . rawurlencode($sitemapUrl)
            );

        $response->throw();

        $connection->forceFill(['last_synced_at' => now()])->save();

        return [
            'site_property' => $siteProperty,
            'sitemap_url' => $sitemapUrl,
            'status_code' => $response->status(),
            'submitted' => true,
        ];
    }

    protected function authorizedRequest(SearchEngineConnection $connection)
    {
        return Http::acceptJson()
            ->withToken($this->resolveAccessToken($connection))
            ->timeout(20);
    }

    protected function resolveAccessToken(SearchEngineConnection $connection): string
    {
        if (
            $connection->token_expires_at
            && $connection->token_expires_at->greaterThan(now()->addMinute())
            && $connection->access_token
        ) {
            return $connection->access_token;
        }

        if (!$connection->refresh_token) {
            return (string) $connection->access_token;
        }

        $token = $this->provider()->refreshToken($connection->refresh_token);

        $connection->forceFill([
            'access_token' => $token->token,
            'refresh_token' => $token->refreshToken ?: $connection->refresh_token,
            'token_expires_at' => $token->expiresIn
                ? now()->addSeconds((int) $token->expiresIn)
                : null,
            'last_synced_at' => now(),
        ])->save();

        return $connection->access_token;
    }
}
