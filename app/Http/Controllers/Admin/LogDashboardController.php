<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogDashboardController extends Controller
{
    /**
     * Display the logging dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '7d');
        $level = $request->get('level', 'all');
        $type = $request->get('type', 'all');
        
        // Calculate date range
        $startDate = match($dateRange) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7)
        };

        // Build base query
        $query = FrontendLog::where('log_timestamp', '>=', $startDate);
        
        if ($level !== 'all') {
            $query->where('level', $level);
        }
        
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Get statistics
        $stats = $this->getLogStatistics($query);
        
        // Get recent logs
        $recentLogs = $query->with('user:id,name,email')
            ->orderBy('log_timestamp', 'desc')
            ->limit(100)
            ->get();

        // Get chart data
        $chartData = $this->getChartData($startDate, $level, $type);

        return view('admin.logs.dashboard', compact(
            'stats', 
            'recentLogs', 
            'chartData', 
            'dateRange', 
            'level', 
            'type'
        ));
    }

    /**
     * Display detailed logs with pagination and filtering
     */
    public function logs(Request $request)
    {
        $query = FrontendLog::with('user:id,name,email');

        // Apply filters
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', "%{$searchTerm}%")
                  ->orWhere('url', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('log_timestamp', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $logs = $query->orderBy('log_timestamp', 'desc')->paginate(50);
        
        // Get filter options
        $users = User::select('id', 'name', 'email')->get();
        $types = FrontendLog::distinct('type')->whereNotNull('type')->pluck('type');
        $levels = ['debug', 'info', 'warn', 'error'];

        return view('admin.logs.logs', compact('logs', 'users', 'types', 'levels'));
    }

    /**
     * Show individual log details
     */
    public function show(FrontendLog $log)
    {
        $log->load('user:id,name,email');
        
        // Get related logs from same session
        $relatedLogs = FrontendLog::where('session_id', $log->session_id)
            ->where('id', '!=', $log->id)
            ->orderBy('log_timestamp', 'desc')
            ->limit(20)
            ->get();

        return view('admin.logs.show', compact('log', 'relatedLogs'));
    }

    /**
     * Show error analysis
     */
    public function errors(Request $request)
    {
        $dateRange = $request->get('date_range', '7d');
        
        $startDate = match($dateRange) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7)
        };

        // Get error statistics
        $errorStats = FrontendLog::where('level', 'error')
            ->where('log_timestamp', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_errors,
                COUNT(DISTINCT user_id) as affected_users,
                COUNT(DISTINCT session_id) as affected_sessions,
                COUNT(DISTINCT url) as affected_pages
            ')
            ->first();

        // Get top errors by frequency
        $topErrors = FrontendLog::where('level', 'error')
            ->where('log_timestamp', '>=', $startDate)
            ->selectRaw('message, COUNT(*) as count, MAX(log_timestamp) as last_occurrence')
            ->groupBy('message')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Get errors by page
        $errorsByPage = FrontendLog::where('level', 'error')
            ->where('log_timestamp', '>=', $startDate)
            ->selectRaw('url, COUNT(*) as count')
            ->groupBy('url')
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        // Get error trends
        $errorTrends = FrontendLog::where('level', 'error')
            ->where('log_timestamp', '>=', $startDate)
            ->selectRaw('DATE(log_timestamp) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn($item) => [$item->date => $item->count]);

        return view('admin.logs.errors', compact(
            'errorStats',
            'topErrors',
            'errorsByPage',
            'errorTrends',
            'dateRange'
        ));
    }

    /**
     * Show performance analysis
     */
    public function performance(Request $request)
    {
        $dateRange = $request->get('date_range', '7d');
        
        $startDate = match($dateRange) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDays(7)
        };

        // Get performance logs
        $performanceLogs = FrontendLog::where('type', 'performance')
            ->where('log_timestamp', '>=', $startDate)
            ->orderBy('log_timestamp', 'desc')
            ->limit(1000)
            ->get();

        // Process performance data
        $pageLoadTimes = [];
        $ajaxResponseTimes = [];
        
        foreach ($performanceLogs as $log) {
            $data = $log->data;
            if (isset($data['metric'])) {
                if ($data['metric'] === 'page_load') {
                    $pageLoadTimes[] = $data['value'];
                } elseif ($data['metric'] === 'ajax_response') {
                    $ajaxResponseTimes[] = $data['value'];
                }
            }
        }

        $performanceStats = [
            'page_load' => [
                'count' => count($pageLoadTimes),
                'avg' => count($pageLoadTimes) > 0 ? array_sum($pageLoadTimes) / count($pageLoadTimes) : 0,
                'min' => count($pageLoadTimes) > 0 ? min($pageLoadTimes) : 0,
                'max' => count($pageLoadTimes) > 0 ? max($pageLoadTimes) : 0,
            ],
            'ajax_response' => [
                'count' => count($ajaxResponseTimes),
                'avg' => count($ajaxResponseTimes) > 0 ? array_sum($ajaxResponseTimes) / count($ajaxResponseTimes) : 0,
                'min' => count($ajaxResponseTimes) > 0 ? min($ajaxResponseTimes) : 0,
                'max' => count($ajaxResponseTimes) > 0 ? max($ajaxResponseTimes) : 0,
            ]
        ];

        return view('admin.logs.performance', compact(
            'performanceStats',
            'performanceLogs',
            'dateRange'
        ));
    }

    /**
     * Get log statistics
     */
    private function getLogStatistics($query)
    {
        $clonedQuery = clone $query;
        
        return [
            'total_logs' => $clonedQuery->count(),
            'errors' => (clone $query)->where('level', 'error')->count(),
            'warnings' => (clone $query)->where('level', 'warn')->count(),
            'info' => (clone $query)->where('level', 'info')->count(),
            'debug' => (clone $query)->where('level', 'debug')->count(),
            'unique_users' => (clone $query)->distinct('user_id')->whereNotNull('user_id')->count(),
            'unique_sessions' => (clone $query)->distinct('session_id')->count(),
            'js_errors' => (clone $query)->where('type', 'js_error')->count(),
            'ajax_errors' => (clone $query)->where('type', 'ajax_error')->count(),
            'user_actions' => (clone $query)->where('type', 'user_action')->count(),
        ];
    }

    /**
     * Get chart data for dashboard
     */
    private function getChartData($startDate, $level, $type)
    {
        $query = FrontendLog::where('log_timestamp', '>=', $startDate);
        
        if ($level !== 'all') {
            $query->where('level', $level);
        }
        
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Daily log counts
        $dailyCounts = $query->selectRaw('DATE(log_timestamp) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn($item) => [$item->date => $item->count]);

        // Level distribution
        $levelCounts = FrontendLog::where('log_timestamp', '>=', $startDate)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->get()
            ->mapWithKeys(fn($item) => [$item->level => $item->count]);

        // Type distribution
        $typeCounts = FrontendLog::where('log_timestamp', '>=', $startDate)
            ->whereNotNull('type')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($item) => [$item->type => $item->count]);

        return [
            'daily_counts' => $dailyCounts,
            'level_counts' => $levelCounts,
            'type_counts' => $typeCounts,
        ];
    }

    /**
     * Export logs as CSV
     */
    public function export(Request $request)
    {
        $query = FrontendLog::with('user:id,name,email');

        // Apply same filters as logs view
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('log_timestamp', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $logs = $query->orderBy('log_timestamp', 'desc')->limit(10000)->get();

        $filename = 'frontend_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Write CSV headers
            fputcsv($file, [
                'Timestamp',
                'Level',
                'Type',
                'Message',
                'URL',
                'User',
                'Session ID',
                'Browser',
                'Platform',
                'IP Address'
            ]);

            // Write data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->log_timestamp->format('Y-m-d H:i:s'),
                    $log->level,
                    $log->type,
                    $log->message,
                    $log->url,
                    $log->user ? $log->user->name : 'Guest',
                    $log->session_id,
                    $log->browser,
                    $log->platform,
                    $log->ip_address
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}