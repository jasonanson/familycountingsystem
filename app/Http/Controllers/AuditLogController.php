<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'family')->latest('created_at');

        // 動作類型 filter
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        // 操作人員 filter
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        // 日期區間 filter
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->limit(200)->get()->map(function($log) {
            return [
                'id' => 'LOG-' . str_pad($log->id, 6, '0', STR_PAD_LEFT),
                'user_name' => $log->user ? $log->user->name : '系統',
                'user_account' => $log->user ? $log->user->account : 'system',
                'family_name' => $log->family ? $log->family->name : '-',
                'action' => $log->action,
                'details' => $log->entity_type ? "修改 {$log->entity_type} #{$log->entity_id}" : '-',
                'ip_address' => $log->ip ?? '127.0.0.1',
                'severity' => $this->getSeverity($log->action),
                'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
            ];
        });

        // 統計各動作的數量(給 filter 下拉用)
        $actionCounts = AuditLog::selectRaw('action, COUNT(*) as cnt')
            ->groupBy('action')
            ->orderByDesc('cnt')
            ->limit(20)
            ->get();

        return view('audit_logs.index', [
            'logs' => $logs,
            'actionCounts' => $actionCounts,
            'totalCount' => AuditLog::count(),
        ]);
    }

    private function getSeverity(string $action): string
    {
        if (str_contains($action, 'delete') || str_contains($action, 'failed')) return 'warning';
        if (str_contains($action, 'create') || str_contains($action, 'login_success')) return 'success';
        return 'info';
    }
}