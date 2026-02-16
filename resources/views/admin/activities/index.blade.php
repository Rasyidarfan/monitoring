@extends('layouts.app')

@section('title', 'Kelola Kegiatan - Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">🗂️ Kelola Kegiatan</h2>
                <p class="text-gray-600 mt-2">Manage monitoring activities</p>
            </div>
            <a href="{{ route('activities.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                ➕ Tambah Kegiatan Baru
            </a>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kegiatan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desa</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $activity->display_name }}</div>
                                <div class="text-sm text-gray-500">{{ $activity->name }}</div>
                                @if($activity->description)
                                    <div class="text-xs text-gray-400 mt-1">{{ Str::limit($activity->description, 50) }}</div>
                                @endif
                                @if($activity->last_data_upload_at)
                                    <div class="text-xs text-gray-400 mt-1">
                                        Last update: {{ $activity->last_data_upload_at->format('d M Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                    {{ $activity->village_count }} desa
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                {{ number_format($activity->total_target) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                {{ number_format($activity->total_approved) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold {{ $activity->percentage >= 70 ? 'text-green-600' : ($activity->percentage >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $activity->percentage }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('upload.show', $activity) }}"
                                       class="text-green-600 hover:text-green-800 text-sm"
                                       title="Upload Data">
                                        📤 Upload
                                    </a>
                                    <a href="{{ route('activities.show', $activity->name) }}"
                                       class="text-blue-600 hover:text-blue-800 text-sm"
                                       title="View Dashboard">
                                        👁️ View
                                    </a>
                                    <a href="{{ route('activities.edit', $activity) }}"
                                       class="text-yellow-600 hover:text-yellow-800 text-sm"
                                       title="Edit">
                                        ✏️ Edit
                                    </a>
                                    <form action="{{ route('activities.destroy', $activity) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Yakin ingin menghapus kegiatan {{ $activity->display_name }}? Semua data monitoring akan ikut terhapus!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                                title="Delete">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                Belum ada kegiatan. Klik "Tambah Kegiatan Baru" untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-blue-900">
            <strong>💡 Tip:</strong> Setelah membuat kegiatan baru, gunakan halaman upload untuk mengimpor data CSV/JSON/ZIP.
        </p>
    </div>
</div>
@endsection
