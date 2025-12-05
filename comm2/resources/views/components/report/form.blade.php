@props([
    'action',
    'reasons' => [],
    'buttonText' => null,
    'minLength' => \App\Models\Report::COMMENT_MIN_LENGTH,
])

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf

    <flux:field>
        <flux:label>{{ __('Reason') }}</flux:label>
        <flux:select name="report_reason" required>
            <option value="">{{ __('Select a reason') }}</option>
            @foreach ($reasons as $key => $label)
                <option value="{{ $key }}" @selected(old('report_reason') === $key)>{{ $label }}</option>
            @endforeach
        </flux:select>
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Describe what happened') }}</flux:label>
        <flux:textarea
            name="report_comment"
            rows="4"
            minlength="{{ $minLength }}"
            maxlength="2000"
            placeholder="{{ __('Explain the issue in English. Include dates, links, or context so moderators can act quickly.') }}"
            required
        >{{ old('report_comment') }}</flux:textarea>
        <flux:description>
            {{ __('Minimum :min characters, English only. Vague notes get ignored.', ['min' => $minLength]) }}
        </flux:description>
    </flux:field>

    <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ __('By submitting you confirm this report is accurate and written in English. Abusive or false reports may lead to disciplinary action.') }}
    </p>

    <flux:button type="submit" variant="primary" class="w-full">
        {{ $buttonText ?? __('Submit report') }}
    </flux:button>
</form>
