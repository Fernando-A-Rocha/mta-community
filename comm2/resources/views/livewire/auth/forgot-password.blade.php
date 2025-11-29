<x-layouts.app title="{{ __('Forgot password') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

                <!-- Session Status -->
                <x-auth-session-status class="mt-4" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-6">
                    @csrf

                    <!-- Email Address -->
                    <flux:input
                        name="email"
                        :label="__('Email Address')"
                        type="email"
                        required
                        autofocus
                        placeholder="email@example.com"
                    />

                    <div class="flex items-center justify-start">
                        <flux:button variant="primary" type="submit" class="min-w-[200px]" data-test="email-password-reset-link-button">
                            {{ __('Email password reset link') }}
                        </flux:button>
                    </div>
                </form>

                <div class="mt-6 space-x-1 rtl:space-x-reverse text-sm text-zinc-600 dark:text-zinc-400">
                    <span>{{ __('Or, return to') }}</span>
                    <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
