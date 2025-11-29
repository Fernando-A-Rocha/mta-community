<x-layouts.app title="{{ __('Two-Factor Authentication') }}">
    <div class="flex w-full flex-1 flex-col gap-8">
        <section class="p-6">
            <div class="max-w-2xl">
                <div
                    class="relative w-full h-auto"
                    x-cloak
                    x-data="{
                        showRecoveryInput: @js($errors->has('recovery_code')),
                        code: '',
                        recovery_code: '',
                        toggleInput() {
                            this.showRecoveryInput = !this.showRecoveryInput;

                            this.code = '';
                            this.recovery_code = '';

                            $dispatch('clear-2fa-auth-code');

                            $nextTick(() => {
                                this.showRecoveryInput
                                    ? this.$refs.recovery_code?.focus()
                                    : $dispatch('focus-2fa-auth-code');
                            });
                        },
                    }"
                >
                    <div x-show="!showRecoveryInput">
                        <x-auth-header
                            :title="__('Authentication Code')"
                            :description="__('Enter the authentication code provided by your authenticator application.')"
                        />
                    </div>

                    <div x-show="showRecoveryInput">
                        <x-auth-header
                            :title="__('Recovery Code')"
                            :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                        />
                    </div>

                    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6">
                        @csrf

                        <div class="space-y-6">
                            <div x-show="!showRecoveryInput">
                                <div class="flex items-start justify-start my-5">
                                    <x-input-otp
                                        name="code"
                                        digits="6"
                                        autocomplete="one-time-code"
                                        x-model="code"
                                    />
                                </div>

                                @error('code')
                                    <flux:text color="red" class="mt-2">
                                        {{ $message }}
                                    </flux:text>
                                @enderror
                            </div>

                            <div x-show="showRecoveryInput">
                                <div class="my-5">
                                    <flux:input
                                        type="text"
                                        name="recovery_code"
                                        x-ref="recovery_code"
                                        x-bind:required="showRecoveryInput"
                                        autocomplete="one-time-code"
                                        x-model="recovery_code"
                                        :label="__('Recovery Code')"
                                    />
                                </div>

                                @error('recovery_code')
                                    <flux:text color="red" class="mt-2">
                                        {{ $message }}
                                    </flux:text>
                                @enderror
                            </div>

                            <div class="flex items-center justify-start">
                                <flux:button
                                    variant="primary"
                                    type="submit"
                                    class="min-w-[120px]"
                                >
                                    {{ __('Continue') }}
                                </flux:button>
                            </div>
                        </div>

                        <div class="mt-6 space-x-1 text-sm leading-5 text-zinc-600 dark:text-zinc-400">
                            <span>{{ __('or you can') }}</span>
                            <button type="button" class="font-medium underline cursor-pointer hover:text-zinc-900 dark:hover:text-zinc-200" @click="toggleInput()">
                                <span x-show="!showRecoveryInput">{{ __('login using a recovery code') }}</span>
                                <span x-show="showRecoveryInput">{{ __('login using an authentication code') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
