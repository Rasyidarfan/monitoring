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
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-3 px-3 sm:py-4 sm:px-6 font-medium text-sm sm:text-base whitespace-nowrap" data-tab="anomali">
                    ⚠️ <span class="hidden sm:inline">Tab </span>Anomali
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

            <!-- Tab: Anomali -->
            <div id="tab-anomali" class="tab-content hidden">
                <div class="space-y-2 sm:space-y-0 sm:flex sm:justify-between sm:items-center mb-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Data Anomali Per Kepala Keluarga</h3>
                    <input type="text" id="searchAnomali" placeholder="🔍 Cari KK..."
                        class="w-full sm:w-auto border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500 mt-2 sm:mt-0">
                </div>

                @if(count($anomalyCards) > 0)
                    <!-- Cards Container -->
                    <div class="space-y-4">
                        @foreach($anomalyCards as $card)
                            <div class="anomaly-card bg-white rounded-lg border border-gray-200 p-4 shadow-sm"
                                data-search="{{ strtolower($card['nama_krt'] . ' ' . $card['kecamatan'] . ' ' . $card['desa']) }}">

                                <!-- Header: Wilayah + PJ -->
                                <div class="mb-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <div class="font-bold text-gray-900">{{ $card['nama_krt'] }}</div>
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

                                <!-- List ART dengan Anomali -->
                                <div class="space-y-3 mt-3">
                                    @foreach($card['art_list'] as $art)
                                        <div class="bg-gray-50 rounded p-3 border border-gray-100">
                                            <div class="font-medium text-sm text-gray-900 mb-2">
                                                ART {{ $art['no_art'] }}: {{ $art['nama_art'] }}
                                            </div>

                                            @if(count($art['anomali_details']) > 0)
                                                <div class="space-y-1.5">
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
                                                <div class="text-xs text-gray-500">Tidak ada anomali</div>
                                            @endif

                                            @if($art['link'])
                                                <a href="{{ $art['link'] }}" target="_blank" rel="noopener noreferrer"
                                                    class="text-blue-600 hover:text-blue-800 text-xs mt-2 inline-block">
                                                    🔗 Lihat di FASIH
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
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
    // Tab Switching
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;

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
        });
    });

    // Chart: Per Kabupaten (100% Stacked)
    const regencyData = @json($regencyData);
    if (regencyData.length > 0) {
        const ctxRegency = document.getElementById('regencyChart').getContext('2d');
        new Chart(ctxRegency, {
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

    // Chart: Per PJ (Horizontal Bar - 100% Stacked - All PJ)
    const pjData = @json($pjData);
    if (pjData.length > 0) {
        // Set container height dynamis berdasarkan jumlah PJ
        const containerHeight = Math.max(300, pjData.length * 40);
        document.getElementById('pjChartContainer').style.height = containerHeight + 'px';

        const ctxPj = document.getElementById('pjChart').getContext('2d');
        new Chart(ctxPj, {
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

    // Search & Filter Desa
    const searchInput = document.getElementById('searchDesa');
    const filterKabupatenSelect = document.getElementById('filterKabupaten');
    const filterPjSelect = document.getElementById('filterPj');
    const rows = document.querySelectorAll('.village-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRegency = filterKabupatenSelect.value;
        const selectedPj = filterPjSelect.value;

        rows.forEach(row => {
            const villageName = row.dataset.village;
            const regencyName = row.dataset.regency;
            const pjName = row.dataset.pj;

            const matchesSearch = villageName.includes(searchTerm);
            const matchesRegency = !selectedRegency || regencyName === selectedRegency;
            const matchesPj = !selectedPj || pjName === selectedPj;

            if (matchesSearch && matchesRegency && matchesPj) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (filterKabupatenSelect) filterKabupatenSelect.addEventListener('change', filterTable);
    if (filterPjSelect) filterPjSelect.addEventListener('change', filterTable);

    // Search Anomali
    const searchAnomalyInput = document.getElementById('searchAnomali');
    if (searchAnomalyInput) {
        searchAnomalyInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.anomaly-card').forEach(card => {
                const searchText = card.dataset.search || '';
                card.style.display = searchText.includes(term) ? '' : 'none';
            });
        });
    }
</script>
@endsection
