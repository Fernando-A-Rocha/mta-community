<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ReportController extends Controller
{
    public function storeResource(StoreReportRequest $request, Resource $resource): RedirectResponse
    {
        $user = $request->user();

        if ($resource->user_id === $user->id) {
            return Redirect::back()->withErrors([
                'report' => __('You cannot report your own resource.'),
            ], 'report');
        }

        $hasPending = $resource->reports()
            ->where('reporter_id', $user->id)
            ->where('status', ReportStatus::Pending->value)
            ->exists();

        if ($hasPending) {
            return Redirect::back()->withErrors([
                'report' => __('You already have a pending report for this resource. Please wait for staff to review it.'),
            ], 'report');
        }

        $report = $resource->reports()->create([
            'reporter_id' => $user->id,
            'reason' => $request->input('reason'),
            'comment' => $request->input('comment'),
            'status' => ReportStatus::Pending,
        ]);

        ActivityLogger::log('report.resource.created', $user, $request->ip(), [
            'report_id' => $report->id,
            'resource_id' => $resource->id,
            'reason' => $report->reason,
            'reportable_type' => Report::TYPE_RESOURCE,
        ], $request->userAgent());

        return Redirect::back()->with('report_success', __('Thanks. Your report was submitted to the moderation team.'));
    }

    public function storeUser(StoreReportRequest $request, User $user): RedirectResponse
    {
        $reporter = $request->user();

        if ($user->id === $reporter->id) {
            return Redirect::back()->withErrors([
                'report' => __('You cannot report yourself.'),
            ], 'report');
        }

        if (($user->profile_visibility ?? 'public') !== 'public' && ! $reporter->isModerator()) {
            abort(403);
        }

        $hasPending = Report::query()
            ->where('reporter_id', $reporter->id)
            ->where('reportable_type', Report::TYPE_USER)
            ->where('reportable_id', $user->id)
            ->where('status', ReportStatus::Pending->value)
            ->exists();

        if ($hasPending) {
            return Redirect::back()->withErrors([
                'report' => __('You already have a pending report for this profile. Please wait for staff to review it.'),
            ], 'report');
        }

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Report::TYPE_USER,
            'reportable_id' => $user->id,
            'reason' => $request->input('reason'),
            'comment' => $request->input('comment'),
            'status' => ReportStatus::Pending,
        ]);

        ActivityLogger::log('report.user.created', $reporter, $request->ip(), [
            'report_id' => $report->id,
            'user_id' => $user->id,
            'reason' => $report->reason,
            'reportable_type' => Report::TYPE_USER,
        ], $request->userAgent());

        return Redirect::back()->with('report_success', __('Thanks. Your report was submitted to the moderation team.'));
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $user = $request->user();

        abort_if($report->reporter_id !== $user->id, 403);

        if ($report->status !== ReportStatus::Pending) {
            return Redirect::back()->withErrors([
                'report' => __('Only pending reports can be deleted.'),
            ], 'report');
        }

        $context = [
            'report_id' => $report->id,
            'reportable_type' => $report->reportable_type,
            'reportable_id' => $report->reportable_id,
        ];

        $report->delete();

        ActivityLogger::log('report.deleted_by_owner', $user, $request->ip(), $context, $request->userAgent());

        return Redirect::back()->with('report_success', __('Your report was withdrawn.'));
    }
}
