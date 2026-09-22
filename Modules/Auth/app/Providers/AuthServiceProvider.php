<?php

namespace Modules\Auth\Providers;

use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $tooMany = fn () => ApiResponse::error('Too many attempts. Try again later.', 'TOO_MANY_REQUESTS', 429);

        // Fortify convention: login keyed by email+IP so one attacker can't lock out others.
        $perMinute = [
            'auth-login' => 5,
            'auth-register' => 6,
            'auth-verify' => 6,
            'auth-password' => 5,
            'auth-oauth' => 10,
        ];

        foreach ($perMinute as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($attempts)
                ->by($this->limiterKey($name, $request))
                ->response($tooMany));
        }
    }

    private function limiterKey(string $name, Request $request): string
    {
        if ($name === 'auth-login') {
            return $request->input('email').'|'.$request->ip();
        }

        return $request->user()?->id ?: $request->ip();
    }
}
