<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Services\SuperbuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParcelController extends Controller
{
    public function index()
    {
        $parcels = Parcel::where('user_id', Auth::id())
            ->withCount('items') // Telt automatisch hoeveel items erin zitten
            ->with(['items' => function ($query) {
                $query->select('id', 'parcel_id', 'name', 'brand', 'size', 'buy_price', 'sell_price', 'status');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('parcels.index', compact('parcels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parcel_no' => 'required|string|max:255',
            'tracking_code' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $parcel = new Parcel($validated);
        $parcel->user_id = Auth::id();
        $parcel->status = 'prep';
        $parcel->save();

        return redirect()->back()->with('success', 'Pakket aangemaakt!');
    }

    public function update(Request $request, Parcel $parcel)
    {
        if ($parcel->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'parcel_no' => 'sometimes|required|string|max:255',
            'tracking_code' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $parcel->update($validated);
        return redirect()->back()->with('success', 'Pakket geüpdatet');
    }

    public function destroy(Parcel $parcel)
    {
        if ($parcel->user_id !== Auth::id()) abort(403);
        
        // Zet items die in dit pakket zaten weer op parcel_id = null
        $parcel->items()->update(['parcel_id' => null]);
        $parcel->delete();
        
        return redirect()->back()->with('success', 'Pakket verwijderd');
    }

    public function importSuperbuy(SuperbuyService $service)
    {
        try {
            $count = $service->syncParcels(Auth::id());
            return redirect()->back()->with('success', "$count pakketten geïmporteerd");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Superbuy import mislukt: ' . $e->getMessage());
        }
    }
}