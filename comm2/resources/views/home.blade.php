<x-layouts.app :title="__('News')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <!-- Introduction Section -->
        <section class="pb-6">
            <div class="flex items-start gap-6">
                <div class="shrink-0">
                    <x-logo :link="false" class="justify-start" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="mb-4">{{ __('Welcome to the MTA Community') }}</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        <a href="https://multitheftauto.com/" class="underline" target="_blank">{{ __('Multi Theft Auto (MTA)') }}</a>
                        {{ __("is a multiplayer modification for Rockstar's Grand Theft Auto game series: a piece of software that adapts the game in such a way, you can play Grand Theft Auto with your friends online and develop your own gamemodes. It was brought into life because of the lacking multiplayer functionality in the Grand Theft Auto series of games, and provides a completely new platform on-top of the original game, allowing for players to play all sorts and types of game-modes anywhere they want, and developers to develop using our very powerful scripting engine.") }}
                    </flux:text>
                </div>
            </div>
        </section>

        <!-- Latest Resources Section -->
        <section>
            <flux:heading size="lg" class="mb-4">{{ __('Latest Resources') }}</flux:heading>
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @for ($i = 0; $i < 5; $i++)
                    <div class="py-4 first:pt-0">
                        <div class="flex items-start gap-4">
                            <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ __('Resource Title Placeholder') }} {{ $i + 1 }}
                                </h3>
                                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ __('This is a placeholder description for a resource that would be displayed here. In the future, this will show actual resources posted by community members.') }}
                                </p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-500">
                                    <span>{{ __('Posted') }}: {{ now()->subDays($i)->diffForHumans() }}</span>
                                    <span>{{ __('By') }}: {{ __('Community Member') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    </div>
</x-layouts.app>
