<x-layouts.app title="Resources">
    @php
        $activeFilters = collect([
            'search' => request('search'),
            'category' => request('category'),
            'sort' => $sortBy !== 'date' ? ucfirst($sortBy) : null,
            'order' => $sortOrder !== 'desc' ? ucfirst($sortOrder) : null,
        ])->filter();
    @endphp

    <div class="flex w-full flex-1 flex-col gap-8">
        @if ($errors->has('upload'))
            <div class="rounded-2xl border border-red-200 bg-red-50/70 p-4 text-sm text-red-900 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-200">
                {{ $errors->first('upload') }}
            </div>
        @endif

        <section>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-3">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Resources</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                            Browse maps, scripts, gamemodes, and assets built by the community. Refine the catalog with search, filters, and sorting.
                        </p>
                    </div>
                </div>
                @auth
                    <flux:link :href="route('resources.upload.create')" variant="primary">
                        Upload Resource
                    </flux:link>
                @endauth
            </div>
            <form method="GET" action="{{ route('resources.index') }}" class="flex flex-col gap-4 mt-3">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>
                                {{ __('Search') }}
                                @if ($activeFilters->isNotEmpty())
                                <a href="{{ route('resources.index') }}" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-300 ml-2">
                                    {{ __('Clear filters') }}
                                </a>
                                @endif
                            </flux:label>
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
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($resources as $resource)
                <x-resource-card :resource="$resource" />
            @empty
                <div class="col-span-full py-8">
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
