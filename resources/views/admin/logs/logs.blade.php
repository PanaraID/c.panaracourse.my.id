@extends('admin.logs.layout')

@section('title', 'Frontend Logs - Detailed View')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Frontend Logs</h2>
        <p class="mt-1 text-sm text-gray-600">Detailed view of all frontend logs with filtering and search capabilities.</p>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <select name="level" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All Levels</option>
                        @foreach($levels as $levelOption)
                            <option value="{{ $levelOption }}" @if(request('level') === $levelOption) selected @endif>
                                {{ ucfirst($levelOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All Types</option>
                        @foreach($types as $typeOption)
                            <option value="{{ $typeOption }}" @if(request('type') === $typeOption) selected @endif>
                                {{ $typeOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @if(request('user_id') == $user->id) selected @endif>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="datetime-local" name="start_date" value="{{ request('start_date') }}" 
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="datetime-local" name="end_date" value="{{ request('end_date') }}" 
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search in messages and URLs..." 
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="flex space-x-2 pt-6">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('admin.logs.logs') }}" 
                       class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
                        Reset
                    </a>
                    <a href="{{ route('admin.logs.export', request()->query()) }}" 
                       class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">
                        Export CSV
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                Logs ({{ $logs->total() }} total)
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Message
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="log-level-{{ $log->level }} hover:bg-gray-50" id="log-{{ $log->id }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ $log->log_timestamp->format('Y-m-d') }}</div>
                                <div class="text-xs text-gray-500">{{ $log->log_timestamp->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full badge-{{ $log->level }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($log->type)
                                    <span class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">
                                        {{ $log->type }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-md">
                                    <div class="truncate">{{ $log->message }}</div>
                                    @if($log->stack_trace)
                                        <button onclick="toggleStackTrace('{{ $log->id }}')" 
                                                class="text-xs text-blue-600 hover:text-blue-800 mt-1">
                                            View Stack Trace
                                        </button>
                                        <div id="stack-{{ $log->id }}" class="hidden mt-2 p-2 bg-gray-100 rounded text-xs font-mono max-h-32 overflow-y-auto">
                                            {{ $log->short_stack_trace }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($log->user)
                                    <div>{{ $log->user->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-gray-400">Guest</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="max-w-xs">
                                    <a href="{{ $log->url }}" target="_blank" 
                                       class="text-blue-600 hover:text-blue-800 truncate block" 
                                       title="{{ $log->url }}">
                                        {{ $log->url }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.logs.show', $log) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        View
                                    </a>
                                    <button onclick="toggleDetails('{{ $log->id }}')" 
                                            class="text-green-600 hover:text-green-900">
                                        Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Expandable Details Row -->
                        <tr id="details-{{ $log->id }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="font-medium text-gray-900 mb-2">Log Data</h4>
                                            <div class="json-viewer text-xs">{{ $log->formatted_data ?: 'No data' }}</div>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 mb-2">Context</h4>
                                            <div class="json-viewer text-xs">{{ $log->formatted_context }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <span class="font-medium">Session ID:</span> {{ $log->session_id }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Browser:</span> {{ $log->browser }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Platform:</span> {{ $log->platform }}
                                        </div>
                                        <div>
                                            <span class="font-medium">IP Address:</span> {{ $log->ip_address }}
                                        </div>
                                        <div>
                                            <span class="font-medium">User Agent:</span> 
                                            <div class="mt-1 break-all">{{ $log->user_agent }}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No logs found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleStackTrace(logId) {
    const element = document.getElementById('stack-' + logId);
    element.classList.toggle('hidden');
}

function toggleDetails(logId) {
    const element = document.getElementById('details-' + logId);
    element.classList.toggle('hidden');
}

// Auto-refresh every 30 seconds if no filters are applied
@if(!request()->hasAny(['level', 'type', 'user_id', 'search', 'start_date', 'end_date']))
setInterval(function() {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 30000);
@endif
</script>
@endpush