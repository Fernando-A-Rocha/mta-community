@props([
    'href' => route('home'),
    'class' => '',
    'link' => true,
])

@if($link)
    <a href="{{ $href }}" wire:navigate class="flex justify-center {{ $class }}">
        <img
            src="{{ asset('mta-logo.png') }}"
            alt="{{ config('app.name', 'MTA') }} Logo"
            class="h-12 w-auto"
        />
    </a>
@else
    <div class="flex justify-center {{ $class }}">
        <img
            src="{{ asset('mta-logo.png') }}"
            alt="{{ config('app.name', 'MTA') }} Logo"
            class="h-12 w-auto"
        />
    </div>
@endif

