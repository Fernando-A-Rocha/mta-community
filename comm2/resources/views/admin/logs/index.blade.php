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
                <x-log-entry :log="$log" />
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('No log entries found for the chosen filters.') }}
                </div>
            @endforelse
        </div>

        {{ $logs->links() }}
    </div>
</x-layouts.app>
