<x-layouts.app title="Media">
    @php
        $activeFilters = collect([
            'search' => request('search'),
            'sort' => $sortBy !== 'recent' ? ucfirst($sortBy) : null,
            'order' => $sortOrder !== 'desc' ? ucfirst($sortOrder) : null,
        ])->filter();
    @endphp

    <div class="flex w-full flex-1 flex-col gap-8">
        <section>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-3">
                    <div>
                        <flux:heading size="lg" class="mb-3">{{ __('Media') }}</flux:heading>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                            Browse gameplay screenshots, videos, and awesome moments shared by the community.
                        </p>
                    </div>
                </div>
                @auth
                    <flux:link :href="route('media.upload')" variant="primary">
                        Upload Media
                    </flux:link>
                @endauth
            </div>
            <form method="GET" action="{{ route('media.index') }}" class="flex flex-col gap-4 mt-3">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>
                                {{ __('Search') }}
                                @if ($activeFilters->isNotEmpty())
                                <a href="{{ route('media.index') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-300 ml-2">
                                    {{ __('Clear filters') }}
                                </a>
                                @endif
                            </flux:label>
                            <div class="flex gap-2">
                                <flux:input
                                    name="search"
                                    type="text"
                                    placeholder="Search by description or author username"
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
                        <div class="sm:min-w-[180px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            <flux:field>
                                <flux:label>Sort by</flux:label>
                                <flux:select name="sort_by" onchange="this.form.submit()">
                                    <option value="recent" {{ $sortBy === 'recent' ? 'selected' : '' }}>Date</option>
                                    <option value="ratings" {{ $sortBy === 'ratings' ? 'selected' : '' }}>Ratings</option>
                                </flux:select>
                            </flux:field>
                        </div>
                        <div class="sm:min-w-[150px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
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
        </section>

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($media as $item)
                <x-media-card :media="$item" />
            @empty
                <div class="col-span-full py-8">
                    <p class="text-base font-medium text-slate-600 dark:text-slate-300">No media match your filters yet.</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Try adjusting your search or clearing filters to see more results.</p>
                </div>
            @endforelse
        </section>

        <div class="mt-2">
            {{ $media->links() }}
        </div>
    </div>
</x-layouts.app>

