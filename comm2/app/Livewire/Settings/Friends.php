<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\FriendService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Friends extends Component
{
    public string $friendsVisibility = 'public';

    public string $friendUsername = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->friendsVisibility = $user->friends_visibility ?? 'public';
    }

    public function updateFriendsVisibility(): void
    {
        $this->validate([
            'friendsVisibility' => ['required', 'string', 'in:public,private'],
        ]);

        $user = Auth::user();
        $user->friends_visibility = $this->friendsVisibility;
        $user->save();

        $this->dispatch('friends-visibility-updated');
    }

    public function addFriend(): void
    {
        $this->validate([
            'friendUsername' => ['required', 'string', 'alpha_dash', 'max:255'],
        ]);

        $user = Auth::user();
        $normalized = Str::lower(trim($this->friendUsername));

        $friend = User::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if (! $friend) {
            $this->addError('friendUsername', __('We could not find a user with that username.'));

            return;
        }

        if ($friend->is($user)) {
            $this->addError('friendUsername', __('You cannot add yourself as a friend.'));

            return;
        }

        if ($user->hasFriend($friend)) {
            $this->addError('friendUsername', __('This user is already in your friends list.'));

            return;
        }

        app(FriendService::class)->add($user, $friend);

        $this->friendUsername = '';
        $this->dispatch('friend-added');
    }

    public function removeFriend(int $friendId): void
    {
        $user = Auth::user();

        $friend = $user->friends()->where('users.id', $friendId)->first();

        if (! $friend) {
            $friend = User::find($friendId);
        }

        if (! $friend) {
            return;
        }

        app(FriendService::class)->remove($user, $friend);

        $this->dispatch('friend-removed');
    }

    public function render()
    {
        $friends = Auth::user()
            ->friends()
            ->withPivot('created_at')
            ->get();

        return view('livewire.settings.friends', [
            'friends' => $friends,
        ]);
    }
}

