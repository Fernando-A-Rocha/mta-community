<x-layouts.app.sidebar title="Resources">
    <flux:main>
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold">Resources</h1>
                @auth
                    <flux:link :href="route('resources.upload.create')" variant="primary">
                        Upload Resource
                    </flux:link>
                @endauth
            </div>

            @if (session('success'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            @endif

            <div>
                <form method="GET" action="{{ route('resources.index') }}" class="flex flex-col gap-4">
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <!-- Search -->
                        <div class="flex-1 w-full md:w-auto">
                            <div class="flex gap-2">
                                <flux:input
                                    name="search"
                                    type="text"
                                    placeholder="Search resources..."
                                    value="{{ request('search') }}"
                                    class="flex-1"
                                />
                                <flux:button type="submit" variant="primary">Search</flux:button>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="w-full md:w-auto md:min-w-[200px]">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}" />
                            @endif
                            <flux:field>
                                <flux:label>Category</flux:label>
                                <flux:select
                                    name="category"
                                    onchange="this.form.submit()"
                                >
                                    <option value="" {{ !request('category') ? 'selected' : '' }}>All Categories</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                            {{ ucfirst($category) }}
                                        </option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>
                    </div>

                    <!-- Sorting -->
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        @if (request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}" />
                        @endif
                        @if (request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}" />
                        @endif
                        <div class="w-full md:w-auto md:min-w-[200px]">
                            <flux:field>
                                <flux:label>Sort By</flux:label>
                                <flux:select
                                    name="sort_by"
                                    onchange="this.form.submit()"
                                >
                                    <option value="date" {{ $sortBy === 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="rating" {{ $sortBy === 'rating' ? 'selected' : '' }}>Rating</option>
                                    <option value="downloads" {{ $sortBy === 'downloads' ? 'selected' : '' }}>Downloads</option>
                                </flux:select>
                            </flux:field>
                        </div>
                        <div class="w-full md:w-auto md:min-w-[150px]">
                            <flux:field>
                                <flux:label>Order</flux:label>
                                <flux:select
                                    name="sort_order"
                                    onchange="this.form.submit()"
                                >
                                    <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Ascending</option>
                                </flux:select>
                            </flux:field>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Resources Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($resources as $resource)
                    <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow">
                        <a href="{{ route('resources.show', $resource) }}" wire:navigate>
                            @if ($resource->displayImage)
                                <img
                                    src="{{ Storage::disk('public')->url($resource->displayImage->path) }}"
                                    alt="{{ $resource->display_name }}"
                                    class="w-full h-32 object-cover rounded mb-4"
                                />
                            @endif
                            <h3 class="text-xl font-semibold mb-2">{{ $resource->display_name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-2">{{ \Illuminate\Support\Str::limit($resource->short_description, 100) }}</p>
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <span>{{ $resource->category }}</span>
                                <span>{{ $resource->unique_downloads_count }} downloads</span>
                            </div>
                            <div class="flex items-center justify-between text-sm mb-2">
                                @php
                                    $avgRating = $resource->ratings_avg_rating ?? $resource->average_rating;
                                    $ratingCount = $resource->ratings_count ?? $resource->rating_count;
                                @endphp
                                @if ($avgRating)
                                    <div class="flex items-center gap-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= floor($avgRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @endfor
                                        <span class="text-gray-600 dark:text-gray-400 ml-1">{{ number_format($avgRating, 1) }} ({{ $ratingCount }})</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No ratings yet</span>
                                @endif
                                @if ($resource->currentVersion)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $resource->currentVersion->created_at->diffForHumans() }}
                                    </span>
                                @elseif ($resource->updated_at)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $resource->updated_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            @if ($resource->tags->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($resource->tags->take(3) as $tag)
                                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500">No resources found.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $resources->links() }}
            </div>
        </div>
    </flux:main>
</x-layouts.app.sidebar>

