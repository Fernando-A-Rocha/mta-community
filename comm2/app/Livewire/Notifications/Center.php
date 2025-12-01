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
            ->paginate(15);

        return view('livewire.notifications.center', [
            'notifications' => $notifications,
        ]);
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
        $notification->markAsRead();
        $this->dispatch('notification-updated');
    }

    public function markAsUnread(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $notification->markAsUnread();
        $this->dispatch('notification-updated');
    }

    public function deleteNotification(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $notification->delete();
        $this->selected = array_values(array_diff($this->selected, [$notificationId]));
        $this->dispatch('notification-updated');
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
        $this->dispatch('notification-updated');
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
        $this->dispatch('notification-updated');
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
        $this->dispatch('notification-updated');
    }

    public function openNotification(string $notificationId): void
    {
        $notification = $this->notificationQuery()->findOrFail($notificationId);
        $notification->markAsRead();

        $this->activeNotificationId = $notificationId;
        $this->showModal = true;

        $this->dispatch('notification-updated');
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
