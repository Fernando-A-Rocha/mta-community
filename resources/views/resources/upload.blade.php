@php
    $canUpload = $canUpload ?? true;
@endphp

<x-layouts.app title="Upload Resource">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <a href="{{ route('resources.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900 hover:underline dark:text-slate-400 dark:hover:text-white">
                ← Back to Resources
            </a>
        </div>

        <div class="mb-2">
            <h2 class="text-2xl font-bold">Upload Resource</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Use this form to upload <strong>maps, scripts, gamemodes, and other resources</strong> to the community. All uploads must not violate any rules or guidelines.
            </p>
        </div>

        @if (! $canUpload)
            <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-6 text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-50">
                <h3 class="text-xl font-semibold">{{ __('Make your profile public first') }}</h3>
                <p class="mt-2 text-sm">
                    {{ __('Community uploads require a public profile so other players can discover and contact you. Switch your profile visibility to public and then return here.') }}
                </p>
                <div class="mt-4">
                    <flux:link :href="route('profile.edit')" variant="primary">
                        {{ __('Open profile settings') }}
                    </flux:link>
                </div>
            </div>
        @else
            <div class="grid md:grid-cols-2 gap-6 max-w-4xl">
                <!-- Upload New Resource -->
                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-8 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                    <div class="flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Upload New Resource</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Create a brand new resource with its first version. You'll need to provide a detailed description, tags, and optionally images.
                        </p>
                        <flux:link :href="route('resources.upload.new')" variant="primary" class="mt-2">
                            Upload New Resource
                        </flux:link>
                    </div>
                </div>

                <!-- Upload New Version -->
                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg p-8 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                    <div class="flex flex-col items-center text-center gap-4">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Upload New Version</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Update an existing resource you own with a new version. You'll need to provide a changelog describing what has been updated.
                        </p>
                        <flux:link :href="route('resources.upload.version')" variant="primary" class="mt-2">
                            Upload New Version
                        </flux:link>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
