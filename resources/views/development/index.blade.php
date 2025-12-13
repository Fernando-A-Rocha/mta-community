<x-layouts.app :title="__('Development')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <section>
            <flux:heading size="lg" class="mb-4">{{ __('Development Activity') }}</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 mb-6">
                {{ __('Current stable MTA version') }}: <strong>{{ config('mta.current_stable_version') }}</strong>
                <a href="https://multitheftauto.com/download" target="_blank" rel="noopener noreferrer" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline">
                    {{ __('Download') }}
                </a>
            </flux:text>

            <div class="mb-6 flex flex-wrap gap-3">
                <a href="https://forum.multitheftauto.com/forum/107-open-source-contributors/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Open Source Contributors (Forum)') }}
                </a>
                <a href="https://buildinfo.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Build Info') }}
                </a>
                <a href="https://nightly.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Nightly Builds') }}
                </a>
                <a href="https://nightly.multitheftauto.com/ver/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Version Info') }}
                </a>
                <a href="https://linux.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Linux Server Packages') }}
                </a>
            </div>
            <flux:text class="text-neutral-600 dark:text-neutral-400 mb-6">
                {{ __('Recent activity from Multi Theft Auto development repositories on GitHub:') }}
            </flux:text>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="{
                blueActivity: [],
                resourcesActivity: [],
                blueLoading: false,
                resourcesLoading: false,
                blueCurrentPage: 1,
                resourcesCurrentPage: 1,
                blueLastPage: 1,
                resourcesLastPage: 1,
                blueTotal: 0,
                resourcesTotal: 0,
                blueFetchTimestamp: null,
                resourcesFetchTimestamp: null,
                async loadBlueActivity(page = 1) {
                    this.blueLoading = true;
                    this.blueCurrentPage = page;
                    try {
                        const params = new URLSearchParams({
                            repo: 'mtasa-blue',
                            page: page,
                        });
                        const response = await fetch(`{{ route('development.activity') }}?${params}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();
                        this.blueActivity = data.activity;
                        this.blueLastPage = data.pagination.last_page;
                        this.blueTotal = data.pagination.total;
                        this.blueFetchTimestamp = data.fetch_timestamp;
                    } catch (error) {
                        console.error('Failed to load blue activity:', error);
                        this.blueActivity = [];
                    } finally {
                        this.blueLoading = false;
                    }
                },
                async loadResourcesActivity(page = 1) {
                    this.resourcesLoading = true;
                    this.resourcesCurrentPage = page;
                    try {
                        const params = new URLSearchParams({
                            repo: 'mtasa-resources',
                            page: page,
                        });
                        const response = await fetch(`{{ route('development.activity') }}?${params}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();
                        this.resourcesActivity = data.activity;
                        this.resourcesLastPage = data.pagination.last_page;
                        this.resourcesTotal = data.pagination.total;
                        this.resourcesFetchTimestamp = data.fetch_timestamp;
                    } catch (error) {
                        console.error('Failed to load resources activity:', error);
                        this.resourcesActivity = [];
                    } finally {
                        this.resourcesLoading = false;
                    }
                },
                getBadgeColor(type) {
                    const colors = {
                        'commit': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'issue': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        'pull_request': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                        'release': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    };
                    return colors[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
                },
                getBadgeLabel(type) {
                    const labels = {
                        'commit': 'Commit',
                        'issue': 'Issue',
                        'pull_request': 'PR',
                        'release': 'Release',
                    };
                    return labels[type] || type.charAt(0).toUpperCase() + type.slice(1);
                },
                formatFetchTimestamp(timestamp) {
                    if (!timestamp) return '';
                    const date = new Date(timestamp * 1000);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                },
                init() {
                    this.loadBlueActivity();
                    this.loadResourcesActivity();
                }
            }">
                <!-- Multi Theft Auto Codebase Section -->
                <div class="rounded-lg border border-neutral-300 bg-white p-4 dark:border-neutral-600 dark:bg-neutral-800">
                    <div class="mb-3">
                        <flux:heading size="md" class="mb-2">{{ __('Multi Theft Auto Codebase') }}</flux:heading>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <a href="https://github.com/multitheftauto" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                multitheftauto
                            </a>
                            <span class="mx-1">/</span>
                            <a href="https://github.com/multitheftauto/mtasa-blue" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                mtasa-blue
                            </a>
                        </div>
                        <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1" x-show="blueFetchTimestamp">
                            {{ __('Fetched') }}: <span x-text="formatFetchTimestamp(blueFetchTimestamp)"></span>
                        </p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="blueLoading" class="py-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-neutral-900 dark:border-white"></div>
                        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Loading activity...') }}</p>
                    </div>

                    <!-- Activity List -->
                    <div x-show="!blueLoading">
                        <template x-if="blueActivity.length === 0">
                            <div class="py-8 text-center text-neutral-600 dark:text-neutral-400">
                                <p>{{ __('No activity available at the moment.') }}</p>
                            </div>
                        </template>
                        <div x-show="blueActivity.length > 0" class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            <template x-for="activity in blueActivity" :key="activity.url">
                                <div class="py-2 first:pt-0">
                                    <div class="flex items-start gap-2">
                                        <div class="shrink-0">
                                            <span class="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium leading-tight" :class="getBadgeColor(activity.type)" x-text="getBadgeLabel(activity.type)"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                                <a :href="activity.url" target="_blank" rel="noopener noreferrer" class="hover:underline" x-text="activity.title"></a>
                                            </h3>
                                            <div class="flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-500">
                                                <span>{{ __('By') }}: <span x-text="activity.author"></span></span>
                                                <span x-text="activity.date_human"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Pagination -->
                        <div x-show="blueActivity.length > 0 && blueLastPage > 1" class="mt-3 pt-3 flex items-center justify-between">
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('Showing') }} <span x-text="(blueCurrentPage - 1) * 10 + 1"></span> - <span x-text="Math.min(blueCurrentPage * 10, blueTotal)"></span> {{ __('of') }} <span x-text="blueTotal"></span>
                            </div>
                            <div class="flex gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    x-on:click="loadBlueActivity(blueCurrentPage - 1)"
                                    x-bind:disabled="blueCurrentPage === 1 || blueLoading"
                                >
                                    {{ __('Previous') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    x-on:click="loadBlueActivity(blueCurrentPage + 1)"
                                    x-bind:disabled="blueCurrentPage >= blueLastPage || blueLoading"
                                >
                                    {{ __('Next') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Official MTA Resources Section -->
                <div class="rounded-lg border border-neutral-300 bg-white p-4 dark:border-neutral-600 dark:bg-neutral-800">
                    <div class="mb-3">
                        <flux:heading size="md" class="mb-2">{{ __('Official MTA Resources') }}</flux:heading>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <a href="https://github.com/multitheftauto" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                multitheftauto
                            </a>
                            <span class="mx-1">/</span>
                            <a href="https://github.com/multitheftauto/mtasa-resources" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                mtasa-resources
                            </a>
                        </div>
                        <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1" x-show="resourcesFetchTimestamp">
                            {{ __('Fetched') }}: <span x-text="formatFetchTimestamp(resourcesFetchTimestamp)"></span>
                        </p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="resourcesLoading" class="py-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-neutral-900 dark:border-white"></div>
                        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Loading activity...') }}</p>
                    </div>

                    <!-- Activity List -->
                    <div x-show="!resourcesLoading">
                        <template x-if="resourcesActivity.length === 0">
                            <div class="py-8 text-center text-neutral-600 dark:text-neutral-400">
                                <p>{{ __('No activity available at the moment.') }}</p>
                            </div>
                        </template>
                        <div x-show="resourcesActivity.length > 0" class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            <template x-for="activity in resourcesActivity" :key="activity.url">
                                <div class="py-2 first:pt-0">
                                    <div class="flex items-start gap-2">
                                        <div class="shrink-0">
                                            <span class="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium leading-tight" :class="getBadgeColor(activity.type)" x-text="getBadgeLabel(activity.type)"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                                <a :href="activity.url" target="_blank" rel="noopener noreferrer" class="hover:underline" x-text="activity.title"></a>
                                            </h3>
                                            <div class="flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-500">
                                                <span>{{ __('By') }}: <span x-text="activity.author"></span></span>
                                                <span x-text="activity.date_human"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Pagination -->
                        <div x-show="resourcesActivity.length > 0 && resourcesLastPage > 1" class="mt-3 pt-3 flex items-center justify-between">
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('Showing') }} <span x-text="(resourcesCurrentPage - 1) * 10 + 1"></span> - <span x-text="Math.min(resourcesCurrentPage * 10, resourcesTotal)"></span> {{ __('of') }} <span x-text="resourcesTotal"></span>
                            </div>
                            <div class="flex gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    x-on:click="loadResourcesActivity(resourcesCurrentPage - 1)"
                                    x-bind:disabled="resourcesCurrentPage === 1 || resourcesLoading"
                                >
                                    {{ __('Previous') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    x-on:click="loadResourcesActivity(resourcesCurrentPage + 1)"
                                    x-bind:disabled="resourcesCurrentPage >= resourcesLastPage || resourcesLoading"
                                >
                                    {{ __('Next') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

