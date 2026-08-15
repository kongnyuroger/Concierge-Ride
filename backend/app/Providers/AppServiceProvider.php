<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;
use App\Observers\JobObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Job::observe(JobObserver::class);

        // BR-16: the owner role bypasses every permission check rather than
        // having each of the 9+ permissions explicitly assigned. That means
        // any permission a future ticket adds automatically covers the owner
        // too, with no seeder update required — see /docs/adr/0011. Returning
        // null (not false) for non-owners lets normal permission checks run.
        Gate::before(fn (User $user, string $ability) => $user->hasRole(UserRole::Owner->value) ? true : null);
    }
}
