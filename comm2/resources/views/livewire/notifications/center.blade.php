<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Notifications') }}</flux:heading>
            <flux:subheading>{{ __('Stay on top of resource, friend, and report activity.') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button
                size="sm"
                variant="ghost"
                wire:click="markSelectedAsUnread"
                :disabled="empty($selected)"
            >
                {{ __('Mark unread') }}
            </flux:button>
            <flux:button
                size="sm"
                variant="ghost"
                wire:click="markSelectedAsRead"
                :disabled="empty($selected)"
            >
                {{ __('Mark read') }}
            </flux:button>
            <flux:button
                size="sm"
                variant="outline"
                wire:click="deleteSelected"
                :disabled="empty($selected)"
            >
                {{ __('Delete') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
            @forelse ($notifications as $notification)
                <div
                    wire:key="notification-{{ $notification->id }}"
                    class="flex flex-col gap-2 p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-800/50 {{ $notification->isRead() ? '' : 'bg-blue-50/70 dark:bg-blue-900/10' }}"
                >
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            wire:model="selected"
                            value="{{ $notification->id }}"
                            class="mt-1 h-4 w-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500 dark:border-neutral-600"
                        />
                        <div class="flex-1 space-y-1" wire:click="openNotification('{{ $notification->id }}')">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $notification->category->colorClasses() }}">
                                    {{ $notification->category->label() }}
                                </span>
                                <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ $notification->title }}
                                </p>
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 dark:text-neutral-300">
                                {{ \Illuminate\Support\Str::limit($notification->body, 140) }}
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <button
                                type="button"
                                class="rounded-lg border border-neutral-200 px-2 py-1 text-xs text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                wire:click="markAsUnread('{{ $notification->id }}')"
                            >
                                {{ __('Unread') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-neutral-200 px-2 py-1 text-xs text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                wire:click="markAsRead('{{ $notification->id }}')"
                            >
                                {{ __('Read') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                                wire:click="deleteNotification('{{ $notification->id }}')"
                            >
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('No notifications yet. Follow resources, users, or send reports to receive updates.') }}
                </div>
            @endforelse
        </div>
    </div>

    {{ $notifications->links() }}

    <flux:modal wire:model="showModal" max-width="lg">
        @if ($this->activeNotification)
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-neutral-600 dark:text-neutral-300">{{ $this->activeNotification->category->label() }}</p>
                        <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100">{{ $this->activeNotification->title }}</h2>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $this->activeNotification->created_at->toDayDateTimeString() }}</p>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="markAsUnread('{{ $this->activeNotification->id }}')">
                            {{ __('Mark unread') }}
                        </flux:button>
                        <flux:button size="sm" variant="outline" wire:click="deleteNotification('{{ $this->activeNotification->id }}')">
                            {{ __('Delete') }}
                        </flux:button>
                    </div>
                </div>

                <p class="text-sm text-neutral-700 dark:text-neutral-200 whitespace-pre-line">
                    {{ $this->activeNotification->body }}
                </p>

                @if (! empty($this->activeNotification->payload))
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-xs text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                        <pre class="overflow-auto">{{ json_encode($this->activeNotification->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif

                @if ($this->activeNotification->action_url)
                    <flux:link :href="$this->activeNotification->action_url" target="_blank" rel="noopener" variant="primary">
                        {{ __('Open related page') }}
                    </flux:link>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
