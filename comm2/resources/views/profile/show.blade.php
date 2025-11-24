<x-layouts.app :title="__('Profile') . ' - ' . $user->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-2xl font-semibold text-black dark:bg-neutral-700 dark:text-white">
                {{ $user->initials() }}
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                @if ($isOwner)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $user->email }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
                        {{ __('Profile Visibility') }}:
                        <span class="font-medium">
                            {{ ($user->profile_visibility ?? 'public') === 'public' ? __('Public') : __('Private') }}
                        </span>
                    </p>
                @endif
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-500">
                    {{ __('Member since') }}: {{ $user->created_at->format('F Y') }}
                </p>
            </div>
        </div>

        <flux:separator />

        <div class="space-y-4">
            @if ($resources->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold mb-3">{{ __('Resources Uploaded') }}</h2>
                    <div class="space-y-2">
                        @foreach ($resources as $resource)
                            <div class="flex items-center justify-between py-2 border-b border-neutral-200 dark:border-neutral-700 last:border-0">
                                <a href="{{ route('resources.show', $resource) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                    {{ $resource->display_name }}
                                </a>
                                @if ($resource->currentVersion)
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                        v{{ $resource->currentVersion->version }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

