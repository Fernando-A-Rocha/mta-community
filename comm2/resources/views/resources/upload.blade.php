@php
    $canUpload = $canUpload ?? true;
@endphp

<x-layouts.app title="Upload Resource">
        <div class="flex w-full flex-1 flex-col gap-6">
            <div class="mb-2">
                <h2 class="text-2xl font-bold">Upload Resource</h2>
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
            <form action="{{ route('resources.upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="resource-upload-form">
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
                        />
                        <flux:description>Upload a ZIP file named after your resource. Max size: 20MB</flux:description>
                        <div id="zip_file_info" class="mt-2 hidden">
                            <div class="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="text-sm text-blue-800 dark:text-blue-200">
                                    <span class="text-blue-600 dark:text-blue-400">Last file selected: </span>
                                    <span id="zip_file_name"></span>
                                </span>
                            </div>
                        </div>
                        @error('zip_file')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Upload Mode Toggle -->
                <div>
                    <flux:field>
                        <flux:label>Upload Type</flux:label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="upload_mode"
                                    value="first_version"
                                    id="upload_mode_first"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    {{ old('upload_mode', 'first_version') === 'first_version' ? 'checked' : '' }}
                                />
                                <span class="text-sm font-medium">First version of the resource</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="upload_mode"
                                    value="new_release"
                                    id="upload_mode_release"
                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    {{ old('upload_mode', 'first_version') === 'new_release' ? 'checked' : '' }}
                                />
                                <span class="text-sm font-medium">New release of existing resource</span>
                            </label>
                        </div>
                        <flux:description>
                            Select whether this is a new resource or an update to an existing one you own.
                        </flux:description>
                    </flux:field>
                </div>

                <!-- Long Description (for first-time uploads) -->
                <div id="first_version_fields" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>Long Description *</flux:label>
                        <div class="relative">
                            <flux:textarea
                                name="long_description"
                                rows="10"
                                id="long_description"
                                data-min="50"
                                data-max="10000"
                                class="pb-8"
                            >{{ old('long_description') }}</flux:textarea>
                            <div class="absolute bottom-3 right-3 text-xs bg-white dark:bg-zinc-800 px-2 py-1 rounded" id="long_description_counter">
                                <span id="long_description_count">0</span>/<span id="long_description_max">10000</span>
                                <span id="long_description_min_status" class="ml-2"></span>
                            </div>
                        </div>
                        <flux:description>Detailed description of your resource (required for first-time uploads, minimum 50 characters). Must be in English only.</flux:description>
                        @error('long_description')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Changelog (for updates) -->
                <div id="new_release_fields" class="{{ old('upload_mode', 'first_version') === 'first_version' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>Changelog *</flux:label>
                        <div class="relative">
                            <flux:textarea
                                name="changelog"
                                rows="5"
                                id="changelog"
                                data-min="10"
                                data-max="5000"
                                class="pb-8"
                            >{{ old('changelog') }}</flux:textarea>
                            <div class="absolute bottom-3 right-3 text-xs bg-white dark:bg-zinc-800 px-2 py-1 rounded" id="changelog_counter">
                                <span id="changelog_count">0</span>/<span id="changelog_max">5000</span>
                                <span id="changelog_min_status" class="ml-2"></span>
                            </div>
                        </div>
                        <flux:description>Describe what changed in this version (required for updates, minimum 10 characters). Must be in English only.</flux:description>
                        @error('changelog')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Languages (only for first version) -->
                <div id="languages_field" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>Languages *</flux:label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 border rounded-lg p-3 bg-white dark:bg-zinc-800 max-h-48 overflow-y-auto">
                            @foreach ($languages as $language)
                                <flux:checkbox
                                    name="languages[]"
                                    value="{{ $language->id }}"
                                    :label="$language->name"
                                    :checked="in_array($language->id, old('languages', []))"
                                />
                            @endforeach
                        </div>
                        <flux:description>Select the language(s) your resource supports. You can select multiple languages if your resource is multi-lingual.</flux:description>
                        @error('languages')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                        @error('languages.*')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Tags (only for first version) -->
                <div id="tags_field" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>Tags (max 5)</flux:label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 border rounded-lg p-3 bg-white dark:bg-zinc-800 max-h-48 overflow-y-auto">
                            @foreach ($tags as $tag)
                                <flux:checkbox
                                    name="tags[]"
                                    value="{{ $tag->id }}"
                                    :label="$tag->name"
                                    :checked="in_array($tag->id, old('tags', []))"
                                />
                            @endforeach
                        </div>
                        @error('tags')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                        @error('tags.*')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Images (only for first version) -->
                <div id="images_field" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>Images (max 10, first will be display image)</flux:label>
                        <flux:input
                            type="file"
                            name="images[]"
                            multiple
                            accept="image/jpeg,image/png"
                            id="images_input"
                        />
                        <flux:description>JPG or PNG images. Max 2MB each.</flux:description>
                        @error('images')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                        @error('images.*')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- GitHub URL (only for first version) -->
                <div id="github_url_field" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>GitHub Repository URL (optional)</flux:label>
                        <flux:input
                            type="url"
                            name="github_url"
                            id="github_url"
                            placeholder="https://github.com/username/repository"
                            value="{{ old('github_url') }}"
                        />
                        <flux:description>Link to your GitHub repository where this resource's code is hosted (e.g., https://github.com/username/repository)</flux:description>
                        @error('github_url')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Forum Thread URL (only for first version) -->
                <div id="forum_thread_url_field" class="{{ old('upload_mode', 'first_version') === 'new_release' ? 'hidden' : '' }}">
                    <flux:field>
                        <flux:label>MTA Forum Thread URL (optional)</flux:label>
                        <flux:input
                            type="url"
                            name="forum_thread_url"
                            id="forum_thread_url"
                            placeholder="https://forum.multitheftauto.com/topic/12345-topic-title"
                            value="{{ old('forum_thread_url') }}"
                        />
                        <flux:description>Link to your MTA Forum thread where this resource is showcased/discussed (must be at forum.multitheftauto.com)</flux:description>
                        @error('forum_thread_url')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <flux:link :href="route('resources.index')" variant="ghost">
                        Cancel
                    </flux:link>
                    <flux:button type="submit" variant="primary" id="submit-button">
                        <span id="submit-text">Upload Resource</span>
                        <span id="submit-loading" class="hidden">Uploading...</span>
                    </flux:button>
                </div>
            </form>

            <script>
                // Upload mode toggle handler
                const uploadModeFirst = document.getElementById('upload_mode_first');
                const uploadModeRelease = document.getElementById('upload_mode_release');
                const firstVersionFields = document.getElementById('first_version_fields');
                const newReleaseFields = document.getElementById('new_release_fields');
                const languagesField = document.getElementById('languages_field');
                const tagsField = document.getElementById('tags_field');
                const imagesField = document.getElementById('images_field');
                const githubUrlField = document.getElementById('github_url_field');
                const forumThreadUrlField = document.getElementById('forum_thread_url_field');

                function updateUploadMode() {
                    const isFirstVersion = uploadModeFirst?.checked || false;

                    // Show/hide fields based on mode
                    if (firstVersionFields) {
                        const wasHidden = firstVersionFields.classList.contains('hidden');
                        firstVersionFields.classList.toggle('hidden', !isFirstVersion);
                        // Update counter when field becomes visible
                        if (wasHidden && isFirstVersion && typeof window.updateLongDescCounter === 'function') {
                            setTimeout(window.updateLongDescCounter, 50);
                        }
                    }
                    if (newReleaseFields) {
                        const wasHidden = newReleaseFields.classList.contains('hidden');
                        newReleaseFields.classList.toggle('hidden', isFirstVersion);
                        // Update counter when field becomes visible
                        if (wasHidden && !isFirstVersion && typeof window.updateChangelogCounter === 'function') {
                            setTimeout(window.updateChangelogCounter, 50);
                        }
                    }
                    if (languagesField) {
                        languagesField.classList.toggle('hidden', !isFirstVersion);
                    }
                    if (tagsField) {
                        tagsField.classList.toggle('hidden', !isFirstVersion);
                    }
                    if (imagesField) {
                        imagesField.classList.toggle('hidden', !isFirstVersion);
                    }
                    if (githubUrlField) {
                        githubUrlField.classList.toggle('hidden', !isFirstVersion);
                    }
                    if (forumThreadUrlField) {
                        forumThreadUrlField.classList.toggle('hidden', !isFirstVersion);
                    }
                }

                if (uploadModeFirst) {
                    uploadModeFirst.addEventListener('change', updateUploadMode);
                }
                if (uploadModeRelease) {
                    uploadModeRelease.addEventListener('change', updateUploadMode);
                }

                // Initial update
                updateUploadMode();

                // Form submission handler
                document.getElementById('resource-upload-form')?.addEventListener('submit', function(e) {
                    const submitButton = document.getElementById('submit-button');
                    const submitText = document.getElementById('submit-text');
                    const submitLoading = document.getElementById('submit-loading');

                    if (submitButton && submitText && submitLoading) {
                        submitButton.disabled = true;
                        submitText.classList.add('hidden');
                        submitLoading.classList.remove('hidden');
                    }
                });

                // Character counter functions - use name attribute which is more reliable
                window.updateLongDescCounter = function() {
                    const textarea = document.querySelector('textarea[name="long_description"]');
                    const countEl = document.getElementById('long_description_count');
                    const statusEl = document.getElementById('long_description_min_status');

                    if (!textarea || !countEl || !statusEl) {
                        return;
                    }

                    const length = (textarea.value || '').length;
                    const min = 50;
                    countEl.textContent = length;

                    if (length >= min) {
                        statusEl.textContent = '✓';
                        statusEl.className = 'ml-2 text-green-600 dark:text-green-400 font-semibold';
                    } else {
                        const remaining = min - length;
                        statusEl.textContent = `${remaining} more needed`;
                        statusEl.className = 'ml-2 text-red-600 dark:text-red-400';
                    }
                };

                window.updateChangelogCounter = function() {
                    const textarea = document.querySelector('textarea[name="changelog"]');
                    const countEl = document.getElementById('changelog_count');
                    const statusEl = document.getElementById('changelog_min_status');

                    if (!textarea || !countEl || !statusEl) {
                        return;
                    }

                    const length = (textarea.value || '').length;
                    const min = 10;
                    countEl.textContent = length;

                    if (length >= min) {
                        statusEl.textContent = '✓';
                        statusEl.className = 'ml-2 text-green-600 dark:text-green-400 font-semibold';
                    } else {
                        const remaining = min - length;
                        statusEl.textContent = `${remaining} more needed`;
                        statusEl.className = 'ml-2 text-red-600 dark:text-red-400';
                    }
                };

                // Initialize character counters with multiple attempts
                function initializeCharacterCounters() {
                    const form = document.getElementById('resource-upload-form');
                    if (!form) {
                        return;
                    }

                    // Use event delegation - listen to ALL input events on textareas
                    form.addEventListener('input', function(e) {
                        if (e.target.tagName === 'TEXTAREA') {
                            if (e.target.name === 'long_description' && window.updateLongDescCounter) {
                                window.updateLongDescCounter();
                            } else if (e.target.name === 'changelog' && window.updateChangelogCounter) {
                                window.updateChangelogCounter();
                            }
                        }
                    });

                    form.addEventListener('keyup', function(e) {
                        if (e.target.tagName === 'TEXTAREA') {
                            if (e.target.name === 'long_description' && window.updateLongDescCounter) {
                                window.updateLongDescCounter();
                            } else if (e.target.name === 'changelog' && window.updateChangelogCounter) {
                                window.updateChangelogCounter();
                            }
                        }
                    });

                    form.addEventListener('paste', function(e) {
                        if (e.target.tagName === 'TEXTAREA') {
                            setTimeout(function() {
                                if (e.target.name === 'long_description' && window.updateLongDescCounter) {
                                    window.updateLongDescCounter();
                                } else if (e.target.name === 'changelog' && window.updateChangelogCounter) {
                                    window.updateChangelogCounter();
                                }
                            }, 10);
                        }
                    });

                    // Initial update - try multiple times in case Flux hasn't rendered yet
                    let attempts = 0;
                    const tryUpdate = function() {
                        attempts++;
                        if (window.updateLongDescCounter) window.updateLongDescCounter();
                        if (window.updateChangelogCounter) window.updateChangelogCounter();
                        if (attempts < 10) {
                            setTimeout(tryUpdate, 200);
                        }
                    };
                    setTimeout(tryUpdate, 100);
                }

                // Initialize when ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(initializeCharacterCounters, 100);
                    });
                } else {
                    setTimeout(initializeCharacterCounters, 100);
                }
            </script>
        @endif
        </div>
</x-layouts.app>

