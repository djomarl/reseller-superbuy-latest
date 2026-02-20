<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemTemplate;
use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    /**
     * Toon de export/import pagina.
     */
    public function index()
    {
        return view('export.index');
    }

    /**
     * Exporteer alle data van de ingelogde user als JSON download.
     */
    public function export()
    {
        $user = Auth::user();

        $data = [
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'app'         => 'Reseller Pro',
                'version'     => '1.0',
                'user'        => $user->name,
            ],
            'items'          => Item::where('user_id', $user->id)->get()->map(function ($item) {
                return [
                    'item_no'     => $item->item_no,
                    'order_nmr'   => $item->order_nmr,
                    'name'        => $item->name,
                    'brand'       => $item->brand,
                    'size'        => $item->size,
                    'category'    => $item->category,
                    'buy_price'   => $item->buy_price,
                    'sell_price'  => $item->sell_price,
                    'is_sold'     => $item->is_sold,
                    'sold_date'   => $item->sold_date?->toDateString(),
                    'status'      => $item->status,
                    'image_url'   => $item->image_url,
                    'qc_photos'   => $item->qc_photos,
                    'source_link' => $item->source_link,
                    'notes'       => $item->notes,
                    'parcel_no'   => $item->parcel?->parcel_no,
                ];
            })->toArray(),
            'parcels'        => Parcel::where('user_id', $user->id)->get()->map(function ($parcel) {
                return [
                    'parcel_no'     => $parcel->parcel_no,
                    'tracking_code' => $parcel->tracking_code,
                    'description'   => $parcel->description,
                    'shipping_cost' => $parcel->shipping_cost,
                    'status'        => $parcel->status,
                ];
            })->toArray(),
            'item_templates' => ItemTemplate::where('user_id', $user->id)->get()->map(function ($tpl) {
                return [
                    'name'              => $tpl->name,
                    'brand'             => $tpl->brand,
                    'size'              => $tpl->size,
                    'category'          => $tpl->category,
                    'default_buy_price' => $tpl->default_buy_price,
                    'default_sell_price'=> $tpl->default_sell_price,
                    'default_qc_link'   => $tpl->default_qc_link,
                    'image_url'         => $tpl->image_url,
                ];
            })->toArray(),
            'user_settings'  => DB::table('user_settings')->where('user_id', $user->id)->first([
                'general_costs', 'exchange_rate', 'profit_goal'
            ]),
        ];

        $filename = 'reseller-export-' . now()->format('Y-m-d_His') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Importeer data vanuit een geüpload JSON bestand.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt|max:10240',
        ]);

        $json = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($json, true);

        if (!$data || !isset($data['meta'])) {
            return back()->with('error', 'Ongeldig export bestand. Controleer of het een geldig Reseller Pro export is.');
        }

        $user = Auth::user();
        $stats = ['items' => 0, 'parcels' => 0, 'templates' => 0, 'settings' => false, 'skipped' => 0];

        // 1. Importeer parcels eerst (items hebben een relatie)
        if (!empty($data['parcels'])) {
            foreach ($data['parcels'] as $parcelData) {
                $existing = Parcel::where('user_id', $user->id)
                    ->where('parcel_no', $parcelData['parcel_no'])
                    ->first();

                if ($existing) {
                    $existing->update($parcelData);
                } else {
                    Parcel::create(array_merge($parcelData, ['user_id' => $user->id]));
                    $stats['parcels']++;
                }
            }
        }

        // 2. Importeer items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $itemData) {
                // Check of item al bestaat op basis van item_no + order_nmr
                $existing = Item::where('user_id', $user->id)
                    ->where('item_no', $itemData['item_no'])
                    ->where('order_nmr', $itemData['order_nmr'] ?? null)
                    ->first();

                if ($existing) {
                    $stats['skipped']++;
                    continue; // Skip bestaande items
                }

                // Zoek parcel_id op basis van parcel_no
                $parcelId = null;
                if (!empty($itemData['parcel_no'])) {
                    $parcel = Parcel::where('user_id', $user->id)
                        ->where('parcel_no', $itemData['parcel_no'])
                        ->first();
                    $parcelId = $parcel?->id;
                }

                unset($itemData['parcel_no']);
                Item::create(array_merge($itemData, [
                    'user_id'  => $user->id,
                    'parcel_id'=> $parcelId,
                ]));
                $stats['items']++;
            }
        }

        // 3. Importeer templates
        if (!empty($data['item_templates'])) {
            foreach ($data['item_templates'] as $tplData) {
                $existing = ItemTemplate::where('user_id', $user->id)
                    ->where('name', $tplData['name'])
                    ->first();

                if (!$existing) {
                    ItemTemplate::create(array_merge($tplData, ['user_id' => $user->id]));
                    $stats['templates']++;
                }
            }
        }

        // 4. Importeer settings
        if (!empty($data['user_settings'])) {
            $settingsData = (array) $data['user_settings'];
            $existing = DB::table('user_settings')->where('user_id', $user->id)->first();

            if ($existing) {
                DB::table('user_settings')->where('user_id', $user->id)->update($settingsData);
            } else {
                DB::table('user_settings')->insert(array_merge($settingsData, ['user_id' => $user->id]));
            }
            $stats['settings'] = true;
        }

        $message = "Import voltooid! {$stats['items']} items, {$stats['parcels']} parcels, {$stats['templates']} templates toegevoegd.";
        if ($stats['skipped'] > 0) {
            $message .= " {$stats['skipped']} items overgeslagen (bestond al).";
        }
        if ($stats['settings']) {
            $message .= " Settings bijgewerkt.";
        }

        return back()->with('success', $message);
    }
}
