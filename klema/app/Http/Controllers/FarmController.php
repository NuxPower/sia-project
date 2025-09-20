<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\FarmPoint;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function index()
    {
        $farms = auth()->user()->farms()->with(['weatherData' => function($query) {
            $query->latest('recorded_at')->limit(1);
        }, 'alerts' => function($query) {
            $query->where('resolved', false);
        }])->get();
        
        return view('farms.index', compact('farms'));
    }

    public function create()
    {
        return view('farms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $farm = Farm::create([
            'user_id' => auth()->id(),
            'farm_name' => $validated['farm_name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return redirect()->route('farms.show', $farm->farm_id)
            ->with('success', 'Farm created successfully!');
    }

    public function show(Farm $farm)
    {
        $this->authorize('view', $farm);
        
        $farm->load([
            'weatherData' => function($query) {
                $query->latest('recorded_at')->limit(10);
            },
            'farmPoints',
            'alerts' => function($query) {
                $query->latest('issued_at');
            }
        ]);
        
        return view('farms.show', compact('farm'));
    }

    public function edit(Farm $farm)
    {
        $this->authorize('update', $farm);
        return view('farms.edit', compact('farm'));
    }

    public function update(Request $request, Farm $farm)
    {
        $this->authorize('update', $farm);
        
        $validated = $request->validate([
            'farm_name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $farm->update($validated);

        return redirect()->route('farms.show', $farm->farm_id)
            ->with('success', 'Farm updated successfully!');
    }

    public function destroy(Farm $farm)
    {
        $this->authorize('delete', $farm);
        
        $farm->delete();

        return redirect()->route('farms.index')
            ->with('success', 'Farm deleted successfully!');
    }

    public function addPoint(Request $request, Farm $farm)
    {
        $this->authorize('update', $farm);
        
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'point_type' => 'nullable|string|max:50',
        ]);

        $point = $farm->farmPoints()->create([
            'label' => $validated['label'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'point_type' => $validated['point_type'] ?? 'general',
        ]);

        return response()->json([
            'success' => true,
            'point' => $point,
            'message' => 'Point added successfully!'
        ]);
    }
}
