<x-layouts.app :title="$resource->display_name">
    @php
        $avgRating = $resource->average_rating;
        $ratingCount = $resource->rating_count;
        $latestVersion = $resource->currentVersion;
        $lastUpdated = $latestVersion?->created_at ?? $resource->updated_at;
        $canViewDetails = ! ($resource->is_disabled && (! auth()->check() || ! auth()->user()->isModerator()));
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <a href="{{ route('resources.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900 hover:underline dark:text-slate-400 dark:hover:text-white">
                ← Back to Resources
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-800/60 bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-lg">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 px-4 py-1.5 text-xs font-bold uppercase tracking-wide text-white shadow-lg">
                            {{ ucfirst($resource->category) }}
                        </span>
                        @if ($resource->is_disabled)
                            <span class="rounded-full bg-red-500/80 px-3 py-1 text-white text-xs font-semibold">Disabled</span>
                        @endif
                        @if ($resource->oop_enabled)
                            <span class="rounded-full bg-emerald-500/90 px-3 py-1 text-white text-xs font-semibold">OOP Ready</span>
                        @endif
                        @if ($resource->tags->isNotEmpty())
                            @foreach ($resource->tags->take(6) as $tag)
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/90">{{ $tag->name }}</span>
                            @endforeach
                            @if ($resource->tags->count() > 6)
                                <span class="text-xs font-medium text-white/70">+{{ $resource->tags->count() - 6 }} more</span>
                            @endif
                        @endif
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold">{{ $resource->display_name }}</h1>
                        <p class="mt-3 max-w-3xl text-base text-slate-200">
                            {{ $resource->short_description }}
                        </p>
                        <a href="{{ route('profile.show', $resource->user) }}" class="mt-3 flex items-center gap-2 text-sm font-semibold text-white hover:underline">
                            <x-user-avatar :user="$resource->user" size="sm" />
                            {{ $resource->user->name }}
                        </a>
                    </div>
                </div>
                <div class="w-full max-w-sm space-y-4 rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-200">Average rating</dt>
                            <dd class="mt-1 text-3xl font-semibold">
                                @if ($avgRating)
                                    {{ number_format($avgRating, 1) }}
                                @else
                                    —
                                @endif
                            </dd>
                            <dd class="text-xs text-slate-300">{{ $ratingCount }} {{ \Illuminate\Support\Str::plural('review', $ratingCount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-200">Downloads</dt>
                            <dd class="mt-1 text-3xl font-semibold">{{ number_format($resource->unique_downloads_count) }}</dd>
                            <dd class="text-xs text-slate-300">unique</dd>
                        </div>
                        <div>
                            <dt class="text-slate-200">Latest version</dt>
                            <dd class="mt-1 text-lg font-semibold">
                                {{ $latestVersion?->version ?? '—' }}
                            </dd>
                            <dd class="text-xs text-slate-300">Released {{ $latestVersion?->created_at?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-200">Updated</dt>
                            <dd class="mt-1 text-lg font-semibold">
                                {{ $lastUpdated?->diffForHumans() ?? '—' }}
                            </dd>
                            <dd class="text-xs text-slate-300">relative</dd>
                        </div>
                    </dl>
                    <div class="flex flex-wrap gap-3">
                        @if (!$resource->is_disabled || (auth()->check() && auth()->user()->isModerator()))
                            @if ($resource->currentVersion)
                                <a href="{{ route('resources.download', $resource) }}">
                                    <flux:button variant="primary">
                                        Download v{{ $resource->currentVersion->version }}
                                    </flux:button>
                                </a>
                            @endif
                        @endif
                        @can('update', $resource)
                            <flux:link :href="route('resources.edit', $resource)" variant="outline">
                                Edit Resource
                            </flux:link>
                        @endcan
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @if (! $canViewDetails)
                    <div class="rounded-3xl border border-dashed border-red-300 bg-white/80 p-8 text-center dark:border-red-800 dark:bg-red-900/10">
                        <h2 class="text-2xl font-bold text-red-700 dark:text-red-200">Resource Disabled</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            This resource has been hidden from public view. Moderators can still access the content and files.
                        </p>
                    </div>
                @else
                    @if ($resource->images->isNotEmpty())
                        @php
                            $primaryImage = $resource->images->first();
                            $secondaryImages = $resource->images->slice(1);
                        @endphp
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Gallery</h2>
                            <div class="grid gap-3 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <div class="relative overflow-hidden rounded-2xl">
                                        <img
                                            src="{{ Storage::disk('public')->url($primaryImage->path) }}"
                                            alt="{{ $resource->display_name }}"
                                            class="h-72 w-full cursor-pointer object-cover transition duration-300 hover:scale-105"
                                            onclick="window.open(this.src, '_blank')"
                                        />
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-1">
                                    @forelse ($secondaryImages as $image)
                                        <div class="overflow-hidden rounded-2xl">
                                            <img
                                                src="{{ Storage::disk('public')->url($image->path) }}"
                                                alt="{{ $resource->display_name }}"
                                                class="h-32 w-full cursor-pointer object-cover transition duration-300 hover:scale-105"
                                                onclick="window.open(this.src, '_blank')"
                                            />
                                        </div>
                                    @empty
                                        <div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700">
                                            More images to be added
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($resource->long_description)
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Description</h2>
                            <div class="prose mt-4 max-w-none text-slate-700 dark:prose-invert dark:text-slate-200">
                                <div class="whitespace-pre-wrap">{!! nl2br(e($resource->long_description)) !!}</div>
                            </div>
                        </div>
                    @endif


                    @if ($resource->versions->isNotEmpty())
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <div class="flex items-center justify-between">
                                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Releases</h2>
                                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $resource->versions->count() }} {{ \Illuminate\Support\Str::plural('release', $resource->versions->count()) }}</span>
                            </div>
                            <div class="mt-6 space-y-4">
                                @foreach ($resource->versions as $version)
                                    <div class="rounded-2xl border border-slate-100/80 p-4 transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-800 dark:hover:border-blue-400/70 dark:hover:bg-blue-900/20 {{ $version->is_current ? 'border-blue-400 bg-blue-50/60 dark:border-blue-400/80 dark:bg-blue-900/30' : '' }}">
                                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Version {{ $version->version }}</h3>
                                                    @if ($version->is_current)
                                                        <span class="rounded-full bg-blue-500/20 px-2 py-1 text-xs font-semibold text-blue-700 dark:text-blue-200">Latest</span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Released {{ $version->created_at->format('M d, Y') }}</p>
                                                @if ($version->changelog)
                                                    <div class="prose mt-3 max-w-none text-sm text-slate-600 dark:prose-invert dark:text-slate-200">
                                                        <div class="whitespace-pre-wrap">{!! nl2br(e($version->changelog)) !!}</div>
                                                    </div>
                                                @endif
                                            </div>
                                            <a href="{{ route('resources.download.version', [$resource, $version->version]) }}">
                                                <flux:button variant="{{ $version->is_current ? 'primary' : 'outline' }}" size="sm">
                                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4-4 4m0 0-4-4m4 4V4" />
                                                    </svg>
                                                    Download
                                                </flux:button>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($resource->ratings->isNotEmpty())
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">Ratings & reviews</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($resource->ratings->take(10) as $rating)
                                    <div class="rounded-2xl border border-slate-100/80 p-4 dark:border-slate-800">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('profile.show', $rating->user) }}" class="flex items-center gap-2 font-semibold text-slate-900 hover:underline dark:text-white">
                                                    <x-user-avatar :user="$rating->user" size="sm" />
                                                    {{ $rating->user->name }}
                                                </a>
                                                <div class="flex">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg class="h-4 w-4 {{ $i <= $rating->rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                            </div>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $rating->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if ($rating->comment)
                                            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $rating->comment }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div class="space-y-6">
                @auth
                    @if (auth()->id() !== $resource->user_id)
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Rate this resource</h3>
                            <form action="{{ route('resources.rating.store', $resource) }}" method="POST" class="mt-4 space-y-4">
                                @csrf
                                <div>
                                    <flux:field>
                                        <flux:label>Rating</flux:label>
                                        <div class="rating-stars flex gap-2" data-selected="{{ old('rating', $userRating?->rating ?? 0) }}">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <label class="cursor-pointer star-label" data-rating="{{ $i }}">
                                                    <input
                                                        type="radio"
                                                        name="rating"
                                                        value="{{ $i }}"
                                                        class="hidden star-input"
                                                        {{ old('rating', $userRating?->rating) == $i ? 'checked' : '' }}
                                                        required
                                                    />
                                                    <svg class="h-8 w-8 text-slate-300 transition-colors star-svg dark:text-slate-600" fill="currentColor" viewBox="0 0 20 20">
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
                                <script>
                                    (function() {
                                        const script = document.currentScript;
                                        const container = script.parentElement.querySelector('.rating-stars');
                                        if (!container) return;

                                        const stars = container.querySelectorAll('.star-label');
                                        const inputs = container.querySelectorAll('.star-input');

                                        function updateStars(selectedRating) {
                                            stars.forEach((star, index) => {
                                                const rating = index + 1;
                                                const svg = star.querySelector('.star-svg');
                                                if (rating <= selectedRating) {
                                                    svg.classList.remove('text-slate-300', 'dark:text-slate-600');
                                                    svg.classList.add('text-amber-400');
                                                } else {
                                                    svg.classList.remove('text-amber-400');
                                                    svg.classList.add('text-slate-300', 'dark:text-slate-600');
                                                }
                                            });
                                        }

                                        // Initialize with selected rating
                                        const selectedInput = container.querySelector('.star-input:checked');
                                        if (selectedInput) {
                                            updateStars(parseInt(selectedInput.value));
                                        }

                                        // Handle input changes
                                        inputs.forEach(input => {
                                            input.addEventListener('change', function() {
                                                updateStars(parseInt(this.value));
                                            });
                                        });

                                        // Handle hover
                                        stars.forEach((star, index) => {
                                            star.addEventListener('mouseenter', function() {
                                                const rating = index + 1;
                                                updateStars(rating);
                                            });
                                        });

                                        container.addEventListener('mouseleave', function() {
                                            const selectedInput = container.querySelector('.star-input:checked');
                                            if (selectedInput) {
                                                updateStars(parseInt(selectedInput.value));
                                            } else {
                                                updateStars(0);
                                            }
                                        });
                                    })();
                                </script>
                                <div>
                                    <flux:field>
                                        <flux:label>Comment (optional)</flux:label>
                                        <flux:textarea
                                            name="comment"
                                            rows="3"
                                            placeholder="Share your experience, tips, or bugs others should know..."
                                        >{{ old('comment', $userRating?->comment) }}</flux:textarea>
                                        @error('comment')
                                            <flux:error>{{ $message }}</flux:error>
                                        @enderror
                                    </flux:field>
                                </div>
                                <flux:button type="submit" variant="primary">
                                    {{ $userRating ? 'Update rating' : 'Submit rating' }}
                                </flux:button>
                            </form>
                        </div>
                    @endif
                @endauth

                @if (!$resource->is_disabled || (auth()->check() && auth()->user()->isModerator()))
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Additional information</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            @if ($resource->min_mta_version)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-500 dark:text-slate-400">Min MTA</dt>
                                    <dd class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ $resource->min_mta_version }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    @if ($resource->github_url || $resource->forum_thread_url)
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Links</h3>
                            <div class="mt-4 space-y-3">
                                @if ($resource->github_url)
                                    <a href="{{ $resource->github_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:hover:border-white/60 dark:hover:bg-slate-800">
                                        <svg class="h-6 w-6 text-slate-700 dark:text-slate-200" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">GitHub Repository</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Open source code & issues</p>
                                        </div>
                                    </a>
                                @endif
                                @if ($resource->forum_thread_url)
                                    <a href="{{ $resource->forum_thread_url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:hover:border-white/60 dark:hover:bg-slate-800">
                                        <img src="{{ asset('mta-logo.png') }}" alt="MTA Logo" class="h-7 w-7 opacity-80 grayscale">
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">MTA Forum Thread</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Community discussion</p>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                @auth
                    @if (auth()->user()->isModerator())
                        <div class="rounded-3xl border border-red-200 bg-red-50/70 p-5 shadow-sm dark:border-red-500/40 dark:bg-red-900/20">
                            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Moderation</h3>
                            <p class="mt-1 text-sm text-red-700/80 dark:text-red-200/80">Toggle visibility for the community.</p>
                            @if ($resource->is_disabled)
                                <form method="POST" action="{{ route('resources.enable', $resource) }}" class="mt-4">
                                    @csrf
                                    <flux:button type="submit" variant="primary" class="w-full">
                                        Enable resource
                                    </flux:button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('resources.disable', $resource) }}" class="mt-4">
                                    @csrf
                                    <flux:button type="submit" variant="outline" class="w-full">
                                        Disable resource
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

