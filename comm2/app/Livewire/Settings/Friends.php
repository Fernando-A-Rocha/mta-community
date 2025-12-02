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
    public string $allowFriendRequests = '1';

    public function mount(): void
    {
        $user = Auth::user();
        // Convert boolean to string for radio group compatibility
        $this->allowFriendRequests = (bool) ($user->allow_friend_requests ?? true) ? '1' : '0';
    }

    public function updatedAllowFriendRequests($value): void
    {
        $user = Auth::user();
        // Convert string value to boolean
        $boolValue = $value === '1' || $value === 1 || $value === true;
        $user->allow_friend_requests = $boolValue;
        $user->save();

        // Keep the property as string for radio group
        $this->allowFriendRequests = $boolValue ? '1' : '0';

        session()->flash('success', $boolValue ? __('Friend requests enabled.') : __('Friend requests disabled.'));
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
