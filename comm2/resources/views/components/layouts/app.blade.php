@php
    use App\Enums\ReportStatus;
    use App\Models\Notification;
    use App\Models\Report;
    
    $pendingReportsCount = auth()->check() && auth()->user()->isModerator()
        ? Report::where('status', ReportStatus::Pending)->count()
        : 0;

    $unreadNotificationsCount = auth()->check()
        ? Notification::where('user_id', auth()->id())->whereNull('read_at')->count()
        : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 flex flex-col">
        <x-mta-header />
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Navigation')" class="grid">
                    <flux:navlist.item icon="newspaper" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>{{ __('News') }}</flux:navlist.item>
                    <flux:navlist.item icon="code-bracket" :href="route('development.index')" :current="request()->routeIs('development.*')" wire:navigate>{{ __('Development') }}</flux:navlist.item>
                    <flux:navlist.item icon="server" :href="route('servers.index')" :current="request()->routeIs('servers.*')" wire:navigate>{{ __('Servers') }}</flux:navlist.item>
                    <flux:navlist.item icon="folder" :href="route('resources.index')" :current="request()->routeIs('resources.*')" wire:navigate>{{ __('Resources') }}</flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('members.index')" :current="request()->routeIs('members.*')" wire:navigate>{{ __('Members') }}</flux:navlist.item>
                    @auth
                        <flux:navlist.item icon="bell" :href="route('notifications.index')" :current="request()->routeIs('notifications.*')" wire:navigate>
                            <span class="flex items-center gap-2">
                                {{ __('Notifications') }}
                                @if ($unreadNotificationsCount > 0)
                                    <span class="inline-flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-semibold h-5 w-5 min-w-[1.25rem]">
                                        {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                                    </span>
                                @endif
                            </span>
                        </flux:navlist.item>
                    @endauth
                </flux:navlist.group>
                @auth
                    @if (auth()->user()->isModerator())
                        <flux:navlist.group :heading="__('Admin Panel')" class="mt-4 grid">
                            <flux:navlist.item icon="flag" :href="route('admin.reports.index')" :current="request()->routeIs('admin.reports.*')" wire:navigate>
                                <span class="flex items-center gap-2">
                                    {{ __('Reports') }}
                                    @if ($pendingReportsCount > 0)
                                        <span class="inline-flex items-center justify-center rounded-full bg-red-500 text-white text-xs font-semibold h-5 w-5 min-w-[1.25rem]">
                                            {{ $pendingReportsCount > 99 ? '99+' : $pendingReportsCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navlist.item>
                            <flux:navlist.item icon="book-open-text" :href="route('admin.logs.index')" :current="request()->routeIs('admin.logs.*')" wire:navigate>
                                {{ __('Logs') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    @endif
                @endauth
            </flux:navlist>

            <flux:spacer />

            @auth
            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <button type="button" class="flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    <x-user-avatar :user="auth()->user()" size="sm" />
                    <span class="flex-1 truncate">{{ auth()->user()->name }}</span>
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.show', auth()->user())" icon="user" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('account.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
            @endauth
            @guest
            <!-- Login Link -->
            <div class="px-3 py-2 text-sm">
                {{ __('Existing user?') }}
                <flux:link :href="route('login')" wire:navigate>
                    {{ __('Log in') }}
                </flux:link>
            </div>
            @endguest
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
                <a
                    href="{{ route('notifications.index') }}"
                    class="relative rounded-full border border-neutral-200 p-2 text-neutral-600 transition hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                    wire:navigate
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10V6a4 4 0 00-8 0v4a4 4 0 01-.879 2.545L4 14h12l-1.121-1.455A4 4 0 0114 10z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h2a2 2 0 002-2v-1H5v1a2 2 0 002 2z" />
                    </svg>
                    @if ($unreadNotificationsCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>
            @endauth

            @auth
            <flux:dropdown position="top" align="end">
                <button type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    <x-user-avatar :user="auth()->user()" size="sm" />
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <x-user-avatar :user="auth()->user()" size="sm" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <a href="{{ route('profile.show', auth()->user()) }}" class="truncate font-semibold hover:underline" wire:navigate>{{ auth()->user()->name }}</a>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('account.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
            @endauth
        </flux:header>

        <flux:main class="flex-1 flex flex-col">
            <div class="flex-1">
                {{ $slot }}
            </div>
            <x-footer />
        </flux:main>


        @fluxScripts
    </body>
</html>
