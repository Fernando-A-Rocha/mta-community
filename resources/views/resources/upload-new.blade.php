@php
    $canUpload = $canUpload ?? true;
@endphp

<x-layouts.app title="Upload New Resource">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="mb-2">
            <h2 class="text-2xl font-bold">Upload New Resource</h2>
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
            <form action="{{ route('resources.upload.new.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="resource-upload-form">
                @csrf

                <!-- English Only Notice -->
                <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                    <p class="text-sm text-orange-800 dark:text-orange-200">
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
                        <flux:description>Upload a ZIP file named after your resource. Max size: 20MB</flux:description>
                    </flux:field>
                </div>

                <!-- Long Description -->
                <div>
                    <flux:field>
                        <flux:label>Long Description *</flux:label>
                        <flux:textarea
                            name="long_description"
                            rows="10"
                            id="long_description"
                            minlength="50"
                            maxlength="10000"
                            required
                        >{{ old('long_description') }}</flux:textarea>
                        <flux:description>Detailed description of your resource (minimum 50 characters). Must be in English only.</flux:description>
                    </flux:field>
                </div>

                <!-- Languages -->
                <div>
                    <flux:field>
                        <flux:label>Languages</flux:label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-zinc-800 max-h-48 overflow-y-auto">
                            @foreach ($languages as $language)
                                <flux:checkbox
                                    name="languages[]"
                                    value="{{ $language->id }}"
                                    :label="$language->name"
                                    :checked="in_array($language->id, old('languages', []))"
                                />
                            @endforeach
                        </div>
                        <flux:description>Select the language(s) your resource supports. You can select multiple languages if your resource is multi-lingual. Languages are optional - you can leave this empty if your resource doesn't output any text.</flux:description>
                    </flux:field>
                </div>

                <!-- Tags -->
                <div>
                    <flux:field>
                        <flux:label>Tags (max 5)</flux:label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-zinc-800 max-h-48 overflow-y-auto">
                            @foreach ($tags as $tag)
                                <flux:checkbox
                                    name="tags[]"
                                    value="{{ $tag->id }}"
                                    :label="$tag->name"
                                    :checked="in_array($tag->id, old('tags', []))"
                                />
                            @endforeach
                        </div>
                    </flux:field>
                </div>

                <!-- Images -->
                <div>
                    <flux:field>
                        <flux:label>Images (max 10, first will be display image)</flux:label>
                        <flux:input
                            type="file"
                            name="images[]"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            id="images_input"
                        />
                        <flux:description>JPG, PNG, or WEBP images. Max 2MB each.</flux:description>
                    </flux:field>
                </div>

                <!-- GitHub URL -->
                <div>
                    <flux:field>
                        <flux:label>GitHub Repository URL</flux:label>
                        <flux:input
                            type="url"
                            name="github_url"
                            id="github_url"
                            placeholder="https://github.com/username/repository"
                            value="{{ old('github_url') }}"
                        />
                        <flux:description>Link to your GitHub repository where this resource's code is hosted (e.g., https://github.com/username/repository)</flux:description>
                    </flux:field>
                </div>

                <!-- Forum Thread URL -->
                <div>
                    <flux:field>
                        <flux:label>MTA Forum Thread URL</flux:label>
                        <flux:input
                            type="url"
                            name="forum_thread_url"
                            id="forum_thread_url"
                            placeholder="https://forum.multitheftauto.com/topic/12345-topic-title"
                            value="{{ old('forum_thread_url') }}"
                        />
                        <flux:description>Link to your MTA Forum thread where this resource is showcased/discussed (must be at forum.multitheftauto.com)</flux:description>
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <flux:link :href="route('resources.upload.create')" variant="ghost">
                        Cancel
                    </flux:link>
                    <flux:button type="submit" variant="primary">
                        Upload Resource
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>

