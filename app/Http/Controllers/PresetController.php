<?php

namespace App\Http\Controllers;

use App\Models\ItemTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresetController extends Controller
{
    public function index()
    {
        $templates = ItemTemplate::where('user_id', Auth::id())->get();
        return view('presets.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'brand' => 'nullable',
            'size' => 'nullable',
            'category' => 'nullable',
            'default_buy_price' => 'nullable',
            'default_sell_price' => 'nullable',
            'image_url' => 'nullable',
            'default_qc_link' => 'nullable'
        ]);

        $request->user()->itemTemplates()->create($validated);
        return redirect()->back()->with('success', 'Preset aangemaakt');
    }

    public function update(Request $request, ItemTemplate $preset)
    {
        if ($preset->user_id !== Auth::id()) abort(403);
        $preset->update($request->all());
        return redirect()->back()->with('success', 'Preset geüpdatet');
    }

    public function destroy(ItemTemplate $preset)
    {
        if ($preset->user_id !== Auth::id()) abort(403);
        $preset->delete();
        return redirect()->back();
    }
}