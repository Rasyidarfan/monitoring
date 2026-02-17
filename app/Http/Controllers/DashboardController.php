<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MonitoringData;
use App\Models\PjMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display dashboard overview - All activities
     */
    public function index(Request $request): View
    {
        // Get all activities with aggregated metrics
        $activities = $this->getAllActivitiesWithMetrics();

        // Calculate grand totals
        $grandTotals = $this->calculateGrandTotals($activities);

        return view('dashboard.overview', [
            'activities' => $activities,
            'grandTotals' => $grandTotals,
        ]);
    }

    /**
     * Get dashboard metrics (target from pj_mappings, metrics from monitoring_data)
     */
    protected function getDashboardMetrics(?Activity $activity): array
    {
        if (!$activity) {
            return [
                'total_target' => 0,
                'total_open' => 0,
                'total_submitted' => 0,
                'total_approved' => 0,
                'total_rejected' => 0,
            ];
        }

        // Target from pj_mappings
        $targetData = \App\Models\PjMapping::where('activity_id', $activity->id)
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

        return [
            'total_target' => $targetData->total_target ?? 0,
            'total_open' => $metricsData->total_open ?? 0,
            'total_submitted' => $metricsData->total_submitted ?? 0,
            'total_approved' => $metricsData->total_approved ?? 0,
            'total_rejected' => $metricsData->total_rejected ?? 0,
        ];
    }

    /**
     * Get data per regency (target from pj_mappings via monitoring_data)
     */
    protected function getRegencyData(?Activity $activity): array
    {
        if (!$activity) {
            return [];
        }

        $data = MonitoringData::where('activity_id', $activity->id)
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

    /**
     * Get all activities with aggregated metrics (target from pj_mappings)
     */
    protected function getAllActivitiesWithMetrics()
    {
        $activities = Activity::orderBy('display_name')->get();

        return $activities->map(function ($activity) {
            // Target from pj_mappings
            $targetData = \App\Models\PjMapping::where('activity_id', $activity->id)
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

            $activity->total_target = $targetData->total_target ?? 0;
            $activity->total_open = $metricsData->total_open ?? 0;
            $activity->total_submitted = $metricsData->total_submitted ?? 0;
            $activity->total_approved = $metricsData->total_approved ?? 0;
            $activity->total_rejected = $metricsData->total_rejected ?? 0;

            // Calculate percentage
            $activity->percentage = $activity->total_target > 0
                ? round(($activity->total_approved / $activity->total_target) * 100, 2)
                : 0;

            return $activity;
        });
    }

    /**
     * Calculate grand totals across all activities
     */
    protected function calculateGrandTotals($activities): array
    {
        $totals = [
            'total_target' => $activities->sum('total_target'),
            'total_open' => $activities->sum('total_open'),
            'total_submitted' => $activities->sum('total_submitted'),
            'total_approved' => $activities->sum('total_approved'),
            'total_rejected' => $activities->sum('total_rejected'),
        ];

        $totals['percentage'] = $totals['total_target'] > 0
            ? round(($totals['total_approved'] / $totals['total_target']) * 100, 2)
            : 0;

        return $totals;
    }
}
