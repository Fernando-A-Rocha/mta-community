@php
    $generatedId = 'flux-toggle-'.\Illuminate\Support\Str::random(8);
    $id = $attributes->get('id', $generatedId);
@endphp

<label for="{{ $id }}" class="inline-flex items-center gap-3 select-none">
    <input
        type="checkbox"
        id="{{ $id }}"
        {{ $attributes->class([
            'peer sr-only',
        ])->merge([
            'role' => 'switch',
        ]) }}
    >

    <span
        class="relative inline-flex h-6 w-11 items-center rounded-full bg-neutral-300 transition-colors duration-200 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-500 peer-checked:bg-primary-500 peer-disabled:opacity-50 dark:bg-neutral-700 dark:peer-checked:bg-primary-400"
        aria-hidden="true"
    >
        <span
            class="mx-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200 peer-checked:translate-x-5 dark:bg-neutral-200"
        ></span>
    </span>

    @if (trim($slot))
        <span class="text-sm text-neutral-700 dark:text-neutral-200">
            {{ $slot }}
        </span>
    @endif
</label>
