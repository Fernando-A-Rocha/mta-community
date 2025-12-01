<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Friends')" :subheading="__('Manage your friends list and visibility preferences')">
        <div class="my-6 w-full space-y-8">
            <form wire:submit="updateFriendsVisibility" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Friends visibility') }}</flux:label>
                    <flux:radio.group wire:model="friendsVisibility" variant="segmented">
                        <flux:radio value="public">{{ __('Public') }}</flux:radio>
                        <flux:radio value="private">{{ __('Private') }}</flux:radio>
                    </flux:radio.group>
                    <flux:description>
                        {{ __('Choose who can view your friends list. Public lists appear on your profile, private lists are only visible to you.') }}
                    </flux:description>
                    @error('friendsVisibility')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>
                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="updateFriendsVisibility">
                        {{ __('Save visibility') }}
                    </flux:button>
                    <x-action-message class="me-3" on="friends-visibility-updated">
                        {{ __('Visibility updated.') }}
                    </x-action-message>
                </div>
            </form>

            <flux:separator />

            <form wire:submit="addFriend" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Add a friend by username') }}</flux:label>
                    <flux:input
                        wire:model.defer="friendUsername"
                        type="text"
                        autocomplete="off"
                        placeholder="{{ __('Enter username (e.g. racer123)') }}"
                    />
                    <flux:description>{{ __('Usernames are case-insensitive and must match exactly.') }}</flux:description>
                    @error('friendUsername')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>
                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="addFriend">
                        {{ __('Add friend') }}
                    </flux:button>
                    <x-action-message class="me-3" on="friend-added">
                        {{ __('Friend added!') }}
                    </x-action-message>
                </div>
            </form>

            <flux:separator />

            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Friends list') }}</h3>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">
                            {{ __('Remove friends you no longer want to follow.') }}
                        </p>
                    </div>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __(':count friends', ['count' => $friends->count()]) }}
                    </span>
                </div>

                @if ($friends->isEmpty())
                    <p class="mt-4 text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('You have not added any friends yet. Use the form above to add your first friend!') }}
                    </p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($friends as $friend)
                            <div class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white/60 p-4 dark:border-neutral-800 dark:bg-neutral-900/40">
                                <div class="flex items-center gap-3">
                                    <x-user-avatar :user="$friend" size="md" class="!h-12 !w-12" />
                                    <div>
                                        <p class="font-medium">{{ $friend->name }}</p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ __('Friends since :date', ['date' => $friend->pivot->created_at->format('M Y')]) }}
                                        </p>
                                    </div>
                                </div>
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    class="text-sm text-red-600 hover:text-red-700 dark:text-red-400"
                                    wire:click="removeFriend({{ $friend->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="removeFriend({{ $friend->id }})"
                                >
                                    {{ __('Remove') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <x-action-message class="mt-4" on="friend-removed">
                    {{ __('Friend removed.') }}
                </x-action-message>
            </div>
        </div>
    </x-settings.layout>
</section>

