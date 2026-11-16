<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogArchiveService;
use App\Models\User;
use App\Support\ActivityLogSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogArchiveService $archiveService) {}

    /**
     * Menampilkan daftar log aktivitas (audit trail) beserta filter.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'source' => ['nullable', Rule::in(['active', 'archive'])],
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'action' => ['nullable', 'string', 'max:50'],
            'module' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);
        $source = $validated['source'] ?? 'active';
        $archivePeriods = $this->archiveService->archivePeriods();
        $period = $validated['period'] ?? $archivePeriods[0] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 20);
        $logs = null;
        $modules = [];
        $actions = [];

        if ($source === 'archive') {
            abort_unless($period && in_array($period, $archivePeriods, true), 404);
            $logs = $this->archiveService->archivedLogs($period, $validated, $perPage);
            $archiveDetails = $this->archiveService->archiveDetails($period);
            $modules = $archiveDetails['modules'];
            $actions = $archiveDetails['actions'];
            $stats = [
                'total' => $archiveDetails['count'],
                'today' => 0,
                'create' => $archiveDetails['action_counts'][ActivityLog::ACTION_CREATE] ?? 0,
                'update' => $archiveDetails['action_counts'][ActivityLog::ACTION_UPDATE] ?? 0,
                'delete' => $archiveDetails['action_counts'][ActivityLog::ACTION_DELETE] ?? 0,
            ];
        } else {
            $query = ActivityLog::with('user')->latest('id');

            if (! empty($validated['action'])) {
                $query->where('action', strtoupper($validated['action']));
            }

            if (! empty($validated['module'])) {
                $query->where('module_name', $validated['module']);
            }

            if (! empty($validated['user_id'])) {
                $query->where('user_id', $validated['user_id']);
            }

            if (! empty($validated['date_from'])) {
                $query->where('created_at', '>=', Carbon::parse($validated['date_from'])->startOfDay());
            }

            if (! empty($validated['date_to'])) {
                $query->where('created_at', '<', Carbon::parse($validated['date_to'])->addDay()->startOfDay());
            }

            if (isset($validated['search']) && $validated['search'] !== '') {
                $search = ActivityLogSearch::ftsMatchExpression($validated['search']);
                $query->whereRaw(
                    $search === null ? '1 = 0' : 'id IN (SELECT rowid FROM activity_logs_fts WHERE activity_logs_fts MATCH ?)',
                    $search === null ? [] : [$search]
                );
            }

            $logs = $query->paginate($perPage)->withQueryString();

            $modules = ActivityLog::query()->select('module_name')->distinct()->orderBy('module_name')->pluck('module_name')->all();
            $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action')->all();
            $users = User::orderBy('username')->limit(200)->get(['id', 'username', 'name']);

            $stats = [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::where('created_at', '>=', now()->startOfDay())->count(),
                'create' => ActivityLog::where('action', ActivityLog::ACTION_CREATE)->count(),
                'update' => ActivityLog::where('action', ActivityLog::ACTION_UPDATE)->count(),
                'delete' => ActivityLog::where('action', ActivityLog::ACTION_DELETE)->count(),
            ];
        }

        $users ??= User::orderBy('username')->limit(200)->get(['id', 'username', 'name']);

        return view('activity_logs.index', compact('logs', 'modules', 'actions', 'users', 'stats', 'source', 'archivePeriods', 'period', 'perPage'));
    }

    /**
     * Mengembalikan detail satu log (termasuk properties JSON) untuk modal.
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->loadMissing('user');

        return response()->json($this->logPayload($activityLog));
    }

    public function showArchived(string $period, int $id)
    {
        abort_unless(in_array($period, $this->archiveService->archivePeriods(), true), 404);
        $activityLog = $this->archiveService->findArchivedLog($period, $id);
        abort_unless($activityLog, 404);

        return response()->json($this->logPayload($activityLog));
    }

    private function logPayload(ActivityLog $activityLog): array
    {
        $activityLog->loadMissing('user');

        return [
            'id'          => $activityLog->id,
            'action'      => $activityLog->action,
            'module_name' => $activityLog->module_name,
            'description' => $activityLog->description,
            'user_name'   => $activityLog->user_name ?? $activityLog->user?->username,
            'created_at'  => optional($activityLog->created_at)->translatedFormat('d M Y H:i:s'),
            'properties'  => $activityLog->properties,
        ];
    }
}
