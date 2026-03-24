<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::with([
            'user:id,name,email',
            'page:id,page_name',
            'post:id,content',
        ])->orderByDesc('created_at');

        // Filters
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('page_id')) {
            $query->where('page_id', $request->get('page_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $logs = $query->paginate(50)->withQueryString();

        // Available filter options
        $actions = AuditLog::distinct()->pluck('action')->sort()->values();
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();
        $pages = \App\Models\FacebookPage::select('id', 'page_name')->orderBy('page_name')->get();

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
            'pages' => $pages,
            'filters' => $request->only(['action', 'user_id', 'page_id', 'date_from', 'date_to']),
        ]);
    }
}
