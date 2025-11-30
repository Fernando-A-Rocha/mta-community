@php
    use App\Enums\ReportStatus;
    use App\Models\Report as ReportModel;
    use Illuminate\Support\Str;
@endphp

<x-layouts.app :title="__('Reports Center')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('Abuse reports') }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Review community submitted reports for resources and profiles.') }}</p>
            </div>
            @if (auth()->user()?->isAdmin())
                <form method="POST" action="{{ route('admin.reports.cleanup') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm">
                        {{ __('Delete stale resolved reports (>90d)') }}
                    </flux:button>
                </form>
            @endif
        </div>

        @if (session('report_admin_notice'))
            <div class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100">
                {{ session('report_admin_notice') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center dark:border-slate-800 dark:bg-slate-900/30">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Pending') }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ number_format($stats['pending']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center dark:border-slate-800 dark:bg-slate-900/30">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Resolved') }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ number_format($stats['resolved']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center dark:border-slate-800 dark:bg-slate-900/30">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Invalid') }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ number_format($stats['invalid']) }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/30 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <flux:label>{{ __('Status') }}</flux:label>
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <flux:label>{{ __('Type') }}</flux:label>
                <select name="type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">{{ __('All types') }}</option>
                    <option value="resource" @selected($filters['type'] === 'resource')>{{ __('Resource') }}</option>
                    <option value="user" @selected($filters['type'] === 'user')>{{ __('User') }}</option>
                </select>
            </div>
            <div>
                <flux:label>{{ __('Reason') }}</flux:label>
                <select name="reason" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">{{ __('All reasons') }}</option>
                    <optgroup label="{{ __('Resource reasons') }}">
                        @foreach ($resourceReasons as $key => $label)
                            <option value="{{ $key }}" @selected($filters['reason'] === $key)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="{{ __('User reasons') }}">
                        @foreach ($userReasons as $key => $label)
                            <option value="{{ $key }}" @selected($filters['reason'] === $key)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div>
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('keyword, reporter...') }}" />
            </div>
            <div class="col-span-full flex justify-end gap-2">
                <flux:link :href="route('admin.reports.index')" variant="ghost">{{ __('Reset') }}</flux:link>
                <flux:button type="submit" variant="primary">{{ __('Apply filters') }}</flux:button>
            </div>
        </form>

        <div class="space-y-4">
            @forelse ($reports as $report)
                @php
                    $target = $report->reportable;
                    $isResource = $report->reportable_type === ReportModel::TYPE_RESOURCE;
                    $targetUrl = $target ? ($isResource ? route('resources.show', $target) : route('profile.show', $target)) : null;
                    $updateAllowed = true;
                    $canDelete = auth()->user()?->isAdmin() || (auth()->user()?->isModerator() && $report->status === ReportStatus::Invalid);
                @endphp
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $report->reasonLabel() }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $isResource ? __('Resource') : __('User') }} • {{ $report->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $report->status->badgeClasses() }}">{{ $report->status->label() }}</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-800 dark:text-slate-200">{{ Str::limit($report->comment, 400) }}</p>
                    <div class="mt-4 grid gap-2 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-2">
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Reporter') }}:</span>
                            @if ($report->reporter)
                                <a href="{{ route('profile.show', $report->reporter) }}" class="hover:underline">{{ $report->reporter->name }}</a>
                            @else
                                <span>{{ __('Deleted user') }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Target') }}:</span>
                            @if ($target && $targetUrl)
                                <a href="{{ $targetUrl }}" class="hover:underline">
                                    {{ $isResource ? $target->display_name ?? $target->name : $target->name }}
                                </a>
                            @else
                                <span>{{ __('Unavailable') }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Updated') }}:</span>
                            {{ $report->updated_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status->value }}" @selected($report->status->value === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            <flux:button type="submit" size="sm">{{ __('Update') }}</flux:button>
                        </form>
                        @if ($canDelete)
                            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button type="submit" variant="outline" size="sm">
                                    {{ __('Delete') }}
                                </flux:button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('No reports match the current filters.') }}
                </div>
            @endforelse
        </div>

        {{ $reports->links() }}
    </div>
</x-layouts.app>
