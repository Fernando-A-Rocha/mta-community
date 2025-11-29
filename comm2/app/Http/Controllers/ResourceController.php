<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use App\Models\ResourceDownload;
use App\Models\ResourceImage;
use App\Models\ResourceRating;
use App\Models\ResourceVersion;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of resources.
     */
    public function index(Request $request): View
    {
        $query = Resource::with(['user', 'tags', 'displayImage', 'currentVersion'])
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

        return view('resources.show', compact('resource', 'userRating'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource): View
    {
        $this->authorize('update', $resource);

        $tags = Tag::orderBy('name')->get();
        $resource->load([
            'tags',
            'images',
            'versions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        return view('resources.edit', compact('resource', 'tags'));
    }

    /**
     * Update the specified resource.
     */
    public function update(UpdateResourceRequest $request, Resource $resource): RedirectResponse
    {
        $this->authorize('update', $resource);

        DB::transaction(function () use ($request, $resource) {
            // Update basic fields
            $resource->update([
                'short_description' => $request->input('short_description'),
                'long_description' => $request->input('long_description'),
                'github_url' => $request->input('github_url'),
                'forum_thread_url' => $request->input('forum_thread_url'),
            ]);

            // Update tags
            if ($request->has('tags')) {
                $resource->tags()->sync($request->input('tags', []));
            }

            // Remove images
            if ($request->has('remove_images')) {
                $imagesToRemove = ResourceImage::whereIn('id', $request->input('remove_images', []))
                    ->where('resource_id', $resource->id)
                    ->get();

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
                    }
                }
            }

            // Touch updated_at
            $resource->touch();
        });

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

        ResourceRating::updateOrCreate(
            [
                'resource_id' => $resource->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]
        );

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Rating saved successfully!');
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

        return redirect()
            ->route('resources.show', $resource)
            ->with('success', 'Resource has been enabled.');
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

        DB::transaction(function () use ($resource, $version) {
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
                }
            }

            // Delete the version
            $version->delete();

            // Touch resource updated_at
            $resource->touch();
        });

        return redirect()
            ->route('resources.edit', $resource)
            ->with('success', 'Version deleted successfully.');
    }

    /**
     * Delete a resource permanently (author or admin+)
     */
    public function destroy(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorize('delete', $resource);

        // For authors, require confirmation by typing resource name
        if ($resource->user_id === Auth::id() && ! Auth::user()->isModerator()) {
            $confirmedName = $request->input('resource_name');
            if ($confirmedName !== $resource->name) {
                return redirect()
                    ->route('resources.edit', $resource)
                    ->withErrors(['resource_name' => 'Resource name does not match. Deletion cancelled.']);
            }
        }

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

            // Delete the resource
            $resource->delete();
        });

        return redirect()
            ->route('resources.index')
            ->with('success', 'Resource has been permanently deleted.');
    }
}
