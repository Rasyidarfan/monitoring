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
</div>
@endsection
