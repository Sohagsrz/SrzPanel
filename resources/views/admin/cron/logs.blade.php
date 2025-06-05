@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Task Logs: {{ $id }}</h2>
                    <a href="{{ route('admin.cron.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Back to Tasks
                    </a>
                </div>

                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                    <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $logs }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 