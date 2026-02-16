<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MonitoringData;
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
     * Get activity overall metrics
     */
    protected function getActivityMetrics(Activity $activity): array
    {
        $totals = MonitoringData::where('activity_id', $activity->id)
            ->selectRaw('
                SUM(target) as total_target,
                SUM(open) as total_open,
                SUM(submitted) as total_submitted,
                SUM(approved) as total_approved,
                SUM(rejected) as total_rejected
            ')
            ->first();

        $total = $totals->total_target ?? 0;

        return [
            'total_target' => $totals->total_target ?? 0,
            'total_open' => $totals->total_open ?? 0,
            'total_submitted' => $totals->total_submitted ?? 0,
            'total_approved' => $totals->total_approved ?? 0,
            'total_rejected' => $totals->total_rejected ?? 0,
            'pct_open' => $total > 0 ? round(($totals->total_open / $total) * 100, 2) : 0,
            'pct_submitted' => $total > 0 ? round(($totals->total_submitted / $total) * 100, 2) : 0,
            'pct_approved' => $total > 0 ? round(($totals->total_approved / $total) * 100, 2) : 0,
            'pct_rejected' => $total > 0 ? round(($totals->total_rejected / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get data per regency
     */
    protected function getRegencyData(Activity $activity): array
    {
        $data = MonitoringData::where('activity_id', $activity->id)
            ->selectRaw('
                regency_code,
                SUM(target) as total_target,
                SUM(open) as total_open,
                SUM(submitted) as total_submitted,
                SUM(approved) as total_approved,
                SUM(rejected) as total_rejected
            ')
            ->groupBy('regency_code')
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
        $data = MonitoringData::where('activity_id', $activity->id)
            ->whereNotNull('pj_name')
            ->selectRaw('
                pj_name,
                SUM(target) as total_target,
                SUM(open) as total_open,
                SUM(submitted) as total_submitted,
                SUM(approved) as total_approved,
                SUM(rejected) as total_rejected,
                COUNT(DISTINCT village_code) as village_count
            ')
            ->groupBy('pj_name')
            ->get()
            ->map(function ($item) {
                $total = $item->total_target > 0 ? $item->total_target : 1;
                return [
                    'pj_name' => $item->pj_name,
                    'village_count' => $item->village_count,
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
            ->sortByDesc('total_target')
            ->values()
            ->toArray();

        return $data;
    }

    /**
     * Get all village data
     */
    protected function getVillageData(Activity $activity): array
    {
        $data = MonitoringData::where('activity_id', $activity->id)
            ->orderBy('regency_code')
            ->orderBy('village_name')
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
                    'target' => $item->target,
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
