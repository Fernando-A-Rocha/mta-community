<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Friends extends Component
{
    public bool $allowFriendRequests = true;

    public function mount(): void
    {
        $user = Auth::user();
        $this->allowFriendRequests = (bool) ($user->allow_friend_requests ?? true);
    }

    public function updatedAllowFriendRequests($value): void
    {
        $user = Auth::user();
        $user->allow_friend_requests = (bool) $value;
        $user->save();

        session()->flash('success', $value ? __('Friend requests enabled.') : __('Friend requests disabled.'));
    }

    public function render(): View
    {
        $user = Auth::user();

        $incoming = $user->receivedFriendRequests()
            ->with('requester')
            ->where('status', FriendshipStatus::Pending)
            ->get();

        $outgoing = $user->sentFriendRequests()
            ->with('addressee')
            ->where('status', FriendshipStatus::Pending)
            ->get();

        $friends = Friendship::query()
            ->with(['requester', 'addressee'])
            ->where('status', FriendshipStatus::Accepted)
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->get()
            ->map(function (Friendship $friendship) use ($user) {
                return [
                    'id' => $friendship->id,
                    'user' => $friendship->requester_id === $user->id ? $friendship->addressee : $friendship->requester,
                ];
            });

        return view('livewire.settings.friends', [
            'incomingRequests' => $incoming,
            'outgoingRequests' => $outgoing,
            'friends' => $friends,
        ]);
    }
}
