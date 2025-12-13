@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-white border border-neutral-300 cursor-not-allowed leading-5 rounded-md dark:text-neutral-400 dark:bg-zinc-800 dark:border-neutral-600">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 leading-5 rounded-md hover:text-neutral-900 focus:outline-none focus:ring ring-neutral-300 focus:border-blue-300 active:bg-neutral-50 active:text-neutral-800 transition ease-in-out duration-150 dark:bg-zinc-800 dark:border-neutral-600 dark:text-neutral-200 dark:focus:border-blue-700 dark:active:bg-neutral-700 dark:active:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 dark:hover:text-neutral-100">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 leading-5 rounded-md hover:text-neutral-900 focus:outline-none focus:ring ring-neutral-300 focus:border-blue-300 active:bg-neutral-50 active:text-neutral-800 transition ease-in-out duration-150 dark:bg-zinc-800 dark:border-neutral-600 dark:text-neutral-200 dark:focus:border-blue-700 dark:active:bg-neutral-700 dark:active:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 dark:hover:text-neutral-100">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-white border border-neutral-300 cursor-not-allowed leading-5 rounded-md dark:text-neutral-400 dark:bg-zinc-800 dark:border-neutral-600">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
