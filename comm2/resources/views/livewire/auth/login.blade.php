<x-layouts.app title="{{ __('Log in') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header :title="__('Log in to your account')" :description="__('Enter your username and password below to log in')" />

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 flex flex-col gap-6">
                    @csrf

                    <!-- Username -->
                    <flux:input
                        name="name"
                        :label="__('Username')"
                        type="text"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Username"
                    />

                    <!-- Password -->
                    <div class="flex flex-col gap-2">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Password')"
                            viewable
                        />

                        @if (Route::has('password.request'))
                            <div class="flex justify-end">
                                <flux:link class="text-sm" :href="route('password.request')" wire:navigate>
                                    {{ __('Forgot your password?') }}
                                </flux:link>
                            </div>
                        @endif
                    </div>

                    <!-- Remember Me -->
                    <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                    <div class="flex items-center justify-start">
                        <flux:button variant="primary" type="submit" class="min-w-[120px]" data-test="login-button">
                            {{ __('Log in') }}
                        </flux:button>
                    </div>
                </form>

                @if (Route::has('register'))
                    <div class="mt-6 space-x-1 text-sm rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                        <span>{{ __('Don\'t have an account?') }}</span>
                        <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-layouts.app>
