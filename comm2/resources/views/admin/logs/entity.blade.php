@php
    use Illuminate\Support\Str;
@endphp

<x-layouts.app :title="__('Activity Logs') . ' - ' . e($entityName)">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <a href="{{ $type === 'user' ? route('profile.show', $entityId) : route('resources.show', $entityId) }}" class="text-sm font-medium text-slate-500 hover:text-slate-900 hover:underline dark:text-slate-400 dark:hover:text-white">
                ← Back to {{ $type === 'user' ? __('User') : __('Resource') }}
            </a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ __('Activity Logs') }} - {{ e($entityName) }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Chronological log of all actions related to this :type', ['type' => $type === 'user' ? __('user') : __('resource')]) }}
            </p>
        </div>

        <!-- Search Box -->
        <form method="GET" action="{{ route('admin.logs.entity.page', [$type, $entityId]) }}" class="flex gap-2">
            <flux:input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('Search logs...') }}"
                class="flex-1"
            />
            <flux:button type="submit" variant="primary">
                {{ __('Search') }}
            </flux:button>
            @if ($search)
                <flux:link :href="route('admin.logs.entity.page', [$type, $entityId])" variant="ghost">
                    {{ __('Clear') }}
                </flux:link>
            @endif
        </form>

        <!-- Logs List -->
        <div class="space-y-4">
            @forelse ($logs as $log)
                <x-log-entry :log="$log" />
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('No log entries found.') }}
                </div>
            @endforelse
        </div>

        {{ $logs->links() }}
    </div>
</x-layouts.app>

