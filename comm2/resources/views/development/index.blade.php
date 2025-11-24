<x-layouts.app :title="__('Development')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section>
            <flux:heading size="lg" class="mb-4">{{ __('Development Activity') }}</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 mb-6">
                {{ __('Current stable MTA version') }}: <strong>{{ config('mta.current_stable_version') }}</strong>
                <a href="https://multitheftauto.com/download" target="_blank" rel="noopener noreferrer" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline">
                    {{ __('Download') }}
                </a>
            </flux:text>

            <div class="mb-6 flex flex-wrap gap-3">
                <a href="https://forum.multitheftauto.com/forum/107-open-source-contributors/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Open Source Contributors (Forum)') }}
                </a>
                <a href="https://buildinfo.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Build Info') }}
                </a>
                <a href="https://nightly.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Nightly Builds') }}
                </a>
                <a href="https://nightly.multitheftauto.com/ver/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Version Info') }}
                </a>
                <a href="https://linux.multitheftauto.com/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm transition-colors hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700">
                    {{ __('Linux Server Packages') }}
                </a>
            </div>
            <flux:text class="text-neutral-600 dark:text-neutral-400 mb-6">
                {{ __('Recent activity from Multi Theft Auto development repositories on GitHub:') }}
            </flux:text>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Multi Theft Auto Codebase Section -->
                <div class="rounded-lg border border-neutral-300 bg-white p-4 dark:border-neutral-600 dark:bg-neutral-800">
                    <div class="mb-3">
                        <flux:heading size="md" class="mb-2">{{ __('Multi Theft Auto Codebase') }}</flux:heading>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <a href="https://github.com/multitheftauto" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                multitheftauto
                            </a>
                            <span class="mx-1">/</span>
                            <a href="https://github.com/multitheftauto/mtasa-blue" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                mtasa-blue
                            </a>
                        </div>
                    </div>

                    @if($mtasaBlueActivity->count() > 0)
                        <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach($mtasaBlueActivity as $activity)
                                <div class="py-2 first:pt-0">
                                    <div class="flex items-start gap-2">
                                        <div class="shrink-0">
                                            @php
                                                $badgeColors = [
                                                    'commit' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'issue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'pull_request' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                    'release' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                ];
                                                $badgeColor = $badgeColors[$activity['type']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
                                                $badgeLabel = match($activity['type']) {
                                                    'commit' => 'Commit',
                                                    'issue' => 'Issue',
                                                    'pull_request' => 'PR',
                                                    'release' => 'Release',
                                                    default => ucfirst($activity['type']),
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium leading-tight {{ $badgeColor }}">
                                                {{ $badgeLabel }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                                <a href="{{ $activity['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                                    {{ $activity['title'] }}
                                                </a>
                                            </h3>
                                            <div class="flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-500">
                                                <span>{{ __('By') }}: {{ $activity['author'] }}</span>
                                                <span>{{ $activity['date']->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if ($mtasaBlueActivity->hasPages())
                            <div class="mt-3 pt-3">
                                {{ $mtasaBlueActivity->links() }}
                            </div>
                        @endif
                    @else
                        <div class="py-8 text-center text-neutral-600 dark:text-neutral-400">
                            <p>{{ __('No activity available at the moment.') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Official MTA Resources Section -->
                <div class="rounded-lg border border-neutral-300 bg-white p-4 dark:border-neutral-600 dark:bg-neutral-800">
                    <div class="mb-3">
                        <flux:heading size="md" class="mb-2">{{ __('Official MTA Resources') }}</flux:heading>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <a href="https://github.com/multitheftauto" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                multitheftauto
                            </a>
                            <span class="mx-1">/</span>
                            <a href="https://github.com/multitheftauto/mtasa-resources" target="_blank" rel="noopener noreferrer" class="hover:underline font-medium">
                                mtasa-resources
                            </a>
                        </div>
                    </div>

                    @if($mtasaResourcesActivity->count() > 0)
                        <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach($mtasaResourcesActivity as $activity)
                                <div class="py-2 first:pt-0">
                                    <div class="flex items-start gap-2">
                                        <div class="shrink-0">
                                            @php
                                                $badgeColors = [
                                                    'commit' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'issue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'pull_request' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                    'release' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                ];
                                                $badgeColor = $badgeColors[$activity['type']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
                                                $badgeLabel = match($activity['type']) {
                                                    'commit' => 'Commit',
                                                    'issue' => 'Issue',
                                                    'pull_request' => 'PR',
                                                    'release' => 'Release',
                                                    default => ucfirst($activity['type']),
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium leading-tight {{ $badgeColor }}">
                                                {{ $badgeLabel }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-neutral-900 dark:text-neutral-100 mb-1">
                                                <a href="{{ $activity['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                                    {{ $activity['title'] }}
                                                </a>
                                            </h3>
                                            <div class="flex items-center gap-4 text-xs text-neutral-500 dark:text-neutral-500">
                                                <span>{{ __('By') }}: {{ $activity['author'] }}</span>
                                                <span>{{ $activity['date']->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if ($mtasaResourcesActivity->hasPages())
                            <div class="mt-3 pt-3">
                                {{ $mtasaResourcesActivity->links() }}
                            </div>
                        @endif
                    @else
                        <div class="py-8 text-center text-neutral-600 dark:text-neutral-400">
                            <p>{{ __('No activity available at the moment.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

