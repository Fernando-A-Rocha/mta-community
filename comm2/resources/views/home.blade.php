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

        <!-- Latest News Section -->
        <section>
            <flux:heading size="lg" class="mb-4">{{ __('Latest News') }}</flux:heading>
            @if($news->count() > 0)
                <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @foreach($news as $entry)
                        @php
                            $isFirstOnFirstPage = $loop->first && $news->currentPage() === 1;
                        @endphp
                        <div class="py-4 first:pt-0 {{ $isFirstOnFirstPage ? 'pb-6' : '' }}">
                            <div>
                                <h3 class="{{ $isFirstOnFirstPage ? 'text-2xl font-bold' : 'text-base font-medium' }} {{ $isFirstOnFirstPage ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400' }} mb-2">
                                    <a href="{{ $entry['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                        {{ $entry['title'] }}
                                    </a>
                                </h3>
                                <div class="flex items-center gap-4 {{ $isFirstOnFirstPage ? 'text-sm' : 'text-xs' }} text-neutral-500 dark:text-neutral-500">
                                    <span>{{ __('By') }}: {{ $entry['author'] }}</span>
                                    <span>{{ __('Posted') }}: {{ $entry['date']->format('F j, Y') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $news->links() }}
                </div>
            @else
                <div class="py-8 text-center text-neutral-600 dark:text-neutral-400">
                    <p>{{ __('No news entries available at the moment.') }}</p>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
