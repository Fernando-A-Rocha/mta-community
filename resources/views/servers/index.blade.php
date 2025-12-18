<x-layouts.app :title="__('Servers')">
    <div class="flex w-full flex-1 flex-col gap-6" x-data="{
        servers: [],
        statistics: { total_players: 0, total_servers: 0 },
        history: [],
        historyMeta: { page: 1, per_page_days: 30, has_prev: false, has_next: false, range: null },
        loading: false,
        historyLoading: false,
        historyError: null,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        fetchTimestamp: null,
        searchQuery: '',
        activeChart: 'players',
        charts: { players: null, servers: null },
        async loadServers(page = 1, search = '') {
            this.loading = true;
            this.currentPage = page;
            this.searchQuery = search;
            try {
                const params = new URLSearchParams({ page });
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
                this.lastPage = data.pagination.last_page;
                this.total = data.pagination.total;
            } catch (error) {
                console.error('Failed to load servers:', error);
                this.servers = [];
            } finally {
                this.loading = false;
            }
        },
        handleHeaderStats(event) {
            const { players, servers, fetchedAt } = event.detail || {};
            if (!Number.isFinite(players) || !Number.isFinite(servers)) {
                return;
            }

            this.statistics = {
                total_players: players,
                total_servers: servers,
            };
            this.fetchTimestamp = fetchedAt ? Math.floor(fetchedAt / 1000) : Math.floor(Date.now() / 1000);
        },
        async ensureChartJs() {
            if (window.Chart) {
                return;
            }

            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-chartjs]');
                if (existing) {
                    existing.addEventListener('load', resolve, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.async = true;
                script.dataset.chartjs = 'true';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },
        destroyChart(key) {
            if (this.charts[key]) {
                this.charts[key].destroy();
                this.charts[key] = null;
            }
        },
        renderChartInstance(key, { labels, data, label, color, tooltipLabels = null, pointColors = null, pointHoverColors = null, pointRadii = null, pointHoverRadii = null, highlightedIndices = [] }) {
            const canvas = this.$refs[`${key}ChartCanvas`];
            if (!canvas || !window.Chart) {
                return;
            }

            const context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            const existing = this.charts[key];
            if (existing) {
                existing.data.labels = labels;
                existing.data.datasets[0].data = data;
                existing.data.datasets[0].tooltipLabels = tooltipLabels;
                existing.data.datasets[0].pointBackgroundColor = pointColors ?? color;
                existing.data.datasets[0].pointBorderColor = pointColors ?? color;
                existing.data.datasets[0].pointHoverBackgroundColor = pointHoverColors ?? color;
                existing.data.datasets[0].pointHoverBorderColor = pointHoverColors ?? color;
                existing.data.datasets[0].pointRadius = pointRadii ?? 3;
                existing.data.datasets[0].pointHoverRadius = pointHoverRadii ?? 5;
                existing.data.datasets[0].highlightedIndices = highlightedIndices;
                existing.update('none');

                return;
            }

            this.charts[key] = new Chart(context, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label,
                        data,
                        borderColor: color,
                        backgroundColor: color + '33',
                        tension: 0.25,
                        fill: true,
                        tooltipLabels,
                        pointBackgroundColor: pointColors ?? color,
                        pointBorderColor: pointColors ?? color,
                        pointHoverBackgroundColor: pointHoverColors ?? color,
                        pointHoverBorderColor: pointHoverColors ?? color,
                        pointRadius: pointRadii ?? 3,
                        pointHoverRadius: pointHoverRadii ?? 5,
                        highlightedIndices,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: { maxTicksLimit: 8 },
                        },
                        y: {
                            beginAtZero: true,
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title() {
                                    return [];
                                },
                                label(context) {
                                    const fullLabel = context.dataset.tooltipLabels?.[context.dataIndex] ?? context.label ?? '';
                                    const pointLabel = fullLabel ? `${fullLabel} — ` : '';
                                    const isHighest = Array.isArray(context.dataset.highlightedIndices) && context.dataset.highlightedIndices.includes(context.dataIndex);
                                    const highestSuffix = isHighest ? ' — Highest' : '';
                                    return `${pointLabel}${label}: ${context.formattedValue}${highestSuffix}`;
                                },
                            },
                        },
                    },
                },
            });
        },
        buildPointStyles(values, baseColor, highlightColor) {
            const maxValue = Math.max(...values);
            const highlightedIndices = [];

            const pointColors = values.map((value, index) => {
                if (value === maxValue) {
                    highlightedIndices.push(index);
                    return highlightColor;
                }

                return baseColor;
            });

            const pointHoverColors = pointColors;
            const pointRadii = values.map((value) => (value === maxValue ? 5 : 3));
            const pointHoverRadii = values.map((value) => (value === maxValue ? 7 : 5));

            return { pointColors, pointHoverColors, pointRadii, pointHoverRadii, highlightedIndices };
        },
        renderHistoryCharts(ignoreLoadingGuard = false) {
            if ((this.historyLoading && !ignoreLoadingGuard) || !this.history?.length) {
                this.destroyChart('players');
                this.destroyChart('servers');
                return;
            }

            const labels = this.history.map((item) => {
                const date = new Date(item.created_at);
                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                });
            });

            const tooltipLabels = this.history.map((item) => {
                const date = new Date(item.created_at);
                return date.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                });
            });

            const playersData = this.history.map((item) => item.players);
            const serversData = this.history.map((item) => item.servers);

            const playersPointStyles = this.buildPointStyles(playersData, '#2563eb', '#f59e0b');
            const serversPointStyles = this.buildPointStyles(serversData, '#16a34a', '#f59e0b');

            this.renderChartInstance('players', {
                labels,
                data: playersData,
                label: '{{ __('Players') }}',
                color: '#2563eb',
                tooltipLabels,
                ...playersPointStyles,
            });

            this.renderChartInstance('servers', {
                labels,
                data: serversData,
                label: '{{ __('Servers') }}',
                color: '#16a34a',
                tooltipLabels,
                ...serversPointStyles,
            });
        },
        async loadHistory(page = 1) {
            this.historyLoading = true;
            this.historyError = null;
            try {
                const params = new URLSearchParams({ page });
                const response = await fetch(`{{ route('servers.history') }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                this.history = payload?.data ?? [];
                this.historyMeta = payload?.meta ?? this.historyMeta;

                await this.ensureChartJs();
                this.renderHistoryCharts(true);
            } catch (error) {
                console.error('Failed to load history:', error);
                this.history = [];
                this.historyError = '{{ __('Unable to load history right now.') }}';
            } finally {
                this.historyLoading = false;
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
            window.addEventListener('mta:online-stats', this.handleHeaderStats.bind(this));
            if (window.__mtaOnlineStats) {
                this.handleHeaderStats({ detail: window.__mtaOnlineStats });
            }

            this.loadHistory();
        }
    }">
        <section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg" class="mb-1">{{ __('Player & Server History') }}</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        {{ __('Showing the last 30 days by default. Use the buttons to browse older history.') }}
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-show="historyMeta?.has_prev"
                        x-on:click="loadHistory(Math.max(1, (historyMeta.page || 1) - 1))"
                        x-bind:disabled="historyLoading || !(historyMeta?.has_prev)"
                    >
                        {{ __('Newer 30 days') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-show="historyMeta?.has_next"
                        x-on:click="loadHistory((historyMeta.page || 1) + 1)"
                        x-bind:disabled="historyLoading || !(historyMeta?.has_next)"
                    >
                        {{ __('Older 30 days') }}
                    </flux:button>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <flux:button
                    size="sm"
                    variant="ghost"
                    x-bind:class="activeChart === 'players' ? 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600' : ''"
                    x-on:click="activeChart = 'players';"
                >
                    {{ __('Players') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    x-bind:class="activeChart === 'servers' ? 'bg-green-600 text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' : ''"
                    x-on:click="activeChart = 'servers';"
                >
                    {{ __('Servers') }}
                </flux:button>
            </div>

            <div x-show="historyLoading" class="py-10 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-neutral-900 dark:border-white"></div>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Loading history...') }}</p>
            </div>

            <template x-if="!historyLoading && history.length === 0">
                <div class="py-8 text-center">
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        {{ __('No history data available for this period.') }}
                    </flux:text>
                    <p x-show="historyError" class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="historyError"></p>
                </div>
            </template>

            <div x-show="!historyLoading && history.length > 0" class="relative mt-6 h-72">
                <canvas x-ref="playersChartCanvas" x-show="activeChart === 'players'" x-cloak></canvas>
                <canvas x-ref="serversChartCanvas" x-show="activeChart === 'servers'" x-cloak></canvas>
            </div>
        </section>

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


