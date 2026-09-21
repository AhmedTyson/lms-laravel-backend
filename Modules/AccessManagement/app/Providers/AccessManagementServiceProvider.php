<?php

namespace Modules\AccessManagement\Providers;

use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Policies\GroupPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AccessManagementServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AccessManagement';

    protected string $nameLower = 'accessmanagement';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Group::class, GroupPolicy::class);

        $tooMany = fn () => ApiResponse::error('Too many attempts. Try again later.', 'TOO_MANY_REQUESTS', 429);

        RateLimiter::for('access-grants', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())->response($tooMany));
        RateLimiter::for('access-groups', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())->response($tooMany));
    }
}
