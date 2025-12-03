<x-layouts.app title="Media">
    <div class="flex w-full flex-1 flex-col gap-8">
        @if ($errors->has('upload') || $errors->has('reaction'))
            <div class="rounded-2xl border border-red-200 bg-red-50/70 p-4 text-sm text-red-900 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-200">
                {{ $errors->first('upload') ?: $errors->first('reaction') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200/60 bg-slate-50/80 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-3">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Media</h1>
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
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200/60 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/30">
            <form method="GET" action="{{ route('media.index') }}" class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Search</flux:label>
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
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white/50 py-16 text-center dark:border-slate-700 dark:bg-slate-900/30">
                    <p class="text-base font-medium text-slate-600 dark:text-slate-300">No media found yet.</p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Be the first to share your gameplay moments!</p>
                </div>
            @endforelse
        </section>

        <div class="mt-2">
            {{ $media->links() }}
        </div>
    </div>
</x-layouts.app>

