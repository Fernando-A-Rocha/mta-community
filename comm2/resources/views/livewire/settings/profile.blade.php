<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <flux:field>
                    <flux:label>{{ __('Profile Visibility') }}</flux:label>
                    <flux:radio.group wire:model="profile_visibility" variant="segmented">
                        <flux:radio value="public">{{ __('Public') }}</flux:radio>
                        <flux:radio value="private">{{ __('Private') }}</flux:radio>
                    </flux:radio.group>
                    <flux:description>{{ __('Control who can view') }} <a href="{{ route('profile.show', auth()->user()) }}" class="underline" wire:navigate>{{ __('your profile') }}</a>. {{__('Public profiles can be viewed by anyone, while private profiles are only visible to you.') }}</flux:description>
                </flux:field>
            </div>

            <flux:separator />

            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold">{{ __('Favorites') }}</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400 italic">
                        {{ __('Share your preferences with the community and let others discover what makes your experience unique. Your favorites help build connections and showcase your personality!') }}
                    </p>
                </div>

                <flux:field>
                    <flux:label>{{ __('City') }}</flux:label>
                    <flux:select wire:model="favorite_city">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::cities() as $city)
                            <option value="{{ $city }}" {{ $favorite_city === $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Vehicle') }}</flux:label>
                    <flux:select wire:model="favorite_vehicle">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::vehicles() as $vehicle)
                            <option value="{{ $vehicle }}" {{ $favorite_vehicle === $vehicle ? 'selected' : '' }}>{{ $vehicle }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Character') }}</flux:label>
                    <flux:select wire:model="favorite_character">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::characters() as $character)
                            <option value="{{ $character }}" {{ $favorite_character === $character ? 'selected' : '' }}>{{ $character }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Gang') }}</flux:label>
                    <flux:select wire:model="favorite_gang">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::gangs() as $gang)
                            <option value="{{ $gang }}" {{ $favorite_gang === $gang ? 'selected' : '' }}>{{ $gang }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Weapon') }}</flux:label>
                    <flux:select wire:model="favorite_weapon">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::weapons() as $weapon)
                            <option value="{{ $weapon }}" {{ $favorite_weapon === $weapon ? 'selected' : '' }}>{{ $weapon }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Radio Station') }}</flux:label>
                    <flux:select wire:model="favorite_radio_station">
                        <option value="">{{ __('None') }}</option>
                        @foreach (\App\Data\ProfileFavorites::radioStations() as $radioStation)
                            <option value="{{ $radioStation }}" {{ $favorite_radio_station === $radioStation ? 'selected' : '' }}>{{ $radioStation }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
