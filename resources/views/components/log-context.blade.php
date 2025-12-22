@props([
    'action',
    'context',
])

@php
    use Illuminate\Support\Str;
    
    // Helper function to format boolean values
    $formatBool = fn($value) => $value ? __('Yes') : __('No');
    
    // Helper function to format status values
    $formatStatus = function($value) {
        if (is_bool($value)) {
            return $value ? __('Verified') : __('Not Verified');
        }
        return ucfirst(str_replace('_', ' ', $value));
    };
    
    // Helper function to create resource link
    $resourceLink = function($resourceId, $resourceName) {
        return '<a href="' . route('resources.show', $resourceId) . '" class="text-orange-500 hover:underline dark:text-orange-300">' . e($resourceName) . '</a>';
    };
    
    // Helper function to create user link
    $userLink = function($userId, $userName) {
        return '<a href="' . route('profile.show', $userId) . '" class="text-orange-500 hover:underline dark:text-orange-300">' . e($userName) . '</a>';
    };
@endphp

<div class="space-y-3">
    @if (str_starts_with($action, 'resource.enabled') || str_starts_with($action, 'resource.disabled'))
        <div class="grid gap-2 text-sm">
            <div>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                {!! $resourceLink($context['resource_id'] ?? '', $context['resource_name'] ?? __('Unknown')) !!}
                <span class="text-slate-500 dark:text-slate-400">(ID: {{ $context['resource_id'] ?? 'N/A' }})</span>
            </div>
            @if (isset($context['resource_owner_id']) && isset($context['resource_owner_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Owner') }}:</span>
                    {!! $userLink($context['resource_owner_id'], $context['resource_owner_name']) !!}
                </div>
            @endif
        </div>

    @elseif ($action === 'resource.created')
        <div class="grid gap-2 text-sm">
            <div>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                {!! $resourceLink($context['resource_id'] ?? '', $context['resource_name'] ?? __('Unknown')) !!}
            </div>
            @if (isset($context['long_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Long Name') }}:</span>
                    <span>{{ $context['long_name'] }}</span>
                </div>
            @endif
            @if (isset($context['category']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Category') }}:</span>
                    <span>{{ ucfirst($context['category']) }}</span>
                </div>
            @endif
            @if (isset($context['version']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span>
                    <span>{{ $context['version'] }}</span>
                </div>
            @endif
            <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                @if (isset($context['tag_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Tags') }}:</span>
                        <span>{{ $context['tag_count'] }}</span>
                    </div>
                @endif
                @if (isset($context['language_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Languages') }}:</span>
                        <span>{{ $context['language_count'] }}</span>
                    </div>
                @endif
                @if (isset($context['image_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Images') }}:</span>
                        <span>{{ $context['image_count'] }}</span>
                    </div>
                @endif
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Links') }}:</span>
                    <span>
                        @if (($context['has_github_url'] ?? false) && ($context['has_forum_url'] ?? false))
                            {{ __('GitHub & Forum') }}
                        @elseif ($context['has_github_url'] ?? false)
                            {{ __('GitHub') }}
                        @elseif ($context['has_forum_url'] ?? false)
                            {{ __('Forum') }}
                        @else
                            {{ __('None') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

    @elseif ($action === 'resource.updated')
        <div class="grid gap-2 text-sm">
            <div>
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                {!! $resourceLink($context['resource_id'] ?? '', $context['resource_name'] ?? __('Unknown')) !!}
            </div>
            @if (isset($context['is_moderator_edit']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Edit Type') }}:</span>
                    <span>{{ $context['is_moderator_edit'] ? __('Moderator Edit') : __('Owner Edit') }}</span>
                </div>
            @endif
            @if (isset($context['changes']) && is_array($context['changes']) && count($context['changes']) > 0)
                <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Changes') }}:</span>
                    <ul class="mt-1 space-y-1 list-disc list-inside text-slate-600 dark:text-slate-300">
                        @foreach ($context['changes'] as $field => $change)
                            <li>
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                @if (is_array($change))
                                    @if (isset($change['old']) && isset($change['new']))
                                        <span class="text-red-600 dark:text-red-400">{{ is_array($change['old']) ? json_encode($change['old']) : $change['old'] }}</span>
                                        →
                                        <span class="text-green-600 dark:text-green-400">{{ is_array($change['new']) ? json_encode($change['new']) : $change['new'] }}</span>
                                    @else
                                        {{ json_encode($change) }}
                                    @endif
                                @else
                                    {{ $change }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

    @elseif ($action === 'resource.deleted')
        <div class="grid gap-2 text-sm">
            @if (isset($context['resource_id']) && isset($context['resource_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                    <span>{{ $context['resource_name'] }}</span>
                    <span class="text-slate-500 dark:text-slate-400">(ID: {{ $context['resource_id'] }})</span>
                </div>
            @endif
            @if (isset($context['resource_owner_id']) && isset($context['resource_owner_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Owner') }}:</span>
                    {!! $userLink($context['resource_owner_id'], $context['resource_owner_name']) !!}
                </div>
            @endif
            @if (isset($context['category']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Category') }}:</span>
                    <span>{{ ucfirst($context['category']) }}</span>
                </div>
            @endif
            <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                @if (isset($context['version_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Versions') }}:</span>
                        <span>{{ $context['version_count'] }}</span>
                    </div>
                @endif
                @if (isset($context['rating_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Ratings') }}:</span>
                        <span>{{ $context['rating_count'] }}</span>
                    </div>
                @endif
                @if (isset($context['download_count']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Downloads') }}:</span>
                        <span>{{ $context['download_count'] }}</span>
                    </div>
                @endif
                @if (isset($context['is_owner_delete']))
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted By Owner') }}:</span>
                        <span>{{ $formatBool($context['is_owner_delete']) }}</span>
                    </div>
                @endif
            </div>
        </div>

    @elseif ($action === 'resource.version.deleted')
        <div class="grid gap-2 text-sm">
            @if (isset($context['resource_id']) && isset($context['resource_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                    {!! $resourceLink($context['resource_id'], $context['resource_name']) !!}
                </div>
            @endif
            @if (isset($context['version']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span>
                    <span>{{ $context['version'] }}</span>
                </div>
            @endif
            @if (isset($context['was_current']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Was Current Version') }}:</span>
                    <span>{{ $formatBool($context['was_current']) }}</span>
                </div>
            @endif
            @if (isset($context['new_current_version']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('New Current Version') }}:</span>
                    <span>{{ $context['new_current_version'] }}</span>
                </div>
            @endif
            @if (isset($context['is_owner_delete']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted By Owner') }}:</span>
                    <span>{{ $formatBool($context['is_owner_delete']) }}</span>
                </div>
            @endif
        </div>

    @elseif ($action === 'resource.version.verification.updated')
        <div class="grid gap-2 text-sm">
            @if (isset($context['resource_id']) && isset($context['resource_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                    {!! $resourceLink($context['resource_id'], $context['resource_name']) !!}
                </div>
            @endif
            @if (isset($context['version']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Version') }}:</span>
                    <span>{{ $context['version'] }}</span>
                </div>
            @endif
            @if (isset($context['old_status']) && isset($context['new_status']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Verification Status') }}:</span>
                    <span class="text-red-600 dark:text-red-400">{{ $formatStatus($context['old_status']) }}</span>
                    →
                    <span class="text-green-600 dark:text-green-400">{{ $formatStatus($context['new_status']) }}</span>
                </div>
            @endif
        </div>

    @elseif (str_starts_with($action, 'review.'))
        <div class="grid gap-2 text-sm">
            @if (isset($context['resource_id']) && isset($context['resource_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource') }}:</span>
                    {!! $resourceLink($context['resource_id'], $context['resource_name']) !!}
                </div>
            @endif
            @if (isset($context['rating']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Rating') }}:</span>
                    <span>{{ $context['rating'] }}/5</span>
                </div>
            @endif
            @if (isset($context['has_comment']) || isset($context['had_comment']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Has Comment') }}:</span>
                    <span>{{ $formatBool($context['has_comment'] ?? $context['had_comment'] ?? false) }}</span>
                </div>
            @endif
            @if (isset($context['reviewer_id']) && isset($context['reviewer_name']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Reviewer') }}:</span>
                    {!! $userLink($context['reviewer_id'], $context['reviewer_name']) !!}
                </div>
            @endif
        </div>

    @elseif (str_starts_with($action, 'report.'))
        <div class="grid gap-2 text-sm">
            @if (isset($context['report_id']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Report ID') }}:</span>
                    <span>{{ $context['report_id'] }}</span>
                </div>
            @endif
            @if (isset($context['reportable_type']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Report Type') }}:</span>
                    <span>{{ $context['reportable_type'] === 'resource' ? __('Resource') : __('User') }}</span>
                </div>
            @endif
            @if (isset($context['resource_id']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Resource ID') }}:</span>
                    <span>{{ $context['resource_id'] }}</span>
                </div>
            @endif
            @if (isset($context['user_id']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('User ID') }}:</span>
                    <span>{{ $context['user_id'] }}</span>
                </div>
            @endif
            @if (isset($context['reason']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Reason') }}:</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $context['reason'])) }}</span>
                </div>
            @endif
            @if (isset($context['status']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Status') }}:</span>
                    <span>{{ ucfirst($context['status']) }}</span>
                </div>
            @endif
            @if (isset($context['deleted']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Deleted Count') }}:</span>
                    <span>{{ $context['deleted'] }}</span>
                </div>
            @endif
            @if (isset($context['threshold']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Threshold') }}:</span>
                    <span>{{ \Carbon\Carbon::parse($context['threshold'])->format('Y-m-d H:i:s') }}</span>
                </div>
            @endif
        </div>

    @elseif (str_starts_with($action, 'media.'))
        <div class="grid gap-2 text-sm">
            @if (isset($context['media_id']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Media ID') }}:</span>
                    <span>{{ $context['media_id'] }}</span>
                </div>
            @endif
            @if (isset($context['type']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Type') }}:</span>
                    <span>{{ ucfirst($context['type']) }}</span>
                </div>
            @endif
            @if (isset($context['image_count']))
                <div>
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Image Count') }}:</span>
                    <span>{{ $context['image_count'] }}</span>
                </div>
            @endif
        </div>

    @else
        {{-- Fallback: Show formatted JSON for unknown log types --}}
        <div class="rounded-2xl bg-slate-900/5 p-4 text-xs font-mono text-slate-800 dark:bg-slate-800/60 dark:text-slate-100">
            <pre class="whitespace-pre-wrap">{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>

