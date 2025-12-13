<x-layouts.app title="{{ __('Reset password') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

                <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-6">
                    @csrf
                    <!-- Token -->
                    <input type="hidden" name="token" value="{{ request()->route('token') }}">

                    <!-- Email Address -->
                    <flux:input
                        name="email"
                        value="{{ request('email') }}"
                        :label="__('Email')"
                        type="email"
                        required
                        autocomplete="email"
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
                        <flux:button type="submit" variant="primary" class="min-w-[140px]" data-test="reset-password-button">
                            {{ __('Reset password') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
