<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Models\Resource;
use App\Models\Tag;
use App\Services\ResourceUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResourceUploadController extends Controller
{
    public function __construct(
        private readonly ResourceUploadService $uploadService
    ) {}

    /**
     * Show the resource upload form.
     */
    public function create(): View
    {
        $tags = Tag::orderBy('name')->get();
        $userOwnedResources = Auth::user()
            ? Resource::where('user_id', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'long_name'])
            : collect();

        return view('resources.upload', [
            'tags' => $tags,
            'userOwnedResources' => $userOwnedResources,
        ]);
    }

    /**
     * Store a newly uploaded resource.
     */
    public function store(StoreResourceRequest $request): RedirectResponse
    {
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
            $user = Auth::user();
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
                    $images = array_filter($images, fn($img) => $img && $img->isValid());
                }
            }

            $longDescription = $isFirstVersion ? $request->input('long_description') : null;
            $tagIds = $isFirstVersion ? ($request->input('tags', [])) : [];
            $githubUrl = $isFirstVersion ? $request->input('github_url') : null;
            $forumThreadUrl = $isFirstVersion ? $request->input('forum_thread_url') : null;

            // The service will handle all validation, parsing, and business logic
            // Version will be extracted from meta.xml by the service
            $resource = $this->uploadService->upload(
                user: $user,
                zipFile: $zipFile,
                version: null,
                changelog: $changelog,
                tagIds: $tagIds,
                images: $images,
                longDescription: $longDescription,
                githubUrl: $githubUrl,
                forumThreadUrl: $forumThreadUrl
            );

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
}
