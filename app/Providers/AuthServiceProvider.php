<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Contact Management Gates
        Gate::define('view-contacts', function (User $user) {
            return $user->is_active;
        });

        Gate::define('manage-contacts', function (User $user) {
            // Allow all active users to perform CRUD on their own contacts
            return $user->is_active;
        });



        Gate::define('export-contacts', function (User $user) {
            return $user->is_active && ($user->isAdmin() || $user->isReseller());
        });

        // Contact Group Management Gates
        Gate::define('manage-contact-groups', function (User $user) {
            return $user->is_active;
        });

        // Admin-only Gates
        Gate::define('admin-access', function (User $user) {
            return $user->is_active && $user->isAdmin();
        });

        Gate::define('view-all-contacts', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-user-contacts', function (User $user, User $targetUser) {
            return $user->isAdmin() || $user->id === $targetUser->id;
        });

        // API Access Gates
        Gate::define('api-access', function (User $user) {
            return $user->is_active;
        });
    }
}
