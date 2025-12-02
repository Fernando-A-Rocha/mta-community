<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Following extends Component
{
    public function render(): View
    {
        $user = Auth::user();

        $followedResources = $user->followedResources()
            ->with(['user', 'currentVersion'])
            ->orderBy('resource_follows.created_at', 'desc')
            ->get();

        $followedUsers = $user->followedUsers()
            ->orderBy('user_follows.created_at', 'desc')
            ->get();

        return view('livewire.settings.following', [
            'followedResources' => $followedResources,
            'followedUsers' => $followedUsers,
        ]);
    }
}
