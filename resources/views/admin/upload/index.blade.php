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

    <!-- Anomaly Master Data Section -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <p class="text-yellow-900 text-sm">
            <strong>⚠️ Master Data Anomali:</strong> Sebelum mengupload data anomali per ART, upload dulu JSON penjelasan anomali (A01-A75) di bawah ini. Data ini hanya perlu di-upload sekali atau saat ada update penjelasan anomali.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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

        <!-- Upload JSON (Anomaly Master Data) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Upload JSON (Anomali Master Data)</h3>
            <form action="{{ route('upload.anomaly-json', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Anomaly JSON File
                        </label>
                        <input type="file"
                               name="anomaly_json_file"
                               accept=".json,.txt"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            Format: [{"No. Anomali": "A01", "Rule dg Nama Variable di FASIH": "...", "Keterangan ...": "..."}, ...]
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                        Upload Anomaly JSON
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Upload Anomaly CSV -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">⚠️ Upload CSV Anomali</h3>
            <form action="{{ route('upload.anomaly-csv', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Anomaly CSV File
                        </label>
                        <input type="file"
                               name="anomaly_csv_file"
                               accept=".csv,.txt"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            Format: KODE_DAERAH, KEC, DESA, DSRT, NO_ART, NAMA_KRT, NAMA_ART, LINK, Anomali
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                        Upload Anomaly CSV
                    </button>
                </div>
            </form>
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
                                        {{ $upload->file_type === 'zip' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $upload->file_type === 'anomaly_json' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $upload->file_type === 'anomaly_csv' ? 'bg-orange-100 text-orange-800' : '' }}">
                                        @if($upload->file_type === 'anomaly_csv')
                                            ANOMALY CSV
                                        @elseif($upload->file_type === 'anomaly_json')
                                            ANOMALY JSON
                                        @else
                                            {{ strtoupper($upload->file_type) }}
                                        @endif
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
            <li>Upload <strong>JSON (Anomali Master)</strong> untuk penjelasan anomali (A01-A75) - hanya perlu di-upload sekali saat awal setup</li>
            <li>Upload <strong>JSON (PJ Mapping)</strong> untuk mapping Penanggung Jawab (PJ) ke desa - akan mengganti mapping lama</li>
            <li>Upload <strong>CSV</strong> untuk data monitoring (Target, Open, Submitted, Approved, Rejected) - akan menghapus semua data lama dan mengimport data baru</li>
            <li>Upload <strong>ZIP</strong> untuk batch upload CSV (lebih praktis) - akan menghapus semua data lama dan mengimport data baru</li>
            <li>Upload <strong>Anomaly CSV</strong> untuk data anomali per ART - akan menampilkan tab Anomali dengan detail anomali per keluarga</li>
            <li>Mapping PJ disimpan terpisah dan dapat di-update kapan saja tanpa menghapus data monitoring</li>
        </ul>
    </div>
</div>
@endsection
