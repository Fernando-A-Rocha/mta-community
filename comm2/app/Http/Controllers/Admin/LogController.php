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

    private function ensureModerator(): void
    {
        abort_unless(auth()->user()?->isModerator(), 403);
    }
}
