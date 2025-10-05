<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrontendLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FrontendLogController extends Controller
{
    /**
     * Store frontend logs
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'logs' => 'required|array|max:100', // Limit batch size
                'logs.*.id' => 'required|string',
                'logs.*.timestamp' => 'required|date',
                'logs.*.level' => 'required|in:debug,info,warn,error',
                'logs.*.message' => 'required|string|max:1000',
                'logs.*.data' => 'nullable|array',
                'logs.*.context' => 'required|array',
                'logs.*.stackTrace' => 'nullable|string|max:10000',
                'session' => 'required|array',
                'session.sessionId' => 'required|string',
                'session.userId' => 'nullable|string',
                'session.deviceInfo' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logsData = $request->input('logs');
            $sessionData = $request->input('session');
            $ipAddress = $request->ip();
            $savedLogs = [];

            foreach ($logsData as $logData) {
                try {
                    // Check if log already exists
                    if (FrontendLog::where('log_id', $logData['id'])->exists()) {
                        continue; // Skip duplicate logs
                    }

                    $frontendLog = FrontendLog::create([
                        'log_id' => $logData['id'],
                        'session_id' => $sessionData['sessionId'],
                        'user_id' => $this->resolveUserId($sessionData['userId'] ?? null),
                        'level' => $logData['level'],
                        'message' => $logData['message'],
                        'data' => $logData['data'] ?? [],
                        'context' => $logData['context'],
                        'stack_trace' => $logData['stackTrace'] ?? null,
                        'url' => $logData['context']['url'] ?? $request->header('referer', ''),
                        'user_agent' => $request->header('user-agent', ''),
                        'ip_address' => $ipAddress,
                        'type' => $logData['context']['type'] ?? null,
                        'log_timestamp' => $logData['timestamp'],
                    ]);

                    $savedLogs[] = $frontendLog->id;

                    // Log critical errors to Laravel log
                    if ($logData['level'] === 'error') {
                        Log::channel('frontend')->error('Frontend Error', [
                            'message' => $logData['message'],
                            'url' => $logData['context']['url'] ?? '',
                            'user_id' => $frontendLog->user_id,
                            'session_id' => $sessionData['sessionId'],
                            'stack_trace' => $logData['stackTrace'] ?? null,
                            'data' => $logData['data'] ?? []
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to save frontend log', [
                        'log_data' => $logData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Logs saved successfully',
                'saved_count' => count($savedLogs),
                'total_received' => count($logsData)
            ]);

        } catch (\Exception $e) {
            Log::error('Frontend log endpoint error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get frontend logs for dashboard
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'level' => 'nullable|in:debug,info,warn,error',
                'type' => 'nullable|string',
                'user_id' => 'nullable|integer',
                'session_id' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:10|max:100',
                'sort_by' => 'nullable|in:log_timestamp,level,message,user_id',
                'sort_order' => 'nullable|in:asc,desc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = FrontendLog::with('user:id,name,email');

            // Apply filters
            if ($request->filled('level')) {
                $query->level($request->level);
            }

            if ($request->filled('type')) {
                $query->type($request->type);
            }

            if ($request->filled('user_id')) {
                $query->user($request->user_id);
            }

            if ($request->filled('session_id')) {
                $query->session($request->session_id);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            }

            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('message', 'like', "%{$searchTerm}%")
                      ->orWhere('url', 'like', "%{$searchTerm}%")
                      ->orWhereJsonContains('data', $searchTerm);
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'log_timestamp');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 50);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            Log::error('Frontend logs fetch error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logs'
            ], 500);
        }
    }

    /**
     * Get frontend log statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'user_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = FrontendLog::query();

            // Apply date filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } else {
                // Default to last 7 days
                $query->where('log_timestamp', '>=', now()->subDays(7));
            }

            // Apply user filter
            if ($request->filled('user_id')) {
                $query->user($request->user_id);
            }

            $stats = [
                'total_logs' => $query->count(),
                'by_level' => $query->selectRaw('level, COUNT(*) as count')
                    ->groupBy('level')
                    ->pluck('count', 'level'),
                'by_type' => $query->selectRaw('type, COUNT(*) as count')
                    ->whereNotNull('type')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'errors_count' => $query->where('level', 'error')->count(),
                'warnings_count' => $query->where('level', 'warn')->count(),
                'unique_users' => $query->distinct('user_id')->whereNotNull('user_id')->count(),
                'unique_sessions' => $query->distinct('session_id')->count(),
                'daily_breakdown' => $query->selectRaw('DATE(log_timestamp) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->date => $item->count]),
                'top_errors' => $query->where('level', 'error')
                    ->selectRaw('message, COUNT(*) as count')
                    ->groupBy('message')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(fn($item) => [$item->message => $item->count]),
                'browser_breakdown' => $this->getBrowserStats($query),
                'platform_breakdown' => $this->getPlatformStats($query)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Frontend log stats error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Delete old logs (cleanup)
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'required|integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $days = $request->input('days');
            $cutoffDate = now()->subDays($days);

            $deletedCount = FrontendLog::where('log_timestamp', '<', $cutoffDate)->delete();

            Log::info('Frontend logs cleanup', [
                'days' => $days,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} logs older than {$days} days"
            ]);

        } catch (\Exception $e) {
            Log::error('Frontend logs cleanup error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed'
            ], 500);
        }
    }

    /**
     * Resolve user ID from session data
     */
    private function resolveUserId($sessionUserId): ?int
    {
        if (!$sessionUserId) {
            return Auth::id();
        }

        // If it's already numeric, use it
        if (is_numeric($sessionUserId)) {
            return (int) $sessionUserId;
        }

        // Try to resolve from auth
        return Auth::id();
    }

    /**
     * Get browser statistics
     */
    private function getBrowserStats($query)
    {
        $userAgents = $query->pluck('user_agent');
        $browsers = [];

        foreach ($userAgents as $userAgent) {
            $browser = 'Unknown';
            if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
            elseif (strpos($userAgent, 'Opera') !== false) $browser = 'Opera';

            $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;
        }

        return $browsers;
    }

    /**
     * Get platform statistics
     */
    private function getPlatformStats($query)
    {
        $userAgents = $query->pluck('user_agent');
        $platforms = [];

        foreach ($userAgents as $userAgent) {
            $platform = 'Unknown';
            if (strpos($userAgent, 'Windows') !== false) $platform = 'Windows';
            elseif (strpos($userAgent, 'Mac') !== false) $platform = 'macOS';
            elseif (strpos($userAgent, 'Linux') !== false) $platform = 'Linux';
            elseif (strpos($userAgent, 'Android') !== false) $platform = 'Android';
            elseif (strpos($userAgent, 'iOS') !== false) $platform = 'iOS';

            $platforms[$platform] = ($platforms[$platform] ?? 0) + 1;
        }

        return $platforms;
    }
}