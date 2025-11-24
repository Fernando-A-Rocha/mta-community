<?php

namespace App\Providers;

use App\Actions\Fortify\AuthenticateUser;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\LoginResponse;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureRedirects();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::authenticateUsing(fn ($request) => app(AuthenticateUser::class)($request));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // Register custom login response
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            LoginResponse::class
        );
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        // loginView is configured in configureRedirects() to handle intended URL storage
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn () => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure redirects after authentication.
     */
    private function configureRedirects(): void
    {
        // Store referrer URL when user visits login page directly
        Fortify::loginView(function (Request $request) {
            // If there's no intended URL already set (from auth middleware),
            // and the user came from within the same site, store the referrer
            if (! $request->session()->has('url.intended')) {
                $referrer = $request->headers->get('referer');

                if ($referrer) {
                    $appUrl = rtrim(config('app.url'), '/');
                    $referrerHost = parse_url($referrer, PHP_URL_HOST);
                    $appHost = parse_url($appUrl, PHP_URL_HOST);

                    // Store referrer if it's from the same host
                    if ($referrerHost && $referrerHost === $appHost) {
                        $request->session()->put('url.intended', $referrer);
                    }
                }
            }

            return view('livewire.auth.login');
        });

    }
}
