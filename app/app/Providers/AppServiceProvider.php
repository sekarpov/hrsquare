<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => [Limit::perMinute(5)->by(strtolower((string) $request->input('login')).'|'.$request->ip()), Limit::perMinute(30)->by($request->ip())]);
        RateLimiter::for('password-change', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()->id));
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
