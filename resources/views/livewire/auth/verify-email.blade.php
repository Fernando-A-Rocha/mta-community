<x-layouts.app title="{{ __('Verify email') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <x-auth-header
                    :title="__('Verify your email address')"
                    :description="__('Please verify your email address by clicking on the link we just emailed to you.')"
                />

                <div class="mt-6 flex flex-col gap-4">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <flux:button type="submit" variant="primary" class="min-w-[200px]">
                            {{ __('Resend verification email') }}
                        </flux:button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button variant="ghost" type="submit" class="w-fit text-sm cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
