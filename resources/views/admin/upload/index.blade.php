@extends('layouts.app')

@section('title', 'Upload Data - ' . $activity->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('activities.index') }}" class="text-blue-600 hover:text-blue-800">
                    ← Kembali
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">📤 Upload Data</h2>
                    <p class="text-gray-600 mt-1">{{ $activity->display_name }}</p>
                </div>
            </div>
            <a href="{{ route('activities.show', $activity->name) }}"
               class="text-blue-600 hover:text-blue-800 text-sm">
                👁️ Lihat Dashboard
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upload JSON (PJ Mapping) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📄 Upload JSON (PJ Mapping)</h3>
            <form action="{{ route('upload.json', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select JSON File
                        </label>
                        <input type="file"
                               name="json_file"
                               accept=".json,.txt"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            Format: [{"Id": "9702010001", "PJ": "Ibu Farah"}, ...]
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Upload JSON
                    </button>
                </div>
            </form>
            @if($activity->json_filename)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600">
                        <strong>Last uploaded:</strong> {{ $activity->json_filename }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Upload CSV (Monitoring Data) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Upload CSV (Monitoring Data)</h3>
            <form action="{{ route('upload.csv', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select CSV File
                        </label>
                        <input type="file"
                               name="csv_file"
                               accept=".csv,.txt"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            Supports both old (UPPERCASE) and new (Mixed case) format
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Upload CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Upload ZIP -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📦 Upload ZIP (Batch Upload)</h3>
            <form action="{{ route('upload.zip', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select ZIP File
                        </label>
                        <input type="file"
                               name="zip_file"
                               accept=".zip"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            ZIP must contain <code>query_1.csv</code>. File <code>query_2.csv</code> will be ignored.
                        </p>
                    </div>
                    <button type="submit"
                            class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
                        Upload ZIP
                    </button>
                </div>
            </form>
            @if($activity->zip_filename)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600">
                        <strong>Last uploaded:</strong> {{ $activity->zip_filename }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Upload History -->
    @if($uploadHistory->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Upload History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filename</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Records</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($uploadHistory as $upload)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $upload->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded
                                        {{ $upload->file_type === 'json' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $upload->file_type === 'csv' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $upload->file_type === 'zip' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        {{ strtoupper($upload->file_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $upload->original_filename }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    {{ number_format($upload->records_imported) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $upload->uploader->name ?? 'Unknown' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-blue-900 text-sm">
            <strong>💡 Tips:</strong>
        </p>
        <ul class="list-disc list-inside text-blue-900 text-sm mt-2 space-y-1">
            <li>Upload <strong>JSON</strong> untuk mapping Penanggung Jawab (PJ) ke desa</li>
            <li>Upload <strong>CSV</strong> untuk data monitoring (Target, Open, Submitted, Approved, Rejected)</li>
            <li>Upload <strong>ZIP</strong> untuk batch upload CSV (lebih praktis)</li>
            <li>Data yang sudah ada akan di-update jika village_code sama</li>
        </ul>
    </div>
</div>
@endsection
