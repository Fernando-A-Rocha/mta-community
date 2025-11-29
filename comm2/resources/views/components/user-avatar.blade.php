@props(['user', 'size' => 'sm'])

@php
    $containerSize = match($size) {
        'xs' => 'h-6 w-6',
        'sm' => 'h-8 w-8',
        'md' => 'h-10 w-10',
        'lg' => 'h-12 w-12',
        default => 'h-8 w-8',
    };
    
    $textSize = match($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
        default => 'text-sm',
    };
@endphp

<span class="relative flex {{ $containerSize }} shrink-0 overflow-hidden rounded-lg">
    <span
        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white {{ $textSize }}"
    >
        {{ $user->initials() }}
    </span>
</span>

