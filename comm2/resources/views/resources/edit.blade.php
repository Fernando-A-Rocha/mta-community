<x-layouts.app title="Edit Resource">
        <div class="flex w-full flex-1 flex-col gap-6">
            <div class="mb-2">
                <h2 class="text-2xl font-bold">Edit Resource: {{ $resource->display_name }}</h2>
            </div>

            <form action="{{ route('resources.update', $resource) }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="resource-edit-form">
                @csrf
                @method('PUT')

                <!-- Short Description -->
                <div>
                    <flux:field>
                        <flux:label>Short Description</flux:label>
                        <flux:textarea
                            name="short_description"
                            rows="3"
                            id="short_description"
                            maxlength="500"
                        >{{ old('short_description', $resource->short_description) }}</flux:textarea>
                        <flux:description>Brief description of your resource (max 500 characters). This appears in the resource header.</flux:description>
                    </flux:field>
                </div>

                <!-- Long Description -->
                <div>
                    <flux:field>
                        <flux:label>Long Description</flux:label>
                        <flux:textarea
                            name="long_description"
                            rows="10"
                            id="long_description"
                            minlength="50"
                            maxlength="10000"
                        >{{ old('long_description', $resource->long_description) }}</flux:textarea>
                        <flux:description>Detailed description of your resource (minimum 50 characters if provided)</flux:description>
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
                                    :checked="in_array($tag->id, old('tags', $resource->tags->pluck('id')->toArray()))"
                                />
                            @endforeach
                        </div>
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
                                    :checked="in_array($language->id, old('languages', $resource->languages->pluck('id')->toArray()))"
                                />
                            @endforeach
                        </div>
                        <flux:description>Select the language(s) your resource supports. You can select multiple languages if your resource is multi-lingual. Languages are optional - you can leave this empty if your resource doesn't output any text.</flux:description>
                    </flux:field>
                </div>

                <!-- Existing Images -->
                @if ($resource->images->isNotEmpty())
                    <div>
                        <flux:field>
                            <flux:label>Current Images</flux:label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach ($resource->images as $image)
                                    <div class="relative border rounded-lg overflow-hidden">
                                        <img
                                            src="{{ Storage::disk('public')->url($image->path) }}"
                                            alt="Resource image"
                                            class="w-full h-32 object-cover"
                                        />
                                        <div class="p-2 bg-white dark:bg-zinc-800">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="remove_images[]"
                                                    value="{{ $image->id }}"
                                                    class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                                                />
                                                <span class="text-sm text-red-600 dark:text-red-400">Remove</span>
                                            </label>
                                            @if ($image->is_display_image)
                                                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Display Image</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <flux:description>Check the boxes to remove images. You can add new images below.</flux:description>
                        </flux:field>
                    </div>
                @endif

                <!-- Add New Images -->
                <div>
                    <flux:field>
                        <flux:label>Add New Images (max 10 total)</flux:label>
                        <flux:input
                            type="file"
                            name="images[]"
                            multiple
                            accept="image/jpeg,image/png"
                            id="images_input"
                        />
                        <flux:description>JPG or PNG images. Max 2MB each. Current: {{ $resource->images->count() }}/10</flux:description>
                        <div id="images_info" class="mt-2 hidden">
                            <div class="flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm text-blue-800 dark:text-blue-200">
                                    <span id="images_count"></span> image(s) selected
                                </span>
                            </div>
                        </div>
                    </flux:field>
                </div>

                <!-- GitHub URL -->
                <div>
                    <flux:field>
                        <flux:label>GitHub Repository URL (optional)</flux:label>
                        <flux:input
                            type="url"
                            name="github_url"
                            id="github_url"
                            placeholder="https://github.com/username/repository"
                            value="{{ old('github_url', $resource->github_url) }}"
                        />
                        <flux:description>Link to your GitHub repository where this resource's code is hosted</flux:description>
                    </flux:field>
                </div>

                <!-- Forum Thread URL -->
                <div>
                    <flux:field>
                        <flux:label>MTA Forum Thread URL (optional)</flux:label>
                        <flux:input
                            type="url"
                            name="forum_thread_url"
                            id="forum_thread_url"
                            placeholder="https://forum.multitheftauto.com/topic/12345-topic-title"
                            value="{{ old('forum_thread_url', $resource->forum_thread_url) }}"
                        />
                        <flux:description>Link to your MTA Forum thread where this resource is showcased/discussed</flux:description>
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <flux:link :href="route('resources.show', $resource)" variant="ghost">
                        Cancel
                    </flux:link>
                    <flux:button type="submit" variant="primary" id="submit-button">
                        <span id="submit-text">Update Resource</span>
                        <span id="submit-loading" class="hidden">Updating...</span>
                    </flux:button>
                </div>
            </form>

            <!-- Releases List -->
            @if ($resource->versions->isNotEmpty())
                <div class="mt-6">
                    <flux:field>
                        <flux:label>Releases</flux:label>
                        @php
                            $firstVersion = $resource->versions->sortBy('created_at')->first();
                        @endphp
                        @foreach ($resource->versions as $version)
                            @php
                                $isFirstVersion = $firstVersion->id === $version->id;
                            @endphp
                            <div class="flex items-center justify-between p-3 border rounded {{ $version->is_current ? 'border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">Version {{ $version->version }}</span>
                                        @if ($version->is_current)
                                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-medium">
                                                Latest
                                            </span>
                                        @endif
                                        @if ($isFirstVersion)
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded text-xs font-medium">
                                                First Release
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Released {{ $version->created_at->format('M d, Y') }}
                                    </p>
                                </div>
                                @auth
                                    @can('deleteVersion', $resource)
                                        @if (!$isFirstVersion)
                                            <form method="POST" action="{{ route('resources.versions.destroy', [$resource, $version]) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete release v{{ $version->version }}? This action cannot be undone.');"
                                                    class="ml-4">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button type="submit" variant="danger" size="sm">
                                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete Release
                                                </flux:button>
                                            </form>
                                        @endif
                                    @endcan
                                @endauth
                            </div>
                        @endforeach
                        <flux:description>Manage your resource releases. You can delete individual releases (except the first one).</flux:description>
                    </flux:field>
                </div>
            @endif

            <!-- Danger Zone -->
            @auth
                @can('delete', $resource)
                    <div class="border rounded-lg p-4 bg-white dark:bg-zinc-800 border-red-200 dark:border-red-800 mt-6">
                        <h3 class="font-bold mb-4 text-lg text-red-600 dark:text-red-400">Danger Zone</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Once you delete a resource, there is no going back. This will permanently delete the resource and all associated files, versions, and data.
                        </p>
                        @if (auth()->user()->id === $resource->user_id && !auth()->user()->isModerator())
                            <!-- Author delete with JS confirm and prompt -->
                            <form id="delete-resource-form-{{ $resource->id }}" method="POST" action="{{ route('resources.destroy', $resource) }}" class="inline-block w-full">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="resource_name" id="resource_name_input-{{ $resource->id }}">
                                <flux:button
                                    type="button"
                                    variant="danger"
                                    class="w-full"
                                    onclick="
                                        const resourceName = prompt('Type the resource name to confirm deletion:');
                                        if (resourceName !== null) {
                                            document.getElementById('resource_name_input-{{ $resource->id }}').value = resourceName;
                                            if (confirm('Are you sure you want to permanently delete this resource? This will delete all versions and cannot be undone.')) {
                                                document.getElementById('delete-resource-form-{{ $resource->id }}').submit();
                                            }
                                        }
                                    "
                                >
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete Resource
                                </flux:button>
                            </form>
                        @else
                            <!-- Admin/Moderator delete with JS confirm -->
                            <form method="POST" action="{{ route('resources.destroy', $resource) }}"
                                  onsubmit="return confirm('Are you sure you want to permanently delete this resource? This will delete all versions and cannot be undone.');"
                                  class="inline-block w-full">
                                @csrf
                                @method('DELETE')
                                <flux:button type="submit" variant="danger" class="w-full">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete Resource
                                </flux:button>
                            </form>
                        @endif
                    </div>
                @endcan
            @endauth

            <script>
                // Images input
                const imagesInput = document.getElementById('images_input');
                const imagesInfo = document.getElementById('images_info');
                const imagesCount = document.getElementById('images_count');

                // Update images info when files are selected
                if (imagesInput) {
                    imagesInput.addEventListener('change', function(e) {
                        const files = Array.from(e.target.files || []);
                        if (files.length > 0 && imagesInfo && imagesCount) {
                            imagesCount.textContent = files.length;
                            imagesInfo.classList.remove('hidden');
                        } else if (imagesInfo) {
                            imagesInfo.classList.add('hidden');
                        }
                    });
                }

                // Form submission handler
                document.getElementById('resource-edit-form')?.addEventListener('submit', function(e) {
                    const submitButton = document.getElementById('submit-button');
                    const submitText = document.getElementById('submit-text');
                    const submitLoading = document.getElementById('submit-loading');

                    if (submitButton && submitText && submitLoading) {
                        submitButton.disabled = true;
                        submitText.classList.add('hidden');
                        submitLoading.classList.remove('hidden');
                    }
                });
            </script>
        </div>
</x-layouts.app>

