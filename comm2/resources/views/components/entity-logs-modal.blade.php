@props([
    'type', // 'user' or 'resource'
    'entityId',
    'entityName',
])

@php
    $modalName = 'entity-logs-modal-' . $type . '-' . $entityId;
@endphp

<div
    x-data="{
        logs: [],
        loading: false,
        search: '',
        currentPage: 1,
        lastPage: 1,
        total: 0,
        modalOpen: false,
        async loadLogs(page = 1) {
            this.loading = true;
            this.currentPage = page;
            try {
                const params = new URLSearchParams({
                    type: '{{ $type }}',
                    id: {{ $entityId }},
                    page: page,
                });
                if (this.search) {
                    params.append('search', this.search);
                }
                const response = await fetch(`{{ route('admin.logs.entity') }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                this.logs = data.logs;
                this.lastPage = data.pagination.last_page;
                this.total = data.pagination.total;
            } catch (error) {
                console.error('Failed to load logs:', error);
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },
        async searchLogs() {
            await this.loadLogs(1);
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString() + ' • ' + date.toLocaleDateString();
        },
    }"
>
    <flux:modal.trigger name="{{ $modalName }}">
        <flux:button
            variant="ghost"
            size="sm"
            x-on:click.prevent="loadLogs(); $dispatch('open-modal', '{{ $modalName }}')"
        >
            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ __('View Logs') }}
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="{{ $modalName }}" class="max-w-4xl" focusable @close="search = ''">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ __('Activity Logs') }} - {{ $entityName }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Chronological log of all actions related to this :type', ['type' => $type === 'user' ? __('user') : __('resource')]) }}
                </flux:subheading>
            </div>

            <!-- Search Box -->
            <div class="flex gap-2">
                <flux:input
                    x-model="search"
                    placeholder="{{ __('Search logs...') }}"
                    x-on:keyup.enter="searchLogs()"
                    class="flex-1"
                />
                <flux:button
                    variant="primary"
                    x-on:click="searchLogs()"
                    x-bind:disabled="loading"
                >
                    {{ __('Search') }}
                </flux:button>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900 dark:border-white"></div>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Loading logs...') }}</p>
            </div>

            <!-- Logs List -->
            <div x-show="!loading" class="space-y-4 max-h-[60vh] overflow-y-auto">
                <template x-if="logs.length === 0">
                    <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        {{ __('No log entries found.') }}
                    </div>
                </template>
                <template x-for="log in logs" :key="log.id">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="log.action"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(log.created_at)"></p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="'#' + log.id"></span>
                        </div>
                        <div class="mt-4 grid gap-2 text-xs text-slate-500 dark:text-slate-400 md:grid-cols-3">
                            <div>
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Actor') }}:</span>
                                <template x-if="log.user">
                                    <a x-bind:href="`{{ route('profile.show', ['user' => '__ID__']) }}`.replace('__ID__', log.user.id)" class="hover:underline" x-text="log.user.name + ' (ID ' + log.user_id + ')'"></a>
                                </template>
                                <template x-if="!log.user">
                                    <span>{{ __('System / deleted user') }}</span>
                                </template>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700 dark:text-slate-200">IP:</span>
                                <span x-text="log.ip_address || '{{ __('Unknown') }}'"></span>
                            </div>
                            <div class="md:col-span-1">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('User agent') }}:</span>
                                <span class="block truncate" x-bind:title="log.user_agent || '{{ __('n/a') }}'" x-text="log.user_agent ? (log.user_agent.length > 80 ? log.user_agent.substring(0, 80) + '...' : log.user_agent) : '{{ __('n/a') }}'"></span>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl bg-slate-900/5 p-4 text-xs font-mono text-slate-800 dark:bg-slate-800/60 dark:text-slate-100">
                            <pre class="whitespace-pre-wrap" x-text="JSON.stringify(log.context, null, 2)"></pre>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && logs.length > 0" class="flex items-center justify-between">
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Showing') }} <span x-text="(currentPage - 1) * 20 + 1"></span> - <span x-text="Math.min(currentPage * 20, total)"></span> {{ __('of') }} <span x-text="total"></span>
                </div>
                <div class="flex gap-2">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-on:click="loadLogs(currentPage - 1)"
                        x-bind:disabled="currentPage === 1 || loading"
                    >
                        {{ __('Previous') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        x-on:click="loadLogs(currentPage + 1)"
                        x-bind:disabled="currentPage >= lastPage || loading"
                    >
                        {{ __('Next') }}
                    </flux:button>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>

