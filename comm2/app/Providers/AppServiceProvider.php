<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\Resource;
use App\Policies\MediaPolicy;
use App\Policies\ResourcePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Resource::class => ResourcePolicy::class,
        Media::class => MediaPolicy::class,
    ];

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
        $this->configureRateLimiting();

        // Use Tailwind pagination view by default
        Paginator::defaultView('pagination.tailwind');
        Paginator::defaultSimpleView('pagination.simple-tailwind');
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('resource-upload', function (Request $request) {
            return Limit::perHour(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return redirect()
                        ->route('resources.index')
                        ->withErrors(['upload' => 'You have exceeded the upload limit of 5 resources per hour. Please try again later.']);
                });
        });

        RateLimiter::for('resource-download', function (Request $request) {
            return Limit::perHour(60)->by($request->ip());
        });

        RateLimiter::for('media-upload', function (Request $request) {
            return Limit::perDay(1)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return redirect()
                        ->route('media.index')
                        ->withErrors(['upload' => 'You can only upload media once per 24 hours. Please try again later.']);
                });
        });

        RateLimiter::for('media-reactions', function (Request $request) {
            return Limit::perDay(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'You have reached the daily reaction limit of 10 reactions. Please try again tomorrow.',
                    ], 429);
                });
        });
    }
}
