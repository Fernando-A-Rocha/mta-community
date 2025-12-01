@props([
    'log',
])

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
                {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 80) : __('n/a') }}
            </span>
        </div>
    </div>
    <div class="mt-4 rounded-2xl bg-slate-900/5 p-4 text-xs font-mono text-slate-800 dark:bg-slate-800/60 dark:text-slate-100">
        <pre class="whitespace-pre-wrap">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>

