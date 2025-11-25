<x-layouts.app :title="__('News')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <!-- Introduction Section -->
        <section>
            <div class="flex items-start gap-6">
                <div class="shrink-0">
                    <x-logo :link="false" class="justify-start" />
                </div>
                <div class="flex-1">
                    <flux:heading size="lg" class="mb-3">{{ __('Welcome to the MTA Community') }}</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        <a href="https://multitheftauto.com/" class="underline" target="_blank">{{ __('Multi Theft Auto (MTA)') }}</a>
                        {{ __("is a multiplayer modification for Rockstar's Grand Theft Auto game series: a piece of software that adapts the game in such a way, you can play Grand Theft Auto with your friends online and develop your own gamemodes. It was brought into life because of the lacking multiplayer functionality in the Grand Theft Auto series of games, and provides a completely new platform on-top of the original game, allowing for players to play all sorts and types of game-modes anywhere they want, and developers to develop using our very powerful scripting engine.") }}
                    </flux:text>
                </div>
            </div>
        </section>
        <section>
            <a href="https://discord.com/invite/mtasa" target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors group">
                <div class="shrink-0">
                    <svg class="w-8 h-8 text-[#5865F2] group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <flux:heading size="md" class="mb-1 text-neutral-900 dark:text-neutral-100">
                        {{ __('Stay tuned to our Discord server') }}
                    </flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        {{ __('Follow the announcement channels to get notified about new updates and events.') }}
                    </flux:text>
                </div>
                <div class="shrink-0">
                    <svg class="w-5 h-5 text-neutral-400 group-hover:text-neutral-600 dark:group-hover:text-neutral-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        </section>

        <!-- Latest News Section -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">{{ __('Latest News') }}</flux:heading>
                    @if($fetchTimestamp)
                        <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1">
                            {{ __('Fetched') }}: {{ \Carbon\Carbon::createFromTimestamp($fetchTimestamp)->format('M j, Y g:i A') }}
                        </p>
                    @endif
                </div>
                <flux:link :href="config('mta.news_forum_url')" variant="outline" target="_blank" rel="noopener noreferrer" class="text-sm">
                    {{ __('View on Forum') }}
                </flux:link>
            </div>
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
