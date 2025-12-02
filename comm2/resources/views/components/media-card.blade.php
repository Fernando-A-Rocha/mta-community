@props(['media'])

@php
    $user = auth()->user();
    // Ensure reactions are loaded for counting
    if (!$media->relationLoaded('reactions')) {
        $media->load('reactions');
    }
    $userReaction = $media->userReaction($user);
    $reactionCounts = $media->reaction_counts;
    $totalReactions = $media->reactions_count ?? $media->reactionCount();
    $uploadService = app(\App\Services\MediaUploadService::class);
    $youtubeVideoId = $media->isVideo() ? $uploadService->extractYouTubeVideoId($media->youtube_url) : null;
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/60">
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
            <iframe
                class="h-full w-full"
                src="https://www.youtube.com/embed/{{ $youtubeVideoId }}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        @endif
    </div>

    <!-- Content -->
    <div class="flex flex-1 flex-col gap-4 p-5">
        <!-- Description -->
        <p class="text-sm text-slate-700 dark:text-slate-300">
            {{ $media->description }}
        </p>

        <!-- Author Info -->
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
                            class="ml-1 inline-flex items-center justify-center rounded-full p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-500 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                            title="Delete media"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                @endif
            @endauth
        </div>

        <!-- Reactions -->
        <div class="relative flex items-center gap-2 flex-wrap">
            @php
                $validEmojis = \App\Models\MediaReaction::VALID_EMOJIS;
                $isOwner = $user && $user->id === $media->user_id;
            @endphp
            @foreach ($validEmojis as $emoji)
                @php
                    $count = $reactionCounts[$emoji] ?? 0;
                    $isUserReaction = $userReaction && $userReaction->emoji === $emoji;
                @endphp
                @if ($count > 0 || $isUserReaction)
                    @if (!$isOwner)
                        <button
                            class="reaction-button flex items-center gap-1 rounded-full border px-2 py-1 text-sm transition {{ $isUserReaction ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-slate-200 bg-white hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800' }}"
                            data-emoji="{{ $emoji }}"
                            data-media-id="{{ $media->id }}"
                            data-is-user-reaction="{{ $isUserReaction ? 'true' : 'false' }}"
                            onclick="toggleReaction({{ $media->id }}, '{{ $emoji }}', {{ $isUserReaction ? 'true' : 'false' }})"
                        >
                            <span>{{ $emoji }}</span>
                            <span class="text-xs font-semibold">{{ $count }}</span>
                        </button>
                    @else
                        <div
                            class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-800"
                        >
                            <span>{{ $emoji }}</span>
                            <span class="text-xs font-semibold">{{ $count }}</span>
                        </div>
                    @endif
                @endif
            @endforeach

            @auth
                @if (!$isOwner)
                    <div class="flex items-center gap-1">
                        <button
                            id="reaction-toggle-{{ $media->id }}"
                            class="reaction-toggle-button flex items-center justify-center rounded-full border border-slate-200 bg-white p-1.5 text-sm transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800 shrink-0"
                            onclick="toggleEmojiPicker({{ $media->id }}, event)"
                            title="Add reaction"
                        >
                            <!-- Plus Icon (default) -->
                            <svg id="plus-icon-{{ $media->id }}" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <!-- Minus Icon (when open) -->
                            <svg id="minus-icon-{{ $media->id }}" class="h-4 w-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </button>

                        <!-- Inline Emoji Picker -->
                        <div id="emoji-picker-{{ $media->id }}" class="emoji-picker hidden flex items-center gap-1 flex-wrap">
                            @foreach (\App\Models\MediaReaction::VALID_EMOJIS as $emoji)
                                @php
                                    // Skip the emoji if user has already reacted with it
                                    $isUserReactionEmoji = $userReaction && $userReaction->emoji === $emoji;
                                @endphp
                                @if (!$isUserReactionEmoji)
                                    <button
                                        class="emoji-option flex items-center justify-center rounded-full border border-slate-200 bg-white p-1 text-base transition hover:border-blue-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700"
                                        onclick="addReaction({{ $media->id }}, '{{ $emoji }}')"
                                        title="React with {{ $emoji }}"
                                    >
                                        {{ $emoji }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</div>

<script>
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

    function toggleEmojiPicker(mediaId, event) {
        const picker = document.getElementById(`emoji-picker-${mediaId}`);
        const plusIcon = document.getElementById(`plus-icon-${mediaId}`);
        const minusIcon = document.getElementById(`minus-icon-${mediaId}`);
        const isCurrentlyOpen = !picker.classList.contains('hidden');

        // Hide all other pickers and reset their icons
        document.querySelectorAll('.emoji-picker').forEach(p => {
            if (p.id !== `emoji-picker-${mediaId}`) {
                p.classList.add('hidden');
                const otherMediaId = p.id.replace('emoji-picker-', '');
                const otherPlus = document.getElementById(`plus-icon-${otherMediaId}`);
                const otherMinus = document.getElementById(`minus-icon-${otherMediaId}`);
                if (otherPlus) otherPlus.classList.remove('hidden');
                if (otherMinus) otherMinus.classList.add('hidden');
            }
        });

        // Toggle this picker
        if (isCurrentlyOpen) {
            picker.classList.add('hidden');
            plusIcon.classList.remove('hidden');
            minusIcon.classList.add('hidden');
        } else {
            picker.classList.remove('hidden');
            plusIcon.classList.add('hidden');
            minusIcon.classList.remove('hidden');
        }

        // Prevent event bubbling
        if (event) {
            event.stopPropagation();
        }
    }

    function addReaction(mediaId, emoji) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            alert('CSRF token not found. Please refresh the page and try again.');
            return;
        }

        fetch(`/media/${mediaId}/reactions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ emoji: emoji })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Failed to add reaction');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.message) {
                // Reload to show updated reactions
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to add reaction. Please try again.');
        });

        // Hide picker and reset icon
        const picker = document.getElementById(`emoji-picker-${mediaId}`);
        const plusIcon = document.getElementById(`plus-icon-${mediaId}`);
        const minusIcon = document.getElementById(`minus-icon-${mediaId}`);
        if (picker) picker.classList.add('hidden');
        if (plusIcon) plusIcon.classList.remove('hidden');
        if (minusIcon) minusIcon.classList.add('hidden');
    }

    function toggleReaction(mediaId, emoji, isUserReaction) {
        // If user already has this reaction, it will be removed by the backend
        addReaction(mediaId, emoji);
    }

    // Close emoji picker when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.reaction-toggle-button') && !e.target.closest('.emoji-picker') && !e.target.closest('.emoji-option')) {
            document.querySelectorAll('.emoji-picker').forEach(p => {
                p.classList.add('hidden');
                const mediaId = p.id.replace('emoji-picker-', '');
                const plusIcon = document.getElementById(`plus-icon-${mediaId}`);
                const minusIcon = document.getElementById(`minus-icon-${mediaId}`);
                if (plusIcon) plusIcon.classList.remove('hidden');
                if (minusIcon) minusIcon.classList.add('hidden');
            });
        }
    });
</script>

