<x-layouts.app title="{{ __('Sign up') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

                <form method="POST" action="{{ route('register.store') }}" class="mt-6 flex flex-col gap-6">
                    @csrf

                    <!-- Name -->
                    <flux:input
                        name="name"
                        :label="__('Account name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        :placeholder="__('Username/nickname')"
                    />

                    <!-- Email Address -->
                    <flux:input
                        name="email"
                        :label="__('Email address')"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />

                    <!-- Password -->
                    <flux:input
                        name="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Password')"
                        viewable
                    />

                    <!-- Confirm Password -->
                    <flux:input
                        name="password_confirmation"
                        :label="__('Confirm password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Confirm password')"
                        viewable
                    />

                    <div class="flex items-center justify-start">
                        <flux:button type="submit" variant="primary" class="min-w-[140px]">
                            {{ __('Create account') }}
                        </flux:button>
                    </div>
                </form>

                <div class="mt-6 space-x-1 rtl:space-x-reverse text-sm text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('Already have an account?') }}</span>
                    <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
