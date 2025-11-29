<?php

namespace App\Livewire\Settings;

use App\Data\ProfileFavorites;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $profile_visibility = 'public';

    public ?string $favorite_city = null;

    public ?string $favorite_vehicle = null;

    public ?string $favorite_character = null;

    public ?string $favorite_gang = null;

    public ?string $favorite_weapon = null;

    public ?string $favorite_radio_station = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->profile_visibility = $user->profile_visibility ?? 'public';
        $this->favorite_city = $user->favorite_city;
        $this->favorite_vehicle = $user->favorite_vehicle;
        $this->favorite_character = $user->favorite_character;
        $this->favorite_gang = $user->favorite_gang;
        $this->favorite_weapon = $user->favorite_weapon;
        $this->favorite_radio_station = $user->favorite_radio_station;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'alpha_dash',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'profile_visibility' => ['required', 'string', Rule::in(['public', 'private'])],
            'favorite_city' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::cities()))],
            'favorite_vehicle' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::vehicles()))],
            'favorite_character' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::characters()))],
            'favorite_gang' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::gangs()))],
            'favorite_weapon' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::weapons()))],
            'favorite_radio_station' => ['nullable', Rule::in(array_merge([''], ProfileFavorites::radioStations()))],
        ]);

        // Convert empty strings to null
        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
