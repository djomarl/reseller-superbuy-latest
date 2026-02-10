<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SuperbuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperbuyController extends Controller
{
    protected $superbuyService;

    public function __construct(SuperbuyService $superbuyService)
    {
        $this->superbuyService = $superbuyService;
    }

    public function index()
    {
        return view('superbuy.index');
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'curl' => 'required|string',
            'pages' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $orders = $this->superbuyService->getOrdersFromCurl(
                $request->input('curl'),
                $request->input('pages', 3)
            );

            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error("Superbuy Fetch Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Deze functie wordt aangeroepen door de Chrome Extensie.
     * Hij gebruikt een Secret Check i.p.v. sessie cookies.
     */
    public function importFromExtension(Request $request)
    {
        $receivedSecret = $request->input('secret');

        if (!$receivedSecret) {
            return response()->json(['error' => 'Geen secret ontvangen.'], 401);
        }

        // Zoek de gebruiker die bij deze secret hoort
        $user = User::where('sync_secret', $receivedSecret)->first();

        if (!$user) {
            Log::warning("Superbuy Sync: Ongeldige secret geprobeerd: '{$receivedSecret}'");
            return response()->json(['error' => 'Ongeldige Secret Key.'], 401);
        }

        $items = $request->input('items');
        
        if (!$items || !is_array($items)) {
            return response()->json(['error' => 'Geen items ontvangen'], 400);
        }

        $count = 0;
        foreach ($items as $itemData) {
            $orderNo = $itemData['orderNo'] ?? 'UNKNOWN';
            try {
                $this->superbuyService->importItem($user, $itemData, $orderNo);
                $count++;
            } catch (\Exception $e) {
                Log::error("Import error {$orderNo}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true, 
            'message' => "$count items opgeslagen voor " . $user->name
        ]);
    }

/**
     * Checkt welke ordernummers al in de database staan.
     */
    public function checkExistingItems(Request $request)
    {
        $receivedSecret = $request->input('secret');

        if (!$receivedSecret) {
            return response()->json(['error' => 'Geen secret ontvangen.'], 401);
        }

        // Zoek de gebruiker die bij deze secret hoort
        $user = User::where('sync_secret', $receivedSecret)->first();

        if (!$user) {
            Log::warning("Superbuy Sync: Ongeldige secret geprobeerd (check): '{$receivedSecret}'");
            return response()->json(['error' => 'Ongeldige Secret Key.'], 401);
        }

        $orderNos = $request->input('order_nos');

        if (!$orderNos || !is_array($orderNos)) {
            return response()->json(['existing' => []]);
        }

        // 2. Zoek in de database welke van deze nummers al bestaan
        // We zoeken op de kolom 'order_nmr' (zoals in je database migratie)
        // En OPTIONEEL: filter op user_id, zodat gebruikers alleen hun eigen items zien
        $existing = \App\Models\Item::where('user_id', $user->id)
                    ->whereIn('order_nmr', $orderNos)
                    ->pluck('order_nmr')
                    ->toArray();

        return response()->json([
            'existing' => $existing
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $count = 0;
        /** @var User $user */
        $user = Auth::user();

        foreach ($request->input('items') as $itemData) {
            $orderNo = $itemData['orderNo'] ?? 'UNKNOWN';

            try {
                $this->superbuyService->importItem($user, $itemData, $orderNo);
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to import item: " . ($itemData['title'] ?? 'N/A'));
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$count items succesfully imported!"
        ]);
    }
}