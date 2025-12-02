@php
    $canUpload = $canUpload ?? true;
@endphp

<x-layouts.app title="Upload New Version">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="mb-2">
            <h2 class="text-2xl font-bold">Upload New Version</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Upload a new version of an existing resource you own. The ZIP file name must match the existing resource name.
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
            <form action="{{ route('resources.upload.version.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="resource-upload-form">
                @csrf

                @if (session('success'))
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">Please fix the following errors:</p>
                        <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- English Only Notice -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Note:</strong> All text fields including title, descriptions, and changelog must be written in English only (ASCII characters).
                    </p>
                </div>

                <!-- ZIP File Upload -->
                <div>
                    <flux:field>
                        <flux:label>Resource ZIP File *</flux:label>
                        <flux:input
                            type="file"
                            name="zip_file"
                            accept=".zip"
                            id="zip_file"
                            required
                        />
                        <flux:description>Upload a ZIP file. The filename (without .zip) must match the name of an existing resource you own. Max size: 20MB</flux:description>
                        @error('zip_file')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Changelog -->
                <div>
                    <flux:field>
                        <flux:label>Changelog *</flux:label>
                        <flux:textarea
                            name="changelog"
                            rows="5"
                            id="changelog"
                            minlength="10"
                            maxlength="5000"
                            required
                        >{{ old('changelog') }}</flux:textarea>
                        <flux:description>Describe what changed in this version (minimum 10 characters). Must be in English only.</flux:description>
                        @error('changelog')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <flux:link :href="route('resources.upload.create')" variant="ghost">
                        Cancel
                    </flux:link>
                    <flux:button type="submit" variant="primary">
                        Upload Version
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>

