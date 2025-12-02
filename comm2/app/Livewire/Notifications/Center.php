<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Center extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    /**
     * @var list<string>
     */
    public array $selected = [];

    public ?string $activeNotificationId = null;

    public bool $showModal = false;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedPage($page): void
    {
        $this->selected = [];
        $this->showModal = false;
        $this->activeNotificationId = null;
    }

    public function render(): View
    {
        $notifications = $this->notificationQuery()
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('livewire.notifications.center', [
            'notifications' => $notifications,
        ]);
    }

    public function toggleSelectAll($value): void
    {
        $currentIds = $this->currentPageNotificationIds();

        if ($currentIds === []) {
            return;
        }

        $shouldSelectAll = in_array($value, [true, 'true', 1, '1'], true);

        if ($shouldSelectAll) {
            $merged = array_merge($this->selected, $currentIds);
            $this->selected = array_values(array_unique($merged));

            return;
        }

        $this->selected = array_values(array_diff($this->selected, $currentIds));
    }

    public function toggleSelection(string $notificationId): void
    {
        if (in_array($notificationId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$notificationId]));
        } else {
            $this->selected[] = $notificationId;
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $wasUnread = $notification->isRead() === false;
        $notification->markAsRead();

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function markAsUnread(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $notification->markAsUnread();

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function deleteNotification(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $wasUnread = $notification->isRead() === false;
        $notification->delete();
        $this->selected = array_values(array_diff($this->selected, [$notificationId]));

        // Close modal if the deleted notification was the active one
        if ($this->activeNotificationId === $notificationId) {
            $this->closeModal();
        }

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function markSelectedAsRead(): void
    {
        $ids = $this->selectedIds();

        if ($ids->isEmpty()) {
            return;
        }

        $this->notificationQuery()
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);

        $this->resetSelection();

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function markSelectedAsUnread(): void
    {
        $ids = $this->selectedIds();

        if ($ids->isEmpty()) {
            return;
        }

        $this->notificationQuery()
            ->whereIn('id', $ids)
            ->update(['read_at' => null]);

        $this->resetSelection();

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function deleteSelected(): void
    {
        $ids = $this->selectedIds();

        if ($ids->isEmpty()) {
            return;
        }

        $this->notificationQuery()
            ->whereIn('id', $ids)
            ->delete();

        $this->resetSelection();

        $newCount = $this->notificationQuery()->whereNull('read_at')->count();
        $this->dispatch('notification-updated', count: $newCount);
    }

    public function openNotification(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $wasUnread = $notification->isRead() === false;
        $notification->markAsRead();

        $this->activeNotificationId = $notificationId;
        $this->showModal = true;

        if ($wasUnread) {
            $newCount = $this->notificationQuery()->whereNull('read_at')->count();
            $this->dispatch('notification-updated', count: $newCount);
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->activeNotificationId = null;
    }

    public function getActiveNotificationProperty(): ?Notification
    {
        if (! $this->activeNotificationId) {
            return null;
        }

        return $this->notificationQuery()->find($this->activeNotificationId);
    }

    private function notificationQuery()
    {
        return Notification::query()
            ->where('user_id', auth()->id())
            ->whereNull('deleted_at');
    }

    /**
     * @return list<string>
     */
    private function currentPageNotificationIds(): array
    {
        $page = (int) data_get($this, 'page', 1);
        $page = $page > 0 ? $page : 1;

        return $this->notificationQuery()
            ->latest()
            ->forPage($page, self::PER_PAGE)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values()
            ->all();
    }

    private function selectedIds()
    {
        return collect($this->selected)
            ->filter()
            ->unique()
            ->values();
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->showModal = false;
        $this->activeNotificationId = null;
    }
}
