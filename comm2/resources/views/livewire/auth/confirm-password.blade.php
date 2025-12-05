<x-layouts.app title="{{ __('Confirm password') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header
                    :title="__('Confirm password')"
                    :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
                />

                <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 flex flex-col gap-6">
                    @csrf

                    <flux:input
                        name="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="current-password"
                        :placeholder="__('Password')"
                        viewable
                    />

                    <div class="flex items-center justify-start">
                        <flux:button variant="primary" type="submit" class="min-w-[120px]" data-test="confirm-password-button">
                            {{ __('Confirm') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
