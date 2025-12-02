<x-layouts.app title="Upload Media">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="mb-2">
            <h2 class="text-2xl font-bold">Upload Media</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Share your gameplay screenshots, videos, and awesome moments with the community!
            </p>
        </div>

        @if (! $canUpload)
            <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-6 text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-50">
                @if (($user->profile_visibility ?? 'public') !== 'public')
                    <h3 class="text-xl font-semibold">{{ __('Make your profile public first') }}</h3>
                    <p class="mt-2 text-sm">
                        {{ __('Media uploads require a public profile so other players can discover and contact you. Switch your profile visibility to public and then return here.') }}
                    </p>
                    <div class="mt-4">
                        <flux:link :href="route('profile.edit')" variant="primary">
                            {{ __('Open profile settings') }}
                        </flux:link>
                    </div>
                @elseif (! $canUploadByTime)
                    <h3 class="text-xl font-semibold">{{ __('Upload limit reached') }}</h3>
                    <p class="mt-2 text-sm">
                        {{ __('You can only upload media once per 24 hours.') }}
                        @if ($timeUntilNextUpload)
                            {{ __('You can upload again at :time.', ['time' => $timeUntilNextUpload->format('Y-m-d H:i:s')]) }}
                        @endif
                    </p>
                @endif
            </div>
        @else
            <form action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="media-upload-form">
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

                <!-- Content Rules -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Content Guidelines</h3>
                    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                        <li>Gameplay of MTA is encouraged</li>
                        <li>Funny moments and awesome showcases are welcome</li>
                        <li>Keep content appropriate and respectful</li>
                        <li>Images: Up to 5 images per post (max 1MB each, 1920x1080 resolution)</li>
                        <li>Videos: YouTube links only</li>
                        <li>Description: Maximum 100 characters (emojis allowed)</li>
                    </ul>
                </div>

                <!-- Upload Limit Info -->
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        <strong>Note:</strong> You can upload media once per 24 hours. Make sure your profile is set to public.
                    </p>
                </div>

                <!-- Media Type -->
                <div>
                    <flux:field>
                        <flux:label>Media Type *</flux:label>
                        <flux:select name="type" id="media-type" required onchange="toggleMediaFields()">
                            <option value="">Select type</option>
                            <option value="image" {{ old('type') === 'image' ? 'selected' : '' }}>Images (up to 5)</option>
                            <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>YouTube Video</option>
                        </flux:select>
                        <flux:description>Choose whether to upload images or share a YouTube video</flux:description>
                        @error('type')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Image Upload (shown when type is image) -->
                <div id="image-upload-section" style="display: none;">
                    <flux:field>
                        <flux:label>Images *</flux:label>
                        <flux:input
                            type="file"
                            name="images[]"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            id="images"
                            multiple
                        />
                        <flux:description>Upload up to 5 images. Each image must be max 1MB and 1920x1080 resolution. Formats: JPG, PNG, WebP</flux:description>
                        <div id="image-preview" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4"></div>
                        @error('images')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                        @error('images.*')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- YouTube URL (shown when type is video) -->
                <div id="video-upload-section" style="display: none;">
                    <flux:field>
                        <flux:label>YouTube URL *</flux:label>
                        <flux:input
                            type="url"
                            name="youtube_url"
                            placeholder="https://www.youtube.com/watch?v=..."
                            value="{{ old('youtube_url') }}"
                            id="youtube_url"
                        />
                        <flux:description>Paste a YouTube video URL (watch, youtu.be, embed formats supported)</flux:description>
                        @error('youtube_url')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Description -->
                <div>
                    <flux:field>
                        <flux:label>Description *</flux:label>
                        <flux:textarea
                            name="description"
                            rows="3"
                            id="description"
                            maxlength="100"
                            required
                            oninput="updateCharCount()"
                        >{{ old('description') }}</flux:textarea>
                        <flux:description>
                            <span id="char-count">0</span>/100 characters (emojis allowed)
                        </flux:description>
                        @error('description')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <flux:button type="submit" variant="primary">
                        Upload Media
                    </flux:button>
                </div>
            </form>
        @endif
    </div>

    <script>
        function toggleMediaFields() {
            const type = document.getElementById('media-type').value;
            const imageSection = document.getElementById('image-upload-section');
            const videoSection = document.getElementById('video-upload-section');
            const imageInput = document.getElementById('images');
            const videoInput = document.getElementById('youtube_url');

            if (type === 'image') {
                imageSection.style.display = 'block';
                videoSection.style.display = 'none';
                imageInput.required = true;
                videoInput.required = false;
            } else if (type === 'video') {
                imageSection.style.display = 'none';
                videoSection.style.display = 'block';
                imageInput.required = false;
                videoInput.required = true;
            } else {
                imageSection.style.display = 'none';
                videoSection.style.display = 'none';
                imageInput.required = false;
                videoInput.required = false;
            }
        }

        function updateCharCount() {
            const textarea = document.getElementById('description');
            const charCount = document.getElementById('char-count');
            charCount.textContent = textarea.value.length;
        }

        // Image preview
        document.getElementById('images')?.addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            const files = Array.from(e.target.files).slice(0, 5);

            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-32 object-cover rounded-lg border border-slate-200 dark:border-slate-700';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleMediaFields();
            updateCharCount();
        });
    </script>
</x-layouts.app>

