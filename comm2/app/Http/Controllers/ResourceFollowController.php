<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ResourceFollowController extends Controller
{
    public function store(Resource $resource): RedirectResponse
    {
        $user = Auth::user();

        abort_if($user->id === $resource->user_id, 403);

        $user->followedResources()->syncWithoutDetaching([$resource->id]);

        return back()->with('success', __('You will now receive notifications about :resource.', ['resource' => $resource->display_name]));
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $user = Auth::user();

        $user->followedResources()->detach($resource->id);

        return back()->with('success', __('You will no longer receive notifications about :resource.', ['resource' => $resource->display_name]));
    }
}
