@extends('layouts.app')

@section('title', 'Edit Kegiatan - ' . $activity->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('activities.index') }}" class="text-blue-600 hover:text-blue-800">
                ← Kembali
            </a>
            <h2 class="text-2xl font-bold text-gray-800">✏️ Edit Kegiatan</h2>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('activities.update', $activity) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name (slug) -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name (Slug) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $activity->name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="contoh: monitoring-2024"
                           required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">
                        Format: lowercase, gunakan dash (-) untuk spasi
                    </p>
                </div>

                <!-- Display Name -->
                <div>
                    <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Display Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="display_name"
                           id="display_name"
                           value="{{ old('display_name', $activity->display_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500 @error('display_name') border-red-500 @enderror"
                           placeholder="Monitoring Dashboard 2024"
                           required>
                    @error('display_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description (Optional)
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500 @error('description') border-red-500 @enderror"
                              placeholder="Deskripsi singkat tentang kegiatan ini">{{ old('description', $activity->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info -->
                <div class="bg-gray-50 border border-gray-200 rounded p-4">
                    <p class="text-sm text-gray-600">
                        <strong>Created:</strong> {{ $activity->created_at->format('d M Y H:i') }}
                    </p>
                    @if($activity->last_data_upload_at)
                        <p class="text-sm text-gray-600 mt-1">
                            <strong>Last Data Upload:</strong> {{ $activity->last_data_upload_at->format('d M Y H:i') }}
                        </p>
                    @endif
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <a href="{{ route('activities.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Update Kegiatan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- PJ Mappings Table -->
    @if($pjMappings->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">📋 PJ Mapping ({{ $pjMappings->count() }})</h3>
            <button type="button" id="toggleSelectAll" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded"
                    onclick="toggleSelectAll()">
                Pilih Semua
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 w-12">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Village Code</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Desa Nama</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">PJ Name</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Target</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pjMappings as $pj)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" class="pj-checkbox" value="{{ $pj->id }}" onchange="updateBatchButtonState()">
                        </td>
                        <td class="px-6 py-3 text-gray-800">{{ $pj->village_code }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $pj->desa_nama ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $pj->pj_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-800 font-semibold">{{ $pj->target }}</td>
                        <td class="px-6 py-3 text-center">
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    onclick="openEditModal({{ $pj->id }}, '{{ $pj->village_code }}', '{{ $pj->desa_nama ?? '' }}', '{{ $pj->pj_name ?? '' }}', {{ $pj->target }})">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Batch Edit Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">⚙️ Edit Massal</h3>
            <p class="text-sm text-gray-600 mt-1">Pilih baris di atas untuk mengedit PJ Name dan Target secara batch</p>
        </div>
        <div class="p-6">
            <form id="batchEditForm" method="POST" action="{{ route('activities.batch-update-pj', $activity) }}">
                @csrf
                <div class="space-y-4">
                    <!-- PJ Name -->
                    <div>
                        <label for="batch_pj_name" class="block text-sm font-medium text-gray-700 mb-2">
                            PJ Name (Kosongkan jika tidak ingin diubah)
                        </label>
                        <input type="text" id="batch_pj_name" name="pj_name"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               placeholder="">
                    </div>

                    <!-- Target -->
                    <div>
                        <label for="batch_target" class="block text-sm font-medium text-gray-700 mb-2">
                            Target (Kosongkan jika tidak ingin diubah)
                        </label>
                        <input type="number" id="batch_target" name="target" min="0"
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                               placeholder="">
                    </div>

                    <!-- Hidden input untuk selected IDs -->
                    <input type="hidden" id="selectedIds" name="selected_ids" value="">

                    <!-- Status -->
                    <div id="batchStatus" class="text-sm text-gray-600 bg-gray-50 p-3 rounded">
                        Pilih 0 baris untuk edit massal
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" onclick="clearBatchForm()"
                                class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                            Bersihkan
                        </button>
                        <button type="submit" id="batchSubmitBtn" disabled
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Update Massal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<!-- Edit PJ Mapping Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit PJ Mapping</h3>
        <form id="editForm" method="POST" action="">
            @csrf

            <div class="space-y-4">
                <!-- Village Code (read-only) -->
                <div>
                    <label for="village_code_display" class="block text-sm font-medium text-gray-700 mb-1">
                        Village Code
                    </label>
                    <input type="text" id="village_code_display" readonly
                           class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 text-gray-600">
                </div>

                <!-- Desa Nama -->
                <div>
                    <label for="edit_desa_nama" class="block text-sm font-medium text-gray-700 mb-1">
                        Desa Nama
                    </label>
                    <input type="text" id="edit_desa_nama" name="desa_nama"
                           class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                </div>

                <!-- PJ Name -->
                <div>
                    <label for="edit_pj_name" class="block text-sm font-medium text-gray-700 mb-1">
                        PJ Name
                    </label>
                    <input type="text" id="edit_pj_name" name="pj_name"
                           class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                </div>

                <!-- Target -->
                <div>
                    <label for="edit_target" class="block text-sm font-medium text-gray-700 mb-1">
                        Target <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="edit_target" name="target" required min="0"
                           class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(pjId, villageCode, desaNama, pjName, target) {
    document.getElementById('village_code_display').value = villageCode;
    document.getElementById('edit_desa_nama').value = desaNama;
    document.getElementById('edit_pj_name').value = pjName;
    document.getElementById('edit_target').value = target;

    const form = document.getElementById('editForm');
    form.action = `{{ route('activities.index') }}`.replace('activities', 'admin/kegiatan') +
                  '/{{ $activity->id }}/pj-mapping/' + pjId;

    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Batch Edit Functions
function getSelectedCheckboxes() {
    return Array.from(document.querySelectorAll('.pj-checkbox:checked')).map(cb => cb.value);
}

function updateBatchButtonState() {
    const selected = getSelectedCheckboxes();
    const submitBtn = document.getElementById('batchSubmitBtn');
    const statusDiv = document.getElementById('batchStatus');
    const selectedIdsInput = document.getElementById('selectedIds');

    if (selected.length > 0) {
        submitBtn.disabled = false;
        statusDiv.innerHTML = `✓ Pilih <strong>${selected.length}</strong> baris untuk edit massal`;
        statusDiv.className = 'text-sm text-green-700 bg-green-50 p-3 rounded';
        selectedIdsInput.value = selected.join(',');
    } else {
        submitBtn.disabled = true;
        statusDiv.innerHTML = 'Pilih 0 baris untuk edit massal';
        statusDiv.className = 'text-sm text-gray-600 bg-gray-50 p-3 rounded';
        selectedIdsInput.value = '';
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.pj-checkbox');
    const isChecked = checkbox ? checkbox.checked : document.getElementById('selectAllCheckbox').checked;

    checkboxes.forEach(cb => {
        cb.checked = isChecked;
    });

    updateBatchButtonState();
}

function clearBatchForm() {
    document.getElementById('batch_desa_nama').value = '';
    document.getElementById('batch_pj_name').value = '';
    document.getElementById('batch_target').value = '';

    document.querySelectorAll('.pj-checkbox').forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;

    updateBatchButtonState();
}

// Form submission handler
document.getElementById('batchEditForm').addEventListener('submit', function(e) {
    const pj_name = document.getElementById('batch_pj_name').value.trim();
    const target = document.getElementById('batch_target').value.trim();

    if (!pj_name && !target) {
        e.preventDefault();
        alert('Silakan isi minimal satu field (PJ Name atau Target) untuk di-update');
        return false;
    }

    const selected = getSelectedCheckboxes();
    if (selected.length === 0) {
        e.preventDefault();
        alert('Silakan pilih minimal satu baris');
        return false;
    }
});

// Initialize batch button state on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBatchButtonState();
});
</script>

@endsection
