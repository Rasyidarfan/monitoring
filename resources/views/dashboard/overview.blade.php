@extends('layouts.app')

@section('title', 'Dashboard Monitoring - Semua Kegiatan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-800">📊 Ringkasan Semua Kegiatan</h2>
        <p class="text-gray-600 mt-2">Monitoring progress seluruh kegiatan BPS Kabupaten Jayawijaya</p>
    </div>

    @if($activities->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <p class="text-yellow-800">Belum ada kegiatan. Silakan login untuk membuat kegiatan baru.</p>
        </div>
    @else
        <!-- Summary Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Mobile: Card layout (hidden di md+) -->
            <div class="block md:hidden p-4 space-y-3">
                @foreach($activities as $activity)
                <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 hover:bg-gray-100 transition cursor-pointer"
                    onclick="window.location='/kegiatan/{{ $activity->name }}'">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 text-sm">{{ $activity->display_name }}</div>
                            @if($activity->last_data_upload_at)
                                <div class="text-xs text-gray-500 mt-0.5">Last: {{ $activity->last_data_upload_at->format('d M H:i') }}</div>
                            @endif
                        </div>
                        <a href="/kegiatan/{{ $activity->name }}" class="text-blue-600 text-sm font-medium ml-2">→</a>
                    </div>

                    <!-- Progress bar -->
                    @php
                        $total = $activity->total_target > 0 ? $activity->total_target : 1;
                        $pctOpen = round(($activity->total_open / $total) * 100);
                        $pctSubmitted = round(($activity->total_submitted / $total) * 100);
                        $pctApproved = round(($activity->total_approved / $total) * 100);
                        $pctRejected = round(($activity->total_rejected / $total) * 100);
                    @endphp
                    <div class="flex h-5 rounded overflow-hidden mb-2 text-xs font-semibold text-white bg-gray-200">
                        @if($pctOpen > 0)
                            <div style="width: {{ $pctOpen }}%" class="bg-amber-300" title="Open: {{ $activity->total_open }}"></div>
                        @endif
                        @if($pctSubmitted > 0)
                            <div style="width: {{ $pctSubmitted }}%" class="bg-yellow-500" title="Submitted: {{ $activity->total_submitted }}"></div>
                        @endif
                        @if($pctApproved > 0)
                            <div style="width: {{ $pctApproved }}%" class="bg-green-500" title="Approved: {{ $activity->total_approved }}"></div>
                        @endif
                        @if($pctRejected > 0)
                            <div style="width: {{ $pctRejected }}%" class="bg-red-500" title="Rejected: {{ $activity->total_rejected }}"></div>
                        @endif
                    </div>

                    <!-- Counts: Grid 4 kolom -->
                    <div class="grid grid-cols-4 gap-1 text-center text-xs">
                        <div class="bg-gray-100 rounded p-1.5">
                            <div class="text-gray-500 text-2xs leading-tight">Open</div>
                            <div class="font-bold text-gray-700">{{ $activity->total_open }}</div>
                        </div>
                        <div class="bg-yellow-50 rounded p-1.5">
                            <div class="text-gray-500 text-2xs leading-tight">Subm</div>
                            <div class="font-bold text-yellow-600">{{ $activity->total_submitted }}</div>
                        </div>
                        <div class="bg-green-50 rounded p-1.5">
                            <div class="text-gray-500 text-2xs leading-tight">Appr</div>
                            <div class="font-bold text-green-600">{{ $activity->total_approved }}</div>
                        </div>
                        <div class="bg-red-50 rounded p-1.5">
                            <div class="text-gray-500 text-2xs leading-tight">Rej</div>
                            <div class="font-bold text-red-600">{{ $activity->total_rejected }}</div>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Total Summary Card -->
                <div class="border border-blue-200 rounded-lg p-3 bg-blue-50">
                    <div class="font-semibold text-blue-900 text-sm mb-2">TOTAL SEMUA KEGIATAN</div>
                    <div class="grid grid-cols-4 gap-1 text-center text-xs">
                        <div>
                            <div class="text-gray-600 text-2xs">Target</div>
                            <div class="font-bold text-blue-700">{{ number_format($grandTotals['total_target']) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 text-2xs">Open</div>
                            <div class="font-bold text-gray-700">{{ number_format($grandTotals['total_open']) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 text-2xs">Appr</div>
                            <div class="font-bold text-green-600">{{ number_format($grandTotals['total_approved']) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-600 text-2xs">Rej</div>
                            <div class="font-bold text-red-600">{{ number_format($grandTotals['total_rejected']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop: Tabel biasa (hidden di mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kegiatan</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Open</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activities as $activity)
                            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location='/kegiatan/{{ $activity->name }}'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $activity->display_name }}</div>
                                    @if($activity->last_data_upload_at)
                                        <div class="text-xs text-gray-500">Last update: {{ $activity->last_data_upload_at->format('d M Y H:i') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                    {{ number_format($activity->total_target) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-600">
                                    {{ number_format($activity->total_open) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-yellow-600">
                                    {{ number_format($activity->total_submitted) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-green-600 font-semibold">
                                    {{ number_format($activity->total_approved) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-red-600">
                                    {{ number_format($activity->total_rejected) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="/kegiatan/{{ $activity->name }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Detail →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td class="px-6 py-4 text-gray-900">TOTAL SEMUA KEGIATAN</td>
                            <td class="px-6 py-4 text-right">{{ number_format($grandTotals['total_target']) }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($grandTotals['total_open']) }}</td>
                            <td class="px-6 py-4 text-right text-yellow-600">{{ number_format($grandTotals['total_submitted']) }}</td>
                            <td class="px-6 py-4 text-right text-green-600">{{ number_format($grandTotals['total_approved']) }}</td>
                            <td class="px-6 py-4 text-right text-red-600">{{ number_format($grandTotals['total_rejected']) }}</td>
                            <td class="px-6 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </div>
        </div>

        <!-- Comparison Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Perbandingan Progress Kegiatan</h3>
            <div class="relative h-48 sm:h-56 md:h-64">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>

        <script>
            const activities = @json($activities);

            // Calculate percentages for 100% stacked bar
            const chartData = activities.map(a => {
                const total = a.total_target > 0 ? a.total_target : 1;
                return {
                    name: a.display_name,
                    open: ((a.total_open / total) * 100).toFixed(2),
                    submitted: ((a.total_submitted / total) * 100).toFixed(2),
                    approved: ((a.total_approved / total) * 100).toFixed(2),
                    rejected: ((a.total_rejected / total) * 100).toFixed(2)
                };
            });

            const ctx = document.getElementById('comparisonChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.map(d => d.name),
                    datasets: [
                        {
                            label: 'Open',
                            data: chartData.map(d => d.open),
                            backgroundColor: 'rgba(252, 211, 77, 0.9)',
                        },
                        {
                            label: 'Submitted',
                            data: chartData.map(d => d.submitted),
                            backgroundColor: 'rgba(59, 130, 246, 0.9)',
                        },
                        {
                            label: 'Approved',
                            data: chartData.map(d => d.approved),
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        },
                        {
                            label: 'Rejected',
                            data: chartData.map(d => d.rejected),
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                        },
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
                        title: {
                            display: true,
                            text: 'Progress per Kegiatan (100% Stacked)'
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endif

    <!-- Info Box -->
    @auth
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-blue-900">
                <strong>Info:</strong> Anda sudah login. Klik kegiatan untuk melihat detail atau
                <a href="/admin/kegiatan" class="underline font-semibold">kelola kegiatan</a>.
            </p>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <p class="text-gray-700">
                💡 <strong>Tip:</strong> Klik baris kegiatan untuk melihat detail breakdown per kabupaten, PJ, dan desa.
            </p>
        </div>
    @endauth
</div>
@endsection
