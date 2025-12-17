<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the locale from various sources.
     */
    protected function resolveLocale(Request $request): string
    {
        $supportedLocales = config('app.supported_locales', ['en']);

        // 1. Check session
        if ($sessionLocale = session('locale')) {
            $normalized = $this->normalizeLocale($sessionLocale);
            if (in_array($normalized, $supportedLocales)) {
                return $normalized;
            }
        }

        // 2. Check cookie
        if ($cookieLocale = $request->cookie('locale')) {
            $normalized = $this->normalizeLocale($cookieLocale);
            if (in_array($normalized, $supportedLocales)) {
                return $normalized;
            }
        }

        // 3. Check Accept-Language header
        $preferredLocale = $this->getPreferredLocaleFromHeader($request, $supportedLocales);
        if ($preferredLocale) {
            return $preferredLocale;
        }

        // 4. Fall back to default locale
        return config('app.locale', 'en');
    }

    /**
     * Normalize locale variants (e.g., pt-BR → pt_BR).
     */
    protected function normalizeLocale(string $locale): string
    {
        $normalizations = [
            'pt-BR' => 'pt_BR',
            'pt-PT' => 'pt_PT',
            'zh-TW' => 'zh_TW',
        ];

        return $normalizations[$locale] ?? str_replace('-', '_', $locale);
    }

    /**
     * Get the best matching locale from the Accept-Language header.
     */
    protected function getPreferredLocaleFromHeader(Request $request, array $supportedLocales): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $locales = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $parts = explode(';', trim($part));
            $locale = trim($parts[0]);
            $quality = 1.0;

            if (isset($parts[1]) && str_starts_with(trim($parts[1]), 'q=')) {
                $quality = (float) substr(trim($parts[1]), 2);
            }

            $locales[$locale] = $quality;
        }

        // Sort by quality (descending)
        arsort($locales);

        // Find the first matching supported locale
        foreach (array_keys($locales) as $locale) {
            $normalized = $this->normalizeLocale($locale);

            // Exact match
            if (in_array($normalized, $supportedLocales)) {
                return $normalized;
            }

            // Try base language (e.g., pt from pt-BR)
            $baseLocale = explode('_', $normalized)[0];
            if (in_array($baseLocale, $supportedLocales)) {
                return $baseLocale;
            }

            // Try to find a variant of the base language
            foreach ($supportedLocales as $supported) {
                if (str_starts_with($supported, $baseLocale.'_')) {
                    return $supported;
                }
            }
        }

        return null;
    }
}
