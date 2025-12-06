@props(['media'])

@php
    $user = auth()->user();
    // Ensure reactions are loaded for counting
    if (!$media->relationLoaded('reactions')) {
        $media->load('reactions.user');
    }
    $userReaction = $media->userReaction($user);
    $reactionCounts = $media->reaction_counts;
    $totalReactions = $media->reactions_count ?? $media->reactionCount();
    $youtubeVideoId = $media->isVideo() ? $media->youtube_video_id : null;

    // Group reactions by emoji with user names for tooltips
    $reactionUsersByEmoji = [];
    foreach ($media->reactions as $reaction) {
        if (!isset($reactionUsersByEmoji[$reaction->emoji])) {
            $reactionUsersByEmoji[$reaction->emoji] = [];
        }
        $reactionUsersByEmoji[$reaction->emoji][] = $reaction->user->name;
    }
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/60">
    <!-- Media Content -->
    <div class="relative aspect-[16/9] overflow-hidden bg-slate-100 dark:bg-slate-800">
        @if ($media->isImage())
            @if ($media->images->count() > 0)
                <div class="media-slideshow relative h-full w-full" data-media-id="{{ $media->id }}">
                    @foreach ($media->images as $index => $image)
                        <div class="media-slide {{ $index === 0 ? 'active' : 'hidden' }}" data-index="{{ $index }}">
                            <img
                                src="{{ Storage::disk('public')->url($image->path) }}"
                                alt="Media image {{ $index + 1 }}"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    @endforeach

                    @if ($media->images->count() > 1)
                        <!-- Navigation arrows -->
                        <button
                            class="media-prev absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white opacity-0 transition-opacity hover:bg-black/70 group-hover:opacity-100"
                            onclick="changeSlide({{ $media->id }}, -1)"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            class="media-next absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-2 text-white opacity-0 transition-opacity hover:bg-black/70 group-hover:opacity-100"
                            onclick="changeSlide({{ $media->id }}, 1)"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Slide indicators -->
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                            @foreach ($media->images as $index => $image)
                                <button
                                    class="media-indicator h-2 w-2 rounded-full {{ $index === 0 ? 'bg-white' : 'bg-white/50' }}"
                                    onclick="goToSlide({{ $media->id }}, {{ $index }})"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <svg class="h-16 w-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        @elseif ($media->isVideo() && $youtubeVideoId)
            <div class="youtube-thumbnail-container relative h-full w-full group" data-youtube-id="{{ $youtubeVideoId }}" data-media-id="{{ $media->id }}">
                <img
                    src="https://img.youtube.com/vi/{{ $youtubeVideoId }}/maxresdefault.jpg"
                    alt="Video thumbnail"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <div class="absolute inset-0 flex items-center justify-center bg-black/20 transition-opacity group-hover:bg-black/30">
                    <button
                        type="button"
                        onclick="loadYouTubeVideo({{ $media->id }}, '{{ $youtubeVideoId }}', this)"
                        class="youtube-play-button rounded-full bg-red-600 p-4 shadow-lg transition-all hover:bg-red-700 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        aria-label="Play video"
                    >
                        <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Content -->
    <div class="flex flex-1 flex-col gap-4 p-5">
        <!-- Description -->
        <p class="text-sm text-slate-700 dark:text-slate-300">
            {{ $media->description }}
        </p>

        <!-- Author Info -->
        <div class="flex items-center gap-2 justify-between">
            <div class="flex items-center gap-2">
                <x-user-avatar :user="$media->user" size="sm" />
                <a
                    href="{{ route('profile.show', $media->user) }}"
                    wire:navigate
                    class="text-sm font-medium text-slate-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
                >
                    {{ $media->user->name }}
                </a>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    • {{ $media->created_at->diffForHumans() }}
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-1">
                @auth
                    @if ($user && ($user->id === $media->user_id || $user->isModerator()))
                        <form
                            action="{{ route('media.destroy', $media) }}"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this media?');"
                            class="inline"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-full p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-500 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                title="Delete"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    @endif
                @endauth

                @if ($media->isVideo() && $youtubeVideoId)
                    <button
                        type="button"
                        onclick="copyVideoLink({{ $media->id }}, '{{ $youtubeVideoId }}', event)"
                        class="inline-flex items-center justify-center rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        title="Copy Link"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                @endif

                <button
                    type="button"
                    id="view-reactions-btn-{{ $media->id }}"
                    x-data="{ totalReactions: {{ $totalReactions }} }"
                    x-show="totalReactions > 0"
                    onclick="openReactionsModal({{ $media->id }})"
                    class="inline-flex items-center justify-center rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                    title="View Reactions"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Reactions -->
        @php
            $validEmojis = \App\Models\MediaReaction::VALID_EMOJIS;
            $isOwner = $user && $user->id === $media->user_id;

            // Sort emojis by count (highest first), but include user's reaction even if count is 0
            $sortedEmojis = collect($validEmojis)->sortByDesc(function ($emoji) use ($reactionCounts, $userReaction) {
                $count = $reactionCounts[$emoji] ?? 0;
                // If user has this reaction but count is 0, treat it as 1 for sorting
                if ($count === 0 && $userReaction && $userReaction->emoji === $emoji) {
                    return 1;
                }
                return $count;
            })->values()->all();
        @endphp
        <div
            x-data="reactionState({{ $media->id }}, {{ json_encode($reactionCounts) }}, {{ $userReaction ? json_encode(['emoji' => $userReaction->emoji]) : 'null' }}, {{ $isOwner ? 'true' : 'false' }}, {{ json_encode($reactionUsersByEmoji) }})"
            class="relative flex items-center gap-2 flex-wrap"
        >
            @foreach ($sortedEmojis as $emoji)
                @php
                    $count = $reactionCounts[$emoji] ?? 0;
                    $isUserReaction = $userReaction && $userReaction->emoji === $emoji;
                    $isCustom = \App\Models\MediaReaction::isCustomReaction($emoji);
                    $customImagePath = $isCustom ? \App\Models\MediaReaction::getCustomReactionImage($emoji) : null;
                @endphp
                <template x-if="getCount('{{ $emoji }}') > 0 || getUserReaction() === '{{ $emoji }}'">
                    <div>
                        @if ($isOwner)
                            <div
                                :title="getReactionTooltip('{{ $emoji }}')"
                                class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-800"
                            >
                                @if ($isCustom && $customImagePath)
                                    <img src="{{ asset($customImagePath) }}" alt="{{ $emoji }}" class="h-5 w-5 object-contain" />
                                @else
                                    <span>{{ $emoji }}</span>
                                @endif
                                <span class="text-xs font-semibold" x-text="getCount('{{ $emoji }}')"></span>
                            </div>
                        @elseif ($user)
                            <button
                                @click="toggleReaction('{{ $emoji }}')"
                                :class="getUserReaction() === '{{ $emoji }}' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-slate-200 bg-white hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800'"
                                :title="getReactionTooltip('{{ $emoji }}')"
                                class="reaction-button flex items-center gap-1 rounded-full border px-2 py-1 text-sm transition"
                            >
                                @if ($isCustom && $customImagePath)
                                    <img src="{{ asset($customImagePath) }}" alt="{{ $emoji }}" class="h-5 w-5 object-contain" />
                                @else
                                    <span>{{ $emoji }}</span>
                                @endif
                                <span class="text-xs font-semibold" x-text="getCount('{{ $emoji }}')"></span>
                            </button>
                        @else
                            <div
                                :title="getReactionTooltip('{{ $emoji }}')"
                                class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-800"
                            >
                                @if ($isCustom && $customImagePath)
                                    <img src="{{ asset($customImagePath) }}" alt="{{ $emoji }}" class="h-5 w-5 object-contain" />
                                @else
                                    <span>{{ $emoji }}</span>
                                @endif
                                <span class="text-xs font-semibold" x-text="getCount('{{ $emoji }}')"></span>
                            </div>
                        @endif
                    </div>
                </template>
            @endforeach

            @auth
                @if (!$isOwner)
                    <div class="flex items-center gap-1">
                        <button
                            @click="togglePicker()"
                            class="reaction-toggle-button flex items-center justify-center rounded-full border border-slate-200 bg-white p-1.5 text-sm transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800 shrink-0"
                            title="Add reaction"
                        >
                            <!-- Plus Icon (default) -->
                            <svg x-show="!pickerOpen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <!-- Minus Icon (when open) -->
                            <svg x-show="pickerOpen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </button>

                        <!-- Inline Emoji Picker -->
                        <div x-show="pickerOpen" @click.away="pickerOpen = false" class="emoji-picker flex items-center gap-1 flex-wrap">
                            @foreach (\App\Models\MediaReaction::VALID_EMOJIS as $emoji)
                                @php
                                    $isCustom = \App\Models\MediaReaction::isCustomReaction($emoji);
                                    $customImagePath = $isCustom ? \App\Models\MediaReaction::getCustomReactionImage($emoji) : null;
                                @endphp
                                <button
                                    x-show="getUserReaction() !== '{{ $emoji }}'"
                                    @click="addReaction('{{ $emoji }}')"
                                    class="emoji-option flex items-center justify-center rounded-full border border-slate-200 bg-white p-1 text-base transition hover:border-blue-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700"
                                    title="React with {{ $emoji }}"
                                >
                                    @if ($isCustom && $customImagePath)
                                        <img src="{{ asset($customImagePath) }}" alt="{{ $emoji }}" class="h-5 w-5 object-contain" />
                                    @else
                                        {{ $emoji }}
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</div>

