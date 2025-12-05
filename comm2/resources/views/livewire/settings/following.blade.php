<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Following')" :subheading="__('Manage resources and users you follow')">
        <div class="space-y-6">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Followed Resources') }}</h3>
                @if ($followedResources->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('You are not following any resources.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($followedResources as $resource)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="flex-1">
                                    <a href="{{ route('resources.show', $resource) }}" class="font-semibold hover:underline" wire:navigate>
                                        {{ $resource->display_name }}
                                    </a>
                                    <p class="text-xs text-neutral-500">
                                        {{ __('by') }}
                                        <a href="{{ route('profile.show', $resource->user) }}" class="hover:underline" wire:navigate>
                                            {{ $resource->user->name }}
                                        </a>
                                        @if ($resource->currentVersion)
                                            • {{ __('Version') }} {{ $resource->currentVersion->version }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-neutral-500">{{ __('Following since') }} {{ $resource->pivot->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('resources.unfollow', $resource) }}" class="ml-4">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="outline" type="submit">{{ __('Unfollow') }}</flux:button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <flux:separator />

            <div class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Followed Users') }}</h3>
                @if ($followedUsers->isEmpty())
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('You are not following any users.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($followedUsers as $followedUser)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="flex-1">
                                    <a href="{{ route('profile.show', $followedUser) }}" class="font-semibold hover:underline" wire:navigate>
                                        {{ $followedUser->name }}
                                    </a>
                                    <p class="text-xs text-neutral-500">{{ __('Following since') }} {{ $followedUser->pivot->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('users.unfollow', $followedUser) }}" class="ml-4">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="outline" type="submit">{{ __('Unfollow') }}</flux:button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-settings.layout>
</section>

