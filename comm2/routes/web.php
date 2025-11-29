<?php

use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceUploadController;
use App\Http\Controllers\ServerController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('servers', [ServerController::class, 'index'])->name('servers.index');

Route::get('development', [DevelopmentController::class, 'index'])->name('development.index');

// Resources
Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');

// Members
Route::get('members', [MemberController::class, 'index'])->name('members.index');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Resource Upload (must be before resources/{resource} to avoid route conflict)
    Route::get('resources/upload', [ResourceUploadController::class, 'create'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.upload.create');
    Route::post('resources/upload', [ResourceUploadController::class, 'store'])
        ->middleware(['throttle:resource-upload', 'ensure.resource.modification.enabled'])
        ->name('resources.upload.store');

    // Resource edit (author or admin+)
    Route::get('resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::put('resources/{resource}', [ResourceController::class, 'update'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.update');

    // Resource rating
    Route::post('resources/{resource}/rating', [ResourceController::class, 'storeRating'])->name('resources.rating.store');

    // Resource moderation (moderator+)
    Route::post('resources/{resource}/disable', [ResourceController::class, 'disable'])->name('resources.disable');
    Route::post('resources/{resource}/enable', [ResourceController::class, 'enable'])->name('resources.enable');

    // Resource version deletion (author or admin+)
    Route::delete('resources/{resource}/versions/{version}', [ResourceController::class, 'destroyVersion'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.versions.destroy');

    // Resource deletion (author or admin+)
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.destroy');
});

// Resource downloads (public, must be before show route to avoid conflict)
Route::get('resources/{resource}/download', [ResourceController::class, 'download'])
    ->middleware('throttle:resource-download')
    ->name('resources.download');
Route::get('resources/{resource}/download/{version}', [ResourceController::class, 'downloadVersion'])
    ->middleware('throttle:resource-download')
    ->name('resources.download.version');

// Resource show (public, after upload routes to avoid conflict)
Route::get('resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');

Route::get('profile/{user}', [ProfileController::class, 'show'])->name('profile.show');
