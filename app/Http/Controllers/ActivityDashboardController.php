<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MonitoringData;
use App\Models\PjMapping;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // Get all village data
        $villageData = $this->getVillageData($activity);

        // Get overall metrics
        $metrics = $this->getActivityMetrics($activity);

        return view('activities.dashboard', [
            'activity' => $activity,
            'regencyData' => $regencyData,
            'pjData' => $pjData,
            'villageData' => $villageData,
            'metrics' => $metrics,
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

        // Sort by total_target descending
        usort($data, function ($a, $b) {
            return $b['total_target'] <=> $a['total_target'];
        });

        return $data;
    }

    /**
     * Get all village data (target from pj_mappings)
     */
    protected function getVillageData(Activity $activity): array
    {
        $data = MonitoringData::where('monitoring_data.activity_id', $activity->id)
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
            ->orderBy('monitoring_data.regency_code')
            ->orderBy('monitoring_data.village_name')
            ->get()
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
                    'open' => $item->open,
                    'submitted' => $item->submitted,
                    'approved' => $item->approved,
                    'rejected' => $item->rejected,
                    'pct_open' => round(($item->open / $total) * 100, 2),
                    'pct_submitted' => round(($item->submitted / $total) * 100, 2),
                    'pct_approved' => round(($item->approved / $total) * 100, 2),
                    'pct_rejected' => round(($item->rejected / $total) * 100, 2),
                ];
            })
            ->toArray();

        return $data;
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
