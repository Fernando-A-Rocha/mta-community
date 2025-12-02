<?php

use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyResourceVersionController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceFollowController;
use App\Http\Controllers\ResourceUploadController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\UserFollowController;
use App\Livewire\Settings\Account;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Friends as FriendsSettings;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('home/news', [HomeController::class, 'news'])->name('home.news');

Route::get('legacy/mta/resources', LegacyResourceVersionController::class)
    ->name('legacy.mta.resources');

Route::get('servers', [ServerController::class, 'index'])->name('servers.index');
Route::get('servers/list', [ServerController::class, 'servers'])->name('servers.list');

Route::get('development', [DevelopmentController::class, 'index'])->name('development.index');
Route::get('development/activity', [DevelopmentController::class, 'activity'])->name('development.activity');

// Resources
Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');

// Members
Route::get('members', [MemberController::class, 'index'])->name('members.index');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/account');

    Route::view('notifications', 'notifications.index')->name('notifications.index');

    Route::get('settings/account', Account::class)->name('account.edit');
    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::get('settings/friends', FriendsSettings::class)->name('friends.manage');

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

    // Upload new resource
    Route::get('resources/upload/new', [ResourceUploadController::class, 'createNew'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.upload.new');
    Route::post('resources/upload/new', [ResourceUploadController::class, 'storeNew'])
        ->middleware(['throttle:resource-upload', 'ensure.resource.modification.enabled'])
        ->name('resources.upload.new.store');

    // Upload new version
    Route::get('resources/upload/version', [ResourceUploadController::class, 'createVersion'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.upload.version');
    Route::post('resources/upload/version', [ResourceUploadController::class, 'storeVersion'])
        ->middleware(['throttle:resource-upload', 'ensure.resource.modification.enabled'])
        ->name('resources.upload.version.store');

    // Resource edit (author or admin+)
    Route::get('resources/{resource}/edit', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::put('resources/{resource}', [ResourceController::class, 'update'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.update');

    // Resource rating
    Route::post('resources/{resource}/rating', [ResourceController::class, 'storeRating'])->name('resources.rating.store');
    Route::delete('resources/{resource}/ratings/{rating}', [ResourceController::class, 'deleteRating'])->name('resources.rating.delete');

    Route::post('resources/{resource}/follow', [ResourceFollowController::class, 'store'])->name('resources.follow');
    Route::delete('resources/{resource}/follow', [ResourceFollowController::class, 'destroy'])->name('resources.unfollow');

    Route::post('profile/{user}/follow', [UserFollowController::class, 'store'])->name('users.follow');
    Route::delete('profile/{user}/follow', [UserFollowController::class, 'destroy'])->name('users.unfollow');

    Route::post('profile/{user}/friends', [FriendshipController::class, 'store'])->name('friends.request');
    Route::patch('profile/{user}/friends', [FriendshipController::class, 'accept'])->name('friends.accept');
    Route::delete('profile/{user}/friends', [FriendshipController::class, 'destroy'])->name('friends.destroy');
    Route::post('friends/request-by-username', [FriendshipController::class, 'storeByUsername'])->name('friends.request-by-username');

    // Resource moderation (moderator+)
    Route::post('resources/{resource}/disable', [ResourceController::class, 'disable'])->name('resources.disable');
    Route::post('resources/{resource}/enable', [ResourceController::class, 'enable'])->name('resources.enable');
    Route::post('resources/{resource}/verify', [ResourceController::class, 'updateVerification'])->name('resources.verify');

    // Resource version deletion (author or admin only)
    Route::delete('resources/{resource}/versions/{version}', [ResourceController::class, 'destroyVersion'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.versions.destroy');

    // Resource deletion (author or admin only)
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])
        ->middleware('ensure.resource.modification.enabled')
        ->name('resources.destroy');

    // Reports (public submission + management)
    Route::post('resources/{resource}/report', [ReportController::class, 'storeResource'])->name('reports.resources.store');
    Route::post('profile/{user}/report', [ReportController::class, 'storeUser'])->name('reports.users.store');
    Route::delete('reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::patch('reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
        Route::delete('reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');
        Route::post('reports/cleanup', [AdminReportController::class, 'cleanup'])->name('reports.cleanup');

        Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');
        Route::get('logs/entity', [AdminLogController::class, 'entityLogs'])->name('logs.entity');
    });
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
