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
        <!-- Row 1: Upload ZIP -->
        <div class="bg-white rounded-lg shadow p-6">
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

        <!-- Row 2: Upload CSV (Monitoring Data) -->
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

        <!-- Row 2: Upload JSON (Anomaly Master Data) -->
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

        <!-- Row 3: Upload Anomaly CSV (Multi-File with Drag & Drop) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">⚠️ Upload CSV Anomali (Multi-File)</h3>

            <!-- Upload Mode Info (INTELLIGENT SYNC only) -->
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-green-900 mb-1">🔄 INTELLIGENT SYNC Mode</h4>
                        <ul class="text-xs text-green-800 space-y-1">
                            <li>✓ Anomali di BOTH CSV dan DB → KEEP & update</li>
                            <li>✓ Anomali hanya di CSV → INSERT (baru)</li>
                            <li>✓ Anomali hanya di DB → DELETE (orphaned)</li>
                        </ul>
                    </div>
                </div>
                <input type="hidden" id="upload-mode" value="intelligent_sync">
            </div>

            <!-- Drag & Drop Zone -->
            <div id="drop-zone"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 hover:bg-blue-50 transition-colors cursor-pointer mb-4 bg-gray-50">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                    <span class="font-semibold">Drag & drop file CSV di sini</span> atau klik untuk memilih
                </p>
                <p class="text-xs text-gray-500 mt-1">Mendukung multiple files (max 10MB setiap file, max 20 files)</p>
            </div>

            <!-- File Input (Hidden) -->
            <input type="file"
                   id="file-input"
                   name="anomaly_csv_files[]"
                   accept=".csv,.txt"
                   multiple
                   class="hidden">

            <!-- Selected Files List -->
            <div id="file-list" class="mb-4 space-y-2 hidden">
                <h4 class="text-sm font-medium text-gray-700">File Terpilih:</h4>
                <div id="file-items" class="space-y-2">
                    <!-- File items akan diinsert oleh JavaScript -->
                </div>
            </div>

            <!-- Upload Progress -->
            <div id="upload-progress" class="mb-4 hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Uploading...</span>
                    <span id="progress-percentage" class="text-sm text-gray-600">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div id="upload-status" class="mt-2 text-xs text-gray-600">
                    <!-- Status messages -->
                </div>
            </div>

            <!-- Upload Results -->
            <div id="upload-results" class="mb-4 hidden">
                <!-- Results akan diinsert oleh JavaScript -->
            </div>

            <!-- Upload Button -->
            <button type="button"
                    id="upload-button"
                    class="w-full bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                    disabled>
                Upload Anomaly CSV Files
            </button>

            <!-- Info Text -->
            <p class="text-xs text-gray-500 mt-4">
                Format: KODE_DAERAH/Kode_Desa, KEC/Kecamatan, DESA, LINK/assignment_id, Anomali/Kode_Anomali (required). Optional: DSRT, NO_ART, NAMA_KRT, NAMA_ART untuk data per-ART.
            </p>
        </div>

        <!-- Row 3: Upload JSON (PJ Mapping) -->
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

        <!-- Row 3: Upload JSON (Officer Mapping) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">👥 Upload JSON (Petugas)</h3>
            <form action="{{ route('upload.officer-json', $activity) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Officer JSON File
                        </label>
                        <input type="file"
                               name="officer_json_file"
                               accept=".json,.txt"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            Format: [{"Provinsi": "97", "Kabupaten/Kota": "02", ..., "Email Pengawas": "...", "Email Pencacah": "..."}, ...]
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Upload Officer JSON
                    </button>
                </div>
            </form>
            @if($activity->officer_json_filename)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-600">
                        <strong>Last uploaded:</strong> {{ $activity->officer_json_filename }}
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
                                        {{ $upload->file_type === 'zip' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $upload->file_type === 'anomaly_json' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $upload->file_type === 'anomaly_csv' ? 'bg-orange-100 text-orange-800' : '' }}
                                        {{ $upload->file_type === 'officer_json' ? 'bg-indigo-100 text-indigo-800' : '' }}">
                                        @if($upload->file_type === 'anomaly_csv')
                                            ANOMALY CSV
                                        @elseif($upload->file_type === 'anomaly_json')
                                            ANOMALY JSON
                                        @elseif($upload->file_type === 'officer_json')
                                            OFFICER JSON
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
            <li>Upload <strong>Anomaly CSV</strong> untuk data anomali - <strong>Mode MERGE:</strong> upload file bergantian akan menggabungkan data anomali. Mendukung format PODES (desa-level) dan per-ART. Setiap kode anomali disimpan terpisah untuk tracking yang akurat.</li>
            <li>Mapping PJ disimpan terpisah dan dapat di-update kapan saja tanpa menghapus data monitoring</li>
        </ul>
    </div>

    <!-- CSV Column Mapping Guide -->
    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 rounded-lg p-6">
        <p class="text-cyan-900 font-semibold mb-4">📊 Keterangan Mapping Kolom CSV</p>
        <p class="text-cyan-900 text-sm mb-4">
            Sistem secara otomatis mengenali dan memetakan kolom CSV ke 5 kategori target utama. Sistem mendukung berbagai variasi nama kolom (case-insensitive) dari file CSV/ZIP yang berbeda format.
        </p>

        <div class="space-y-4">
            <!-- Target Mapping -->
            <div class="bg-white rounded p-4 border-l-4 border-purple-500">
                <p class="font-semibold text-gray-800 text-sm mb-2">🎯 Target</p>
                <p class="text-gray-600 text-sm mb-2">Kolom yang dikenali:</p>
                <div class="bg-gray-100 rounded p-2 font-mono text-xs text-gray-800 space-y-1">
                    <div>• Target</div>
                    <div>• TARGET</div>
                </div>
            </div>

            <!-- Open Mapping -->
            <div class="bg-white rounded p-4 border-l-4 border-blue-500">
                <p class="font-semibold text-gray-800 text-sm mb-2">📂 Open</p>
                <p class="text-gray-600 text-sm mb-2">Kolom yang dikenali:</p>
                <div class="bg-gray-100 rounded p-2 font-mono text-xs text-gray-800 space-y-1">
                    <div>• Open</div>
                    <div>• OPEN</div>
                </div>
            </div>

            <!-- Submitted Mapping -->
            <div class="bg-white rounded p-4 border-l-4 border-green-500">
                <p class="font-semibold text-gray-800 text-sm mb-2">✅ Submit (Submitted)</p>
                <p class="text-gray-600 text-sm mb-2">Kolom yang dikenali:</p>
                <div class="bg-gray-100 rounded p-2 font-mono text-xs text-gray-800 space-y-1">
                    <div>• Submitted by PPL</div>
                    <div>• SUBMITTED BY PPL</div>
                    <div>• Submitted by Pencacah</div>
                    <div>• SUBMITTED BY PENCACAH</div>
                    <div>• Submitted</div>
                    <div>• SUBMITTED</div>
                </div>
            </div>

            <!-- Approved Mapping -->
            <div class="bg-white rounded p-4 border-l-4 border-yellow-500">
                <p class="font-semibold text-gray-800 text-sm mb-2">👍 Approve (Approved)</p>
                <p class="text-gray-600 text-sm mb-2">Kolom yang dikenali:</p>
                <div class="bg-gray-100 rounded p-2 font-mono text-xs text-gray-800 space-y-1">
                    <div>• Approved by PML</div>
                    <div>• APPROVED BY PML</div>
                    <div>• Approved by Pengawas</div>
                    <div>• APPROVED BY PENGAWAS</div>
                    <div>• Completed by PML</div>
                    <div>• COMPLETED BY PML</div>
                    <div>• Completed by Admin Kabupaten</div>
                    <div>• COMPLETED BY ADMIN KABUPATEN</div>
                    <div>• Approved by Admin Kabupaten</div>
                    <div>• APPROVED BY ADMIN KABUPATEN</div>
                    <div>• Edited by Admin</div>
                    <div>• EDITED BY ADMIN</div>
                    <div>• Completed by Admin</div>
                    <div>• COMPLETED BY ADMIN</div>
                    <div>• Approved</div>
                    <div>• APPROVED</div>
                    <div>• Completed</div>
                    <div>• COMPLETED</div>
                    <div>• Edited</div>
                    <div>• EDITED</div>
                </div>
            </div>

            <!-- Rejected Mapping -->
            <div class="bg-white rounded p-4 border-l-4 border-red-500">
                <p class="font-semibold text-gray-800 text-sm mb-2">❌ Reject (Rejected)</p>
                <p class="text-gray-600 text-sm mb-2">Kolom yang dikenali:</p>
                <div class="bg-gray-100 rounded p-2 font-mono text-xs text-gray-800 space-y-1">
                    <div>• Rejected by PML</div>
                    <div>• REJECTED BY PML</div>
                    <div>• Rejected by Pengawas</div>
                    <div>• REJECTED BY PENGAWAS</div>
                    <div>• Rejected by Admin Kabupaten</div>
                    <div>• REJECTED BY ADMIN KABUPATEN</div>
                    <div>• Rejected</div>
                    <div>• REJECTED</div>
                </div>
            </div>
        </div>

        <p class="text-cyan-900 text-xs mt-4 pt-4 border-t border-cyan-200">
            💡 <strong>Catatan:</strong> Sistem menggunakan logika MAX saat update - akan menyimpan nilai terbesar jika ada update dari file berbeda.
        </p>
    </div>
