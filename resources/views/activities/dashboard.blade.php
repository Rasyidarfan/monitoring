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
                <a href="/admin/kegiatan/{{ $activity->id }}/upload" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    📤 Upload Data
                </a>
            @endauth
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Target</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['total_target']) }}</div>
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
            <nav class="flex -mb-px">
                <button class="tab-button active border-b-2 border-blue-500 text-blue-600 py-4 px-6 font-medium" data-tab="kabupaten">
                    📍 Per Kabupaten
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-4 px-6 font-medium" data-tab="pj">
                    👤 Per PJ
                </button>
                <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-4 px-6 font-medium" data-tab="desa">
                    🏘️ Per Desa
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Tab: Per Kabupaten -->
            <div id="tab-kabupaten" class="tab-content">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Breakdown Per Kabupaten</h3>

                @if(count($regencyData) > 0)
                    <div class="overflow-x-auto mb-6">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-yellow-600">
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

                    <!-- Chart -->
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Progress Per Kabupaten</h4>
                        <canvas id="regencyChart" height="100"></canvas>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data kabupaten.</p>
                @endif
            </div>

            <!-- Tab: Per PJ -->
            <div id="tab-pj" class="tab-content hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Breakdown Per Penanggung Jawab</h3>

                @if(count($pjData) > 0)
                    <div class="overflow-x-auto mb-6">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-yellow-600">
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

                    <!-- Chart -->
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Perbandingan Progress PJ (Top 10)</h4>
                        <canvas id="pjChart" height="150"></canvas>
                    </div>
                @else
                    <p class="text-gray-500">Belum ada data PJ. Upload file JSON mapping PJ terlebih dahulu.</p>
                @endif
            </div>

            <!-- Tab: Per Desa -->
            <div id="tab-desa" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Semua Desa</h3>
                    <div class="flex space-x-2">
                        <input type="text" id="searchDesa" placeholder="Cari desa..."
                            class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        <select id="filterKabupaten" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">Semua Kabupaten</option>
                            @foreach(array_unique(array_column($villageData, 'regency_name')) as $regency)
                                <option value="{{ $regency }}">{{ $regency }}</option>
                            @endforeach
                        </select>
                        <select id="filterPj" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">Semua PJ</option>
                            @foreach(array_unique(array_filter(array_column($villageData, 'pj_name'))) as $pj)
                                <option value="{{ $pj }}">{{ $pj }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(count($villageData) > 0)
                    <div class="overflow-x-auto">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-yellow-600">
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
                @else
                    <p class="text-gray-500">Belum ada data desa.</p>
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
                        backgroundColor: '#ebd28d',
                    },
                    {
                        label: 'Submitted',
                        data: regencyData.map(r => r.pct_submitted),
                        backgroundColor: '#383beb',
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

    // Chart: Per PJ (Horizontal Bar - 100% Stacked)
    const pjData = @json($pjData);
    if (pjData.length > 0) {
        const ctxPj = document.getElementById('pjChart').getContext('2d');
        const topPj = pjData.slice(0, 10);
        new Chart(ctxPj, {
            type: 'bar',
            data: {
                labels: topPj.map(p => p.pj_name),
                datasets: [
                    {
                        label: 'Open',
                        data: topPj.map(p => p.pct_open),
                        backgroundColor: '#ebd28d',
                    },
                    {
                        label: 'Submitted',
                        data: topPj.map(p => p.pct_submitted),
                        backgroundColor: '#383beb',
                    },
                    {
                        label: 'Approved',
                        data: topPj.map(p => p.pct_approved),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    },
                    {
                        label: 'Rejected',
                        data: topPj.map(p => p.pct_rejected),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
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
</script>
@endsection
