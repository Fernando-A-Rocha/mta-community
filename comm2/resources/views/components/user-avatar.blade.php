@props(['user', 'size' => 'sm', 'class' => ''])

@php
    $containerSize = match($size) {
        'xs' => 'h-6 w-6',
        'sm' => 'h-8 w-8',
        'md' => 'h-10 w-10',
        'lg' => 'h-12 w-12',
        default => 'h-8 w-8',
    };

    $iconSize = match($size) {
        'xs' => 'h-3.5 w-3.5',
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
        default => 'h-4 w-4',
    };

    $avatarUrl = $user->avatarUrl();
    $roundedClass = $class ? '' : 'rounded-lg';
@endphp

<span class="relative flex {{ $containerSize }} shrink-0 overflow-hidden {{ $roundedClass }} {{ $class }}">
    @if ($avatarUrl && $user->hasAvatar())
        <img
            src="{{ $avatarUrl }}"
            alt="{{ $user->name }}"
            class="h-full w-full {{ $roundedClass }} object-cover"
        />
    @else
        <span
            class="flex h-full w-full items-center justify-center {{ $roundedClass }} bg-neutral-200 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400"
        >
            <svg
                class="{{ $iconSize }}"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </span>
    @endif
</span>

