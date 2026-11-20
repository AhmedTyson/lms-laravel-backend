<?php

namespace Modules\AccessManagement\Providers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Modules\AccessManagement\Models\Group;
use Modules\AccessManagement\Models\UserPermission;
use Modules\AccessManagement\Policies\GroupPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

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

        // ADR-0007 / ADR-008: Unified authorization check consulting user_permissions
        // read model and explicit team-0 Spatie permissions without ambient team leakage.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($user->suspended_at !== null) {
                return false;
            }

            $groupId = null;
            if (isset($arguments[0])) {
                if (is_int($arguments[0])) {
                    $groupId = $arguments[0];
                } elseif ($arguments[0] instanceof Group) {
                    $groupId = $arguments[0]->id;
                }
            }

            if (Schema::hasTable('user_permissions')) {
                $holdsInReadModel = UserPermission::where('user_id', $user->id)
                    ->where('permission_name', $ability)
                    ->where(fn ($query) => $groupId !== null
                        ? $query->where('group_id', $groupId)->orWhereNull('group_id')
                        : $query->whereNull('group_id'))
                    ->exists();

                if ($holdsInReadModel) {
                    return true;
                }
            }

            if (Schema::hasTable('permissions')) {
                $previousTeamId = getPermissionsTeamId();
                setPermissionsTeamId($groupId ?? 0);

                try {
                    if ($user->hasPermissionTo($ability, 'api')) {
                        return true;
                    }
                } catch (PermissionDoesNotExist) {
                    // Ability is a model policy action (view, manage, create); defer to policy.
                } finally {
                    setPermissionsTeamId($previousTeamId);
                }
            }

            return null;
        });

        $tooMany = fn () => ApiResponse::error('Too many attempts. Try again later.', 'TOO_MANY_REQUESTS', 429);

        foreach (['access-grants' => 30, 'access-groups' => 60] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($attempts)
                ->by($request->user()?->id ?: $request->ip())
                ->response($tooMany));
        }
    }
}
