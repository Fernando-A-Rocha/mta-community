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
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/30">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Report this profile') }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Let moderators know about impersonation, harassment, or spam coming from this user.') }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Confidential</span>
                </div>

                @if ($viewerReport && $viewerReport->status === ReportStatus::Pending)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-100">
                        <p class="font-semibold">{{ __('Pending review') }}</p>
                        <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">
                            {{ __('You reported this profile (:reason). It was last updated :time.', ['reason' => $viewerReport->reasonLabel(), 'time' => $viewerReport->updated_at->diffForHumans()]) }}
                        </p>
                        <form method="POST" action="{{ route('reports.destroy', $viewerReport) }}" class="mt-3 flex justify-end">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="ghost" size="sm">
                                {{ __('Withdraw report') }}
                            </flux:button>
                        </form>
                    </div>
                @else
                    @if ($viewerReport)
                        <div class="rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-xs text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/30 dark:text-blue-100">
                            <p class="font-semibold">{{ __('Previous report status: :status', ['status' => $viewerReport->status->label()]) }}</p>
                            <p class="mt-1">{{ $viewerReport->reasonLabel() }} • {{ $viewerReport->updated_at->diffForHumans() }}</p>
                        </div>
                    @endif

                    <x-report.form
                        :action="route('reports.users.store', $user)"
                        :reasons="ReportModel::USER_REASONS"
                        :button-text="__('Submit report')"
                    />
                @endif
            </div>

            <flux:separator />
        @endif
    </div>
</x-layouts.app>

