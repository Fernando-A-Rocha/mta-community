<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Friends')" :subheading="__('Manage your connections and requests')">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Allow Friend Requests') }}</flux:label>
                <flux:radio.group wire:model.live="allowFriendRequests" variant="segmented">
                    <flux:radio value="1">{{ __('Enabled') }}</flux:radio>
                    <flux:radio value="0">{{ __('Disabled') }}</flux:radio>
                </flux:radio.group>
                <flux:description>{{ __('Disable this to prevent new friend requests.') }}</flux:description>
            </flux:field>

            <flux:separator />

            <form method="POST" action="{{ route('friends.request-by-username') }}" class="space-y-3">
                @csrf
                <flux:field>
                    <flux:label>{{ __('Send Request by Username') }}</flux:label>
                    <flux:input
                        type="text"
                        name="username"
                        placeholder="{{ __('Enter username') }}"
                        value="{{ old('username') }}"
                        required
                    />
                    @error('username')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <flux:description>{{ __('Usernames are case-sensitive.') }}</flux:description>
                </flux:field>
                <flux:button type="submit" variant="primary">{{ __('Send request') }}</flux:button>
            </form>

            <flux:separator />

            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Incoming Requests') }}</h3>
                @if ($incomingRequests->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No pending requests.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($incomingRequests as $request)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div>
                                    <a href="{{ route('profile.show', $request->requester) }}" class="font-semibold hover:underline" wire:navigate>{{ $request->requester->name }}</a>
                                    <p class="text-xs text-neutral-500">{{ $request->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('friends.accept', $request->requester) }}">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button size="sm" variant="primary" type="submit">{{ __('Accept') }}</flux:button>
                                    </form>
                                    <form method="POST" action="{{ route('friends.destroy', $request->requester) }}">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button size="sm" variant="outline" type="submit">{{ __('Decline') }}</flux:button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:separator />

            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Outgoing Requests') }}</h3>
                @if ($outgoingRequests->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No outgoing requests.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($outgoingRequests as $request)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div>
                                    <a href="{{ route('profile.show', $request->addressee) }}" class="font-semibold hover:underline" wire:navigate>{{ $request->addressee->name }}</a>
                                    <p class="text-xs text-neutral-500">{{ $request->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('friends.destroy', $request->addressee) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="ghost" type="submit">{{ __('Cancel') }}</flux:button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:separator />

            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Friends') }}</h3>
                @if ($friends->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No friends yet.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($friends as $friend)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div>
                                    <a href="{{ route('profile.show', $friend['user']) }}" class="font-semibold hover:underline" wire:navigate>{{ $friend['user']->name }}</a>
                                </div>
                                <form method="POST" action="{{ route('friends.destroy', $friend['user']) }}" onsubmit="return confirm('{{ __('Remove friend?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="outline" type="submit">{{ __('Remove') }}</flux:button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-settings.layout>
</section>
