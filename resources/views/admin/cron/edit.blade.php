@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Edit Cron Job</h2>
                    <a href="{{ route('admin.cron.index') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        Back to List
                    </a>
                </div>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.cron.update', $id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $cronJob['description']) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A brief description of what this cron job does.</p>
                    </div>

                    <div>
                        <label for="schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schedule</label>
                        <input type="text" name="schedule" id="schedule" value="{{ old('schedule', $cronJob['schedule']) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="* * * * *">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cron schedule expression (minute hour day month weekday).</p>
                    </div>

                    <div>
                        <label for="command" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Command</label>
                        <input type="text" name="command" id="command" value="{{ old('command', $cronJob['command']) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                            placeholder="/usr/bin/php /path/to/script.php">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The command to execute. Use absolute paths.</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update Cron Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 