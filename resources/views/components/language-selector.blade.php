@php
    $currentLocale = app()->getLocale();
    $localeLabels = config('app.locale_labels', []);
    $supportedLocales = config('app.supported_locales', ['en']);
@endphp

<flux:dropdown position="bottom" align="start">
    <flux:button variant="subtle" icon="language" size="sm">
        {{ $localeLabels[$currentLocale] ?? $currentLocale }}
        <x-slot:iconTrailing>
            <flux:icon.chevron-down variant="mini" class="size-4" />
        </x-slot:iconTrailing>
    </flux:button>

    <flux:menu class="max-h-64 overflow-y-auto">
        @foreach ($supportedLocales as $locale)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <flux:menu.item
                    as="button"
                    type="submit"
                    class="w-full {{ $locale === $currentLocale ? 'font-semibold' : '' }}"
                >
                    @if ($locale === $currentLocale)
                        <flux:icon.check variant="mini" class="size-4 me-2" />
                    @else
                        <span class="size-4 me-2"></span>
                    @endif
                    {{ $localeLabels[$locale] ?? $locale }}
                </flux:menu.item>
            </form>
        @endforeach
    </flux:menu>
</flux:dropdown>
