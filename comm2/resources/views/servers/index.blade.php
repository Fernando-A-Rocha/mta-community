<x-layouts.app :title="__('Servers')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section>
            <div class="mb-4">
                <flux:heading size="lg" class="mb-2">{{ __('Active MTA Servers') }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    {{ __('There are :players players playing on :servers public MTA :version servers.', [
                        'players' => number_format($statistics['total_players']),
                        'servers' => number_format($statistics['total_servers']),
                        'version' => config('mta.server_version', '1.6'),
                    ]) }}
                </flux:text>
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
                <!-- Table Header -->
                <div class="grid grid-cols-[60px_1fr_120px_180px] gap-4 py-3 bg-neutral-50 dark:bg-zinc-800/50">
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
                    <div class="grid grid-cols-[60px_1fr_120px_180px] gap-4 py-3 hover:bg-neutral-50 dark:hover:bg-zinc-800">
                        <!-- Position Index Column -->
                        <div class="flex items-center text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                            {{ $server['original_position'] ?? (($servers->currentPage() - 1) * $servers->perPage() + $index + 1) }}
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

