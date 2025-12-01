@php
    use App\Enums\ReportStatus;
    use App\Models\Report as ReportModel;
@endphp

<x-layouts.app :title="__('Profile') . ' - ' . $user->name">
    <div class="flex w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center gap-4">
            <x-user-avatar :user="$user" size="lg" class="!h-20 !w-20 !rounded-full" />
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                    @if ($roleBadge = $user->roleBadge())
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roleBadge['color'] }}">
                            {{ $roleBadge['name'] }}
                        </span>
                    @endif
                    @auth
                        @if (auth()->user()->isModerator())
                            <x-entity-logs-modal
                                type="user"
                                :entityId="$user->id"
                                :entityName="$user->name"
                            />
                        @endif
                    @endauth
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

        @if (session('report_success'))
            <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100">
                {{ session('report_success') }}
            </div>
        @endif

        @if (session('friends_success'))
            <div class="rounded-2xl border border-green-200 bg-green-50/80 p-4 text-sm text-green-900 dark:border-green-500/30 dark:bg-green-900/20 dark:text-green-100">
                {{ session('friends_success') }}
            </div>
        @endif

        @if (session('friends_error'))
            <div class="rounded-2xl border border-red-200 bg-red-50/80 p-4 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-900/20 dark:text-red-100">
                {{ session('friends_error') }}
            </div>
        @endif

        @if (session('friends_info'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-100">
                {{ session('friends_info') }}
            </div>
        @endif

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

            @if ($canViewFriends || $resources->isNotEmpty())
                <flux:separator />
            @endif
        @endif

        @if ($canViewFriends)
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ __('Friends') }}</h2>
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __(':count friends', ['count' => $friends->count()]) }}
                    </span>
                </div>

                @if ($isOwner && $friendsVisibility === 'private')
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ __('Only you can see this list because it is set to private.') }}
                    </p>
                @elseif ($isModerator && ! $isOwner && $friendsVisibility === 'private')
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ __('This user keeps their friends list private, but moderators can review it.') }}
                    </p>
                @endif

                @if ($friends->isEmpty())
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $isOwner ? __('You have not added any friends yet.') : __('This user has not added any friends yet.') }}
                    </p>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($friends as $friend)
                            <a
                                href="{{ route('profile.show', $friend) }}"
                                wire:navigate
                                class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white/60 p-3 transition hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900/40 dark:hover:border-neutral-700"
                            >
                                <x-user-avatar :user="$friend" size="md" class="!h-12 !w-12" />
                                <div>
                                    <p class="font-medium">{{ $friend->name }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ __('Friends since :date', ['date' => $friend->pivot->created_at->format('M Y')]) }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($resources->isNotEmpty())
                <flux:separator />
            @endif
        @elseif (! $isOwner && $friendsVisibility === 'private')
            <div class="rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 text-sm text-neutral-700 dark:border-neutral-800 dark:bg-neutral-900/40 dark:text-neutral-300">
                {{ __('This user keeps their friends list private.') }}
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

        @if (auth()->check() && ! $isOwner && ($profileIsPublic || ($isModerator ?? false)))
            <div class="flex flex-wrap items-center justify-end gap-3">
                <form
                    method="POST"
                    action="{{ $viewerIsFriend ? route('friends.destroy', $user) : route('friends.store', $user) }}"
                >
                    @csrf
                    @if ($viewerIsFriend)
                        @method('DELETE')
                    @endif
                    <flux:button
                        type="submit"
                        variant="{{ $viewerIsFriend ? 'ghost' : 'primary' }}"
                        class="{{ $viewerIsFriend ? 'text-red-600 hover:text-red-700 dark:text-red-400' : '' }}"
                    >
                        {{ $viewerIsFriend ? __('Remove friend') : __('Add friend') }}
                    </flux:button>
                </form>

                <x-report-modal
                    type="user"
                    :entityId="$user->id"
                    :entityName="$user->name"
                    :action="route('reports.users.store', $user)"
                    :reasons="ReportModel::USER_REASONS"
                    :existingReport="$viewerReport"
                />
            </div>
        @endif
    </div>
</x-layouts.app>

