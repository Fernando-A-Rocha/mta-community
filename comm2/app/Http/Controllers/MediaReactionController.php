<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\MediaReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MediaReactionController extends Controller
{
    /**
     * Check if user can react (24h limit)
     */
    private function canReact(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Check 24h reaction limit
        $reactionsToday = $user->reactions()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $reactionsToday < 10;
    }

    /**
     * Get remaining reactions for today
     */
    private function getRemainingReactions(): int
    {
        $user = Auth::user();
        if (! $user) {
            return 0;
        }

        $reactionsToday = $user->reactions()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return max(0, 10 - $reactionsToday);
    }

    /**
     * Store a newly created reaction.
     */
    public function store(Request $request, Media $media): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        // Prevent users from reacting to their own media
        if ($media->user_id === $user->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You cannot react to your own media posts.',
                ], 403);
            }

            return redirect()
                ->route('media.index')
                ->withErrors(['reaction' => 'You cannot react to your own media posts.']);
        }

        $validated = $request->validate([
            'emoji' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! MediaReaction::isValidEmoji($value)) {
                        $fail('The selected emoji is not valid.');
                    }
                },
            ],
        ]);

        // Check if user already reacted to this media
        $existingReaction = $media->userReaction($user);

        // If user already has this exact emoji reaction, remove it (toggle off)
        // This bypasses the daily limit since they're removing, not adding
        if ($existingReaction && $existingReaction->emoji === $validated['emoji']) {
            return DB::transaction(function () use ($media, $existingReaction) {
                $existingReaction->delete();

                // Refresh media to get updated reaction counts
                $media->refresh();
                $media->load('reactions.user');

                // Group reactions by emoji with user names
                $reactionUsersByEmoji = [];
                foreach ($media->reactions as $reaction) {
                    if (! isset($reactionUsersByEmoji[$reaction->emoji])) {
                        $reactionUsersByEmoji[$reaction->emoji] = [];
                    }
                    $reactionUsersByEmoji[$reaction->emoji][] = $reaction->user->name;
                }

                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Reaction removed successfully.',
                        'removed' => true,
                        'remaining_reactions' => $this->getRemainingReactions(),
                        'reaction_counts' => $media->reaction_counts,
                        'user_reaction' => null,
                        'reaction_users' => $reactionUsersByEmoji,
                    ]);
                }

                return redirect()
                    ->route('media.index')
                    ->with('success', __('Reaction removed successfully.'));
            });
        }

        // Check 24h reaction limit only when adding/updating a reaction
        if (! $this->canReact()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You have reached the daily reaction limit of 10 reactions. Please try again tomorrow.',
                ], 429);
            }

            return redirect()
                ->route('media.index')
                ->withErrors(['reaction' => 'You have reached the daily reaction limit of 10 reactions. Please try again tomorrow.']);
        }

        return DB::transaction(function () use ($media, $user, $validated, $existingReaction) {

            // If user has a different emoji reaction, update it
            if ($existingReaction) {
                $existingReaction->update(['emoji' => $validated['emoji']]);
                $userReaction = $existingReaction;
            } else {
                // Create new reaction
                $userReaction = MediaReaction::create([
                    'media_id' => $media->id,
                    'user_id' => $user->id,
                    'emoji' => $validated['emoji'],
                ]);
            }

            // Refresh media to get updated reaction counts
            $media->refresh();
            $media->load('reactions.user');

            // Group reactions by emoji with user names
            $reactionUsersByEmoji = [];
            foreach ($media->reactions as $reaction) {
                if (! isset($reactionUsersByEmoji[$reaction->emoji])) {
                    $reactionUsersByEmoji[$reaction->emoji] = [];
                }
                $reactionUsersByEmoji[$reaction->emoji][] = $reaction->user->name;
            }

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Reaction added successfully.',
                    'removed' => false,
                    'reaction' => $userReaction,
                    'remaining_reactions' => $this->getRemainingReactions(),
                    'reaction_counts' => $media->reaction_counts,
                    'user_reaction' => [
                        'emoji' => $userReaction->emoji,
                    ],
                    'reaction_users' => $reactionUsersByEmoji,
                ]);
            }

            return redirect()
                ->route('media.index')
                ->with('success', __('Reaction added successfully.'));
        });
    }

    /**
     * Remove the specified reaction.
     */
    public function destroy(Request $request, Media $media, MediaReaction $reaction): JsonResponse|RedirectResponse
    {
        // Verify reaction belongs to media
        if ($reaction->media_id !== $media->id) {
            abort(404);
        }

        // Only user can delete their own reaction
        if ($reaction->user_id !== Auth::id()) {
            abort(403);
        }

        $reaction->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Reaction removed successfully.',
                'remaining_reactions' => $this->getRemainingReactions(),
            ]);
        }

        return redirect()
            ->route('media.index')
            ->with('success', __('Reaction removed successfully.'));
    }

    /**
     * Get all reactions for a media with user information.
     */
    public function index(Request $request, Media $media): JsonResponse
    {
        $reactions = $media->reactions()
            ->with('user:id,name,profile_visibility')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group reactions by emoji
        $groupedReactions = $reactions->groupBy('emoji')->map(function ($reactionGroup) {
            return $reactionGroup->map(function ($reaction) {
                $profileVisibility = $reaction->user->profile_visibility ?? 'public';
                $isPublic = $profileVisibility === 'public';

                return [
                    'id' => $reaction->id,
                    'user_id' => $reaction->user_id,
                    'user_name' => $reaction->user->name,
                    'profile_is_public' => $isPublic,
                    'created_at' => $reaction->created_at->toIso8601String(),
                ];
            })->values();
        });

        return response()->json([
            'reactions' => $groupedReactions,
        ]);
    }
}
