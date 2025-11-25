<x-layouts.app title="{{ __('Verify email') }}">
    <div class="flex flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-6">
            <flux:text class="text-center">
                {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
            </flux:text>

            @if (session('status') == 'verification-link-sent')
                <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </flux:text>
            @endif

            <div class="flex flex-col items-center justify-between space-y-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Resend verification email') }}
                    </flux:button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                   <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
