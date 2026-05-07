<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Anomaly;
use App\Models\AnomalyData;
use App\Models\MonitoringData;
use App\Models\OfficerMapping;
use App\Models\PjMapping;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ActivityDashboardController extends Controller
{
    /**
     * Display detailed dashboard for specific activity
     */
    public function show(Request $request, string $slug): View
    {
        $activity = Activity::where('name', $slug)->firstOrFail();

        // Get data grouped by regency
        $regencyData = $this->getRegencyData($activity);

        // Get data grouped by PJ
        $pjData = $this->getPjData($activity);

        // Get data grouped by Officer (Petugas)
        $officerData = $this->getOfficerData($activity);

        // Get all village data
        $villageData = $this->getVillageData($activity);

        // Get overall metrics
        $metrics = $this->getActivityMetrics($activity);

        // Get anomaly cards data
        $anomalyCards = $this->getAnomalyCards($activity);

        // Get anomaly statistics per PJ
        $anomalyStats = $this->getAnomalyStatsByPj($activity);

        return view('activities.dashboard', [
            'activity' => $activity,
            'regencyData' => $regencyData,
            'pjData' => $pjData,
            'officerData' => $officerData,
            'villageData' => $villageData,
            'metrics' => $metrics,
            'anomalyCards' => $anomalyCards,
            'anomalyStats' => $anomalyStats,
        ]);
    }

    /**
     * Get activity overall metrics (target from pj_mappings)
     */
    protected function getActivityMetrics(Activity $activity): array
    {
        // Target from pj_mappings
        $targetData = PjMapping::where('activity_id', $activity->id)
            ->selectRaw('SUM(target) as total_target')
            ->first();

        // Metrics from monitoring_data
        $metricsData = MonitoringData::where('activity_id', $activity->id)
            ->selectRaw('
                SUM(open) as total_open,
                SUM(submitted) as total_submitted,
                SUM(approved) as total_approved,
                SUM(rejected) as total_rejected
            ')
            ->first();

        $total = $targetData->total_target ?? 0;

        return [
            'total_target' => $total,
            'total_open' => $metricsData->total_open ?? 0,
            'total_submitted' => $metricsData->total_submitted ?? 0,
            'total_approved' => $metricsData->total_approved ?? 0,
            'total_rejected' => $metricsData->total_rejected ?? 0,
            'pct_open' => $total > 0 ? round(($metricsData->total_open ?? 0) / $total * 100, 2) : 0,
            'pct_submitted' => $total > 0 ? round(($metricsData->total_submitted ?? 0) / $total * 100, 2) : 0,
            'pct_approved' => $total > 0 ? round(($metricsData->total_approved ?? 0) / $total * 100, 2) : 0,
            'pct_rejected' => $total > 0 ? round(($metricsData->total_rejected ?? 0) / $total * 100, 2) : 0,
        ];
    }

    /**
     * Get data per regency (target from pj_mappings)
     */
    protected function getRegencyData(Activity $activity): array
    {
        $data = MonitoringData::where('monitoring_data.activity_id', $activity->id)
            ->leftJoin('pj_mappings', function($join) use ($activity) {
                $join->on('monitoring_data.village_code', '=', 'pj_mappings.village_code')
                     ->where('pj_mappings.activity_id', $activity->id);
            })
            ->selectRaw('
                monitoring_data.regency_code,
                COALESCE(SUM(pj_mappings.target), 0) as total_target,
                SUM(monitoring_data.open) as total_open,
                SUM(monitoring_data.submitted) as total_submitted,
                SUM(monitoring_data.approved) as total_approved,
                SUM(monitoring_data.rejected) as total_rejected
            ')
            ->groupBy('monitoring_data.regency_code')
            ->get()
            ->map(function ($item) {
                $total = $item->total_target > 0 ? $item->total_target : 1;
                return [
                    'regency_code' => $item->regency_code,
                    'regency_name' => $this->getRegencyName($item->regency_code),
                    'total_target' => $item->total_target,
                    'total_open' => $item->total_open,
                    'total_submitted' => $item->total_submitted,
                    'total_approved' => $item->total_approved,
                    'total_rejected' => $item->total_rejected,
                    'pct_open' => round(($item->total_open / $total) * 100, 2),
                    'pct_submitted' => round(($item->total_submitted / $total) * 100, 2),
                    'pct_approved' => round(($item->total_approved / $total) * 100, 2),
                    'pct_rejected' => round(($item->total_rejected / $total) * 100, 2),
                ];
            })
            ->toArray();

        return $data;
    }

    /**
     * Get data per Officer (Petugas/Pencacah)
     */
    protected function getOfficerData(Activity $activity): array
    {
        // Get unique enumerator emails from officer_mappings
        $officers = $activity->officerMappings()
            ->select('enumerator_email', 'supervisor_email')
            ->distinct()
            ->get();

        if ($officers->isEmpty()) {
            return [];
        }

        $officerData = [];

        foreach ($officers as $officer) {
            $enumeratorEmail = $officer->enumerator_email;

            if (!$enumeratorEmail) {
                continue;
            }

            // Get village codes for this enumerator
            $villageCodes = $activity->officerMappings()
                ->where('enumerator_email', $enumeratorEmail)
                ->pluck('village_code');

            // Get total target
            $totalTarget = $activity->officerMappings()
                ->where('enumerator_email', $enumeratorEmail)
                ->sum('target');

            // Get supervisor email (take first)
            $supervisorEmail = $activity->officerMappings()
                ->where('enumerator_email', $enumeratorEmail)
                ->value('supervisor_email');

            // Aggregate monitoring data (if available)
            $metricsData = MonitoringData::where('monitoring_data.activity_id', $activity->id)
                ->whereIn('monitoring_data.village_code', $villageCodes)
                ->selectRaw('
                    SUM(open) as total_open,
                    SUM(submitted) as total_submitted,
                    SUM(approved) as total_approved,
                    SUM(rejected) as total_rejected,
                    COUNT(DISTINCT village_code) as village_count
                ')
                ->first();

            // Display officer even if no monitoring data yet
            $villageCount = $villageCodes->count();

            // Get village codes with names (if available in monitoring_data, otherwise just codes)
            $villages = $activity->monitoringData()
                ->whereIn('village_code', $villageCodes)
                ->select('village_code', 'village_name')
                ->distinct()
                ->get()
                ->mapWithKeys(fn($md) => [$md->village_code => $md->village_name])
                ->toArray();

            // Get PJ names for villages assigned to this enumerator
            $pjMappings = PjMapping::where('activity_id', $activity->id)
                ->whereIn('village_code', $villageCodes)
                ->select('village_code', 'pj_code', 'pj_name')
                ->get()
                ->map(fn($pj) => [
                    'code' => $pj->village_code,
                    'pj_code' => $pj->pj_code,
                    'pj_name' => $pj->pj_name,
                ])
                ->keyBy('code')
                ->toArray();

            // Get list of PJ names assigned to this enumerator
            $pjNames = array_filter(array_map(fn($pj) => $pj['pj_name'] ?? null, $pjMappings));
            $uniquePjNames = array_unique($pjNames);

            // If no village names from monitoring_data, use codes
            // Include PJ name for each village
            $villagesList = [];
            foreach ($villageCodes as $code) {
                $pjName = $pjMappings[$code]['pj_name'] ?? null;
                $villagesList[] = [
                    'code' => $code,
                    'name' => $villages[$code] ?? $code,
                    'pj_name' => $pjName,
                ];
            }

            $total = $totalTarget > 0 ? $totalTarget : 1;

            $officerData[] = [
                'enumerator_email' => $enumeratorEmail,
                'supervisor_email' => $supervisorEmail,
                'village_count' => $villageCount,
                'villages' => $villagesList,
                'pj_names' => array_values($uniquePjNames),
                'total_target' => $totalTarget,
                'total_open' => $metricsData->total_open ?? 0,
                'total_submitted' => $metricsData->total_submitted ?? 0,
                'total_approved' => $metricsData->total_approved ?? 0,
                'total_rejected' => $metricsData->total_rejected ?? 0,
                'pct_open' => round(($metricsData->total_open ?? 0) / $total * 100, 2),
                'pct_submitted' => round(($metricsData->total_submitted ?? 0) / $total * 100, 2),
                'pct_approved' => round(($metricsData->total_approved ?? 0) / $total * 100, 2),
                'pct_rejected' => round(($metricsData->total_rejected ?? 0) / $total * 100, 2),
            ];
        }

        // Sort by total_target descending
        usort($officerData, function ($a, $b) {
            return $b['total_target'] <=> $a['total_target'];
        });

        return $officerData;
    }

    /**
     * Get data per PJ (Penanggung Jawab)
     */
    protected function getPjData(Activity $activity): array
    {
        // Get unique PJ names from pj_mappings
        $pjMappings = $activity->pjMappings()
            ->select('pj_name')
            ->distinct()
            ->pluck('pj_name');

        $data = [];

        // If no pj_mappings exist, return empty array
        // (data will only be shown after uploading PJ mapping JSON)
        if ($pjMappings->isEmpty()) {
            return [];
        }

        foreach ($pjMappings as $pjName) {
            // Get village codes for this PJ from pj_mappings
            $pjMappingData = $activity->pjMappings()
                ->where('pj_name', $pjName)
                ->get();

            $villageCodes = $pjMappingData->pluck('village_code');
            $totalTarget = $pjMappingData->sum('target');

            // Get metrics for villages under this PJ
            $metricsData = MonitoringData::where('monitoring_data.activity_id', $activity->id)
                ->whereIn('monitoring_data.village_code', $villageCodes)
                ->selectRaw('
                    SUM(open) as total_open,
                    SUM(submitted) as total_submitted,
                    SUM(approved) as total_approved,
                    SUM(rejected) as total_rejected,
                    COUNT(DISTINCT village_code) as village_count
                ')
                ->first();

            if ($metricsData && $metricsData->village_count > 0) {
                $total = $totalTarget > 0 ? $totalTarget : 1;
                $data[] = [
                    'pj_name' => $pjName,
                    'village_count' => $metricsData->village_count,
                    'total_target' => $totalTarget,
                    'total_open' => $metricsData->total_open ?? 0,
                    'total_submitted' => $metricsData->total_submitted ?? 0,
                    'total_approved' => $metricsData->total_approved ?? 0,
                    'total_rejected' => $metricsData->total_rejected ?? 0,
                    'pct_open' => round(($metricsData->total_open ?? 0) / $total * 100, 2),
                    'pct_submitted' => round(($metricsData->total_submitted ?? 0) / $total * 100, 2),
                    'pct_approved' => round(($metricsData->total_approved ?? 0) / $total * 100, 2),
                    'pct_rejected' => round(($metricsData->total_rejected ?? 0) / $total * 100, 2),
                ];
            }
        }

        // Sort by pct_open ascending (terkecil ke terbesar)
        usort($data, function ($a, $b) {
            return $a['pct_open'] <=> $b['pct_open'];
        });

        return $data;
    }

    /**
     * Get all village data (target from pj_mappings)
     * Include villages from monitoring_data AND villages from pj_mappings without monitoring data
     */
    protected function getVillageData(Activity $activity): array
    {
        // First, get all villages from monitoring_data with pj_mapping data
        $monitoringVillages = MonitoringData::where('monitoring_data.activity_id', $activity->id)
            ->leftJoin('pj_mappings', function ($join) use ($activity) {
                $join->on('monitoring_data.village_code', '=', 'pj_mappings.village_code')
                    ->where('pj_mappings.activity_id', $activity->id);
            })
            ->select(
                'monitoring_data.village_code',
                'monitoring_data.village_name',
                'monitoring_data.regency_code',
                'monitoring_data.open',
                'monitoring_data.submitted',
                'monitoring_data.approved',
                'monitoring_data.rejected',
                'pj_mappings.pj_code',
                'pj_mappings.pj_name',
                'pj_mappings.target'
            )
            ->get()
            ->keyBy('village_code');

        // Get villages from pj_mappings that don't have monitoring_data
        $pjOnlyVillages = PjMapping::where('pj_mappings.activity_id', $activity->id)
            ->whereNotIn('pj_mappings.village_code', $monitoringVillages->keys())
            ->select(
                'pj_mappings.village_code',
                'pj_mappings.desa_nama as village_name',
                \DB::raw('SUBSTRING(pj_mappings.village_code, 1, 4) as regency_code'),
                'pj_mappings.pj_code',
                'pj_mappings.pj_name',
                'pj_mappings.target'
            )
            ->get()
            ->map(function ($item) {
                $item->open = 0;
                $item->submitted = 0;
                $item->approved = 0;
                $item->rejected = 0;
                return $item;
            });

        // Combine both collections
        $allVillages = $monitoringVillages->concat($pjOnlyVillages)
            ->sortBy('regency_code')
            ->sortBy('village_name', SORT_REGULAR, true)
            ->map(function ($item) {
                $total = $item->target > 0 ? $item->target : 1;
                return [
                    'village_code' => $item->village_code,
                    'village_name' => $item->village_name,
                    'regency_code' => $item->regency_code,
                    'regency_name' => $this->getRegencyName($item->regency_code),
                    'pj_code' => $item->pj_code,
                    'pj_name' => $item->pj_name,
                    'target' => $item->target ?? 0,
                    'open' => $item->open ?? 0,
                    'submitted' => $item->submitted ?? 0,
                    'approved' => $item->approved ?? 0,
                    'rejected' => $item->rejected ?? 0,
                    'pct_open' => round((($item->open ?? 0) / $total) * 100, 2),
                    'pct_submitted' => round((($item->submitted ?? 0) / $total) * 100, 2),
                    'pct_approved' => round((($item->approved ?? 0) / $total) * 100, 2),
                    'pct_rejected' => round((($item->rejected ?? 0) / $total) * 100, 2),
                ];
            })
            ->toArray();

        return $allVillages;
    }

    /**
     * Get anomaly cards grouped by KK (Kepala Keluarga) with check status
     */
    protected function getAnomalyCards(Activity $activity): array
    {
        // Get all anomaly data for this activity
        $anomalyDataList = AnomalyData::where('activity_id', $activity->id)
            ->orderBy('kode_daerah')
            ->orderBy('dsrt')
            ->orderBy('no_art')
            ->get();

        if ($anomalyDataList->isEmpty()) {
            return [];
        }

        // Group by KK (kode_daerah + dsrt)
        $groupedByKK = $anomalyDataList->groupBy(function ($item) {
            return $item->kode_daerah . '|' . $item->dsrt;
        });

        $cards = [];

        foreach ($groupedByKK as $kkKey => $artList) {
            // Get first item to get KK info
            $firstArt = $artList->first();

            // Get village code (10 digits) to find PJ mapping
            $villageCode = substr($firstArt->kode_daerah, 0, 10);
            $pjMapping = PjMapping::where('activity_id', $activity->id)
                ->where('village_code', $villageCode)
                ->first();

            // Build ART list with anomali details
            $artWithAnomalies = [];

            // Check if PODES format (no_art = 0)
            $isPODES = $firstArt->no_art === 0;

            if ($isPODES) {
                // PODES format: combine all anomalies into single entry
                $allAnomalies = [];
                $podesId = null;
                $podesLink = null;

                foreach ($artList as $art) {
                    // Collect anomaly codes
                    if (!empty($art->anomali)) {
                        $allAnomalies[] = $art->anomali;
                    }
                    if ($podesId === null) {
                        $podesId = $art->id;
                    }
                    if (!empty($art->link) && $podesLink === null) {
                        $podesLink = $art->link;
                    }
                }

                // Get combined anomaly details
                $combinedAnomalyString = implode(' ', $allAnomalies);
                $anomalyDetails = [];
                if (!empty($combinedAnomalyString)) {
                    $codes = array_unique(array_map('trim', explode(' ', $combinedAnomalyString)));
                    $anomalyDetails = Anomaly::where('activity_id', $activity->id)
                        ->whereIn('code', $codes)
                        ->get(['code', 'description', 'rule'])
                        ->toArray();

                    // Load check status for each code from the first PODES record
                    if ($podesId) {
                        $podesRecord = AnomalyData::find($podesId);
                        if ($podesRecord) {
                            $checks = $podesRecord->codeChecks()
                                ->whereIn('code', $codes)
                                ->pluck('checked', 'code')
                                ->toArray();

                            // Merge check status into anomaly details
                            foreach ($anomalyDetails as &$anomaly) {
                                $anomaly['checked'] = $checks[$anomaly['code']] ?? false;
                            }
                        }
                    }
                }

                $artWithAnomalies[] = [
                    'id' => $podesId,
                    'no_art' => 0,
                    'nama_art' => 'Anomali Desa-Level',
                    'link' => $podesLink,
                    'anomali_details' => $anomalyDetails,
                ];
            } else {
                // ART-level format: one entry per ART
                foreach ($artList as $art) {
                    $anomalyDetails = $art->getAnomalyDetails();

                    $artWithAnomalies[] = [
                        'id' => $art->id,
                        'no_art' => $art->no_art,
                        'nama_art' => $art->nama_art,
                        'link' => $art->link,
                        'anomali_details' => $anomalyDetails,
                    ];
                }
            }

            // Create card
            $cards[] = [
                'kode_daerah' => $firstArt->kode_daerah,
                'dsrt' => $firstArt->dsrt,
                'no_art' => $firstArt->no_art,
                'kecamatan' => $firstArt->kecamatan,
                'desa' => $firstArt->desa,
                'nama_krt' => $firstArt->nama_krt,
                'pj_name' => $pjMapping?->pj_name ?? null,
                'pj_code' => $pjMapping?->pj_code ?? null,
                'art_list' => $artWithAnomalies,
            ];
        }

        return $cards;
    }

    /**
     * Get anomaly statistics per PJ (unchecked vs checked counts)
     * Counts individual anomaly codes (not AnomalyData records)
     */
    protected function getAnomalyStatsByPj(Activity $activity): array
    {
        // Count anomaly codes per PJ from anomaly_code_checks table
        $stats = AnomalyData::where('anomaly_data.activity_id', $activity->id)
            ->leftJoin('pj_mappings', function($join) use ($activity) {
                $join->on(DB::raw('SUBSTRING(anomaly_data.kode_daerah, 1, 10)'), '=', 'pj_mappings.village_code')
                     ->where('pj_mappings.activity_id', $activity->id);
            })
            ->leftJoin('anomaly_code_checks', 'anomaly_data.id', '=', 'anomaly_code_checks.anomaly_data_id')
            ->selectRaw('
                COALESCE(pj_mappings.pj_name, "Belum ditentukan") as pj_name,
                COUNT(DISTINCT anomaly_code_checks.id) as total,
                COUNT(DISTINCT CASE WHEN anomaly_code_checks.checked = 1 THEN anomaly_code_checks.id END) as checked
            ')
            ->where(DB::raw('anomaly_data.anomali IS NOT NULL AND anomaly_data.anomali != ""'))
            ->groupBy('pj_name')
            ->orderBy('pj_name')
            ->get()
            ->map(function ($item) {
                $total = $item->total ?? 0;
                $checked = $item->checked ?? 0;
                return [
                    'pj_name' => $item->pj_name,
                    'unchecked' => $total - $checked,
                    'checked' => $checked,
                    'total' => $total,
                ];
            })
            ->toArray();

        return $stats;
    }

    /**
     * Get current anomaly code progress per PJ (API endpoint)
     * Returns fresh counts from database for accurate progress calculation
     */
    public function getAnomalyProgress(Activity $activity)
    {
        // Count total anomaly codes and checked codes per PJ
        $stats = AnomalyData::where('anomaly_data.activity_id', $activity->id)
            ->leftJoin('pj_mappings', function($join) use ($activity) {
                $join->on(DB::raw('SUBSTRING(anomaly_data.kode_daerah, 1, 10)'), '=', 'pj_mappings.village_code')
                     ->where('pj_mappings.activity_id', $activity->id);
            })
            ->leftJoin('anomaly_code_checks', 'anomaly_data.id', '=', 'anomaly_code_checks.anomaly_data_id')
            ->selectRaw('
                COALESCE(pj_mappings.pj_name, "Belum ditentukan") as pj_name,
                COUNT(DISTINCT anomaly_code_checks.id) as total,
                COUNT(DISTINCT CASE WHEN anomaly_code_checks.checked = 1 THEN anomaly_code_checks.id END) as checked
            ')
            ->where(DB::raw('anomaly_data.anomali IS NOT NULL AND anomaly_data.anomali != ""'))
            ->groupBy('pj_name')
            ->get()
            ->map(function ($item) {
                $total = $item->total ?? 0;
                $checked = $item->checked ?? 0;
                $percentage = $total > 0 ? round(($checked / $total) * 100) : 0;

                return [
                    'pj_name' => $item->pj_name,
                    'checked' => $checked,
                    'unchecked' => $total - $checked,
                    'total' => $total,
                    'percentage' => $percentage,
                ];
            })
            ->keyBy('pj_name');

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Toggle individual anomaly code check status
     */
    public function toggleCodeCheck(AnomalyData $anomalyData, Request $request)
    {
        try {
            $code = $request->input('code');
            $isChecked = $request->boolean('checked');

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code tidak ditemukan',
                ], 400);
            }

            // Find or create the code check record
            $codeCheck = $anomalyData->codeChecks()
                ->firstOrCreate(
                    ['code' => $code],
                    ['checked' => false]
                );

            // Prevent unchecking
            if ($codeCheck->checked && !$isChecked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode anomali ini sudah diperiksa dan tidak dapat diubah kembali.',
                    'checked' => true,
                ], 422);
            }

            // Update check status
            $codeCheck->update(['checked' => $isChecked]);

            return response()->json([
                'success' => true,
                'message' => 'Status kode anomali berhasil diperbarui.',
                'checked' => $codeCheck->checked,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get regency name from code
     */
    protected function getRegencyName(string $code): string
    {
        $regencies = [
            '9702' => 'Jayawijaya',
            '9705' => 'Mamberamo Tengah',
            '9706' => 'Yalimo',
        ];

        return $regencies[$code] ?? "Kabupaten $code";
    }
}
