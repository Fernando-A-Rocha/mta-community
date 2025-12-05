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
        formatLogContext(action, context) {
            if (!context || typeof context !== 'object') {
                return '<div class="text-xs text-slate-500 dark:text-slate-400">No context data</div>';
            }
            
            const formatBool = (value) => value ? '{{ __('Yes') }}' : '{{ __('No') }}';
            const formatStatus = (value) => {
                if (typeof value === 'boolean') {
                    return value ? '{{ __('Verified') }}' : '{{ __('Not Verified') }}';
                }
                return String(value).charAt(0).toUpperCase() + String(value).slice(1).replace(/_/g, ' ');
            };
            
            const resourceLink = (id, name) => {
                const url = `{{ route('resources.show', ['resource' => '__ID__']) }}`.replace('__ID__', id);
                return `<a href="${url}" class="text-blue-600 hover:underline dark:text-blue-400">${escapeHtml(name)}</a>`;
            };
            
            const userLink = (id, name) => {
                const url = `{{ route('profile.show', ['user' => '__ID__']) }}`.replace('__ID__', id);
                return `<a href="${url}" class="text-blue-600 hover:underline dark:text-blue-400">${escapeHtml(name)}</a>`;
            };
            
            const escapeHtml = (text) => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            let html = '<div class="space-y-3">';
            
            // Resource enabled/disabled
            if (action.startsWith('resource.enabled') || action.startsWith('resource.disabled')) {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)} <span class="text-slate-500 dark:text-slate-400">(ID: ${context.resource_id})</span></div>`;
                }
                if (context.resource_owner_id && context.resource_owner_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Owner') }}:</span> ${userLink(context.resource_owner_id, context.resource_owner_name)}</div>`;
                }
                html += '</div>';
            }
            // Resource created
            else if (action === 'resource.created') {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)}</div>`;
                }
                if (context.long_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Long Name') }}:</span> <span>${escapeHtml(context.long_name)}</span></div>`;
                }
                if (context.category) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Category') }}:</span> <span>${escapeHtml(context.category.charAt(0).toUpperCase() + context.category.slice(1))}</span></div>`;
                }
                if (context.version) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span> <span>${escapeHtml(context.version)}</span></div>`;
                }
                html += '<div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">';
                if (context.tag_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Tags') }}:</span> <span>${context.tag_count}</span></div>`;
                }
                if (context.language_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Languages') }}:</span> <span>${context.language_count}</span></div>`;
                }
                if (context.image_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Images') }}:</span> <span>${context.image_count}</span></div>`;
                }
                const links = [];
                if (context.has_github_url) links.push('{{ __('GitHub') }}');
                if (context.has_forum_url) links.push('{{ __('Forum') }}');
                html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Links') }}:</span> <span>${links.length > 0 ? links.join(' & ') : '{{ __('None') }}'}</span></div>`;
                html += '</div></div>';
            }
            // Resource updated
            else if (action === 'resource.updated') {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)}</div>`;
                }
                if (context.is_moderator_edit !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Edit Type') }}:</span> <span>${context.is_moderator_edit ? '{{ __('Moderator Edit') }}' : '{{ __('Owner Edit') }}'}</span></div>`;
                }
                if (context.changes && typeof context.changes === 'object' && Object.keys(context.changes).length > 0) {
                    html += '<div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700"><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Changes') }}:</span><ul class="mt-1 space-y-1 list-disc list-inside text-slate-600 dark:text-slate-300">';
                    for (const [field, change] of Object.entries(context.changes)) {
                        const fieldName = field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
                        if (change && typeof change === 'object' && change.old !== undefined && change.new !== undefined) {
                            const oldVal = typeof change.old === 'object' ? JSON.stringify(change.old) : change.old;
                            const newVal = typeof change.new === 'object' ? JSON.stringify(change.new) : change.new;
                            html += `<li><span class="font-medium">${escapeHtml(fieldName)}:</span> <span class="text-red-600 dark:text-red-400">${escapeHtml(String(oldVal))}</span> → <span class="text-green-600 dark:text-green-400">${escapeHtml(String(newVal))}</span></li>`;
                        } else {
                            html += `<li><span class="font-medium">${escapeHtml(fieldName)}:</span> ${escapeHtml(String(change))}</li>`;
                        }
                    }
                    html += '</ul></div>';
                }
                html += '</div>';
            }
            // Resource version deleted
            else if (action === 'resource.version.deleted') {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)}</div>`;
                }
                if (context.version) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span> <span>${escapeHtml(context.version)}</span></div>`;
                }
                if (context.was_current !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Was Current Version') }}:</span> <span>${formatBool(context.was_current)}</span></div>`;
                }
                if (context.new_current_version) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('New Current Version') }}:</span> <span>${escapeHtml(context.new_current_version)}</span></div>`;
                }
                if (context.is_owner_delete !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted By Owner') }}:</span> <span>${formatBool(context.is_owner_delete)}</span></div>`;
                }
                html += '</div>';
            }
            // Resource deleted
            else if (action === 'resource.deleted') {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> <span>${escapeHtml(context.resource_name)}</span> <span class="text-slate-500 dark:text-slate-400">(ID: ${context.resource_id})</span></div>`;
                }
                if (context.resource_owner_id && context.resource_owner_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Owner') }}:</span> ${userLink(context.resource_owner_id, context.resource_owner_name)}</div>`;
                }
                if (context.category) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Category') }}:</span> <span>${escapeHtml(context.category.charAt(0).toUpperCase() + context.category.slice(1))}</span></div>`;
                }
                html += '<div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">';
                if (context.version_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Versions') }}:</span> <span>${context.version_count}</span></div>`;
                }
                if (context.rating_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Ratings') }}:</span> <span>${context.rating_count}</span></div>`;
                }
                if (context.download_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Downloads') }}:</span> <span>${context.download_count}</span></div>`;
                }
                if (context.is_owner_delete !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted By Owner') }}:</span> <span>${formatBool(context.is_owner_delete)}</span></div>`;
                }
                html += '</div></div>';
            }
            // Resource version verification updated
            else if (action === 'resource.version.verification.updated') {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)}</div>`;
                }
                if (context.version) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span> <span>${escapeHtml(context.version)}</span></div>`;
                }
                if (context.old_status !== undefined && context.new_status !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Verification Status') }}:</span> <span class="text-red-600 dark:text-red-400">${formatStatus(context.old_status)}</span> → <span class="text-green-600 dark:text-green-400">${formatStatus(context.new_status)}</span></div>`;
                }
                html += '</div>';
            }
            // Review created/updated/deleted
            else if (action.startsWith('review.')) {
                html += '<div class="grid gap-2 text-sm">';
                if (context.resource_id && context.resource_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span> ${resourceLink(context.resource_id, context.resource_name)}</div>`;
                }
                if (context.rating !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Rating') }}:</span> <span>${context.rating}/5</span></div>`;
                }
                if (context.has_comment !== undefined || context.had_comment !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Has Comment') }}:</span> <span>${formatBool(context.has_comment ?? context.had_comment ?? false)}</span></div>`;
                }
                if (context.reviewer_id && context.reviewer_name) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Reviewer') }}:</span> ${userLink(context.reviewer_id, context.reviewer_name)}</div>`;
                }
                html += '</div>';
            }
            // Reports
            else if (action.startsWith('report.')) {
                html += '<div class="grid gap-2 text-sm">';
                if (context.report_id) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Report ID') }}:</span> <span>${context.report_id}</span></div>`;
                }
                if (context.reportable_type) {
                    const type = context.reportable_type === 'resource' ? '{{ __('Resource') }}' : '{{ __('User') }}';
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Report Type') }}:</span> <span>${type}</span></div>`;
                }
                if (context.resource_id) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource ID') }}:</span> <span>${context.resource_id}</span></div>`;
                }
                if (context.user_id) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('User ID') }}:</span> <span>${context.user_id}</span></div>`;
                }
                if (context.reason) {
                    const reason = context.reason.charAt(0).toUpperCase() + context.reason.slice(1).replace(/_/g, ' ');
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Reason') }}:</span> <span>${escapeHtml(reason)}</span></div>`;
                }
                if (context.status) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Status') }}:</span> <span>${escapeHtml(context.status.charAt(0).toUpperCase() + context.status.slice(1))}</span></div>`;
                }
                if (context.deleted !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted Count') }}:</span> <span>${context.deleted}</span></div>`;
                }
                if (context.threshold) {
                    const date = new Date(context.threshold);
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Threshold') }}:</span> <span>${date.toLocaleString()}</span></div>`;
                }
                html += '</div>';
            }
            // Media
            else if (action.startsWith('media.')) {
                html += '<div class="grid gap-2 text-sm">';
                if (context.media_id) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Media ID') }}:</span> <span>${context.media_id}</span></div>`;
                }
                if (context.type) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Type') }}:</span> <span>${escapeHtml(context.type.charAt(0).toUpperCase() + context.type.slice(1))}</span></div>`;
                }
                if (context.image_count !== undefined) {
                    html += `<div><span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Image Count') }}:</span> <span>${context.image_count}</span></div>`;
                }
                html += '</div>';
            }
            // Fallback: JSON
            else {
                html += `<div class="rounded-2xl bg-slate-900/5 p-4 text-xs font-mono text-slate-800 dark:bg-slate-800/60 dark:text-slate-100"><pre class="whitespace-pre-wrap">${escapeHtml(JSON.stringify(context, null, 2))}</pre></div>`;
            }
            
            html += '</div>';
            return html;
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
                        <div class="mt-4" x-html="formatLogContext(log.action, log.context)"></div>
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

