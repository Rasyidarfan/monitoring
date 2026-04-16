@extends('layouts.app')

@section('title', $activity->display_name . ' - Dashboard Monitoring')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                    ← Kembali ke Ringkasan
                </a>
                <h2 class="text-2xl font-bold text-gray-800">{{ $activity->display_name }}</h2>
                @if($activity->description)
                    <p class="text-gray-600 mt-2">{{ $activity->description }}</p>
                @endif
                @if($activity->last_data_upload_at)
                    <p class="text-sm text-gray-500 mt-1">
                        Last update: {{ $activity->last_data_upload_at->format('d M Y H:i') }}
                    </p>
                @endif
            </div>
            @auth
                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="{{ route('activities.edit', $activity) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-center">
                        ✏️ Edit Kegiatan
                    </a>
                    <a href="/admin/kegiatan/{{ $activity->id }}/upload" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-center">
                        📤 Upload Data
                    </a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Metrics Cards -->
    <!-- Mobile: 1 card ringkasan dengan progress bar -->
    <div class="block md:hidden bg-white rounded-lg shadow p-4 space-y-3">
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-500">Target</span>
            <span class="text-2xl font-bold text-gray-900">{{ number_format($metrics['total_target']) }}</span>
        </div>
        <!-- Progress bar stacked -->
        @php
            $t = $metrics['total_target'] > 0 ? $metrics['total_target'] : 1;
            $pctOpen = round(($metrics['total_open']/$t)*100);
            $pctSubmitted = round(($metrics['total_submitted']/$t)*100);
            $pctApproved = round(($metrics['total_approved']/$t)*100);
            $pctRejected = round(($metrics['total_rejected']/$t)*100);
        @endphp
        <div class="flex h-6 rounded overflow-hidden bg-gray-200">
            @if($pctOpen > 0)<div style="width:{{$pctOpen}}%" class="bg-amber-300" title="Open"></div>@endif
            @if($pctSubmitted > 0)<div style="width:{{$pctSubmitted}}%" class="bg-blue-500" title="Submitted"></div>@endif
            @if($pctApproved > 0)<div style="width:{{$pctApproved}}%" class="bg-green-500" title="Approved"></div>@endif
            @if($pctRejected > 0)<div style="width:{{$pctRejected}}%" class="bg-red-500" title="Rejected"></div>@endif
        </div>
        <!-- Legend -->
        <div class="flex gap-3 text-xs text-gray-500 flex-wrap">
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-amber-300"></span>Open</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>Submitted</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>Approved</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-red-500"></span>Rejected</span>
        </div>
        <!-- Stats grid 2x2 -->
        <div class="grid grid-cols-2 gap-2">
            <div class="bg-amber-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Open</div>
                <div class="text-lg font-bold text-gray-700">{{ number_format($metrics['total_open']) }}</div>
                <div class="text-xs text-amber-700">{{ $metrics['pct_open'] }}%</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Submitted</div>
                <div class="text-lg font-bold text-blue-700">{{ number_format($metrics['total_submitted']) }}</div>
                <div class="text-xs text-blue-600">{{ $metrics['pct_submitted'] }}%</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Approved</div>
                <div class="text-lg font-bold text-green-700">{{ number_format($metrics['total_approved']) }}</div>
                <div class="text-xs text-green-600">{{ $metrics['pct_approved'] }}%</div>
            </div>
            <div class="bg-red-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Rejected</div>
                <div class="text-lg font-bold text-red-700">{{ number_format($metrics['total_rejected']) }}</div>
                <div class="text-xs text-red-600">{{ $metrics['pct_rejected'] }}%</div>
            </div>
        </div>
    </div>

    <!-- Desktop: 5 cards terpisah (hidden di mobile) -->
    <div class="hidden md:grid md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <div class="text-gray-500 text-xs sm:text-sm font-medium">Target</div>
            <div class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1 sm:mt-2">{{ number_format($metrics['total_target']) }}</div>
            <div class="text-xs text-gray-400 mt-1">Total sampel</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Open</div>
            <div class="text-3xl font-bold text-gray-600 mt-2">{{ number_format($metrics['total_open']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $metrics['pct_open'] }}% dari target</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Submitted</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($metrics['total_submitted']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $metrics['pct_submitted'] }}% dari target</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Approved</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($metrics['total_approved']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $metrics['pct_approved'] }}% dari target</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Rejected</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ number_format($metrics['total_rejected']) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $metrics['pct_rejected'] }}% dari target</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto">
                <button class="tab-button active border-b-2 border-blue-500 text-blue-600 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="kabupaten">
                    📍 <span class="hidden sm:inline">Per </span>Kabupaten
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="pj">
                    👤 <span class="hidden sm:inline">Per </span>PJ
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="desa">
                    🏘️ <span class="hidden sm:inline">Per </span>Desa
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="petugas">
                    👷 <span class="hidden sm:inline">Per </span>Petugas
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="anomali">
                    ⚠️ Anomali
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Tab: Per Kabupaten -->
            <div id="tab-kabupaten" class="tab-content">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Breakdown Per Kabupaten</h3>

                @if(count($regencyData) > 0)
                    <!-- Mobile: Card layout (hidden di md+) -->
                    <div class="block md:hidden space-y-3 mb-6">
                        @foreach($regencyData as $regency)
                        @php
                            $total = $regency['total_target'] > 0 ? $regency['total_target'] : 1;
                            $pctOpen = round(($regency['total_open'] / $total) * 100);
                            $pctSubmitted = round(($regency['total_submitted'] / $total) * 100);
                            $pctApproved = round(($regency['total_approved'] / $total) * 100);
                            $pctRejected = round(($regency['total_rejected'] / $total) * 100);
                        @endphp
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="font-semibold text-gray-900 text-sm">{{ $regency['regency_name'] }}</div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Target</div>
                                    <div class="font-bold text-gray-900">{{ number_format($regency['total_target']) }}</div>
                                </div>
                            </div>

                            <!-- Progress bar stacked -->
                            <div class="flex h-5 rounded overflow-hidden mb-3 text-xs font-semibold text-white bg-gray-200">
                                @if($pctOpen > 0)
                                    <div style="width: {{ $pctOpen }}%" class="bg-amber-300" title="Open: {{ $regency['total_open'] }}"></div>
                                @endif
                                @if($pctSubmitted > 0)
                                    <div style="width: {{ $pctSubmitted }}%" class="bg-blue-500" title="Submitted: {{ $regency['total_submitted'] }}"></div>
                                @endif
                                @if($pctApproved > 0)
                                    <div style="width: {{ $pctApproved }}%" class="bg-green-500" title="Approved: {{ $regency['total_approved'] }}"></div>
                                @endif
                                @if($pctRejected > 0)
                                    <div style="width: {{ $pctRejected }}%" class="bg-red-500" title="Rejected: {{ $regency['total_rejected'] }}"></div>
                                @endif
                            </div>

                            <!-- Counts grid 4 kolom -->
                            <div class="grid grid-cols-4 gap-1 text-center text-xs">
                                <div class="bg-amber-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Open</div>
                                    <div class="font-bold text-gray-700">{{ $regency['total_open'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $regency['pct_open'] }}%</div>
                                </div>
                                <div class="bg-blue-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Submitted</div>
                                    <div class="font-bold text-blue-700">{{ $regency['total_submitted'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $regency['pct_submitted'] }}%</div>
                                </div>
                                <div class="bg-green-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Approved</div>
                                    <div class="font-bold text-green-700">{{ $regency['total_approved'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $regency['pct_approved'] }}%</div>
                                </div>
                                <div class="bg-red-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Rejected</div>
                                    <div class="font-bold text-red-700">{{ $regency['total_rejected'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $regency['pct_rejected'] }}%</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop: Tabel biasa (hidden di mobile) -->
                    <div class="hidden md:block overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kabupaten</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Target</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($regencyData as $regency)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                            {{ $regency['regency_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                            {{ number_format($regency['total_target']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">
                                            {{ number_format($regency['total_open']) }}
                                            <span class="text-xs text-gray-400">({{ $regency['pct_open'] }}%)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600">
                                            {{ number_format($regency['total_submitted']) }}
                                            <span class="text-xs text-gray-400">({{ $regency['pct_submitted'] }}%)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                            {{ number_format($regency['total_approved']) }}
                                            <span class="text-xs text-gray-400">({{ $regency['pct_approved'] }}%)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">
                                            {{ number_format($regency['total_rejected']) }}
                                            <span class="text-xs text-gray-400">({{ $regency['pct_rejected'] }}%)</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Chart (hidden di mobile) -->
                    <div class="hidden md:block mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Progress Per Kabupaten</h4>
                        <div class="relative h-48 sm:h-56 md:h-64">
                            <canvas id="regencyChart"></canvas>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data kabupaten.</p>
                @endif
            </div>

            <!-- Tab: Per PJ -->
            <div id="tab-pj" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Breakdown Per Penanggung Jawab</h3>

                @if(count($pjData) > 0)
                    <!-- Mobile: Card view dengan stacked bar (hidden di md+) -->
                    <div class="block md:hidden space-y-3 mb-6">
                        @foreach($pjData as $pj)
                        @php
                            $total = $pj['total_target'] > 0 ? $pj['total_target'] : 1;
                            $pctOpen = round(($pj['total_open'] / $total) * 100);
                            $pctSubmitted = round(($pj['total_submitted'] / $total) * 100);
                            $pctApproved = round(($pj['total_approved'] / $total) * 100);
                            $pctRejected = round(($pj['total_rejected'] / $total) * 100);
                        @endphp
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ $pj['pj_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $pj['village_count'] }} desa</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Target</div>
                                    <div class="font-bold text-gray-900">{{ number_format($pj['total_target']) }}</div>
                                </div>
                            </div>

                            <!-- Progress bar stacked -->
                            <div class="flex h-5 rounded overflow-hidden mb-3 text-xs font-semibold text-white bg-gray-200">
                                @if($pctOpen > 0)
                                    <div style="width: {{ $pctOpen }}%" class="bg-amber-300" title="Open: {{ $pj['total_open'] }}"></div>
                                @endif
                                @if($pctSubmitted > 0)
                                    <div style="width: {{ $pctSubmitted }}%" class="bg-blue-500" title="Submitted: {{ $pj['total_submitted'] }}"></div>
                                @endif
                                @if($pctApproved > 0)
                                    <div style="width: {{ $pctApproved }}%" class="bg-green-500" title="Approved: {{ $pj['total_approved'] }}"></div>
                                @endif
                                @if($pctRejected > 0)
                                    <div style="width: {{ $pctRejected }}%" class="bg-red-500" title="Rejected: {{ $pj['total_rejected'] }}"></div>
                                @endif
                            </div>

                            <!-- Counts grid 4 kolom -->
                            <div class="grid grid-cols-4 gap-1 text-center text-xs">
                                <div class="bg-amber-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Open</div>
                                    <div class="font-bold text-gray-700">{{ $pj['total_open'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $pj['pct_open'] }}%</div>
                                </div>
                                <div class="bg-blue-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Submitted</div>
                                    <div class="font-bold text-blue-700">{{ $pj['total_submitted'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $pj['pct_submitted'] }}%</div>
                                </div>
                                <div class="bg-green-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Approved</div>
                                    <div class="font-bold text-green-700">{{ $pj['total_approved'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $pj['pct_approved'] }}%</div>
                                </div>
                                <div class="bg-red-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Rejected</div>
                                    <div class="font-bold text-red-700">{{ $pj['total_rejected'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $pj['pct_rejected'] }}%</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop: Tabel biasa (hidden di mobile, tampil di md+) -->
                    <div class="hidden md:block overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penanggung Jawab</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Target</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($pjData as $pj)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $pj['pj_name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $pj['village_count'] }} desa</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                            {{ number_format($pj['total_target']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">
                                            {{ number_format($pj['total_open']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600">
                                            {{ number_format($pj['total_submitted']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                            {{ number_format($pj['total_approved']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">
                                            {{ number_format($pj['total_rejected']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Chart (hidden di mobile) -->
                    <div class="hidden md:block mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Perbandingan Progress PJ</h4>
                        <div id="pjChartContainer" class="relative">
                            <canvas id="pjChart"></canvas>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data PJ. Upload file JSON mapping PJ terlebih dahulu.</p>
                @endif
            </div>

            <!-- Tab: Per Desa -->
            <div id="tab-desa" class="tab-content hidden">
                <div class="space-y-2 sm:space-y-0 sm:flex sm:justify-between sm:items-center mb-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Semua Desa</h3>
                    <div class="flex flex-col gap-2 sm:flex-row sm:gap-2">
                        <input type="text" id="searchDesa" placeholder="🔍 Cari desa..."
                            class="w-full sm:w-auto border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-2">
                            <select id="filterKabupaten" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                                <option value="">Semua Kabupaten</option>
                                @foreach(array_unique(array_column($villageData, 'regency_name')) as $regency)
                                    <option value="{{ $regency }}">{{ $regency }}</option>
                                @endforeach
                            </select>
                            <select id="filterPj" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                                <option value="">Semua PJ</option>
                                @foreach(array_unique(array_filter(array_column($villageData, 'pj_name'))) as $pj)
                                    <option value="{{ $pj }}">{{ $pj }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if(count($villageData) > 0)
                    <!-- Mobile: Card layout dengan progress bar -->
                    <div class="block md:hidden space-y-3 mb-6">
                        @foreach($villageData as $village)
                        @php
                            $total = $village['target'] > 0 ? $village['target'] : 1;
                            $pctOpen = round(($village['open'] / $total) * 100);
                            $pctSubmitted = round(($village['submitted'] / $total) * 100);
                            $pctApproved = round(($village['approved'] / $total) * 100);
                            $pctRejected = round(($village['rejected'] / $total) * 100);
                        @endphp
                        <div class="village-row bg-gray-50 rounded-lg p-3 border border-gray-200"
                            data-regency="{{ $village['regency_name'] }}"
                            data-village="{{ strtolower($village['village_name']) }}"
                            data-pj="{{ $village['pj_name'] ?? '' }}">

                            <!-- Header: Nama desa + lokasi -->
                            <div class="mb-2">
                                <div class="font-semibold text-gray-900 text-sm">{{ $village['village_name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $village['village_code'] }}</div>
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $village['regency_name'] }}
                                    @if($village['pj_name'])
                                        • {{ $village['pj_name'] }}
                                    @endif
                                </div>
                            </div>

                            <!-- Progress bar (100% stacked) -->
                            <div class="flex h-5 rounded overflow-hidden mb-2 text-xs font-semibold text-white bg-gray-200">
                                @if($pctOpen > 0)
                                    <div style="width: {{ $pctOpen }}%" class="bg-amber-300" title="Open: {{ $village['open'] }}"></div>
                                @endif
                                @if($pctSubmitted > 0)
                                    <div style="width: {{ $pctSubmitted }}%" class="bg-blue-500" title="Submitted: {{ $village['submitted'] }}"></div>
                                @endif
                                @if($pctApproved > 0)
                                    <div style="width: {{ $pctApproved }}%" class="bg-green-500" title="Approved: {{ $village['approved'] }}"></div>
                                @endif
                                @if($pctRejected > 0)
                                    <div style="width: {{ $pctRejected }}%" class="bg-red-500" title="Rejected: {{ $village['rejected'] }}"></div>
                                @endif
                            </div>

                            <!-- Counts: Grid 4 kolom compact -->
                            <div class="grid grid-cols-4 gap-1 text-center text-xs">
                                <div class="bg-amber-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs leading-tight">Open</div>
                                    <div class="font-bold text-gray-700">{{ $village['open'] }}</div>
                                </div>
                                <div class="bg-blue-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs leading-tight">Subm</div>
                                    <div class="font-bold text-blue-700">{{ $village['submitted'] }}</div>
                                </div>
                                <div class="bg-green-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs leading-tight">Appr</div>
                                    <div class="font-bold text-green-600">{{ $village['approved'] }}</div>
                                </div>
                                <div class="bg-red-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs leading-tight">Rej</div>
                                    <div class="font-bold text-red-600">{{ $village['rejected'] }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop: Tabel biasa (hidden di mobile) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kabupaten</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PJ</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Target</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="villageTableBody">
                                @foreach($villageData as $village)
                                    <tr class="village-row" data-regency="{{ $village['regency_name'] }}" data-village="{{ strtolower($village['village_name']) }}" data-pj="{{ $village['pj_name'] ?? '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $village['village_name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $village['village_code'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $village['regency_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $village['pj_name'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                            {{ number_format($village['target']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">
                                            {{ number_format($village['open']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600">
                                            {{ number_format($village['submitted']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                            {{ number_format($village['approved']) }}
                                            <span class="text-xs text-gray-400">({{ $village['pct_approved'] }}%)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">
                                            {{ number_format($village['rejected']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data desa.</p>
                @endif
            </div>

            <!-- Tab: Per Petugas -->
            <div id="tab-petugas" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Breakdown Per Petugas</h3>
                </div>

                @if(count($officerData) > 0)
                    <!-- Filter Section -->
                    <div class="bg-white rounded-lg shadow p-4 mb-6 space-y-3">
                        <h4 class="text-sm font-semibold text-gray-700">Filter</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Filter Petugas -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pencacah</label>
                                <input type="text" id="filterPetugas" placeholder="🔍 Cari pencacah..."
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <!-- Filter Pengawas -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pengawas</label>
                                <input type="text" id="filterPengawas" placeholder="🔍 Cari pengawas..."
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            </div>
                            <!-- Filter Desa -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Desa</label>
                                <input type="text" id="filterDesa" placeholder="🔍 Cari desa..."
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Mobile: Card view dengan stacked bar -->
                    <div class="block md:hidden space-y-3 mb-6">
                        @foreach($officerData as $officer)
                        @php
                            $total = $officer['total_target'] > 0 ? $officer['total_target'] : 1;
                            $pctOpen = round(($officer['total_open'] / $total) * 100);
                            $pctSubmitted = round(($officer['total_submitted'] / $total) * 100);
                            $pctApproved = round(($officer['total_approved'] / $total) * 100);
                            $pctRejected = round(($officer['total_rejected'] / $total) * 100);
                        @endphp
                        <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ $officer['enumerator_email'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $officer['supervisor_email'] }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $officer['village_count'] }} desa</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Target</div>
                                    <div class="font-bold text-gray-900">{{ number_format($officer['total_target']) }}</div>
                                </div>
                            </div>

                            <!-- Progress bar stacked -->
                            <div class="flex h-5 rounded overflow-hidden mb-3 text-xs font-semibold text-white bg-gray-200">
                                @if($pctOpen > 0)
                                    <div style="width: {{ $pctOpen }}%" class="bg-amber-300" title="Open: {{ $officer['total_open'] }}"></div>
                                @endif
                                @if($pctSubmitted > 0)
                                    <div style="width: {{ $pctSubmitted }}%" class="bg-blue-500" title="Submitted: {{ $officer['total_submitted'] }}"></div>
                                @endif
                                @if($pctApproved > 0)
                                    <div style="width: {{ $pctApproved }}%" class="bg-green-500" title="Approved: {{ $officer['total_approved'] }}"></div>
                                @endif
                                @if($pctRejected > 0)
                                    <div style="width: {{ $pctRejected }}%" class="bg-red-500" title="Rejected: {{ $officer['total_rejected'] }}"></div>
                                @endif
                            </div>

                            <!-- Counts grid 4 kolom -->
                            <div class="grid grid-cols-4 gap-1 text-center text-xs">
                                <div class="bg-amber-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Open</div>
                                    <div class="font-bold text-gray-700">{{ $officer['total_open'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $officer['pct_open'] }}%</div>
                                </div>
                                <div class="bg-blue-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Submitted</div>
                                    <div class="font-bold text-blue-700">{{ $officer['total_submitted'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $officer['pct_submitted'] }}%</div>
                                </div>
                                <div class="bg-green-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Approved</div>
                                    <div class="font-bold text-green-700">{{ $officer['total_approved'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $officer['pct_approved'] }}%</div>
                                </div>
                                <div class="bg-red-50 rounded p-1.5">
                                    <div class="text-gray-500 text-2xs">Rejected</div>
                                    <div class="font-bold text-red-700">{{ $officer['total_rejected'] }}</div>
                                    <div class="text-2xs text-gray-400">{{ $officer['pct_rejected'] }}%</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Desktop: Tabel biasa dengan expandable desa -->
                    <div class="hidden md:block overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1">✓</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pencacah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengawas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Target</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($officerData as $index => $officer)
                                    <tr class="officer-row hover:bg-gray-50" data-enumerator="{{ $officer['enumerator_email'] }}" data-supervisor="{{ $officer['supervisor_email'] ?? '' }}" data-villages="{{ implode(',', array_column($officer['villages'] ?? [], 'name')) }}">
                                        <td class="px-6 py-4 text-center">
                                            <button class="toggle-villages text-gray-400 hover:text-gray-600 font-bold text-lg leading-none" title="Expand/collapse villages">
                                                +
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $officer['enumerator_email'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $officer['village_count'] }} desa</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $officer['supervisor_email'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <div class="villages-summary cursor-pointer" title="Click for full list">
                                                @php
                                                    $villageNames = array_map(fn($v) => $v['name'], $officer['villages'] ?? []);
                                                    $summary = implode(', ', array_slice($villageNames, 0, 2));
                                                    if (count($villageNames) > 2) {
                                                        $summary .= '...';
                                                    }
                                                @endphp
                                                {{ $summary }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                            {{ number_format($officer['total_target']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">
                                            {{ number_format($officer['total_open']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-blue-600">
                                            {{ number_format($officer['total_submitted']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                            {{ number_format($officer['total_approved']) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">
                                            {{ number_format($officer['total_rejected']) }}
                                        </td>
                                    </tr>
                                    <!-- Hidden row for expanded villages -->
                                    <tr class="villages-expanded" style="display: none !important;">
                                        <td colspan="9" class="px-6 py-4 bg-gray-50">
                                            <div class="text-sm">
                                                <div class="font-semibold text-gray-700 mb-2">Daftar Desa ({{ $officer['village_count'] }} total):</div>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                                    @forelse($officer['villages'] ?? [] as $village)
                                                        <div class="bg-blue-50 border border-blue-200 rounded px-2 py-1 text-xs text-gray-700 flex items-center justify-between">
                                                            <span title="{{ $village['name'] }}">{{ substr($village['name'], 0, 20) }}{{ strlen($village['name']) > 20 ? '...' : '' }}</span>
                                                            <span class="text-2xs text-gray-400 ml-1">({{ $village['code'] }})</span>
                                                        </div>
                                                    @empty
                                                        <div class="text-gray-500">Tidak ada desa</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Chart (hidden di mobile) -->
                    <div class="hidden md:block mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Perbandingan Progress Petugas</h4>
                        <div id="officerChartContainer" class="relative">
                            <canvas id="officerChart"></canvas>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data petugas. Upload file JSON petugas terlebih dahulu.</p>
                @endif
            </div>

            <!-- Tab: Anomali -->
            <div id="tab-anomali" class="tab-content hidden">
                <div class="space-y-4">
                    <!-- Header and Search -->
                    <div class="space-y-2 sm:space-y-0 sm:flex sm:justify-between sm:items-center">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800">Data Anomali Per Kepala Keluarga</h3>
                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-2 mt-2 sm:mt-0">
                            <input type="text" id="searchAnomali" placeholder="🔍 Cari KK..."
                                class="w-full sm:w-auto border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                            <select id="filterAnomalyPj" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                                <option value="">Semua PJ</option>
                                @foreach(array_unique(array_filter(array_column($anomalyCards, 'pj_name'))) as $pj)
                                    <option value="{{ $pj }}">{{ $pj }}</option>
                                @endforeach
                            </select>
                            <button id="copyAssignmentIds" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-medium whitespace-nowrap">
                                📋 Copy ID Assignment
                            </button>
                        </div>
                    </div>

                    <!-- Anomaly Statistics per PJ -->
                    @if(count($anomalyStats) > 0)
                        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Status Pengecekan Anomali Per PJ</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($anomalyStats as $stat)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="font-medium text-sm text-gray-900 mb-2">{{ $stat['pj_name'] }}</div>
                                        <div class="flex gap-4 text-xs">
                                            <div>
                                                <div class="text-gray-500">Belum Dicek</div>
                                                <div class="text-lg font-bold text-orange-600">{{ $stat['unchecked'] }}</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Sudah Dicek</div>
                                                <div class="text-lg font-bold text-green-600">{{ $stat['checked'] }}</div>
                                            </div>
                                            <div class="text-right flex-1">
                                                <div class="text-gray-500">Total</div>
                                                <div class="text-lg font-bold text-gray-900">{{ $stat['total'] }}</div>
                                            </div>
                                        </div>
                                        @php
                                            $pctChecked = $stat['total'] > 0 ? round(($stat['checked'] / $stat['total']) * 100) : 0;
                                        @endphp
                                        <div class="mt-2 h-2 bg-gray-200 rounded overflow-hidden">
                                            <div style="width: {{ $pctChecked }}%" class="h-full bg-green-500"></div>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1 text-center">{{ $pctChecked }}% selesai</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if(count($anomalyCards) > 0)
                    <!-- Cards Container -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($anomalyCards as $card)
                            @php
                                $assignmentId = null;
                                if (!empty($card['art_list'])) {
                                    $firstArt = reset($card['art_list']);
                                    if ($firstArt['link']) {
                                        preg_match('/assignment-detail\/([a-f0-9\-]+)/', $firstArt['link'], $matches);
                                        if (!empty($matches[1])) {
                                            $assignmentId = $matches[1];
                                        }
                                    }
                                }
                            @endphp
                            <div class="anomaly-card bg-white rounded-lg border border-gray-200 p-4 shadow-sm"
                                data-search="{{ strtolower($card['nama_krt'] . ' ' . $card['kecamatan'] . ' ' . $card['desa']) }}"
                                data-pj="{{ $card['pj_name'] ?? 'Belum ditentukan' }}"
                                data-assignment-id="{{ $assignmentId }}">

                                <!-- Header: Wilayah + PJ + Checkbox -->
                                <div class="mb-3">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex-1">
                                            <div class="font-bold text-gray-900 flex items-center gap-2">
                                                <input type="checkbox" class="check-all-kk rounded w-4 h-4 text-green-600"
                                                    data-kk="{{ $card['kode_daerah'] }}-{{ $card['dsrt'] }}"
                                                    @if($card['all_checked']) checked disabled @endif>
                                                {{ $card['nama_krt'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ $card['kecamatan'] }} - {{ $card['desa'] }}
                                            </div>
                                        </div>
                                        <div class="text-right text-sm">
                                            <div class="text-gray-500">PJ:</div>
                                            <div class="font-medium text-gray-900">
                                                {{ $card['pj_name'] ?? 'Belum ditentukan' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- List ART dengan Anomali dan Checkbox -->
                                <div class="space-y-3 mt-3">
                                    @php
                                        $kkLink = null;
                                        $allLinks = array_filter(array_column($card['art_list'], 'link'));
                                        if (!empty($allLinks)) {
                                            $kkLink = reset($allLinks);
                                        }
                                    @endphp
                                    @foreach($card['art_list'] as $art)
                                        <div class="bg-gray-50 rounded p-3 border border-gray-100 art-item">
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <div class="font-medium text-sm text-gray-900 flex-1">
                                                    <input type="checkbox" class="anomaly-checkbox rounded w-4 h-4 text-green-600"
                                                        data-anomaly-id="{{ $art['id'] }}"
                                                        data-kk="{{ $card['kode_daerah'] }}-{{ $card['dsrt'] }}"
                                                        @if($art['checked']) checked disabled @endif>
                                                    <span class="ml-1">ART {{ $art['no_art'] }}: {{ $art['nama_art'] }}</span>
                                                </div>
                                            </div>

                                            @if(count($art['anomali_details']) > 0)
                                                <div class="space-y-1.5 ml-6">
                                                    @foreach($art['anomali_details'] as $anomali)
                                                        <div class="flex items-start gap-2 text-xs">
                                                            <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded font-bold whitespace-nowrap">
                                                                {{ $anomali['code'] }}
                                                            </span>
                                                            <span class="text-gray-700 flex-1">{{ $anomali['description'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500 ml-6">Tidak ada anomali</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Single Link per KK at bottom -->
                                @if($kkLink)
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <a href="{{ $kkLink }}" target="_blank" rel="noopener noreferrer"
                                            class="text-blue-600 hover:text-blue-800 text-xs font-medium inline-flex items-center gap-1">
                                            🔗 Lihat di FASIH
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <p class="text-gray-600">Belum ada data anomali.</p>
                        @auth
                            <p class="text-sm text-gray-500 mt-2">
                                <a href="{{ route('upload.show', $activity) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    Upload file CSV anomali
                                </a> untuk menampilkan data anomali di sini.
                            </p>
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    // Chart visibility control
    function ensureChartVisibility(activeTab) {
        const regencyChartWrapper = document.querySelector('#tab-kabupaten .hidden.md\\:block:has(#regencyChart)');
        const pjChartWrapper = document.querySelector('#tab-pj .hidden.md\\:block:has(#pjChart)');
        const officerChartWrapper = document.querySelector('#tab-petugas .hidden.md\\:block:has(#officerChart)');

        if (regencyChartWrapper) {
            regencyChartWrapper.style.display = activeTab === 'kabupaten' ? 'block' : 'none';
        }
        if (pjChartWrapper) {
            pjChartWrapper.style.display = activeTab === 'pj' ? 'block' : 'none';
        }
        if (officerChartWrapper) {
            officerChartWrapper.style.display = activeTab === 'petugas' ? 'block' : 'none';
        }
    }

    // Chart data and instances
    const regencyData = @json($regencyData);
    const pjData = @json($pjData);
    const officerData = @json($officerData);
    let regencyChartInstance = null;
    let pjChartInstance = null;
    let officerChartInstance = null;

    // Function to initialize regency chart
    function initRegencyChart() {
        if (regencyChartInstance || regencyData.length === 0) return;

        const regencyChartCanvas = document.getElementById('regencyChart');
        if (!regencyChartCanvas) return;

        // Check if canvas is visible (not in a hidden tab)
        const tabKabupaten = document.getElementById('tab-kabupaten');
        if (tabKabupaten && tabKabupaten.classList.contains('hidden')) return;

        const ctxRegency = regencyChartCanvas.getContext('2d');
        regencyChartInstance = new Chart(ctxRegency, {
            type: 'bar',
            data: {
                labels: regencyData.map(r => r.regency_name),
                datasets: [
                    {
                        label: 'Open',
                        data: regencyData.map(r => r.pct_open),
                        backgroundColor: 'rgba(252, 211, 77, 0.9)',
                    },
                    {
                        label: 'Submitted',
                        data: regencyData.map(r => r.pct_submitted),
                        backgroundColor: 'rgba(59, 130, 246, 0.9)',
                    },
                    {
                        label: 'Approved',
                        data: regencyData.map(r => r.pct_approved),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    },
                    {
                        label: 'Rejected',
                        data: regencyData.map(r => r.pct_rejected),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // Function to initialize PJ chart
    function initPjChart() {
        if (pjChartInstance || pjData.length === 0) return;

        const pjChartContainer = document.getElementById('pjChartContainer');
        const pjChartCanvas = document.getElementById('pjChart');
        if (!pjChartContainer || !pjChartCanvas) return;

        // Check if canvas is visible (not in a hidden tab)
        const tabPj = document.getElementById('tab-pj');
        if (tabPj && tabPj.classList.contains('hidden')) return;

        // Set container height dynamis berdasarkan jumlah PJ
        const containerHeight = Math.max(300, pjData.length * 40);
        pjChartContainer.style.height = containerHeight + 'px';

        const ctxPj = pjChartCanvas.getContext('2d');
        pjChartInstance = new Chart(ctxPj, {
            type: 'bar',
            data: {
                labels: pjData.map(p => p.pj_name),
                datasets: [
                    {
                        label: 'Open',
                        data: pjData.map(p => p.pct_open),
                        backgroundColor: 'rgba(252, 211, 77, 0.9)',
                    },
                    {
                        label: 'Submitted',
                        data: pjData.map(p => p.pct_submitted),
                        backgroundColor: 'rgba(59, 130, 246, 0.9)',
                    },
                    {
                        label: 'Approved',
                        data: pjData.map(p => p.pct_approved),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    },
                    {
                        label: 'Rejected',
                        data: pjData.map(p => p.pct_rejected),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: { stacked: true }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.x.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // Function to initialize Officer chart
    function initOfficerChart() {
        if (officerChartInstance || officerData.length === 0) return;

        const officerChartContainer = document.getElementById('officerChartContainer');
        const officerChartCanvas = document.getElementById('officerChart');
        if (!officerChartContainer || !officerChartCanvas) return;

        // Check if canvas is visible (not in a hidden tab)
        const tabPetugas = document.getElementById('tab-petugas');
        if (tabPetugas && tabPetugas.classList.contains('hidden')) return;

        // Set container height dynamis
        const containerHeight = Math.max(300, officerData.length * 40);
        officerChartContainer.style.height = containerHeight + 'px';

        const ctxOfficer = officerChartCanvas.getContext('2d');
        officerChartInstance = new Chart(ctxOfficer, {
            type: 'bar',
            data: {
                labels: officerData.map(o => o.enumerator_email),
                datasets: [
                    {
                        label: 'Open',
                        data: officerData.map(o => o.pct_open),
                        backgroundColor: 'rgba(252, 211, 77, 0.9)',
                    },
                    {
                        label: 'Submitted',
                        data: officerData.map(o => o.pct_submitted),
                        backgroundColor: 'rgba(59, 130, 246, 0.9)',
                    },
                    {
                        label: 'Approved',
                        data: officerData.map(o => o.pct_approved),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    },
                    {
                        label: 'Rejected',
                        data: officerData.map(o => o.pct_rejected),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y: { stacked: true }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.x.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // Tab Switching
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;

            // Destroy charts when leaving their tabs
            if (tabName !== 'kabupaten' && regencyChartInstance) {
                regencyChartInstance.destroy();
                regencyChartInstance = null;
            }
            if (tabName !== 'pj' && pjChartInstance) {
                pjChartInstance.destroy();
                pjChartInstance = null;
            }
            if (tabName !== 'petugas' && officerChartInstance) {
                officerChartInstance.destroy();
                officerChartInstance = null;
            }

            // Update button styles
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            button.classList.add('active', 'border-blue-500', 'text-blue-600');
            button.classList.remove('border-transparent', 'text-gray-500');

            // Show/hide content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`tab-${tabName}`).classList.remove('hidden');

            // Ensure correct chart visibility
            ensureChartVisibility(tabName);

            // Initialize charts only when their tabs become visible
            if (tabName === 'kabupaten') {
                initRegencyChart();
            } else if (tabName === 'pj') {
                initPjChart();
            } else if (tabName === 'petugas') {
                initOfficerChart();
            }
        });
    });

    // Initialize regency chart on page load if kabupaten tab is active
    if (document.getElementById('tab-kabupaten') && !document.getElementById('tab-kabupaten').classList.contains('hidden')) {
        initRegencyChart();
        ensureChartVisibility('kabupaten');
    }

    // Search & Filter Desa
    const searchInput = document.getElementById('searchDesa');
    const filterKabupatenSelect = document.getElementById('filterKabupaten');
    const filterPjSelect = document.getElementById('filterPj');

    function filterTable() {
        const searchTerm = searchInput?.value.toLowerCase() || '';
        const selectedRegency = filterKabupatenSelect?.value || '';
        const selectedPj = filterPjSelect?.value || '';

        document.querySelectorAll('.village-row').forEach(row => {
            const villageName = row.dataset.village || '';
            const regencyName = row.dataset.regency || '';
            const pjName = row.dataset.pj || '';

            const matchesSearch = villageName.includes(searchTerm);
            const matchesRegency = !selectedRegency || regencyName === selectedRegency;
            const matchesPj = !selectedPj || pjName === selectedPj;

            row.style.display = (matchesSearch && matchesRegency && matchesPj) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterKabupatenSelect) filterKabupatenSelect.addEventListener('change', filterTable);
    if (filterPjSelect) filterPjSelect.addEventListener('change', filterTable);

    // Search and Filter Anomali
    const searchAnomalyInput = document.getElementById('searchAnomali');
    const filterAnomalyPjSelect = document.getElementById('filterAnomalyPj');

    function filterAnomalies() {
        const searchTerm = (searchAnomalyInput?.value || '').toLowerCase();
        const selectedPj = filterAnomalyPjSelect?.value || '';

        document.querySelectorAll('.anomaly-card').forEach(card => {
            const searchText = (card.dataset.search || '').toLowerCase();
            const pj = card.dataset.pj || '';

            const matchesSearch = searchText.includes(searchTerm);
            const matchesPj = !selectedPj || pj === selectedPj;

            card.style.display = (matchesSearch && matchesPj) ? '' : 'none';
        });
    }

    if (searchAnomalyInput) searchAnomalyInput.addEventListener('input', filterAnomalies);
    if (filterAnomalyPjSelect) filterAnomalyPjSelect.addEventListener('change', filterAnomalies);

    // Toggle Anomaly Check Status
    document.querySelectorAll('.anomaly-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const anomalyId = e.target.dataset.anomalyId;
            const kkKey = e.target.dataset.kk;

            try {
                const response = await fetch(`/admin/anomaly/${anomalyId}/toggle-check`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                // Handle different HTTP status codes
                if (!response.ok) {
                    // Server returned an error status (4xx, 5xx)
                    e.target.checked = data.checked; // Restore to server state
                    alert(data.message || 'Error updating check status');

                    // If already checked, disable the checkbox to prevent further attempts
                    if (response.status === 422 && data.checked) {
                        e.target.disabled = true;
                    }
                    return;
                }

                if (data.success) {
                    // Successfully updated
                    e.target.checked = data.checked;

                    // If now checked, disable the checkbox to prevent unchecking
                    if (data.checked) {
                        e.target.disabled = true;
                    }

                    // Check if all checkboxes in this KK are checked
                    const kkCheckboxes = document.querySelectorAll(`.anomaly-checkbox[data-kk="${kkKey}"]`);
                    const allChecked = Array.from(kkCheckboxes).every(cb => cb.checked);

                    // Update the KK-level checkbox
                    const kkCheckbox = document.querySelector(`.check-all-kk[data-kk="${kkKey}"]`);
                    if (kkCheckbox) {
                        kkCheckbox.checked = allChecked;
                        if (allChecked) {
                            kkCheckbox.disabled = true;
                        }
                    }
                } else {
                    // Unexpected failure
                    e.target.checked = !e.target.checked;
                    alert(data.message || 'Error updating check status');
                }
            } catch (error) {
                console.error('Error:', error);
                e.target.checked = !e.target.checked;
                alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
            }
        });
    });

    // Toggle all checkboxes for a KK
    document.querySelectorAll('.check-all-kk').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const kkKey = e.target.dataset.kk;
            const isChecked = e.target.checked;

            document.querySelectorAll(`.anomaly-checkbox[data-kk="${kkKey}"]`).forEach(cb => {
                cb.checked = isChecked;
                cb.dispatchEvent(new Event('change'));
            });
        });
    });

    // Copy Assignment IDs from visible anomaly cards
    document.getElementById('copyAssignmentIds').addEventListener('click', () => {
        const assignmentIds = [];

        // Get all visible anomaly cards
        document.querySelectorAll('.anomaly-card').forEach(card => {
            // Check if card is visible
            if (card.style.display !== 'none') {
                const assignmentId = card.dataset.assignmentId;
                if (assignmentId) {
                    assignmentIds.push(assignmentId);
                }
            }
        });

        if (assignmentIds.length === 0) {
            alert('Tidak ada ID assignment yang ditemukan.');
            return;
        }

        // Format as array string: '{id1}','{id2}',...
        const arrayString = assignmentIds.map(id => `'${id}'`).join(',');

        // Copy to clipboard
        navigator.clipboard.writeText(arrayString).then(() => {
            alert(`✓ ${assignmentIds.length} ID assignment telah disalin ke clipboard:\n\n${arrayString}`);
        }).catch(err => {
            console.error('Copy to clipboard error:', err);
            alert('Gagal menyalin ke clipboard. Silakan coba lagi.');
        });
    });

    // Filter Petugas, Pengawas, dan Desa
    const filterPetugasInput = document.getElementById('filterPetugas');
    const filterPengawasInput = document.getElementById('filterPengawas');
    const filterDesaInput = document.getElementById('filterDesa');

    function filterOfficers() {
        const searchPetugas = (filterPetugasInput?.value || '').toLowerCase();
        const searchPengawas = (filterPengawasInput?.value || '').toLowerCase();
        const searchDesa = (filterDesaInput?.value || '').toLowerCase();

        document.querySelectorAll('.officer-row').forEach(row => {
            const enumerator = (row.dataset.enumerator || '').toLowerCase();
            const supervisor = (row.dataset.supervisor || '').toLowerCase();
            const villages = (row.dataset.villages || '').toLowerCase();

            const matchesPetugas = !searchPetugas || enumerator.includes(searchPetugas);
            const matchesPengawas = !searchPengawas || supervisor.includes(searchPengawas);
            const matchesDesa = !searchDesa || villages.includes(searchDesa);

            row.style.display = (matchesPetugas && matchesPengawas && matchesDesa) ? '' : 'none';

            // Also hide the expanded villages row
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('villages-expanded')) {
                nextRow.style.display = 'none';
            }
        });
    }

    // Toggle villages expand/collapse
    document.querySelectorAll('.toggle-villages').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const row = e.target.closest('tr.officer-row');
            if (!row) {
                console.error('Officer row not found');
                return;
            }

            const expandedRow = row.nextElementSibling;
            if (!expandedRow || !expandedRow.classList.contains('villages-expanded')) {
                console.error('Villages expanded row not found or wrong class');
                return;
            }

            // Check current visibility
            const computedStyle = window.getComputedStyle(expandedRow);
            const isVisible = computedStyle.display !== 'none';

            if (isVisible) {
                // Hide the row
                expandedRow.style.setProperty('display', 'none', 'important');
                e.target.textContent = '+';
                e.target.title = 'Click to expand';
            } else {
                // Show the row
                expandedRow.style.setProperty('display', 'table-row', 'important');
                e.target.textContent = '−';
                e.target.title = 'Click to collapse';
            }
        });
    });

    // Add event listeners for filter inputs
    if (filterPetugasInput) filterPetugasInput.addEventListener('input', filterOfficers);
    if (filterPengawasInput) filterPengawasInput.addEventListener('input', filterOfficers);
    if (filterDesaInput) filterDesaInput.addEventListener('input', filterOfficers);
</script>
@endsection
