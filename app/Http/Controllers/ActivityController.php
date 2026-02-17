<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\PjMapping;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $activities = Activity::orderBy('display_name')->get();

        // Add metrics to each activity (target from pj_mappings, metrics from monitoring_data)
        $activities->each(function ($activity) {
            // Target from pj_mappings
            $targetData = $activity->pjMappings()
                ->selectRaw('SUM(target) as total_target')
                ->first();

            // Metrics from monitoring_data
            $metricsData = $activity->monitoringData()
                ->selectRaw('
                    SUM(open) as total_open,
                    SUM(submitted) as total_submitted,
                    SUM(approved) as total_approved,
                    SUM(rejected) as total_rejected,
                    COUNT(DISTINCT village_code) as village_count
                ')
                ->first();

            $activity->total_target = $targetData->total_target ?? 0;
            $activity->total_approved = $metricsData->total_approved ?? 0;
            $activity->village_count = $metricsData->village_count ?? 0;
            $activity->percentage = $activity->total_target > 0
                ? round(($metricsData->total_approved ?? 0) / $activity->total_target * 100, 2)
                : 0;
        });

        return view('admin.activities.index', compact('activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.activities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:activities,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Activity::create($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Kegiatan berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity): View
    {
        $pjMappings = $activity->pjMappings()->get();
        return view('admin.activities.edit', compact('activity', 'pjMappings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:activities,name,' . $activity->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $activity->update($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Kegiatan berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        $name = $activity->display_name;
        $activity->delete();

        return redirect()->route('activities.index')
            ->with('success', "Kegiatan '{$name}' berhasil dihapus!");
    }

    /**
     * Update PJ Mapping data
     */
    public function updatePjMapping(Request $request, Activity $activity, PjMapping $pjMapping): RedirectResponse
    {
        // Verify that this PJ mapping belongs to this activity
        if ($pjMapping->activity_id !== $activity->id) {
            return back()->with('error', 'PJ Mapping tidak ditemukan!');
        }

        $validated = $request->validate([
            'pj_name' => 'nullable|string|max:255',
            'desa_nama' => 'nullable|string|max:255',
            'target' => 'required|integer|min:0',
        ]);

        $pjMapping->update($validated);

        return back()->with('success', 'PJ Mapping berhasil diupdate!');
    }

    /**
     * Batch update PJ Mappings
     */
    public function batchUpdatePjMapping(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'selected_ids' => 'required|string',
            'pj_name' => 'nullable|string|max:255',
            'target' => 'nullable|integer|min:0',
        ]);

        $selectedIds = array_filter(explode(',', $request->input('selected_ids')));

        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada baris yang dipilih!');
        }

        // Update hanya field yang tidak kosong
        $updateData = [];

        if ($request->filled('pj_name')) {
            $updateData['pj_name'] = $request->input('pj_name');
        }

        if ($request->filled('target')) {
            $updateData['target'] = $request->input('target');
        }

        if (empty($updateData)) {
            return back()->with('error', 'Silakan isi minimal satu field untuk di-update!');
        }

        // Update semua selected PJ mappings
        $updated = PjMapping::where('activity_id', $activity->id)
            ->whereIn('id', $selectedIds)
            ->update($updateData);

        $message = "{$updated} PJ Mapping berhasil diupdate!";

        return back()->with('success', $message);
    }
}
