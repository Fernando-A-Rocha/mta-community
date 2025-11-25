<x-layouts.app :title="__('Servers')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <section>
            <div class="mb-4">
                <flux:heading size="lg" class="mb-2">{{ __('Active MTA Servers') }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    {{ __('There are :players players playing on :servers public MTA :version servers.', [
                        'players' => number_format($statistics['total_players']),
                        'servers' => number_format($statistics['total_servers']),
                        'version' => config('mta.current_stable_version', '1.6'),
                    ]) }}
                </flux:text>
                @if($fetchTimestamp)
                    <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1">
                        {{ __('Fetched') }}: {{ \Carbon\Carbon::createFromTimestamp($fetchTimestamp)->format('M j, Y g:i A') }}
                    </p>
                @endif
            </div>
            <!-- Search Bar -->
            <div class="mb-4">
                <form method="GET" action="{{ route('servers.index') }}" class="flex items-center gap-3">
                    <div class="flex-1">
                        <flux:input
                            name="search"
                            type="search"
                            :value="old('search', $searchQuery)"
                            :placeholder="__('Search servers by name or address...')"
                            autocomplete="off"
                        />
                    </div>
                    <flux:button type="submit" variant="primary">
                        {{ __('Search') }}
                    </flux:button>
                    @if (!empty($searchQuery))
                        <flux:button
                            :href="route('servers.index')"
                            variant="outline"
                            wire:navigate
                        >
                            {{ __('Clear') }}
                        </flux:button>
                    @endif
                </form>
            </div>
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                <!-- Desktop Table Header (hidden on mobile) -->
                <div class="hidden md:grid grid-cols-[60px_1fr_120px_180px] gap-4 py-3 bg-neutral-50 dark:bg-zinc-800/50">
                    <div class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                        #
                    </div>
                    <div class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                        {{ __('Name') }}
                    </div>
                    <div class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                        {{ __('Players') }}
                    </div>
                    <div class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                        {{ __('Address') }}
                    </div>
                </div>
                <!-- Table Rows -->
                @forelse ($servers as $index => $server)
                    @php
                        $serverId = 'server-' . $index;
                        $position = $server['original_position'] ?? (($servers->currentPage() - 1) * $servers->perPage() + $index + 1);
                    @endphp
                    <!-- Desktop View (hidden on mobile) -->
                    <div class="hidden md:grid grid-cols-[60px_1fr_120px_180px] gap-4 py-3 hover:bg-neutral-50 dark:hover:bg-zinc-800">
                        <!-- Position Index Column -->
                        <div class="flex items-center text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                            {{ $position }}
                        </div>
                        <!-- Name Column -->
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="server-name truncate font-medium text-neutral-900 dark:text-neutral-100">
                                {{ utf8_decode($server['name']) }}
                            </span>
                            @if ($server['password'] === 1)
                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" title="{{ __('Password Protected') }}">
                                    🔒
                                </span>
                            @endif
                        </div>
                        <!-- Players Column -->
                        <div class="flex items-center text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                            {{ $server['players'] }}/{{ $server['maxplayers'] }}
                        </div>
                        <!-- IP:Port Column -->
                        <div class="flex items-center text-sm whitespace-nowrap">
                            <a href="mtasa://{{ $server['ip'] }}:{{ $server['port'] }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ $server['ip'] }}:{{ $server['port'] }}
                            </a>
                        </div>
                    </div>
                    <!-- Mobile View (visible on mobile only) -->
                    <div 
                        class="md:hidden py-3 px-4 hover:bg-neutral-50 dark:hover:bg-zinc-800 cursor-pointer"
                        x-data="{ open: false }"
                        @click="open = true"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-neutral-900 dark:text-neutral-100 truncate">
                                        {{ utf8_decode($server['name']) }}
                                    </span>
                                    @if ($server['password'] === 1)
                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" title="{{ __('Password Protected') }}">
                                            🔒
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ __('Players') }}: {{ $server['players'] }}/{{ $server['maxplayers'] }}
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <!-- Mobile Modal -->
                        <div 
                            x-show="open"
                            x-cloak
                            class="fixed inset-0 z-50"
                            @keydown.escape.window="open = false"
                        >
                            <!-- Backdrop -->
                            <div 
                                class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @click="open = false"
                            ></div>
                            <!-- Modal Content -->
                            <div class="fixed inset-0 overflow-y-auto">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div 
                                        class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-zinc-800 shadow-xl transition-all"
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        @click.stop
                                    >
                                        <!-- Modal Header -->
                                        <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-700 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                                {{ __('Server Details') }}
                                            </h3>
                                            <button 
                                                @click="open = false"
                                                class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors"
                                            >
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- Modal Body -->
                                        <div class="px-6 py-4 space-y-4">
                                            <div>
                                                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                    {{ __('Name') }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-base text-neutral-900 dark:text-neutral-100 break-words">
                                                        {{ utf8_decode($server['name']) }}
                                                    </div>
                                                    @if ($server['password'] === 1)
                                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" title="{{ __('Password Protected') }}">
                                                            🔒
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                    {{ __('Position') }}
                                                </div>
                                                <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                    #{{ $position }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                    {{ __('Players') }}
                                                </div>
                                                <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                    {{ $server['players'] }}/{{ $server['maxplayers'] }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                    {{ __('Address') }}
                                                </div>
                                                <div class="text-base">
                                                    <a href="mtasa://{{ $server['ip'] }}:{{ $server['port'] }}" class="text-blue-600 hover:underline dark:text-blue-400 break-all">
                                                        {{ $server['ip'] }}:{{ $server['port'] }}
                                                    </a>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                    {{ __('Version') }}
                                                </div>
                                                <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                    {{ $server['version'] ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Modal Footer -->
                                        <div class="border-t border-neutral-200 dark:border-neutral-700 px-6 py-4 flex justify-end">
                                            <flux:button 
                                                variant="primary"
                                                @click="open = false"
                                            >
                                                {{ __('Close') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:text class="text-neutral-600 dark:text-neutral-400">
                            {{ __('No servers found.') }}
                        </flux:text>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($servers->hasPages())
                <div class="mt-4 pt-4">
                    <div class="flex items-center justify-center">
                        {{ $servers->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>

