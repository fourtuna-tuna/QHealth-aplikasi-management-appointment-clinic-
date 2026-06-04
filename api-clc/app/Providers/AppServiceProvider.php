<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $frontendUrl = function (): string {
            $configuredUrl = config('app.frontend_url') ?: env('FRONTEND_URL');

            if ($configuredUrl) {
                return $configuredUrl;
            }

            foreach ([request()->headers->get('origin'), request()->headers->get('referer')] as $candidate) {
                if (! $candidate) {
                    continue;
                }

                $parts = parse_url($candidate);
                $scheme = $parts['scheme'] ?? null;
                $host = $parts['host'] ?? null;

                if (! in_array($scheme, ['http', 'https'], true) || ! $host) {
                    continue;
                }

                $port = isset($parts['port']) ? ':'.$parts['port'] : '';

                return "{$scheme}://{$host}{$port}";
            }

            return 'http://127.0.0.1:8100';
        };

        ResetPassword::createUrlUsing(function (User $user, string $token) use ($frontendUrl) {
            $baseUrl = rtrim($frontendUrl(), '/');

            return "{$baseUrl}/auth?mode=reset&email=".urlencode($user->email)."&token={$token}";
        });
    }
}
