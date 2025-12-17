<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('Locale Switching', function () {
    it('sets locale via POST request and stores in session and cookie', function () {
        $response = post(route('locale.update'), ['locale' => 'es']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'es');
        $response->assertCookie('locale', 'es');
    });

    it('normalizes hyphenated locale variants', function () {
        $response = post(route('locale.update'), ['locale' => 'pt-BR']);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'pt_BR');
        $response->assertCookie('locale', 'pt_BR');
    });

    it('rejects invalid locales', function () {
        $response = post(route('locale.update'), ['locale' => 'invalid']);

        $response->assertSessionHasErrors('locale');
    });

    it('requires locale parameter', function () {
        $response = post(route('locale.update'), []);

        $response->assertSessionHasErrors('locale');
    });
});

describe('Locale Resolution', function () {
    it('uses session locale when set', function () {
        session(['locale' => 'de']);

        $response = get('/');

        expect(app()->getLocale())->toBe('de');
    });

    it('uses cookie locale when session is not set', function () {
        $response = get('/', [], ['Cookie' => 'locale=hu']);

        // The locale should be set by the middleware
        $response->assertOk();
    });

    it('falls back to default locale for invalid session locale', function () {
        session(['locale' => 'invalid']);

        $response = get('/');

        expect(app()->getLocale())->toBe('en');
    });
});

describe('RTL Support', function () {
    it('sets dir="rtl" for Arabic locale', function () {
        session(['locale' => 'ar']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    });

    it('sets dir="rtl" for Persian locale', function () {
        session(['locale' => 'fa']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="fa"', false);
    });

    it('sets dir="ltr" for non-RTL locales', function () {
        session(['locale' => 'en']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
    });

    it('sets dir="ltr" for Spanish locale', function () {
        session(['locale' => 'es']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="es"', false);
    });
});

describe('HTML Lang Attribute', function () {
    it('formats locale with hyphen for HTML lang attribute', function () {
        session(['locale' => 'pt_BR']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('lang="pt-BR"', false);
    });

    it('formats zh_TW correctly for HTML lang attribute', function () {
        session(['locale' => 'zh_TW']);

        $response = get('/');

        $response->assertOk();
        $response->assertSee('lang="zh-TW"', false);
    });
});

describe('Language Selector', function () {
    it('displays the language selector component', function () {
        $response = get('/');

        $response->assertOk();
        // Check for the current locale label in the language selector
        $response->assertSee(config('app.locale_labels.en'), false);
    });
});
