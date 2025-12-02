<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationCategory;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Requests\UpdateVersionVerificationRequest;
use App\Models\Resource;
use App\Models\ResourceDownload;
use App\Models\ResourceImage;
use App\Models\ResourceRating;
use App\Models\ResourceVersion;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Display a listing of resources.
     */
    public function index(Request $request): View
    {
        $query = Resource::with(['user', 'tags', 'languages', 'displayImage', 'currentVersion'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings');

        // Only show disabled resources to moderators+
        if (! Auth::check() || ! Auth::user()->isModerator()) {
            $query->where('is_disabled', false);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('long_name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');

        switch ($sortBy) {
            case 'rating':
                $query->withAvg('ratings', 'rating')
                    ->orderBy('ratings_avg_rating', $sortOrder)
                    ->orderBy('updated_at', 'desc'); // Secondary sort
                break;
            case 'downloads':
                $query->withCount('downloads')
                    ->orderBy('downloads_count', $sortOrder)
                    ->orderBy('updated_at', 'desc'); // Secondary sort
                break;
            case 'date':
            default:
                $query->orderBy('updated_at', $sortOrder);
                break;
        }

        $resources = $query->paginate(24)->withQueryString();
        $categories = ['gamemode', 'script', 'map', 'misc'];

        return view('resources.index', compact('resources', 'categories', 'sortBy', 'sortOrder'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource): View
    {
        $resource->load([
            'user',
            'tags',
            'languages',
            'images',
            'currentVersion',
            'ratings.user',
            'versions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        // Get user's rating if authenticated
        $userRating = null;
        if (Auth::check()) {
            $userRating = $resource->ratings()->where('user_id', Auth::id())->first();
        }

        $existingReport = Auth::check()
            ? $resource->reports()
                ->where('reporter_id', Auth::id())
                ->latest('id')
                ->first()
            : null;

        $resource->loadCount('followers');

        return view('resources.show', compact('resource', 'userRating', 'existingReport'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource): View
    {
        $this->authorize('update', $resource);

        $tags = Tag::orderBy('name')->get();
        $languages = \App\Models\Language::orderBy('name')->get();
        $resource->load([
            'tags',
            'languages',
            'images',
            'versions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        return view('resources.edit', compact('resource', 'tags', 'languages'));
    }

    /**
     * Update the specified resource.
     */
    public function update(UpdateResourceRequest $request, Resource $resource): RedirectResponse
    {
        $this->authorize('update', $resource);

        $user = Auth::user();
        $changes = [];

        DB::transaction(function () use ($request, $resource, &$changes) {
            // Track changes for logging
            $oldValues = [
                'short_description' => $resource->short_description,
                'long_description' => $resource->long_description,
                'github_url' => $resource->github_url,
                'forum_thread_url' => $resource->forum_thread_url,
            ];

            // Update basic fields
            $resource->update([
                'short_description' => $request->input('short_description'),
                'long_description' => $request->input('long_description'),
                'github_url' => $request->input('github_url'),
                'forum_thread_url' => $request->input('forum_thread_url'),
            ]);

            // Track what changed
            foreach ($oldValues as $field => $oldValue) {
                $newValue = $resource->$field;
                if ($oldValue !== $newValue) {
                    $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
                }
            }

            // Log request - DEBUG
            Log::info('Request:', $request->all());

            // Update tags
            $oldTagIds = $resource->tags()->pluck('tags.id')->toArray();
            if ($request->has('tags')) {
                $newTagIds = $request->input('tags', []);
                $resource->tags()->sync($newTagIds);
                if ($oldTagIds !== $newTagIds) {
                    $changes['tags'] = ['old' => $oldTagIds, 'new' => $newTagIds];
                }
            } else {
                // Set no tags
                $resource->tags()->sync([]);
                $changes['tags'] = ['old' => $oldTagIds, 'new' => []];
            }

            // Update languages
            // Note: If no checkboxes are checked, the field won't be in the request
            // We only update if the field is explicitly present
            $oldLanguageIds = $resource->languages()->pluck('languages.id')->toArray();
            if ($request->has('languages')) {
                $newLanguageIds = $request->input('languages', []);
                $resource->languages()->sync($newLanguageIds);
                if ($oldLanguageIds !== $newLanguageIds) {
                    $changes['languages'] = ['old' => $oldLanguageIds, 'new' => $newLanguageIds];
                }
            } else {
                // Set no languages
                $resource->languages()->sync([]);
                $changes['languages'] = ['old' => $oldLanguageIds, 'new' => []];
            }

            $imagesRemoved = 0;
            $imagesAdded = 0;

            // Remove images
            if ($request->has('remove_images')) {
                $imagesToRemove = ResourceImage::whereIn('id', $request->input('remove_images', []))
                    ->where('resource_id', $resource->id)
                    ->get();

                $imagesRemoved = $imagesToRemove->count();

                foreach ($imagesToRemove as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }

                // If we removed the display image, set the first remaining image as display
                if ($resource->displayImage === null && $resource->images()->count() > 0) {
                    $firstImage = $resource->images()->orderBy('order')->first();
                    if ($firstImage) {
                        $firstImage->update(['is_display_image' => true]);
                    }
                }
            }

            // Add new images
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                if (is_array($images)) {
                    $existingCount = $resource->images()->count();
                    $maxImages = 10;
                    $remainingSlots = $maxImages - $existingCount;

                    if ($remainingSlots > 0) {
                        $imagesToAdd = array_slice($images, 0, $remainingSlots);
                        $this->storeImages($resource, $imagesToAdd, $existingCount);
                        $imagesAdded = count($imagesToAdd);
                    }
                }
            }

            if ($imagesRemoved > 0 || $imagesAdded > 0) {
                $changes['images'] = [
                    'removed' => $imagesRemoved,
                    'added' => $imagesAdded,
                ];
            }

            // Touch updated_at
            $resource->touch();
        });

        // Log the update if there were changes
        if (! empty($changes)) {
            ActivityLogger::log(
                'resource.updated',
                $user,
                $request->ip(),
                [
                    'resource_id' => $resource->id,
                    'resource_name' => $resource->name,
                    'changes' => $changes,
                    'is_moderator_edit' => $user->id !== $resource->user_id,
                ],
                $request->userAgent()
            );

            $this->notifyResourceFollowersAboutUpdate($resource, array_keys($changes), $user);
        }

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Resource updated successfully!');
    }

    /**
     * Store or update a rating for the resource.
     */
    public function storeRating(StoreRatingRequest $request, Resource $resource): RedirectResponse
    {
        $user = Auth::user();

        $wasUpdate = $resource->ratings()->where('user_id', $user->id)->exists();

        $rating = ResourceRating::updateOrCreate(
            [
                'resource_id' => $resource->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]
        );

        ActivityLogger::log(
            $wasUpdate ? 'review.updated' : 'review.created',
            $user,
            $request->ip(),
            [
                'rating_id' => $rating->id,
                'resource_id' => $resource->id,
                'resource_name' => $resource->name,
                'rating' => $rating->rating,
                'has_comment' => ! empty($rating->comment),
            ],
            $request->userAgent()
        );

        if (! $wasUpdate) {
            $this->notifyFollowersAboutNewReview($user, $resource, $rating);
        }

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Rating saved successfully!');
    }

    /**
     * Delete a rating/review (moderator+ only).
     */
    public function deleteRating(Request $request, Resource $resource, ResourceRating $rating): RedirectResponse
    {
        // Verify rating belongs to resource
        if ($rating->resource_id !== $resource->id) {
            abort(404);
        }

        // Only moderators+ can delete reviews
        abort_unless(Auth::user()?->isModerator(), 403);

        $ratingUser = $rating->user;
        $context = [
            'rating_id' => $rating->id,
            'resource_id' => $resource->id,
            'resource_name' => $resource->name,
            'reviewer_id' => $ratingUser->id,
            'reviewer_name' => $ratingUser->name,
            'rating' => $rating->rating,
            'had_comment' => ! empty($rating->comment),
        ];

        $rating->delete();

        ActivityLogger::log(
            'review.deleted',
            Auth::user(),
            $request->ip(),
            $context,
            $request->userAgent()
        );

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Review deleted successfully.');
    }

    /**
     * Store images for resource (helper method).
     */
    private function storeImages(Resource $resource, array $images, int $startOrder = 0): void
    {
        $directory = "resources/{$resource->id}";
        $order = $startOrder;

        foreach ($images as $index => $image) {
            if (! $image->isValid()) {
                continue;
            }

            $filename = uniqid().'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs($directory, $filename, 'public');

            if ($path === false) {
                continue;
            }

            // First image added becomes display image if none exists
            $isDisplayImage = $index === 0 && $resource->displayImage === null;

            ResourceImage::create([
                'resource_id' => $resource->id,
                'path' => $path,
                'is_display_image' => $isDisplayImage,
                'order' => $order++,
            ]);
        }
    }

    /**
     * Notify followers of a resource about metadata updates.
     *
     * @param  list<string>  $changedFields
     */
    private function notifyResourceFollowersAboutUpdate(Resource $resource, array $changedFields, User $actor): void
    {
        if (empty($changedFields)) {
            return;
        }

        $followerIds = $resource->followers()
            ->where('users.id', '!=', $actor->id)
            ->pluck('users.id');

        if ($followerIds->isEmpty()) {
            return;
        }

        $fieldsList = collect($changedFields)
            ->map(fn (string $field) => Str::of($field)->replace('_', ' ')->title())
            ->implode(', ');

        $title = __(':resource was updated', ['resource' => $resource->display_name]);
        $body = __(':user updated :resource (:fields)', [
            'user' => $actor->name,
            'resource' => $resource->display_name,
            'fields' => $fieldsList,
        ]);

        $this->notifications->notify(
            $followerIds,
            NotificationCategory::Resource,
            $title,
            $body,
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->display_name,
                'updated_by' => [
                    'id' => $actor->id,
                    'name' => $actor->name,
                ],
                'changed_fields' => $changedFields,
            ],
            route('resources.show', $resource)
        );
    }

    /**
     * Notify followers of a user about a new review they wrote.
     */
    private function notifyFollowersAboutNewReview(User $reviewer, Resource $resource, ResourceRating $rating): void
    {
        $followerIds = $reviewer->followers()
            ->where('users.id', '!=', $reviewer->id)
            ->pluck('users.id');

        if ($followerIds->isEmpty()) {
            return;
        }

        $title = __(':user reviewed :resource', [
            'user' => $reviewer->name,
            'resource' => $resource->display_name,
        ]);

        $body = $rating->comment
            ? Str::limit($rating->comment, 140)
            : __('Left a :stars★ rating.', ['stars' => $rating->rating]);

        $this->notifications->notify(
            $followerIds,
            NotificationCategory::Resource,
            $title,
            $body,
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->display_name,
                'rating' => $rating->rating,
                'comment_excerpt' => $rating->comment ? Str::limit($rating->comment, 200) : null,
                'reviewer' => [
                    'id' => $reviewer->id,
                    'name' => $reviewer->name,
                ],
            ],
            route('resources.show', $resource)
        );
    }

    /**
     * Download the current/latest version of a resource.
     */
    public function download(Resource $resource): StreamedResponse|RedirectResponse
    {
        if ($resource->is_disabled) {
            return redirect()
                ->route('resources.show', $resource)
                ->withErrors(['download' => 'This resource is currently disabled.']);
        }

        $version = $resource->currentVersion;

        if (! $version || ! Storage::disk('local')->exists($version->zip_path)) {
            return redirect()
                ->route('resources.show', $resource)
                ->withErrors(['download' => 'Resource file not found.']);
        }

        // Log the download
        $this->logDownload($resource, $version->version, request());

        return Storage::disk('local')->download(
            $version->zip_path,
            $resource->name.'.zip'
        );
    }

    /**
     * Download a specific version of a resource.
     */
    public function downloadVersion(Resource $resource, string $version): StreamedResponse|RedirectResponse
    {
        if ($resource->is_disabled) {
            return redirect()
                ->route('resources.show', $resource)
                ->withErrors(['download' => 'This resource is currently disabled.']);
        }

        $resourceVersion = $resource->versions()
            ->where('version', $version)
            ->first();

        if (! $resourceVersion || ! Storage::disk('local')->exists($resourceVersion->zip_path)) {
            return redirect()
                ->route('resources.show', $resource)
                ->withErrors(['download' => 'Version file not found.']);
        }

        // Log the download
        $this->logDownload($resource, $version, request());

        return Storage::disk('local')->download(
            $resourceVersion->zip_path,
            $resource->name.'.zip'
        );
    }

    /**
     * Log a resource download. Only logs if the user/IP hasn't downloaded within the last 24 hours.
     */
    private function logDownload(Resource $resource, string $version, Request $request): void
    {
        $user = $request->user();
        $ipAddress = $request->ip();
        $twentyFourHoursAgo = now()->subDay();

        // Check if this user/IP has already downloaded in the last 24 hours
        $recentDownload = $resource->downloads()
            ->where('created_at', '>=', $twentyFourHoursAgo)
            ->where(function ($query) use ($user, $ipAddress) {
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->whereNull('user_id')
                        ->where('ip_address', $ipAddress);
                }
            })
            ->exists();

        // Only create a download record if they haven't downloaded in the last 24 hours
        if (! $recentDownload) {
            ResourceDownload::create([
                'resource_id' => $resource->id,
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'version' => $version,
            ]);
        }
    }

    /**
     * Disable a resource (moderator+)
     */
    public function disable(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorize('disable', $resource);

        $resource->update(['is_disabled' => true]);

        ActivityLogger::log(
            'resource.disabled',
            Auth::user(),
            $request->ip(),
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->name,
                'resource_owner_id' => $resource->user_id,
                'resource_owner_name' => $resource->user->name,
            ],
            $request->userAgent()
        );

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Resource has been disabled.');
    }

    /**
     * Enable a resource (moderator+)
     */
    public function enable(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorize('disable', $resource);

        $resource->update(['is_disabled' => false]);

        ActivityLogger::log(
            'resource.enabled',
            Auth::user(),
            $request->ip(),
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->name,
                'resource_owner_id' => $resource->user_id,
                'resource_owner_name' => $resource->user->name,
            ],
            $request->userAgent()
        );

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Resource has been enabled.');
    }

    /**
     * Update version verification status (moderator+)
     */
    public function updateVerification(UpdateVersionVerificationRequest $request, Resource $resource): RedirectResponse
    {
        $version = ResourceVersion::where('id', $request->input('version_id'))
            ->where('resource_id', $resource->id)
            ->firstOrFail();

        $oldStatus = $version->is_verified;
        $newStatus = $request->boolean('is_verified');

        $version->update([
            'is_verified' => $newStatus,
        ]);

        ActivityLogger::log(
            'resource.version.verification.updated',
            Auth::user(),
            $request->ip(),
            [
                'version_id' => $version->id,
                'version' => $version->version,
                'resource_id' => $resource->id,
                'resource_name' => $resource->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            $request->userAgent()
        );

        $status = $newStatus ? 'verified' : 'unverified';

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', "Version {$version->version} has been {$status}.");
    }

    /**
     * Delete a specific version of a resource.
     */
    public function destroyVersion(Request $request, Resource $resource, ResourceVersion $version): RedirectResponse
    {
        // Verify version belongs to resource
        if ($version->resource_id !== $resource->id) {
            abort(404);
        }

        $this->authorize('deleteVersion', $resource);

        $user = Auth::user();

        // Get the first version (oldest)
        $firstVersion = $resource->versions()->orderBy('created_at', 'asc')->first();

        // If deleting the first version, delete the entire resource
        // Note: This should not happen from the UI (first version delete button is hidden),
        // but if it does, we need to bypass resource_name check since it's coming from version delete
        if ($firstVersion && $firstVersion->id === $version->id) {
            // Merge resource name into request to bypass validation when called from version delete
            $request->merge(['resource_name' => $resource->name]);

            return $this->destroy($request, $resource);
        }

        // Store version info for logging before deletion
        $versionInfo = [
            'version_id' => $version->id,
            'version' => $version->version,
            'resource_id' => $resource->id,
            'resource_name' => $resource->name,
            'was_current' => $version->is_current,
            'is_owner_delete' => $resource->user_id === $user->id,
        ];

        $newCurrentVersion = null;

        DB::transaction(function () use ($resource, $version, &$newCurrentVersion) {
            // Delete the ZIP file
            if (Storage::disk('local')->exists($version->zip_path)) {
                Storage::disk('local')->delete($version->zip_path);
            }

            // If this was the current version, set the most recent remaining version as current
            if ($version->is_current) {
                $newCurrent = $resource->versions()
                    ->where('id', '!=', $version->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($newCurrent) {
                    $newCurrent->update(['is_current' => true]);
                    $newCurrentVersion = $newCurrent->version;
                }
            }

            // Delete the version
            $version->delete();

            // Touch resource updated_at
            $resource->touch();
        });

        if ($newCurrentVersion !== null) {
            $versionInfo['new_current_version'] = $newCurrentVersion;
        }

        ActivityLogger::log(
            'resource.version.deleted',
            $user,
            $request->ip(),
            $versionInfo,
            $request->userAgent()
        );

        return redirect()
            ->route('resources.edit', $resource)
            ->with('success', 'Version deleted successfully.');
    }

    /**
     * Delete a resource permanently (author or admin only)
     */
    public function destroy(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorize('delete', $resource);

        $user = Auth::user();

        // For authors (non-admins), require confirmation by typing resource name
        if ($resource->user_id === $user->id && ! $user->isAdmin()) {
            $confirmedName = $request->input('resource_name');
            if ($confirmedName !== $resource->name) {
                return redirect()
                    ->route('resources.edit', $resource)
                    ->withErrors(['resource_name' => 'Resource name does not match. Deletion cancelled.']);
            }
        }

        // Store resource info for logging before deletion
        $resourceInfo = [
            'resource_id' => $resource->id,
            'resource_name' => $resource->name,
            'resource_owner_id' => $resource->user_id,
            'resource_owner_name' => $resource->user->name,
            'category' => $resource->category,
            'version_count' => $resource->versions()->count(),
            'rating_count' => $resource->ratings()->count(),
            'download_count' => $resource->downloads()->count(),
            'is_owner_delete' => $resource->user_id === $user->id,
        ];

        DB::transaction(function () use ($resource) {
            // Delete files first
            foreach ($resource->versions as $version) {
                Storage::disk('local')->delete($version->zip_path);
            }

            foreach ($resource->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            // Delete all associated data
            $resource->versions()->delete();
            $resource->ratings()->delete();
            $resource->images()->delete();
            $resource->tags()->detach();
            $resource->downloads()->delete();
            $resource->reports()->delete();

            // Delete the resource
            $resource->delete();
        });

        ActivityLogger::log(
            'resource.deleted',
            $user,
            $request->ip(),
            $resourceInfo,
            $request->userAgent()
        );

        return redirect()
            ->route('resources.index')
            ->with('success', 'Resource has been permanently deleted.');
    }
}
