<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Manage your profile settings and preferences')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            {{-- Profile Picture --}}
            <div>
                <flux:field>
                    <flux:label>{{ __('Profile Picture') }}</flux:label>
                    <div class="flex items-center gap-4">
                        @if ($avatarPreview)
                            <img src="{{ $avatarPreview }}" alt="{{ __('Avatar') }}" class="h-20 w-20 rounded-full object-cover border-2 border-neutral-200 dark:border-neutral-700" />
                        @else
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400">
                                <svg
                                    class="h-10 w-10"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 space-y-2">
                            <flux:input wire:model="avatar" type="file" accept="image/*" :label="__('Upload new picture')" />
                            <flux:description>{{ __('Maximum file size: 500KB. Image will be automatically resized to 500x500 pixels.') }}</flux:description>
                            @if ($avatarPreview)
                                <flux:button type="button" variant="ghost" wire:click="deleteAvatar" class="text-sm">
                                    {{ __('Delete picture') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                    @error('avatar')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>
            </div>

            <flux:separator />

            {{-- Profile Visibility --}}
            <div>
                <flux:field>
                    <flux:label>{{ __('Profile Visibility') }}</flux:label>
                    <flux:radio.group wire:model="profile_visibility" variant="segmented">
                        <flux:radio value="public">{{ __('Public') }}</flux:radio>
                        <flux:radio value="private">{{ __('Private') }}</flux:radio>
                    </flux:radio.group>
                    <flux:description>{{ __('Control who can view') }} <a href="{{ route('profile.show', auth()->user()) }}" class="underline" wire:navigate>{{ __('your profile') }}</a>. {{__('Public profiles can be viewed by anyone, while private profiles are only visible to you.') }}</flux:description>
                    @if ($profile_visibility === 'private')
                        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-100">
                            <p>{{ __('Switching to private removes all followers. You currently have :count follower(s).', ['count' => $currentFollowerCount]) }}</p>
                            <label class="mt-2 flex items-center gap-2">
                                <input type="checkbox" wire:model="confirmFollowerLoss" class="h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <span>{{ __('I understand and want to continue.') }}</span>
                            </label>
                        </div>
                    @endif
                    @error('profile_visibility')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
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
