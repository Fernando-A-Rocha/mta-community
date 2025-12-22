@props([
    'type', // 'user' or 'resource'
    'entityId',
    'entityName',
    'action',
    'reasons',
    'existingReport' => null,
])

@php
    use App\Enums\ReportStatus;
    $modalName = 'report-modal-' . $type . '-' . $entityId;
@endphp

<flux:modal.trigger name="{{ $modalName }}">
    <flux:button
        variant="ghost"
        size="sm"
    >
        <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        {{ __('Report') }}
    </flux:button>
</flux:modal.trigger>

<flux:modal name="{{ $modalName }}" class="max-w-2xl" focusable>
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ __('Report :type', ['type' => $type === 'user' ? __('User') : __('Resource')]) }} - {{ $entityName }}
            </flux:heading>
            <flux:subheading>
                @if ($type === 'user')
                    {{ __('Let moderators know about impersonation, harassment, or spam coming from this user.') }}
                @else
                    {{ __('Flag abuse, scams, malware, or other policy violations.') }}
                @endif
            </flux:subheading>
        </div>

        @if ($existingReport && $existingReport->status === ReportStatus::Pending)
            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-100">
                <p class="font-semibold">{{ __('Pending review') }}</p>
                <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-200/80">
                    @if ($type === 'user')
                        {{ __('You reported this profile (:reason). It was last updated :time.', ['reason' => $existingReport->reasonLabel(), 'time' => $existingReport->updated_at->diffForHumans()]) }}
                    @else
                        {{ __('You already reported this resource (:reason). Moderators were notified :time.', ['reason' => $existingReport->reasonLabel(), 'time' => $existingReport->updated_at->diffForHumans()]) }}
                    @endif
                </p>
                <form method="POST" action="{{ route('reports.destroy', $existingReport) }}" class="mt-3 flex justify-end">
                    @csrf
                    @method('DELETE')
                    <flux:button type="submit" variant="ghost" size="sm">
                        {{ __('Withdraw report') }}
                    </flux:button>
                </form>
            </div>
        @else
            @if ($existingReport)
                <div class="rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs text-orange-900 dark:border-orange-500/40 dark:bg-orange-900/30 dark:text-orange-100">
                    <p class="font-semibold">
                        @if ($type === 'user')
                            {{ __('Previous report status: :status', ['status' => $existingReport->status->label()]) }}
                        @else
                            {{ __('Last report status: :status', ['status' => $existingReport->status->label()]) }}
                        @endif
                    </p>
                    <p class="mt-1">{{ $existingReport->reasonLabel() }} • {{ $existingReport->updated_at->diffForHumans() }}</p>
                </div>
            @endif

            <div>
                <x-report.form
                    :action="$action"
                    :reasons="$reasons"
                    :button-text="__('Submit report')"
                />
            </div>
        @endif

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

