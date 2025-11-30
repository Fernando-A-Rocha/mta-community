<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReportStatusRequest;
use App\Models\Report;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureModerator();

        $query = Report::query()
            ->with(['reporter', 'reportable'])
            ->latest();

        // Default to pending if no status filter is provided
        $statusFilter = $request->has('status') ? $request->string('status')->toString() : ReportStatus::Pending->value;

        $filters = [
            'status' => $statusFilter,
            'type' => $request->string('type')->toString(),
            'reason' => $request->string('reason')->toString(),
            'search' => $request->string('search')->toString(),
        ];

        if ($filters['status'] && in_array($filters['status'], ReportStatus::values(), true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] === 'resource') {
            $query->where('reportable_type', Report::TYPE_RESOURCE);
        } elseif ($filters['type'] === 'user') {
            $query->where('reportable_type', Report::TYPE_USER);
        }

        if ($filters['reason']) {
            $query->where('reason', $filters['reason']);
        }

        if ($filters['search']) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('comment', 'like', $term)
                    ->orWhereHas('reporter', fn ($rel) => $rel->where('name', 'like', $term))
                    ->orWhereHas('reportable', function ($rel) use ($term) {
                        $rel->where('name', 'like', $term);
                    });
            });
        }

        /** @var LengthAwarePaginator $reports */
        $reports = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => Report::where('status', ReportStatus::Pending->value)->count(),
            'resolved' => Report::where('status', ReportStatus::Resolved->value)->count(),
            'invalid' => Report::where('status', ReportStatus::Invalid->value)->count(),
        ];

        return view('admin.reports.index', [
            'reports' => $reports,
            'stats' => $stats,
            'filters' => $filters,
            'statusOptions' => ReportStatus::cases(),
            'resourceReasons' => Report::RESOURCE_REASONS,
            'userReasons' => Report::USER_REASONS,
        ]);
    }

    public function update(UpdateReportStatusRequest $request, Report $report): RedirectResponse
    {
        $this->ensureModerator();

        $status = ReportStatus::from($request->validated('status'));

        if ($report->status === $status) {
            return back()->with('report_admin_notice', __('Nothing changed.'));
        }

        $report->update([
            'status' => $status,
            'handled_by_id' => $request->user()->id,
            'handled_at' => Carbon::now(),
        ]);

        ActivityLogger::log('report.status.updated', $request->user(), $request->ip(), [
            'report_id' => $report->id,
            'status' => $status->value,
            'reportable_type' => $report->reportable_type,
        ], $request->userAgent());

        return back()->with('report_admin_notice', __('Report status updated.'));
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $this->ensureModerator();

        $user = $request->user();

        $canDelete = $user->isAdmin() || ($user->isModerator() && $report->status === ReportStatus::Invalid);

        abort_unless($canDelete, 403);

        $context = [
            'report_id' => $report->id,
            'status' => $report->status->value,
            'reportable_type' => $report->reportable_type,
        ];

        $report->delete();

        ActivityLogger::log('report.deleted', $user, $request->ip(), $context, $request->userAgent());

        return back()->with('report_admin_notice', __('Report removed.'));
    }

    public function cleanup(Request $request): RedirectResponse
    {
        $this->ensureModerator();

        abort_unless($request->user()->isAdmin(), 403);

        $threshold = Carbon::now()->subDays(90);

        $deleted = Report::where('status', '!=', ReportStatus::Pending->value)
            ->where('updated_at', '<', $threshold)
            ->delete();

        ActivityLogger::log('report.bulk_cleanup', $request->user(), $request->ip(), [
            'deleted' => $deleted,
            'threshold' => $threshold->toIso8601String(),
        ], $request->userAgent());

        return back()->with('report_admin_notice', __(':count report(s) removed.', ['count' => $deleted]));
    }

    private function ensureModerator(): void
    {
        abort_unless(auth()->user()?->isModerator(), 403);
    }
}
