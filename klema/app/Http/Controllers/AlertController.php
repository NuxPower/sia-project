<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Farm;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = Alert::whereHas('farm', function($query) {
            $query->where('user_id', auth()->id());
        })->with('farm')
          ->orderBy('issued_at', 'desc')
          ->paginate(20);
        
        return view('alerts.index', compact('alerts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,farm_id',
            'alert_type' => 'required|string|max:50',
            'message' => 'required|string',
        ]);

        $farm = Farm::findOrFail($validated['farm_id']);
        $this->authorize('update', $farm);

        $alert = Alert::create([
            'farm_id' => $validated['farm_id'],
            'alert_type' => $validated['alert_type'],
            'message' => $validated['message'],
            'issued_at' => now(),
            'resolved' => false,
        ]);

        return response()->json([
            'success' => true,
            'alert' => $alert->load('farm'),
            'message' => 'Alert created successfully!'
        ]);
    }

    public function resolve(Alert $alert)
    {
        $this->authorize('update', $alert->farm);
        
        $alert->update(['resolved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully!'
        ]);
    }

    public function destroy(Alert $alert)
    {
        $this->authorize('delete', $alert->farm);
        
        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully!'
        ]);
    }
}