<!-- Reactions Modal -->
<div
    id="reactions-modal-{{ $media->id }}"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    onclick="closeReactionsModal({{ $media->id }}, event)"
>
    <div
        class="relative max-w-md w-full max-h-[80vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
        onclick="event.stopPropagation()"
    >
        <!-- Modal Header -->
        <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Reactions</h3>
            <button
                onclick="closeReactionsModal({{ $media->id }})"
                class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Content -->
        <div id="reactions-content-{{ $media->id }}" class="p-6">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function reactionState(mediaId, initialCounts, initialUserReaction, isOwner, initialReactionUsers) {
        return {
            mediaId: mediaId,
            reactionCounts: initialCounts || {},
            userReaction: initialUserReaction ? initialUserReaction.emoji : null,
            isOwner: isOwner,
            pickerOpen: false,
            loading: false,
            reactionUsers: initialReactionUsers || {},

            getCount(emoji) {
                return this.reactionCounts[emoji] || 0;
            },

            getUserReaction() {
                return this.userReaction;
            },

            getReactionTooltip(emoji) {
                const users = this.reactionUsers[emoji] || [];
                if (users.length === 0) {
                    return '';
                }

                const maxNames = 3;
                const displayNames = users.slice(0, maxNames);
                const remaining = users.length - maxNames;

                let tooltip = displayNames.join(', ');
                if (remaining > 0) {
                    tooltip += `, ...`;
                }

                return tooltip;
            },

            togglePicker() {
                this.pickerOpen = !this.pickerOpen;
            },

            async toggleReaction(emoji) {
                if (this.loading) return;
                await this.addReaction(emoji);
            },

            async addReaction(emoji) {
                if (this.loading) return;

                this.loading = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                if (!csrfToken) {
                    alert('CSRF token not found. Please refresh the page and try again.');
                    this.loading = false;
                    return;
                }

                try {
                    const response = await fetch(`/media/${this.mediaId}/reactions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ emoji: emoji })
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        throw new Error(data.message || 'Failed to add reaction');
                    }

                    const data = await response.json();

                    // Update reaction counts
                    if (data.reaction_counts) {
                        this.reactionCounts = data.reaction_counts;
                    }

                    // Update user reaction
                    if (data.removed) {
                        this.userReaction = null;
                    } else if (data.user_reaction) {
                        this.userReaction = data.user_reaction.emoji;
                    }

                    // Update reaction users for tooltips
                    if (data.reaction_users) {
                        this.reactionUsers = data.reaction_users;
                    }

                    // Close picker
                    this.pickerOpen = false;

                    // Update total reactions count for the view reactions button
                    const totalReactions = Object.values(this.reactionCounts).reduce((sum, count) => sum + count, 0);
                    const viewReactionsBtn = document.getElementById(`view-reactions-btn-${this.mediaId}`);
                    if (viewReactionsBtn) {
                        const alpineData = Alpine.$data(viewReactionsBtn);
                        if (alpineData) {
                            alpineData.totalReactions = totalReactions;
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || 'Failed to add reaction. Please try again.');
                } finally {
                    this.loading = false;
                }
            }
        };
    }

    function changeSlide(mediaId, direction) {
        const slideshow = document.querySelector(`.media-slideshow[data-media-id="${mediaId}"]`);
        const slides = slideshow.querySelectorAll('.media-slide');
        const indicators = slideshow.querySelectorAll('.media-indicator');
        let currentIndex = Array.from(slides).findIndex(slide => !slide.classList.contains('hidden'));

        slides[currentIndex].classList.add('hidden');
        indicators[currentIndex].classList.remove('bg-white');
        indicators[currentIndex].classList.add('bg-white/50');

        currentIndex += direction;
        if (currentIndex < 0) currentIndex = slides.length - 1;
        if (currentIndex >= slides.length) currentIndex = 0;

        slides[currentIndex].classList.remove('hidden');
        indicators[currentIndex].classList.remove('bg-white/50');
        indicators[currentIndex].classList.add('bg-white');
    }

    function goToSlide(mediaId, index) {
        const slideshow = document.querySelector(`.media-slideshow[data-media-id="${mediaId}"]`);
        const slides = slideshow.querySelectorAll('.media-slide');
        const indicators = slideshow.querySelectorAll('.media-indicator');

        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
        });

        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('bg-white', i === index);
            indicator.classList.toggle('bg-white/50', i !== index);
        });
    }

    function loadYouTubeVideo(mediaId, youtubeVideoId, button) {
        const container = document.querySelector(`.youtube-thumbnail-container[data-media-id="${mediaId}"]`);
        if (!container) return;

        // Create iframe
        const iframe = document.createElement('iframe');
        iframe.className = 'h-full w-full';
        iframe.src = `https://www.youtube.com/embed/${youtubeVideoId}?autoplay=1`;
        iframe.frameBorder = '0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.loading = 'lazy';

        // Replace container content with iframe
        container.innerHTML = '';
        container.appendChild(iframe);
    }

    function copyVideoLink(mediaId, youtubeVideoId, event) {
        const videoUrl = `https://www.youtube.com/watch?v=${youtubeVideoId}`;

        navigator.clipboard.writeText(videoUrl).then(() => {
            // Show feedback
            const button = event.target.closest('button');
            const originalSvg = button.querySelector('svg').outerHTML;
            button.innerHTML = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
            button.classList.add('text-green-600', 'dark:text-green-400');

            setTimeout(() => {
                button.innerHTML = originalSvg;
                button.classList.remove('text-green-600', 'dark:text-green-400');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy link. Please try again.');
        });
    }

    function openReactionsModal(mediaId) {
        const modal = document.getElementById(`reactions-modal-${mediaId}`);
        const content = document.getElementById(`reactions-content-${mediaId}`);

        // Show modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Fetch reactions
        fetch(`/media/${mediaId}/reactions`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            renderReactions(mediaId, data.reactions);
        })
        .catch(error => {
            console.error('Error fetching reactions:', error);
            content.innerHTML = '<div class="text-center py-8 text-slate-500 dark:text-slate-400">Failed to load reactions. Please try again.</div>';
        });
    }

    function formatUserName(user) {
        const userName = escapeHtml(user.user_name);
        if (user.profile_is_public) {
            const profileUrl = `/profile/${user.user_id}`;
            return `<a href="${profileUrl}" class="hover:underline" wire:navigate>${userName}</a>`;
        }
        return userName;
    }

    function renderReactions(mediaId, reactions) {
        const content = document.getElementById(`reactions-content-${mediaId}`);

        if (!reactions || Object.keys(reactions).length === 0) {
            content.innerHTML = '<div class="text-center py-8 text-slate-500 dark:text-slate-400">No reactions yet.</div>';
            return;
        }

        let html = '<div class="space-y-4">';

        const validEmojis = @json(\App\Models\MediaReaction::VALID_EMOJIS);

        // Helper function to check if emoji is custom
        function isCustomReaction(emoji) {
            return emoji.startsWith('custom:');
        }

        // Helper function to get custom reaction image path
        function getCustomReactionImage(emoji) {
            if (!isCustomReaction(emoji)) {
                return null;
            }
            const reactionName = emoji.replace('custom:', '');
            return `/images/reactions/${reactionName}.png`;
        }

        // Sort emojis by count (highest first)
        const sortedEmojis = validEmojis
            .filter(emoji => reactions[emoji] && reactions[emoji].length > 0)
            .sort((a, b) => {
                const countA = reactions[a] ? reactions[a].length : 0;
                const countB = reactions[b] ? reactions[b].length : 0;
                return countB - countA; // Descending order
            });

        sortedEmojis.forEach(emoji => {
            if (reactions[emoji] && reactions[emoji].length > 0) {
                const users = reactions[emoji];
                const maxVisible = 5;
                const hasMore = users.length > maxVisible;
                const visibleUsers = users.slice(0, maxVisible);
                const hiddenUsers = hasMore ? users.slice(maxVisible) : [];

                // Determine if this is a custom reaction and get image path
                const isCustom = isCustomReaction(emoji);
                const imagePath = isCustom ? getCustomReactionImage(emoji) : null;
                const emojiDisplay = isCustom && imagePath
                    ? `<img src="${imagePath}" alt="${emoji}" class="h-6 w-6 object-contain" />`
                    : `<span class="text-xl">${emoji}</span>`;

                html += `
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">${users.length}</span>
                        ${emojiDisplay}
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            <span id="reaction-users-visible-${mediaId}-${emoji}">
                                ${visibleUsers.map(user => formatUserName(user)).join(', ')}
                            </span>
                            ${hasMore ? `
                                <span id="reaction-users-hidden-${mediaId}-${emoji}" class="hidden" data-count="${hiddenUsers.length}">
                                    , ${hiddenUsers.map(user => formatUserName(user)).join(', ')}
                                </span>
                                <button
                                    onclick="toggleReactionUsers(${mediaId}, '${emoji}')"
                                    class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 ml-1"
                                >
                                    <span id="reaction-toggle-text-${mediaId}-${emoji}">and ${hiddenUsers.length} more...</span>
                                </button>
                            ` : ''}
                        </span>
                    </div>
                `;
            }
        });

        html += '</div>';
        content.innerHTML = html;
    }

    function toggleReactionUsers(mediaId, emoji) {
        const hiddenSpan = document.getElementById(`reaction-users-hidden-${mediaId}-${emoji}`);
        const toggleText = document.getElementById(`reaction-toggle-text-${mediaId}-${emoji}`);

        if (!hiddenSpan || !toggleText) return;

        const hiddenCount = parseInt(hiddenSpan.getAttribute('data-count')) || 0;

        if (hiddenSpan.classList.contains('hidden')) {
            hiddenSpan.classList.remove('hidden');
            toggleText.textContent = 'Show less';
        } else {
            hiddenSpan.classList.add('hidden');
            toggleText.textContent = `and ${hiddenCount} more...`;
        }
    }

    function closeReactionsModal(mediaId, event) {
        if (event && event.target !== event.currentTarget) {
            return;
        }

        const modal = document.getElementById(`reactions-modal-${mediaId}`);
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="reactions-modal-"]').forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    const mediaId = modal.id.replace('reactions-modal-', '');
                    closeReactionsModal(mediaId);
                }
            });
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

