<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationCategory;
use App\Http\Requests\StoreNewResourceRequest;
use App\Http\Requests\StoreNewVersionRequest;
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
     * Show the resource upload selection page.
     */
    public function create(): View
    {
        $user = Auth::user();
        $canUpload = ($user->profile_visibility ?? 'public') === 'public';

        return view('resources.upload', [
            'canUpload' => $canUpload,
        ]);
    }

    /**
     * Show the form for uploading a new resource.
     */
    public function createNew(): View
    {
        $user = Auth::user();
        $canUpload = ($user->profile_visibility ?? 'public') === 'public';

        $tags = Tag::orderBy('name')->get();
        $languages = \App\Models\Language::orderBy('name')->get();

        return view('resources.upload-new', [
            'tags' => $tags,
            'languages' => $languages,
            'canUpload' => $canUpload,
        ]);
    }

    /**
     * Show the form for uploading a new version.
     */
    public function createVersion(): View
    {
        $user = Auth::user();
        $canUpload = ($user->profile_visibility ?? 'public') === 'public';

        return view('resources.upload-version', [
            'canUpload' => $canUpload,
        ]);
    }

    /**
     * Store a newly uploaded resource.
     */
    public function storeNew(StoreNewResourceRequest $request): RedirectResponse
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

        // Check if resource already exists
        $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $existingResource = Resource::where('name', $zipFileName)->first();

        if ($existingResource) {
            return redirect()->back()
                ->withErrors(['zip_file' => 'A resource with this name already exists. Please use "Upload New Version" to update it.'])
                ->withInput();
        }

        try {
            // Get images
            $images = [];
            $uploadedImages = $request->file('images');
            if ($uploadedImages) {
                $images = is_array($uploadedImages) ? $uploadedImages : [$uploadedImages];
                $images = array_filter($images, fn ($img) => $img && $img->isValid());
            }

            $resource = $this->uploadService->upload(
                user: $user,
                zipFile: $zipFile,
                version: null,
                changelog: 'First public release',
                tagIds: $request->input('tags', []),
                languageIds: $request->input('languages', []),
                images: $images,
                longDescription: $request->input('long_description'),
                githubUrl: $request->input('github_url'),
                forumThreadUrl: $request->input('forum_thread_url')
            );

            $resource->refresh();
            $currentVersion = $resource->currentVersion;

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
                    'tag_count' => count($request->input('tags', [])),
                    'language_count' => count($request->input('languages', [])),
                    'image_count' => count($images),
                    'has_github_url' => ! empty($request->input('github_url')),
                    'has_forum_url' => ! empty($request->input('forum_thread_url')),
                ],
                $request->userAgent()
            );

            $this->notifyUserFollowersAboutResourcePublication($user, $resource, $currentVersion, 'First public release', false);

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

    /**
     * Store a new version of an existing resource.
     */
    public function storeVersion(StoreNewVersionRequest $request): RedirectResponse
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

        // Check if resource exists and user owns it
        $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $existingResource = Resource::where('name', $zipFileName)
            ->where('user_id', $user->id)
            ->first();

        if (! $existingResource) {
            return redirect()->back()
                ->withErrors(['zip_file' => 'No resource found with this name that you own. Please ensure the ZIP filename matches an existing resource you own.'])
                ->withInput();
        }

        try {
            $resource = $this->uploadService->upload(
                user: $user,
                zipFile: $zipFile,
                version: null,
                changelog: $request->input('changelog'),
                tagIds: [],
                languageIds: [],
                images: [],
                longDescription: null,
                githubUrl: null,
                forumThreadUrl: null
            );

            $resource->refresh();
            $currentVersion = $resource->currentVersion;

            ActivityLogger::log(
                'resource.version.uploaded',
                $user,
                $request->ip(),
                [
                    'resource_id' => $resource->id,
                    'resource_name' => $resource->name,
                    'version' => $currentVersion?->version,
                    'version_id' => $currentVersion?->id,
                    'changelog' => $request->input('changelog'),
                ],
                $request->userAgent()
            );

            $this->notifyResourceFollowersAboutNewRelease($resource, $currentVersion, $request->input('changelog'));
            $this->notifyUserFollowersAboutResourcePublication($user, $resource, $currentVersion, $request->input('changelog'), true);

            return redirect()
                ->route('resources.show', $resource)
                ->with('success', 'New version uploaded successfully!');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['zip_file' => $e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Resource version upload error: '.$e->getMessage(), [
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
