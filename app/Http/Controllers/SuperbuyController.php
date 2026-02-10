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
        // 1. Haal het wachtwoord op uit de config (die weer uit .env komt)
        $secret = config('services.superbuy.secret');
        $receivedSecret = $request->input('secret');

        // DEBUG: Als dit leeg is, moet je 'php artisan config:clear' draaien!
        if ($receivedSecret !== $secret) {
             Log::error("Superbuy Sync Mismatch! Ontvangen: '{$receivedSecret}' | Verwacht: '{$secret}'");
             return response()->json(['error' => 'Geheim wachtwoord onjuist! Check logs.'], 401);
        }

        // 2. We pakken de eerste user (Admin) omdat de extensie niet ingelogd is
        $user = User::first(); 
        
        if (!$user) {
            return response()->json(['error' => 'Geen gebruikers gevonden in database.'], 500);
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
            'message' => "$count items opgeslagen!"
        ]);
    }

/**
     * Checkt welke ordernummers al in de database staan.
     */
    public function checkExistingItems(Request $request)
    {
        // 1. Secret Check (Dezelfde beveiliging als bij import)
        $secret = config('services.superbuy.secret');
        if ($request->input('secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orderNos = $request->input('order_nos');

        if (!$orderNos || !is_array($orderNos)) {
            return response()->json(['existing' => []]);
        }

        // 2. Zoek in de database welke van deze nummers al bestaan
        // We zoeken op de kolom 'order_nmr' (zoals in je database migratie)
        $existing = \App\Models\Item::whereIn('order_nmr', $orderNos)
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