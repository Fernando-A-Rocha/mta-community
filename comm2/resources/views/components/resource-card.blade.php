@props([
    'resource',
    'showUser' => true,
    'showTags' => true,
])

@php
    $avgRating = $resource->ratings_avg_rating ?? $resource->average_rating;
    $ratingCount = $resource->ratings_count ?? $resource->rating_count;
    $downloadsCount = $resource->unique_downloads_count ?? 0;
    $lastUpdated = $resource->currentVersion?->created_at ?? $resource->updated_at;
    $isVerified = $resource->isLatestVersionVerified();
@endphp

<a
    href="{{ route('resources.show', $resource) }}"
    wire:navigate
    class="group relative flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:bg-slate-900/60 {{ $isVerified ? 'border-emerald-400/80 shadow-emerald-200/50 dark:border-emerald-500/60 dark:shadow-emerald-900/30' : 'border-slate-200/80 hover:border-blue-400 dark:border-slate-800' }}"
>
    <div class="relative aspect-[3/2] max-h-[150px] overflow-hidden">
        @if ($resource->displayImage)
            <img
                src="{{ Storage::disk('public')->url($resource->displayImage->path) }}"
                alt="{{ $resource->display_name }}"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            />
        @else
            <div class="flex h-full w-full items-center justify-center bg-slate-100 dark:bg-slate-800">
                <svg class="h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
        <div class="absolute left-3 top-3 flex flex-wrap gap-2">
            <div class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-900 shadow dark:bg-slate-900/90 dark:text-slate-100">
                {{ ucfirst($resource->category) }}
            </div>
            @if ($resource->is_disabled)
                <div class="rounded-full bg-red-500/90 px-3 py-1 text-xs font-semibold text-white shadow">
                    Disabled
                </div>
            @endif
            @if ($resource->languages->isNotEmpty())
                @if ($resource->languages->count() === 1)
                    <div class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-900 shadow dark:bg-slate-900/90 dark:text-slate-100">
                        {{ $resource->languages->first()->name }}
                    </div>
                @else
                    <div class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-900 shadow dark:bg-slate-900/90 dark:text-slate-100">
                        Multi-lang
                    </div>
                @endif
            @endif
        </div>
        @if ($resource->currentVersion)
            <div class="absolute bottom-3 left-3 flex items-center gap-2">
                <div class="rounded-full bg-blue-500/90 px-3 py-1 text-xs font-semibold text-white shadow">
                    v{{ $resource->currentVersion->version }}
                </div>
                @if ($isVerified)
                    <div class="rounded-full bg-emerald-500/90 px-3 py-1 text-xs font-semibold text-white shadow flex items-center gap-1">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Verified
                    </div>
                @endif
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-4 p-5">
        <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ $resource->display_name }}
                </h3>
                @if ($lastUpdated)
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ $lastUpdated->diffForHumans() }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                {{ \Illuminate\Support\Str::limit($resource->short_description, 140) }}
            </p>
        </div>

        <div class="flex items-center justify-between text-sm">
            @if ($avgRating)
                <div class="flex items-center gap-1 text-amber-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ number_format($avgRating, 1) }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">({{ $ratingCount }})</span>
                </div>
            @else
                <span class="text-xs uppercase tracking-wide text-slate-400">No ratings yet</span>
            @endif
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-300">
                {{ number_format($downloadsCount) }} {{ $downloadsCount === 1 ? 'download' : 'downloads' }}
            </div>
        </div>

        @if ($showUser || $showTags)
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                @if ($showUser && $resource->user)
                    <div class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A11.955 11.955 0 0112 15c2.507 0 4.824.76 6.879 2.063M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $resource->user->name }}
                    </div>
                @endif
                @if ($showTags && $resource->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach ($resource->tags->take(2) as $tag)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                        @if ($resource->tags->count() > 2)
                            <span class="text-[11px] font-semibold text-slate-400">+{{ $resource->tags->count() - 2 }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</a>

