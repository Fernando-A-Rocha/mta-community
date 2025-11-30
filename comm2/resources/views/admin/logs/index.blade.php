@php
    use Illuminate\Support\Str;
@endphp

<x-layouts.app :title="__('Activity logs')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('Activity logs') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Every report action is journaled with actor, metadata and JSON payloads.') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.logs.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/30 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <flux:label>{{ __('Action') }}</flux:label>
                <select name="action" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <flux:label>{{ __('User ID') }}</flux:label>
                <flux:input type="number" min="1" name="user_id" value="{{ $filters['user'] ?? '' }}" placeholder="{{ __('Any') }}" />
            </div>
            <div>
                <flux:label>{{ __('IP address') }}</flux:label>
                <flux:input type="text" name="ip" value="{{ $filters['ip'] }}" placeholder="{{ __('Any') }}" />
            </div>
            <div>
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('action name, context, etc.') }}" />
            </div>
            <div class="col-span-full flex justify-end gap-2">
                <flux:link :href="route('admin.logs.index')" variant="ghost">{{ __('Reset') }}</flux:link>
                <flux:button type="submit" variant="primary">{{ __('Apply filters') }}</flux:button>
            </div>
        </form>

        <div class="space-y-4">
            @forelse ($logs as $log)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $log->action }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }} • {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">#{{ $log->id }}</span>
                    </div>
                    <div class="mt-4 grid gap-2 text-xs text-slate-500 dark:text-slate-400 md:grid-cols-3">
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Actor') }}:</span>
                            @if ($log->user)
                                <a href="{{ route('profile.show', $log->user) }}" class="hover:underline">{{ $log->user->name }} (ID {{ $log->user_id }})</a>
                            @else
                                <span>{{ __('System / deleted user') }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">IP:</span>
                            {{ $log->ip_address ?? __('Unknown') }}
                        </div>
                        <div class="md:col-span-1">
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('User agent') }}:</span>
                            <span class="block truncate" title="{{ $log->user_agent ?? __('n/a') }}">
                                {{ $log->user_agent ? Str::limit($log->user_agent, 80) : __('n/a') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl bg-slate-900/5 p-4 text-xs font-mono text-slate-800 dark:bg-slate-800/60 dark:text-slate-100">
                        <pre class="whitespace-pre-wrap">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('No log entries found for the chosen filters.') }}
                </div>
            @endforelse
        </div>

        {{ $logs->links() }}
    </div>
</x-layouts.app>
