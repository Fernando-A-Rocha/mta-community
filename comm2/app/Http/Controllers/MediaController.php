<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\ActivityLogger;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MediaUploadService $uploadService
    ) {}

    /**
     * Display a listing of media.
     */
    public function index(Request $request): View
    {
        $query = Media::with(['user', 'images', 'reactions.user'])
            ->withCount('reactions');

        // Search by description and author username
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'recent');
        $sortOrder = $request->input('sort_order', 'desc');

        switch ($sortBy) {
            case 'ratings':
                $query->withCount('reactions')
                    ->orderBy('reactions_count', $sortOrder)
                    ->orderBy('created_at', 'desc'); // Secondary sort
                break;
            case 'date':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $media = $query->paginate(24)->withQueryString();

        return view('media.index', compact('media', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new media.
     */
    public function create(): View
    {
        $user = Auth::user();

        // Check if user has public profile
        $canUpload = ($user->profile_visibility ?? 'public') === 'public';

        // Check 24h upload limit
        $canUploadByTime = $this->uploadService->canUpload($user);
        $timeUntilNextUpload = $this->uploadService->getTimeUntilNextUpload($user);

        return view('media.upload', [
            'user' => $user,
            'canUpload' => $canUpload && $canUploadByTime,
            'canUploadByTime' => $canUploadByTime,
            'timeUntilNextUpload' => $timeUntilNextUpload,
        ]);
    }

    /**
     * Store a newly created media.
     */
    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $user = Auth::user();

        // Check profile visibility
        if (($user->profile_visibility ?? 'public') !== 'public') {
            return redirect()
                ->route('media.upload')
                ->withErrors(['profile_visibility' => __('Set your profile visibility to public before uploading media.')]);
        }

        // Check 24h upload limit (also checked in service, but show error here)
        if (! $this->uploadService->canUpload($user)) {
            $timeUntilNextUpload = $this->uploadService->getTimeUntilNextUpload($user);
            $message = __('You can only upload media once per 24 hours.');
            if ($timeUntilNextUpload) {
                $message .= ' '.__('You can upload again at :time.', ['time' => $timeUntilNextUpload->format('Y-m-d H:i:s')]);
            }

            return redirect()
                ->route('media.upload')
                ->withErrors(['upload' => $message]);
        }

        try {
            $images = $request->hasFile('images') ? $request->file('images') : null;

            $media = $this->uploadService->upload(
                user: $user,
                type: $request->input('type'),
                images: $images,
                youtubeUrl: $request->input('youtube_url'),
                description: $request->input('description')
            );

            ActivityLogger::log(
                'media.created',
                $user,
                $request->ip(),
                [
                    'media_id' => $media->id,
                    'type' => $media->type,
                    'image_count' => $media->type === 'image' ? $media->images()->count() : 0,
                ],
                $request->userAgent()
            );

            return redirect()
                ->route('media.index')
                ->with('success', __('Media uploaded successfully!'));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('media.upload')
                ->withErrors(['upload' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Request $request, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $mediaId = $media->id;
        $mediaType = $media->type;

        // Delete associated images and their files
        if ($media->type === 'image') {
            foreach ($media->images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
                // Permanently delete the image record
                $image->delete();
            }
        }

        // Permanently delete reactions
        $media->reactions()->delete();

        // Permanently delete media record
        $media->delete();

        ActivityLogger::log(
            'media.deleted',
            Auth::user(),
            $request->ip(),
            [
                'media_id' => $mediaId,
                'type' => $mediaType,
            ],
            $request->userAgent()
        );

        return redirect()
            ->route('media.index')
            ->with('success', __('Media deleted successfully.'));
    }
}
