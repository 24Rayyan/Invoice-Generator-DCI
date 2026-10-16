<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar log aktivitas (audit trail) beserta filter.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('id');

        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        if ($request->filled('module')) {
            $query->where('module_name', $request->module);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('module_name', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Opsi filter dari data yang sudah ada
        $modules = ActivityLog::query()->select('module_name')->distinct()->orderBy('module_name')->pluck('module_name');
        $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $users   = User::orderBy('username')->get(['id', 'username', 'name']);

        // Ringkasan statistik
        $stats = [
            'total'  => ActivityLog::count(),
            'today'  => ActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'create' => ActivityLog::where('action', ActivityLog::ACTION_CREATE)->count(),
            'update' => ActivityLog::where('action', ActivityLog::ACTION_UPDATE)->count(),
            'delete' => ActivityLog::where('action', ActivityLog::ACTION_DELETE)->count(),
        ];

        return view('activity_logs.index', compact('logs', 'modules', 'actions', 'users', 'stats'));
    }

    /**
     * Mengembalikan detail satu log (termasuk properties JSON) untuk modal.
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->loadMissing('user');

        return response()->json([
            'id'          => $activityLog->id,
            'action'      => $activityLog->action,
            'module_name' => $activityLog->module_name,
            'description' => $activityLog->description,
            'user_name'   => $activityLog->user_name ?? $activityLog->user?->username,
            'created_at'  => optional($activityLog->created_at)->translatedFormat('d M Y H:i:s'),
            'properties'  => $activityLog->properties,
        ]);
    }
}
