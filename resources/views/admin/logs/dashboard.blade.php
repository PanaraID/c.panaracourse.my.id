@extends('admin.logs.layout')

@section('title', 'Frontend Logs Dashboard')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <!-- Header with Filters -->
    <div class="mb-6">
        <div class="sm:flex sm:items-center sm:justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Frontend Logs Overview</h2>
            <div class="mt-4 sm:mt-0 sm:flex sm:space-x-4">
                <form method="GET" class="flex space-x-2">
                    <select name="date_range" onchange="this.form.submit()" 
                            class="rounded-md border-gray-300 text-sm">
                        <option value="1d" @if($dateRange === '1d') selected @endif>Last 24 Hours</option>
                        <option value="7d" @if($dateRange === '7d') selected @endif>Last 7 Days</option>
                        <option value="30d" @if($dateRange === '30d') selected @endif>Last 30 Days</option>
                        <option value="90d" @if($dateRange === '90d') selected @endif>Last 90 Days</option>
                    </select>
                    <select name="level" onchange="this.form.submit()" 
                            class="rounded-md border-gray-300 text-sm">
                        <option value="all" @if($level === 'all') selected @endif>All Levels</option>
                        <option value="debug" @if($level === 'debug') selected @endif>Debug</option>
                        <option value="info" @if($level === 'info') selected @endif>Info</option>
                        <option value="warn" @if($level === 'warn') selected @endif>Warning</option>
                        <option value="error" @if($level === 'error') selected @endif>Error</option>
                    </select>
                    <select name="type" onchange="this.form.submit()" 
                            class="rounded-md border-gray-300 text-sm">
                        <option value="all" @if($type === 'all') selected @endif>All Types</option>
                        <option value="js_error" @if($type === 'js_error') selected @endif>JS Errors</option>
                        <option value="ajax_request" @if($type === 'ajax_request') selected @endif>AJAX</option>
                        <option value="user_action" @if($type === 'user_action') selected @endif>User Actions</option>
                        <option value="performance" @if($type === 'performance') selected @endif>Performance</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Logs</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_logs']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Errors</dt>
                            <dd class="text-lg font-medium text-red-600">{{ number_format($stats['errors']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Warnings</dt>
                            <dd class="text-lg font-medium text-yellow-600">{{ number_format($stats['warnings']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Unique Users</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['unique_users']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Daily Logs Chart -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Log Volume</h3>
            <canvas id="dailyLogsChart" width="400" height="200"></canvas>
        </div>

        <!-- Log Levels Chart -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Log Levels Distribution</h3>
            <canvas id="levelsChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Log Types Chart -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Log Types Distribution</h3>
        <canvas id="typesChart" width="400" height="200"></canvas>
    </div>

    <!-- Recent Logs -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Recent Logs</h3>
                <a href="{{ route('admin.logs.logs') }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All →
                </a>
            </div>
        </div>
        <div class="overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentLogs as $log)
                        <tr class="log-level-{{ $log->level }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->log_timestamp->format('H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full badge-{{ $log->level }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $log->type ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate">{{ $log->message }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $log->user?->name ?? 'Guest' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="max-w-xs truncate">{{ $log->url }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No logs found for the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Logs Chart
    const dailyCtx = document.getElementById('dailyLogsChart').getContext('2d');
    const dailyData = @json($chartData['daily_counts']);
    
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: Object.keys(dailyData),
            datasets: [{
                label: 'Logs per Day',
                data: Object.values(dailyData),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Levels Chart
    const levelsCtx = document.getElementById('levelsChart').getContext('2d');
    const levelsData = @json($chartData['level_counts']);
    
    new Chart(levelsCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(levelsData).map(l => l.toUpperCase()),
            datasets: [{
                data: Object.values(levelsData),
                backgroundColor: [
                    '#6B7280', // debug - gray
                    '#3B82F6', // info - blue
                    '#F59E0B', // warn - yellow
                    '#EF4444'  // error - red
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Types Chart
    const typesCtx = document.getElementById('typesChart').getContext('2d');
    const typesData = @json($chartData['type_counts']);
    
    new Chart(typesCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(typesData),
            datasets: [{
                label: 'Count',
                data: Object.values(typesData),
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush