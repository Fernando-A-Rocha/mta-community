<x-layouts.app title="Members">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section>
            <div class="space-y-3">
                <div>
                    <flux:heading size="lg" class="mb-3">{{ __('Members') }} - {{ __('Top Creators') }}</flux:heading>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                    Members ranked by total downloads, average ratings of their resources, media posts, and reactions received.
                    </p>
                </div>
                <div class="space-y-4">
                    @forelse ($topCreators as $index => $creator)
                        <a
                            href="{{ route('profile.show', $creator['user']) }}"
                            wire:navigate
                            class="group flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/60"
                        >
                            <x-user-avatar :user="$creator['user']" size="md" class="!h-12 !w-12 !rounded-full" />

                            <div class="flex flex-1 items-center justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                            {{ $creator['user']->name }}
                                        </h3>
                                        @if ($roleBadge = $creator['user']->roleBadge())
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $roleBadge['color'] }}">
                                                {{ $roleBadge['name'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-4 text-sm text-slate-600 dark:text-slate-300">
                                        <span class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            {{ number_format($creator['resources_count']) }} {{ $creator['resources_count'] === 1 ? 'resource' : 'resources' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            {{ number_format($creator['total_downloads']) }} {{ $creator['total_downloads'] === 1 ? 'download' : 'downloads' }}
                                        </span>
                                        @if ($creator['average_rating'])
                                            <span class="flex items-center gap-1 text-amber-500">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                <span class="font-semibold">{{ number_format($creator['average_rating'], 1) }}</span>
                                                <span class="text-xs text-slate-500 dark:text-slate-400">avg rating</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400 dark:text-slate-500">No ratings yet</span>
                                        @endif
                                        @if (isset($creator['media_count']) && $creator['media_count'] > 0)
                                            <span class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ number_format($creator['media_count']) }} {{ $creator['media_count'] === 1 ? 'media' : 'media' }}
                                            </span>
                                        @endif
                                        @if (isset($creator['total_reactions']) && $creator['total_reactions'] > 0)
                                            <span class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                                {{ number_format($creator['total_reactions']) }} {{ $creator['total_reactions'] === 1 ? 'reaction' : 'reactions' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                        #{{ $index + 1 }}
                                    </div>
                                    <svg class="h-5 w-5 text-slate-400 transition group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="py-8">
                            <p class="text-base font-medium text-slate-600 dark:text-slate-300">No creators found yet.</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Be the first to upload a resource!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

