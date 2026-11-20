<?php

namespace Modules\Auth\Providers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tymon\JWTAuth\JWTGuard;

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

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $baseUrl = rtrim((string) config('lms.auth.reset_password_url', config('app.url').'/reset-password'), '/');
            $email = method_exists($notifiable, 'getEmailForPasswordReset') ? (string) $notifiable->getEmailForPasswordReset() : '';

            return $baseUrl.'?token='.urlencode($token).'&email='.urlencode($email);
        });

        $this->app->booted(function (): void {
            if ($this->app->bound('tymon.jwt.parser')) {
                $this->app->make('tymon.jwt.parser')->setChain([new AuthHeaders]);
            }

            $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
                $guard = new class($app['tymon.jwt'], $app['auth']->createUserProvider($config['provider']), $app['request']) extends JWTGuard
                {
                    public function setRequest(Request $request)
                    {
                        $this->user = null;
                        $this->jwt->unsetToken()->setRequest($request);

                        return parent::setRequest($request);
                    }

                    public function user()
                    {
                        if ($this->user !== null) {
                            return $this->user;
                        }

                        if ($this->jwt->setRequest($this->request)->getToken() &&
                            ($payload = $this->jwt->check(true)) &&
                            $this->validateSubject()
                        ) {
                            $user = $this->provider->retrieveById($payload['sub']);

                            if ($user instanceof User) {
                                if ($user->suspended_at !== null || (int) ($payload->get('ver') ?? 1) !== (int) ($user->token_version ?? 1)) {
                                    return null;
                                }
                            }

                            return $this->user = $user;
                        }

                        return null;
                    }
                };

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });
        });

        $tooMany = fn () => ApiResponse::error('Too many attempts. Try again later.', 'TOO_MANY_REQUESTS', 429);

        // Fortify convention: login keyed by normalized email+IP so one attacker can't lock out others.
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
            $rawEmail = $request->input('email');
            $email = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';

            return $email.'|'.$request->ip();
        }

        return $request->user()?->id ?: $request->ip();
    }
}
