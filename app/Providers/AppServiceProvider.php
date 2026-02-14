<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

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
        RateLimiter::for('api', function (Request $request) {
            /** @var ?UserModel $user */
            $user = $request->user();

            return Limit::perMinute(60)->by($user?->id ?: $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api-users', function (Request $request) {
            /** @var ?UserModel $user */
            $user = $request->user();

            return Limit::perMinute(30)->by($user?->id ?: $request->ip());
        });

        RateLimiter::for('api-heavy', function (Request $request) {
            /** @var ?UserModel $user */
            $user = $request->user();

            return Limit::perMinute(15)->by($user?->id ?: $request->ip());
        });
    }
}
