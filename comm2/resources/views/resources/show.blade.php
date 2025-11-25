<x-layouts.app :title="$resource->display_name">
        <div class="flex w-full flex-1 flex-col gap-6">
            <div class="mb-6">
                <a href="{{ route('resources.index') }}" class="text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 hover:underline">
                    ← Back to Resources
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    @if ($resource->is_disabled && (!auth()->check() || !auth()->user()->isModerator()))
                        <div class="p-8">
                            <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-gray-100">Resource Disabled</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                The resource you are looking for is not available for viewing or downloading at this time.
                            </p>
                        </div>
                    @else
                        <!-- Header with title and category -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <h1 class="text-3xl font-bold">{{ $resource->display_name }}</h1>
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm font-medium">
                                        {{ ucfirst($resource->category) }}
                                    </span>
                                    @if ($resource->is_disabled)
                                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-full text-sm font-medium">
                                            Disabled
                                        </span>
                                    @endif
                                </div>
                                @auth
                                    @can('update', $resource)
                                        <flux:link :href="route('resources.edit', $resource)" variant="outline" size="sm">
                                            Edit Resource
                                        </flux:link>
                                    @endcan
                                @endauth
                            </div>
                            <p class="text-lg text-gray-600 dark:text-gray-400">{{ $resource->short_description }}</p>
                            @if ($resource->average_rating)
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex items-center">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= floor($resource->average_rating))
                                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @elseif ($i - 0.5 <= $resource->average_rating)
                                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <defs>
                                                    <linearGradient id="half-{{ $i }}">
                                                        <stop offset="50%" stop-color="currentColor" />
                                                        <stop offset="50%" stop-color="transparent" stop-opacity="1" />
                                                    </linearGradient>
                                                </defs>
                                                <path fill="url(#half-{{ $i }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        @endif
                                    @endfor
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($resource->average_rating, 1) }} ({{ $resource->rating_count }} {{ Str::plural('rating', $resource->rating_count) }})
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Images -->
                    @if ($resource->images->isNotEmpty())
                        <div class="border rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 p-2">
                                @foreach ($resource->images as $image)
                                    <div class="relative overflow-hidden rounded-lg">
                                        <img
                                            src="{{ Storage::disk('public')->url($image->path) }}"
                                            alt="{{ $resource->display_name }}"
                                            class="w-full h-64 object-cover hover:scale-105 transition-transform duration-300 cursor-pointer"
                                            onclick="window.open(this.src, '_blank')"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Description -->
                    @if ($resource->long_description)
                        <div class="border rounded-lg p-6 bg-white dark:bg-gray-800">
                            <h2 class="text-2xl font-bold mb-4">Description</h2>
                            <div class="prose dark:prose-invert max-w-none">
                                <div class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{!! nl2br(e($resource->long_description)) !!}</div>
                            </div>
                        </div>
                    @endif

                    <!-- Rating Form -->
                    @auth
                        @if (auth()->user()->id !== $resource->user_id)
                            <div class="border rounded-lg p-6 bg-white dark:bg-gray-800">
                                <h2 class="text-2xl font-bold mb-4">Rate this Resource</h2>
                                <form action="{{ route('resources.rating.store', $resource) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div>
                                        <flux:field>
                                            <flux:label>Rating</flux:label>
                                            <div class="flex gap-2">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <label class="cursor-pointer">
                                                        <input
                                                            type="radio"
                                                            name="rating"
                                                            value="{{ $i }}"
                                                            class="hidden peer"
                                                            {{ old('rating', $userRating?->rating) == $i ? 'checked' : '' }}
                                                            required
                                                        />
                                                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 peer-checked:text-yellow-400 hover:text-yellow-300 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    </label>
                                                @endfor
                                            </div>
                                            @error('rating')
                                                <flux:error>{{ $message }}</flux:error>
                                            @enderror
                                        </flux:field>
                                    </div>
                                    <div>
                                        <flux:field>
                                            <flux:label>Comment (optional)</flux:label>
                                            <flux:textarea
                                                name="comment"
                                                rows="3"
                                                placeholder="Share your thoughts about this resource..."
                                            >{{ old('comment', $userRating?->comment) }}</flux:textarea>
                                            @error('comment')
                                                <flux:error>{{ $message }}</flux:error>
                                            @enderror
                                        </flux:field>
                                    </div>
                                    <flux:button type="submit" variant="primary">
                                        {{ $userRating ? 'Update Rating' : 'Submit Rating' }}
                                    </flux:button>
                                </form>
                            </div>
                        @endif
                    @endauth

                    <!-- Ratings Display -->
                    @if ($resource->ratings->isNotEmpty())
                        <div class="border rounded-lg p-6 bg-white dark:bg-gray-800">
                            <h2 class="text-2xl font-bold mb-4">Ratings & Reviews</h2>
                            <div class="space-y-4">
                                @foreach ($resource->ratings->take(10) as $rating)
                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0 last:pb-0">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold">{{ $rating->user->name }}</span>
                                                <div class="flex">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg class="w-4 h-4 {{ $i <= $rating->rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                            </div>
                                            <span class="text-sm text-gray-500">{{ $rating->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if ($rating->comment)
                                            <p class="text-gray-700 dark:text-gray-300">{{ $rating->comment }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Releases List -->
                    @if ($resource->versions->isNotEmpty())
                        @php
                            $firstVersion = $resource->versions->sortBy('created_at')->first();
                        @endphp
                        <div class="border rounded-lg p-6 bg-white dark:bg-gray-800">
                            <h2 class="text-2xl font-bold mb-6">Releases</h2>
                            <div class="space-y-4">
                                @foreach ($resource->versions as $version)
                                    @php
                                        $isFirstVersion = $firstVersion->id === $version->id;
                                    @endphp
                                    <div class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $version->is_current ? 'border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h3 class="text-lg font-semibold">
                                                        Version {{ $version->version }}
                                                        @if ($version->is_current)
                                                            <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-medium">
                                                                Latest
                                                            </span>
                                                        @endif
                                                    </h3>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    Released {{ $version->created_at->format('M d, Y') }}
                                                </p>
                                                <div class="prose dark:prose-invert max-w-none">
                                                    <div class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{!! nl2br(e($version->changelog)) !!}</div>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 flex flex-col gap-2">
                                                <a href="{{ route('resources.download.version', [$resource, $version->version]) }}" class="inline-block">
                                                    <flux:button variant="{{ $version->is_current ? 'primary' : 'outline' }}" size="sm" class="w-full">
                                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        Download
                                                    </flux:button>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    @if (!$resource->is_disabled || (auth()->check() && auth()->user()->isModerator()))
                        <!-- Download Button -->
                        @if ($resource->currentVersion)
                            <div class="border rounded-lg p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                                <a href="{{ route('resources.download', $resource) }}" class="block">
                                    <flux:button variant="primary" class="w-full text-lg py-3">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download v{{ $resource->currentVersion->version }}
                                    </flux:button>
                                </a>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 text-center">
                                    {{ number_format($resource->unique_downloads_count) }} {{ Str::plural('download', $resource->unique_downloads_count) }}
                                </p>
                            </div>
                        @endif

                        <!-- Resource Info -->
                        <div class="border rounded-lg p-4 bg-white dark:bg-gray-800">
                            <h3 class="font-bold mb-4 text-lg">Resource Information</h3>
                            <div class="space-y-3 text-sm">
                            <div class="flex items-start justify-between">
                                <span class="font-semibold text-gray-600 dark:text-gray-400">Author:</span>
                                <a href="{{ route('profile.show', $resource->user) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                    {{ $resource->user->name }}
                                </a>
                            </div>
                            <div class="flex items-start justify-between">
                                <span class="font-semibold text-gray-600 dark:text-gray-400">Category:</span>
                                <span class="font-medium">{{ ucfirst($resource->category) }}</span>
                            </div>
                            @if ($resource->currentVersion)
                                <div class="flex items-start justify-between">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Latest Version:</span>
                                    <span class="font-medium font-mono">{{ $resource->currentVersion->version }}</span>
                                </div>
                                <div class="flex items-start justify-between">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Total Releases:</span>
                                    <span class="font-medium">{{ $resource->versions->count() }}</span>
                                </div>
                            @endif
                            @if ($resource->min_mta_version)
                                <div class="flex items-start justify-between">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Min MTA:</span>
                                    <span class="font-medium font-mono text-xs">{{ $resource->min_mta_version }}</span>
                                </div>
                            @endif
                            @if ($resource->oop_enabled)
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">OOP:</span>
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-xs font-medium">
                                        Enabled
                                    </span>
                                </div>
                            @endif
                            </div>
                        </div>

                        <!-- Tags -->
                        @if ($resource->tags->isNotEmpty())
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-800">
                            <h3 class="font-bold mb-4 text-lg">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($resource->tags as $tag)
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                            </div>
                        @endif


                        <!-- Links -->
                        @if ($resource->github_url || $resource->forum_thread_url)
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-800">
                            <h3 class="font-bold mb-4 text-lg">Links</h3>
                            <div class="space-y-3">
                                @if ($resource->github_url)
                                    <a href="{{ $resource->github_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                        <svg class="w-6 h-6 text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-blue-600 dark:text-blue-400 group-hover:underline font-medium">GitHub Repository</span>
                                    </a>
                                @endif
                                @if ($resource->forum_thread_url)
                                    <a href="{{ $resource->forum_thread_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                        <img
                                            src="{{ asset('mta-logo.png') }}"
                                            alt="MTA Logo"
                                            class="w-6 h-6 grayscale opacity-70 group-hover:opacity-100 transition-opacity"
                                        />
                                        <span class="text-blue-600 dark:text-blue-400 group-hover:underline font-medium">MTA Forum Thread</span>
                                    </a>
                                @endif
                            </div>
                            </div>
                        @endif
                    @endif

                    <!-- Author Actions -->
                    @auth
                        @if (auth()->user()->isModerator())
                            <div class="border-t pt-4 mt-4">
                                <h3 class="font-bold mb-4 text-red-600 dark:text-red-400">Moderation</h3>
                                @if ($resource->is_disabled)
                                    <form method="POST" action="{{ route('resources.enable', $resource) }}" class="mb-2">
                                        @csrf
                                        <flux:button type="submit" variant="primary" class="w-full">
                                            Enable Resource
                                        </flux:button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('resources.disable', $resource) }}" class="mb-2">
                                        @csrf
                                        <flux:button type="submit" variant="outline" class="w-full">
                                            Disable Resource
                                        </flux:button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
</x-layouts.app>

