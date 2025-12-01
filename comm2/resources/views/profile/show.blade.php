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
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
                    {{ __('Followers') }}: <span class="font-medium">{{ number_format($user->followers_count ?? 0) }}</span>
                </p>
                @auth
                    @if (! $isOwner)
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            @if ($profileIsPublic)
                                <form method="POST" action="{{ $isFollowingUser ? route('users.unfollow', $user) : route('users.follow', $user) }}">
                                    @csrf
                                    @if ($isFollowingUser)
                                        @method('DELETE')
                                    @endif
                                    <flux:button variant="{{ $isFollowingUser ? 'ghost' : 'outline' }}" size="sm">
                                        {{ $isFollowingUser ? __('Following') : __('Follow user') }}
                                    </flux:button>
                                </form>
                            @endif

                                @if ($isFriend)
                                    <form method="POST" action="{{ route('friends.destroy', $user) }}" onsubmit="return confirm('{{ __('Remove friend?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="ghost" size="sm">
                                            {{ __('Unfriend') }}
                                    </flux:button>
                                </form>
                            @elseif ($incomingFriendRequest)
                                <form method="POST" action="{{ route('friends.accept', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <flux:button variant="primary" size="sm">
                                        {{ __('Accept friend request') }}
                                    </flux:button>
                                </form>
                                <form method="POST" action="{{ route('friends.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="outline" size="sm">
                                        {{ __('Decline') }}
                                    </flux:button>
                                </form>
                            @elseif ($outgoingFriendRequest)
                                <form method="POST" action="{{ route('friends.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="ghost" size="sm">
                                        {{ __('Cancel request') }}
                                    </flux:button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('friends.request', $user) }}">
                                    @csrf
                                    <flux:button
                                        variant="outline"
                                        size="sm"
                                        type="submit"
                                        @if (! $user->allow_friend_requests) disabled @endif
                                    >
                                        {{ __('Add friend') }}
                                    </flux:button>
                                </form>
                                @unless ($user->allow_friend_requests)
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Friend requests disabled') }}</span>
                                @endunless
                            @endif
                        </div>
                    @endif
                @endauth
            </div>
        </div>

        <flux:separator />

        @if ($errors->has('friend') || $errors->has('follow'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100">
                {{ $errors->first('friend') ?? $errors->first('follow') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        @if (session('report_success'))
            <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100">
                {{ session('report_success') }}
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
            <div class="flex justify-end">
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

