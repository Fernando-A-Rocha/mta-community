@php
    $alerts = [];

    // Collect session messages
    if (session('success')) {
        $alerts[] = [
            'message' => session('success'),
            'type' => 'success',
        ];
    }

    if (session('report_success')) {
        $alerts[] = [
            'message' => session('report_success'),
            'type' => 'info',
        ];
    }

    if (session('report_admin_notice')) {
        $alerts[] = [
            'message' => session('report_admin_notice'),
            'type' => 'info',
        ];
    }

    if (session('status')) {
        $alerts[] = [
            'message' => session('status'),
            'type' => 'success',
        ];
    }

    // Collect validation errors (only general errors, not field-specific ones)
    // Field-specific errors are handled by Flux components and shown under inputs
    if ($errors->any()) {
        $fieldErrors = $errors->getMessages();
        $allErrors = $errors->all();

        // Get all field names that have errors
        $fieldNames = array_keys($fieldErrors);

        // Only show errors that are NOT field-specific
        // Check each error message to see if it's associated with a field
        foreach ($allErrors as $errorMessage) {
            $isFieldError = false;

            // Check if this error message belongs to any field
            foreach ($fieldNames as $fieldName) {
                if (in_array($errorMessage, $fieldErrors[$fieldName])) {
                    $isFieldError = true;
                    break;
                }
            }

            // Only add to alerts if it's NOT a field-specific error
            if (!$isFieldError) {
                $alerts[] = [
                    'message' => $errorMessage,
                    'type' => 'error',
                ];
            }
        }
    }
@endphp

@if (!empty($alerts))
    <!-- Offset due to the fixed header -->
    <div
        id="alerts-container"
        class="fixed top-[46px] left-1/2 -translate-x-1/2 z-[99999] flex flex-col gap-3 max-w-md w-[calc(100%-2rem)] sm:w-auto"
        style="pointer-events: none;"
    >
        @foreach ($alerts as $index => $alert)
            <div
                id="alert-{{ $index }}"
                class="alert-item rounded-2xl border p-4 text-sm shadow-lg relative overflow-hidden @if($alert['type'] === 'success') border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-900/20 dark:text-emerald-100 @elseif($alert['type'] === 'info') border-blue-200 bg-blue-50/70 text-blue-900 dark:border-blue-500/40 dark:bg-blue-900/20 dark:text-blue-100 @elseif($alert['type'] === 'error') border-red-200 bg-red-50/70 text-red-900 dark:border-red-500/40 dark:bg-red-900/20 dark:text-red-100 @else border-amber-200 bg-amber-50/70 text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100 @endif"
                style="pointer-events: auto; opacity: 0; transform: translateX(100%); transition: opacity 0.3s ease-out, transform 0.3s ease-out;"
                data-progress="100"
            >
                <!-- Progress bar -->
                <div
                    class="alert-progress absolute bottom-0 left-0 right-0 h-1 bg-current opacity-20 transition-all duration-100 ease-linear"
                    style="width: 100%;"
                ></div>

                <!-- Content -->
                <div class="flex items-start gap-3 relative z-10">
                    <div class="flex-1 min-w-0">
                        <p class="break-words">{{ $alert['message'] }}</p>
                    </div>
                    <button
                        onclick="removeAlert({{ $index }})"
                        class="shrink-0 text-current opacity-60 hover:opacity-100 transition-opacity"
                        aria-label="Close"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        (function() {
            const alerts = document.querySelectorAll('.alert-item');

            alerts.forEach((alert, index) => {
                // Show alert with animation
                setTimeout(() => {
                    alert.style.opacity = '1';
                    alert.style.transform = 'translateX(0)';
                }, index * 50);

                // Start progress bar
                let progress = 100;
                const progressBar = alert.querySelector('.alert-progress');
                const timer = setInterval(() => {
                    progress -= 2;
                    if (progressBar) {
                        progressBar.style.width = progress + '%';
                    }
                    if (progress <= 0) {
                        clearInterval(timer);
                        removeAlert(index);
                    }
                }, 100);

                // Store timer on element for cleanup
                alert.dataset.timer = timer;
            });

            window.removeAlert = function(index) {
                const alert = document.getElementById('alert-' + index);
                if (alert) {
                    const timer = alert.dataset.timer;
                    if (timer) {
                        clearInterval(parseInt(timer));
                    }
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }
            };
        })();
    </script>
@endif
