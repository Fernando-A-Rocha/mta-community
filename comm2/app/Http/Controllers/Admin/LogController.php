<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureModerator();

        $query = ActivityLog::query()->with('user')->latest();

        $filters = [
            'search' => $request->string('search')->toString(),
            'action' => $request->string('action')->toString(),
            'user' => $request->integer('user_id'),
            'ip' => $request->string('ip')->toString(),
        ];

        if ($filters['action']) {
            $query->where('action', $filters['action']);
        }

        if ($filters['ip']) {
            $query->where('ip_address', $filters['ip']);
        }

        if ($filters['user']) {
            $query->where('user_id', $filters['user']);
        }

        if ($filters['search']) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('action', 'like', $term)
                    ->orWhere('context', 'like', $term);
            });
        }

        $logs = $query->paginate(30)->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
        ]);
    }

    /**
     * Get logs for a specific entity (user or resource).
     */
    public function entityLogs(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->ensureModerator();

        $type = $request->string('type')->toString(); // 'user' or 'resource'
        $id = $request->integer('id');
        $search = $request->string('search')->toString();
        $page = $request->integer('page', 1);

        if (! in_array($type, ['user', 'resource'], true) || $id <= 0) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $query = ActivityLog::query()->with('user')->latest();

        // Filter by entity type
        if ($type === 'user') {
            $query->where(function ($q) use ($id) {
                $q->where('user_id', $id)
                    ->orWhere('context->user_id', $id)
                    ->orWhere('context->reviewer_id', $id)
                    ->orWhere('context->resource_owner_id', $id);
            });
        } else { // resource
            $query->where(function ($q) use ($id) {
                $q->where('context->resource_id', $id)
                    ->orWhere('context->rating_id', $id)
                    ->orWhere('context->version_id', $id);
            });
        }

        // Search filter
        if ($search) {
            $term = '%'.$search.'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('action', 'like', $term)
                    ->orWhere('context', 'like', $term);
            });
        }

        $logs = $query->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user_id' => $log->user_id,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                    ] : null,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'context' => $log->context,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    private function ensureModerator(): void
    {
        abort_unless(auth()->user()?->isModerator(), 403);
    }
}
