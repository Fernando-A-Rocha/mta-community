<x-layouts.app :title="__('Servers')">
    <div class="flex w-full flex-1 flex-col gap-6" x-data="{
        servers: [],
        statistics: { total_players: 0, total_servers: 0 },
        loading: false,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        fetchTimestamp: null,
        searchQuery: '',
        async loadServers(page = 1, search = '') {
            this.loading = true;
            this.currentPage = page;
            this.searchQuery = search;
            try {
                const params = new URLSearchParams({
                    page: page,
                });
                if (search) {
                    params.append('search', search);
                }
                const response = await fetch(`{{ route('servers.list') }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                this.servers = data.servers;
                this.statistics = data.statistics;
                this.lastPage = data.pagination.last_page;
                this.total = data.pagination.total;
                this.fetchTimestamp = data.fetch_timestamp;
            } catch (error) {
                console.error('Failed to load servers:', error);
                this.servers = [];
            } finally {
                this.loading = false;
            }
        },
        async searchServers() {
            await this.loadServers(1, this.searchQuery);
        },
        clearSearch() {
            this.searchQuery = '';
            this.loadServers(1, '');
        },
        formatFetchTimestamp(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        },
        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        },
        init() {
            this.loadServers();
        }
    }">
        <section>
            <div class="mb-4">
                <flux:heading size="lg" class="mb-2">{{ __('Active MTA Servers') }}</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400" x-show="statistics.total_players !== undefined">
                    {{ __('There are') }} <span x-text="formatNumber(statistics.total_players)"></span> {{ __('players playing on') }} <span x-text="formatNumber(statistics.total_servers)"></span> {{ __('public MTA') }} {{ config('mta.current_stable_version', '1.6') }} {{ __('servers.') }}
                </flux:text>
                <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1" x-show="fetchTimestamp">
                    {{ __('Fetched') }}: <span x-text="formatFetchTimestamp(fetchTimestamp)"></span>
                </p>
            </div>
            <!-- Search Bar -->
            <div class="mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <flux:input
                            x-model="searchQuery"
                            type="search"
                            :placeholder="__('Search servers by name or address...')"
                            autocomplete="off"
                            x-on:keyup.enter="searchServers()"
                        />
                    </div>
                    <flux:button x-on:click="searchServers()" variant="primary" x-bind:disabled="loading">
                        {{ __('Search') }}
                    </flux:button>
                    <flux:button
                        x-show="searchQuery"
                        x-on:click="clearSearch()"
                        variant="outline"
                        x-bind:disabled="loading"
                    >
                        {{ __('Clear') }}
                    </flux:button>
                </div>
            </div>
            <!-- Loading State -->
            <div x-show="loading" class="py-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-neutral-900 dark:border-white"></div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Loading servers...') }}</p>
            </div>

            <!-- Servers List -->
            <div x-show="!loading">
                <template x-if="servers.length === 0">
                    <div class="py-8 text-center">
                        <flux:text class="text-neutral-600 dark:text-neutral-400">
                            {{ __('No servers found.') }}
                        </flux:text>
                    </div>
                </template>
                <div x-show="servers.length > 0" class="divide-y divide-neutral-200 dark:divide-neutral-700">
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
                    <template x-for="(server, index) in servers" :key="server.ip + ':' + server.port">
                        <div>
                            <!-- Desktop View (hidden on mobile) -->
                            <div class="hidden md:grid grid-cols-[60px_1fr_120px_180px] gap-4 py-3 hover:bg-neutral-50 dark:hover:bg-zinc-800">
                                <!-- Position Index Column -->
                                <div class="flex items-center text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                                    <span x-text="server.original_position || ((currentPage - 1) * 30 + index + 1)"></span>
                                </div>
                                <!-- Name Column -->
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="server-name truncate font-medium text-neutral-900 dark:text-neutral-100" x-text="server.name"></span>
                                    <template x-if="server.password === 1">
                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" :title="'{{ __('Password Protected') }}'">
                                            🔒
                                        </span>
                                    </template>
                                </div>
                                <!-- Players Column -->
                                <div class="flex items-center text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                                    <span x-text="server.players + '/' + server.maxplayers"></span>
                                </div>
                                <!-- IP:Port Column -->
                                <div class="flex items-center text-sm whitespace-nowrap">
                                    <a :href="'mtasa://' + server.ip + ':' + server.port" class="text-blue-600 hover:underline dark:text-blue-400" x-text="server.ip + ':' + server.port"></a>
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
                                            <span class="font-medium text-neutral-900 dark:text-neutral-100 truncate" x-text="server.name"></span>
                                            <template x-if="server.password === 1">
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" :title="'{{ __('Password Protected') }}'">
                                                    🔒
                                                </span>
                                            </template>
                                        </div>
                                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                            {{ __('Players') }}: <span x-text="server.players + '/' + server.maxplayers"></span>
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
                                                            <div class="text-base text-neutral-900 dark:text-neutral-100 break-words" x-text="server.name"></div>
                                                            <template x-if="server.password === 1">
                                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20 shrink-0" :title="'{{ __('Password Protected') }}'">
                                                                    🔒
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                            {{ __('Position') }}
                                                        </div>
                                                        <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                            #<span x-text="server.original_position || ((currentPage - 1) * 30 + index + 1)"></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                            {{ __('Players') }}
                                                        </div>
                                                        <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                            <span x-text="server.players + '/' + server.maxplayers"></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                            {{ __('Address') }}
                                                        </div>
                                                        <div class="text-base">
                                                            <a :href="'mtasa://' + server.ip + ':' + server.port" class="text-blue-600 hover:underline dark:text-blue-400 break-all" x-text="server.ip + ':' + server.port"></a>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                                            {{ __('Version') }}
                                                        </div>
                                                        <div class="text-base text-neutral-900 dark:text-neutral-100">
                                                            <span x-text="server.version || 'N/A'"></span>
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
                        </div>
                    </template>
                </div>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && servers.length > 0 && lastPage > 1" class="mt-4 pt-4 flex items-center justify-between">
                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Showing') }} <span x-text="(currentPage - 1) * 30 + 1"></span> - <span x-text="Math.min(currentPage * 30, total)"></span> {{ __('of') }} <span x-text="total"></span>
                </div>
                <div class="flex gap-2">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-on:click="loadServers(currentPage - 1, searchQuery)"
                        x-bind:disabled="currentPage === 1 || loading"
                    >
                        {{ __('Previous') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-on:click="loadServers(currentPage + 1, searchQuery)"
                        x-bind:disabled="currentPage >= lastPage || loading"
                    >
                        {{ __('Next') }}
                    </flux:button>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

