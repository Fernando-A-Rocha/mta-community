<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationCategory;
use App\Http\Requests\StoreResourceRequest;
use App\Models\Resource;
use App\Models\ResourceVersion;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\ResourceUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResourceUploadController extends Controller
{
    public function __construct(
        private readonly ResourceUploadService $uploadService,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Show the resource upload form.
     */
    public function create(): View
    {
        $user = Auth::user();
        $canUpload = ($user->profile_visibility ?? 'public') === 'public';

        $tags = Tag::orderBy('name')->get();
        $languages = \App\Models\Language::orderBy('order')->get();
        $userOwnedResources = Auth::user()
            ? Resource::where('user_id', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'long_name'])
            : collect();

        return view('resources.upload', [
            'tags' => $tags,
            'languages' => $languages,
            'userOwnedResources' => $userOwnedResources,
            'canUpload' => $canUpload,
        ]);
    }

    /**
     * Store a newly uploaded resource.
     */
    public function store(StoreResourceRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if (($user->profile_visibility ?? 'public') !== 'public') {
            return redirect()
                ->route('resources.upload.create')
                ->withErrors(['profile_visibility' => __('Set your profile visibility to public before publishing resources.')]);
        }

        $zipFile = $request->file('zip_file');

        if (! $zipFile || ! $zipFile->isValid()) {
            return redirect()->back()
                ->withErrors(['zip_file' => 'Invalid file upload. Please try again.'])
                ->withInput();
        }

        // Check upload mode from request
        $isFirstVersion = $request->input('upload_mode') === 'first_version';

        // Quick check for existing resource to determine if this is an update
        // This allows us to show better validation messages before processing
        $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $existingResource = Resource::where('name', $zipFileName)->first();
        $isUpdate = $existingResource && $existingResource->user_id === Auth::id();

        // Validate required fields based on upload mode
        if ($isFirstVersion) {
            // First version mode: require long_description, tags, images are optional
            // Cannot be first version if resource already exists
            if ($existingResource) {
                return redirect()->back()
                    ->withErrors(['upload_mode' => 'A resource with this name already exists. Please select "New release of existing resource" to update it.'])
                    ->withInput();
            }

            if (empty($request->input('long_description'))) {
                return redirect()->back()
                    ->withErrors(['long_description' => 'Long description is required for first-time uploads.'])
                    ->withInput();
            }
        } else {
            // New release mode: require changelog, and must be updating an existing resource
            if (empty($request->input('changelog'))) {
                return redirect()->back()
                    ->withErrors(['changelog' => 'Changelog is required for resource updates.'])
                    ->withInput();
            }

            if (! $isUpdate) {
                return redirect()->back()
                    ->withErrors(['changelog' => 'You can only upload a new release for a resource you own. Please select "First version of the resource" for new resources.'])
                    ->withInput();
            }
        }

        try {
            $changelog = $isFirstVersion ? 'First public release' : $request->input('changelog');

            // Get images - handle both single and multiple file inputs
            // When using name="images[]" with multiple, Laravel returns an array
            $images = [];
            if ($isFirstVersion) {
                $uploadedImages = $request->file('images');
                if ($uploadedImages) {
                    // Ensure it's an array (Laravel returns array for multiple file inputs)
                    $images = is_array($uploadedImages) ? $uploadedImages : [$uploadedImages];
                    // Filter out any null/invalid entries
                    $images = array_filter($images, fn ($img) => $img && $img->isValid());
                }
            }

            $longDescription = $isFirstVersion ? $request->input('long_description') : null;
            $tagIds = $isFirstVersion ? ($request->input('tags', [])) : [];
            $languageIds = $isFirstVersion ? ($request->input('languages', [])) : [];
            $githubUrl = $isFirstVersion ? $request->input('github_url') : null;
            $forumThreadUrl = $isFirstVersion ? $request->input('forum_thread_url') : null;

            // Check if resource exists before upload to determine if it's a new resource or update
            $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
            $resourceExistedBefore = Resource::where('name', $zipFileName)
                ->where('user_id', $user->id)
                ->exists();

            // The service will handle all validation, parsing, and business logic
            // Version will be extracted from meta.xml by the service
            $resource = $this->uploadService->upload(
                user: $user,
                zipFile: $zipFile,
                version: null,
                changelog: $changelog,
                tagIds: $tagIds,
                languageIds: $languageIds,
                images: $images,
                longDescription: $longDescription,
                githubUrl: $githubUrl,
                forumThreadUrl: $forumThreadUrl
            );

            // Reload to get the latest version info
            $resource->refresh();
            $currentVersion = $resource->currentVersion;

            // Log the action
            if ($resourceExistedBefore) {
                // New version uploaded
                ActivityLogger::log(
                    'resource.version.uploaded',
                    $user,
                    $request->ip(),
                    [
                        'resource_id' => $resource->id,
                        'resource_name' => $resource->name,
                        'version' => $currentVersion?->version,
                        'version_id' => $currentVersion?->id,
                        'changelog' => $changelog,
                    ],
                    $request->userAgent()
                );

                $this->notifyResourceFollowersAboutNewRelease($resource, $currentVersion, $changelog);
                $this->notifyUserFollowersAboutResourcePublication($user, $resource, $currentVersion, $changelog, true);
            } else {
                // New resource created
                ActivityLogger::log(
                    'resource.created',
                    $user,
                    $request->ip(),
                    [
                        'resource_id' => $resource->id,
                        'resource_name' => $resource->name,
                        'long_name' => $resource->long_name,
                        'category' => $resource->category,
                        'version' => $currentVersion?->version,
                        'version_id' => $currentVersion?->id,
                        'tag_count' => count($tagIds),
                        'language_count' => count($languageIds),
                        'image_count' => count($images),
                        'has_github_url' => ! empty($githubUrl),
                        'has_forum_url' => ! empty($forumThreadUrl),
                    ],
                    $request->userAgent()
                );

                $this->notifyUserFollowersAboutResourcePublication($user, $resource, $currentVersion, $changelog, false);
            }

            return redirect()
                ->route('resources.show', $resource)
                ->with('success', 'Resource uploaded successfully!');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['zip_file' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Resource upload error: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['submit' => 'An error occurred: '.$e->getMessage()])
                ->withInput();
        }
    }

    private function notifyResourceFollowersAboutNewRelease(Resource $resource, ?ResourceVersion $version, string $changelog): void
    {
        $followers = $resource->followers()
            ->where('users.id', '!=', $resource->user_id)
            ->pluck('users.id');

        if ($followers->isEmpty()) {
            return;
        }

        $versionLabel = $version?->version ?? null;
        $title = __('New release for :resource', ['resource' => $resource->display_name]);
        $body = $versionLabel
            ? __('Version :version is now available.', ['version' => $versionLabel])
            : __('A new release is now available.');

        $this->notifications->notify(
            $followers,
            NotificationCategory::Resource,
            $title,
            $body.' '.Str::limit($changelog, 140),
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->display_name,
                'version' => $versionLabel,
                'changelog_excerpt' => Str::limit($changelog, 200),
                'uploaded_by' => [
                    'id' => $resource->user_id,
                    'name' => $resource->user->name,
                ],
            ],
            route('resources.show', $resource)
        );
    }

    private function notifyUserFollowersAboutResourcePublication(
        User $author,
        Resource $resource,
        ?ResourceVersion $version,
        string $changelog,
        bool $isUpdate
    ): void {
        $followers = $author->followers()
            ->where('users.id', '!=', $author->id)
            ->pluck('users.id');

        if ($isUpdate) {
            $resourceFollowerIds = $resource->followers()->pluck('users.id');
            $followers = $followers->diff($resourceFollowerIds);
        }

        if ($followers->isEmpty()) {
            return;
        }

        $versionLabel = $version?->version;
        $title = $isUpdate
            ? __(':user shipped a new release for :resource', [
                'user' => $author->name,
                'resource' => $resource->display_name,
            ])
            : __(':user published a new resource', ['user' => $author->name]);

        $body = $isUpdate
            ? __('Version :version just dropped.', ['version' => $versionLabel ?? __('latest')])
            : __('Check out :resource on the Community.', ['resource' => $resource->display_name]);

        $this->notifications->notify(
            $followers,
            NotificationCategory::Resource,
            $title,
            $body.' '.Str::limit($changelog, 140),
            [
                'resource_id' => $resource->id,
                'resource_name' => $resource->display_name,
                'version' => $versionLabel,
                'is_update' => $isUpdate,
                'changelog_excerpt' => Str::limit($changelog, 200),
            ],
            route('resources.show', $resource)
        );
    }
}
