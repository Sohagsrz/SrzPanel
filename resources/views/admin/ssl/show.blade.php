@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">SSL Certificate Details</h2>
                    <div class="flex space-x-4">
                        <a href="{{ route('admin.ssl.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Back to List
                        </a>
                        <form action="{{ route('admin.ssl.destroy', $certificate['name']) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700" onclick="return confirm('Are you sure you want to remove this certificate?')">
                                Remove Certificate
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Certificate Information</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Domain</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['domain'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Issuer</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['issuer'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valid From</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['valid_from'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valid To</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['valid_to'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Serial Number</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['serial_number'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Signature Type</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $certificate['signature_type'] }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Subject Details</h3>
                        <dl class="space-y-4">
                            @foreach($certificate['subject'] as $key => $value)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($key) }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Issuer Details</h3>
                        <dl class="space-y-4">
                            @foreach($certificate['issuer_details'] as $key => $value)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst($key) }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 