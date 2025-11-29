<x-layouts.app :title="__('Profile') . ' - ' . $user->name">
    <div class="flex w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-2xl font-semibold text-black dark:bg-neutral-700 dark:text-white">
                {{ $user->initials() }}
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                    @if ($roleBadge = $user->roleBadge())
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roleBadge['color'] }}">
                            {{ $roleBadge['name'] }}
                        </span>
                    @endif
                </div>
                @if ($isOwner)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $user->email }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
                        {{ __('Profile Visibility') }}:
                        <span class="font-medium">
                            {{ ($user->profile_visibility ?? 'public') === 'public' ? __('Public') : __('Private') }}
                        </span>
                    </p>
                @endif
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
                    {{ __('Member since') }}: {{ $user->created_at->format('F Y') }}
                </p>
            </div>
        </div>

        <flux:separator />

        @php
            $hasFavorites = $user->favorite_city || $user->favorite_vehicle || $user->favorite_character
                || $user->favorite_gang || $user->favorite_weapon || $user->favorite_radio_station;
        @endphp

        @if ($hasFavorites)
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">{{ __('Favorites') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($user->favorite_city)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('City') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_city }}</span>
                        </div>
                    @endif
                    @if ($user->favorite_vehicle)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('Vehicle') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_vehicle }}</span>
                        </div>
                    @endif
                    @if ($user->favorite_character)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('Character') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_character }}</span>
                        </div>
                    @endif
                    @if ($user->favorite_gang)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('Gang') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_gang }}</span>
                        </div>
                    @endif
                    @if ($user->favorite_weapon)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('Weapon') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_weapon }}</span>
                        </div>
                    @endif
                    @if ($user->favorite_radio_station)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">{{ __('Radio Station') }}:</span>
                            <span class="font-semibold">{{ $user->favorite_radio_station }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if ($resources->isNotEmpty())
                <flux:separator />
            @endif
        @endif

        <div class="space-y-4">
            @if ($resources->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold mb-4">{{ __('Resources Uploaded') }}</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($resources as $resource)
                            <x-resource-card :resource="$resource" :showUser="false" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

