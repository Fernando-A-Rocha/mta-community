<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', function ($attribute, $value, $fail) {
                $normalized = $this->normalizeLocale($value);
                if (! in_array($normalized, config('app.supported_locales', ['en']))) {
                    $fail('The selected locale is not supported.');
                }
            }],
        ]);

        $locale = $this->normalizeLocale($validated['locale']);

        // Store in session
        session(['locale' => $locale]);

        // Store in cookie (1 year)
        $cookie = Cookie::make('locale', $locale, 60 * 24 * 365);

        return redirect()->back()->withCookie($cookie);
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
}
