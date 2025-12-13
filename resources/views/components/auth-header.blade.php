@props([
    'title',
    'description',
])

<div class="flex w-full flex-col">
    <flux:heading size="xl" class="mb-2">{{ $title }}</flux:heading>
    <flux:subheading class="text-neutral-600 dark:text-neutral-400">{{ $description }}</flux:subheading>
</div>
