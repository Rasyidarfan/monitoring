@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-800">Dashboard Monitoring</h2>

        @if($activity)
            <p class="text-gray-600 mt-2">Kegiatan: <strong>{{ $activity->display_name }}</strong></p>
            @if($activity->last_data_upload_at)
                <p class="text-sm text-gray-500">Last update: {{ $activity->last_data_upload_at->format('d M Y H:i') }}</p>
            @endif
        @else
            <p class="text-yellow-600 mt-2">⚠️ Belum ada kegiatan. Silakan login untuk membuat kegiatan.</p>
        @endif
    </div>

    @if($activity)
        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-600">Target</div>
                <div class="text-3xl font-bold text-blue-600">{{ number_format($metrics['total_target']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-600">Open</div>
                <div class="text-3xl font-bold text-gray-600">{{ number_format($metrics['total_open']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-600">Submitted</div>
                <div class="text-3xl font-bold text-yellow-600">{{ number_format($metrics['total_submitted']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-600">Approved</div>
                <div class="text-3xl font-bold text-green-600">{{ number_format($metrics['total_approved']) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm text-gray-600">Rejected</div>
                <div class="text-3xl font-bold text-red-600">{{ number_format($metrics['total_rejected']) }}</div>
            </div>
        </div>

        <!-- Per Kabupaten -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Data Per Kabupaten</h3>

            @if(count($regencyData) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kabupaten</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Target</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">% Approved</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($regencyData as $regency)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $regency['regency_name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">{{ number_format($regency['total_target']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">{{ number_format($regency['total_open']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">{{ number_format($regency['total_submitted']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">{{ number_format($regency['total_approved']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">{{ number_format($regency['total_rejected']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold">{{ $regency['pct_approved'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Chart -->
                <div class="mt-8">
                    <canvas id="regencyChart" height="100"></canvas>
                </div>

                <script>
                    const ctx = document.getElementById('regencyChart').getContext('2d');
                    const regencyData = @json($regencyData);

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: regencyData.map(r => r.regency_name),
                            datasets: [
                                {
                                    label: 'Open',
                                    data: regencyData.map(r => r.pct_open),
                                    backgroundColor: 'rgba(156, 163, 175, 0.8)',
                                },
                                {
                                    label: 'Submitted',
                                    data: regencyData.map(r => r.pct_submitted),
                                    backgroundColor: 'rgba(234, 179, 8, 0.8)',
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
                                x: {
                                    stacked: true,
                                },
                                y: {
                                    stacked: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Progress Per Kabupaten (%)'
                                },
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
                </script>
            @else
                <p class="text-gray-500 text-center py-8">Belum ada data untuk ditampilkan.</p>
            @endif
        </div>
    @endif

    <!-- Info Box for Authenticated Users -->
    @auth
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Akses Upload Data</h3>
            <p class="text-blue-800">Anda sudah login. Silakan kunjungi menu <strong>Kelola Kegiatan</strong> atau <strong>Upload Data</strong> untuk mengelola data monitoring.</p>
        </div>
    @endauth
</div>
@endsection