</div>

<!-- JavaScript for Anomaly CSV Multi-File Upload -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');
    const fileItems = document.getElementById('file-items');
    const uploadButton = document.getElementById('upload-button');
    const uploadMode = document.getElementById('upload-mode');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    const uploadStatus = document.getElementById('upload-status');
    const uploadResults = document.getElementById('upload-results');

    let selectedFiles = [];

    // ===== Drag & Drop Handlers =====
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        const files = Array.from(e.dataTransfer.files);
        addFiles(files);
    });

    // ===== File Input Handler =====
    fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        addFiles(files);
    });

    // ===== Upload Button Handler =====
    uploadButton.addEventListener('click', handleUpload);

    // ===== Mode is now fixed to INTELLIGENT SYNC =====

    // ===== Helper Functions =====
    function addFiles(files) {
        const validFiles = files.filter(file => {
            const isCSV = file.name.endsWith('.csv') || file.name.endsWith('.txt');
            const isUnder10MB = file.size <= 10 * 1024 * 1024;
            return isCSV && isUnder10MB;
        });

        validFiles.forEach(file => {
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });

        renderFileList();
        updateUploadButton();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        renderFileList();
        updateUploadButton();
    }

    function renderFileList() {
        if (selectedFiles.length === 0) {
            fileList.classList.add('hidden');
            return;
        }

        fileList.classList.remove('hidden');
        fileItems.innerHTML = selectedFiles.map((file, index) => `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                <div class="flex items-center space-x-3">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${file.name}</p>
                        <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                    </div>
                </div>
                <button type="button"
                        onclick="window.anomalyUpload.removeFile(${index})"
                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                    Remove
                </button>
            </div>
        `).join('');
    }

    function updateUploadButton() {
        uploadButton.disabled = selectedFiles.length === 0;
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        else return (bytes / 1048576).toFixed(1) + ' MB';
    }

    async function handleUpload() {
        const mode = uploadMode.value;
        const formData = new FormData();

        selectedFiles.forEach(file => {
            formData.append('anomaly_csv_files[]', file);
        });
        formData.append('mode', mode);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        // Show progress
        uploadProgress.classList.remove('hidden');
        uploadResults.classList.add('hidden');
        uploadButton.disabled = true;

        try {
            uploadStatus.innerHTML = '<span class="text-gray-600">Uploading files...</span>';

            const response = await fetch('{{ route("upload.anomaly-csv-multi", $activity) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok) {
                displayResults(result);
                selectedFiles = [];
                renderFileList();
            } else {
                displayError(result.error || 'Upload failed');
            }
        } catch (error) {
            displayError('Network error: ' + error.message);
        } finally {
            uploadProgress.classList.add('hidden');
            uploadButton.disabled = false;
        }
    }

    function displayResults(result) {
        uploadResults.classList.remove('hidden');

        const summaryHtml = `
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <h4 class="font-semibold text-green-900">Upload Successful!</h4>
                </div>

                <div class="text-sm text-green-800 mt-3 space-y-1">
                    <p>📥 Inserted: ${result.summary.total_inserted}</p>
                    <p>✏️ Updated: ${result.summary.total_updated}</p>
                    <p>🗑️ Deleted: ${result.summary.total_deleted}</p>
                    ${result.summary.total_skipped > 0 ? `<p>⏭️ Skipped: ${result.summary.total_skipped}</p>` : ''}
                </div>

                ${result.files.length > 1 ? `
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium text-green-900">
                            📋 File Details (${result.files.length} files)
                        </summary>
                        <div class="mt-2 space-y-2">
                            ${result.files.map(file => `
                                <div class="text-xs bg-white p-2 rounded border border-green-100">
                                    <p class="font-medium text-green-900">${file.filename}</p>
                                    <p class="text-green-700">Rows: ${file.rows_processed} | Processed: ${file.rows_processed - file.skipped} | Skipped: ${file.skipped}</p>
                                </div>
                            `).join('')}
                        </div>
                    </details>
                ` : ''}

                <p class="text-xs text-green-600 mt-3 pt-3 border-t border-green-200">
                    Page will refresh in 3 seconds to update upload history...
                </p>
            </div>
        `;

        uploadResults.innerHTML = summaryHtml;

        // Reload page after 3 seconds
        setTimeout(() => window.location.reload(), 3000);
    }

    function displayError(errorMessage) {
        uploadResults.classList.remove('hidden');
        uploadProgress.classList.add('hidden');

        uploadResults.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <h4 class="font-semibold text-red-900">Upload Failed</h4>
                </div>
                <p class="text-sm text-red-800 mt-2">${errorMessage}</p>
            </div>
        `;
    }

    // Expose removeFile to global scope for onclick handler
    window.anomalyUpload = {
        removeFile: removeFile
    };
});
</script>
@endsection
