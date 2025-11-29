<x-layouts.app title="Resources">
    @php
        $activeFilters = collect([
            'search' => request('search'),
            'category' => request('category'),
            'sort' => $sortBy !== 'date' ? ucfirst($sortBy) : null,
            'order' => $sortOrder !== 'desc' ? ucfirst($sortOrder) : null,
        ])->filter();
        $visibleRange = $resources->count() > 0
            ? sprintf('%s–%s', number_format($resources->firstItem() ?? 0), number_format($resources->lastItem() ?? 0))
            : '0';
    @endphp

    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="rounded-3xl border border-slate-200/60 bg-slate-50/80 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Community Library</p>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Resources</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                            Browse curated scripts, gamemodes, and assets built by the community. Refine the catalog with search, filters, and smarter sorting.
                        </p>
                    </div>
                </div>
                @auth
                    <flux:link :href="route('resources.upload.create')" variant="primary">
                        Upload Resource
                    </flux:link>
                @endauth
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white p-4 text-sm shadow-sm dark:bg-slate-900/70">
                    <dt class="text-slate-500 dark:text-slate-400">Available resources</dt>
                    <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                        {{ number_format($resources->total()) }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-white p-4 text-sm shadow-sm dark:bg-slate-900/70">
                    <dt class="text-slate-500 dark:text-slate-400">Visible this page</dt>
                    <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                        {{ $visibleRange }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-white p-4 text-sm shadow-sm dark:bg-slate-900/70">
                    <dt class="text-slate-500 dark:text-slate-400">Refreshed</dt>
                    <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                        {{ now()->format('M j, Y') }}
                    </dd>
                </div>
            </dl>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200/60 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/30">
            <form method="GET" action="{{ route('resources.index') }}" class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Search</flux:label>
                            <div class="flex gap-2">
                                <flux:input
                                    name="search"
                                    type="text"
                                    placeholder="Search by name, description, or tag"
                                    value="{{ request('search') }}"
                                    class="flex-1"
                                />
                                <flux:button type="submit" variant="primary">
                                    Apply
                                </flux:button>
                            </div>
                        </flux:field>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="sm:min-w-[200px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            <flux:field>
                                <flux:label>Category</flux:label>
                                <flux:select name="category" onchange="this.form.submit()">
                                    <option value="" {{ ! request('category') ? 'selected' : '' }}>All categories</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                            {{ ucfirst($category) }}
                                        </option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>
                        <div class="sm:min-w-[180px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            @if (request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}" />
                            @endif
                            <flux:field>
                                <flux:label>Sort by</flux:label>
                                <flux:select name="sort_by" onchange="this.form.submit()">
                                    <option value="date" {{ $sortBy === 'date' ? 'selected' : '' }}>Last updated</option>
                                    <option value="rating" {{ $sortBy === 'rating' ? 'selected' : '' }}>Rating</option>
                                    <option value="downloads" {{ $sortBy === 'downloads' ? 'selected' : '' }}>Downloads</option>
                                </flux:select>
                            </flux:field>
                        </div>
                        <div class="sm:min-w-[150px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            @if (request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}" />
                            @endif
                            <flux:field>
                                <flux:label>Order</flux:label>
                                <flux:select name="sort_order" onchange="this.form.submit()">
                                    <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Ascending</option>
                                </flux:select>
                            </flux:field>
                        </div>
                    </div>
                </div>
            </form>

            @if ($activeFilters->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white/70 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex flex-wrap gap-2">
                        @if (request('search'))
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                Search: <strong>{{ request('search') }}</strong>
                            </span>
                        @endif
                        @if (request('category'))
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">
                                Category: <strong>{{ ucfirst(request('category')) }}</strong>
                            </span>
                        @endif
                        @if ($sortBy !== 'date')
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                Sort: <strong>{{ ucfirst($sortBy) }}</strong>
                            </span>
                        @endif
                        @if ($sortOrder !== 'desc')
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">
                                Order: <strong>{{ ucfirst($sortOrder) }}</strong>
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('resources.index') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-300">
                        Clear all
                    </a>
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($resources as $resource)
                @php
                    $avgRating = $resource->ratings_avg_rating ?? $resource->average_rating;
                    $ratingCount = $resource->ratings_count ?? $resource->rating_count;
                    $lastUpdated = $resource->currentVersion?->created_at ?? $resource->updated_at;
                @endphp
                <a
                    href="{{ route('resources.show', $resource) }}"
                    wire:navigate
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:-translate-y-1 hover:border-blue-400 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/60"
                >
                    <div class="relative aspect-[3/2] overflow-hidden">
                        @if ($resource->displayImage)
                            <img
                                src="{{ Storage::disk('public')->url($resource->displayImage->path) }}"
                                alt="{{ $resource->display_name }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                            />
                        @else
                            <x-placeholder-pattern class="h-full w-full text-slate-200 dark:text-slate-700" />
                        @endif
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
                        <div class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-900 shadow dark:bg-slate-900/90 dark:text-slate-100">
                            {{ ucfirst($resource->category) }}
                        </div>
                        @if ($resource->currentVersion)
                            <div class="absolute bottom-3 left-3 rounded-full bg-blue-500/90 px-3 py-1 text-xs font-semibold text-white shadow">
                                v{{ $resource->currentVersion->version }}
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
                                {{ number_format($resource->unique_downloads_count) }} downloads
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A11.955 11.955 0 0112 15c2.507 0 4.824.76 6.879 2.063M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $resource->user->name }}
                            </div>
                            @if ($resource->tags->isNotEmpty())
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
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white/50 py-16 text-center dark:border-slate-700 dark:bg-slate-900/30">
                    <p class="text-base font-medium text-slate-600 dark:text-slate-300">No resources match your filters yet.</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Try adjusting your search or clearing filters to see more results.</p>
                </div>
            @endforelse
        </section>

        <div class="mt-2">
            {{ $resources->links() }}
        </div>
    </div>
</x-layouts.app>
