<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Data ophalen
        // We laden parcel.items mee om efficiënt te rekenen (voorkomt 100x database queries)
        $soldItems = Item::where('user_id', $userId)
            ->where('is_sold', true)
            ->with('parcel.items') 
            ->get();
            
        $unsoldItems = Item::where('user_id', $userId)
            ->where('is_sold', false)
            ->with('parcel')
            ->get();

        $allItems = Item::where('user_id', $userId)
            ->with('parcel.items')
            ->get();
            
        $parcels = Parcel::where('user_id', $userId)->get();

        $soldItemsWithPrice = $soldItems->filter(function ($item) {
            return !is_null($item->sell_price) && $item->sell_price > 0;
        });

        // 1. Financiële Stats
        $totalBuyCost = Item::where('user_id', $userId)->sum('buy_price');
        $totalShipping = $parcels->sum('shipping_cost');
        
        // Dit is je "Cash out": alles wat je ooit hebt uitgegeven
        $totalInvested = $totalBuyCost + $totalShipping;
        
        // Dit is je "Cash in": alles wat je hebt ontvangen
        $totalRevenue = $soldItemsWithPrice->sum('sell_price');
        
        // Gerealiseerde winst (alleen verkocht)
        $realizedProfit = $soldItemsWithPrice->sum(function ($item) {
            $buyPrice = $item->buy_price ?? 0;
            
            // Bereken aandeel verzendkosten voor dit specifieke item
            $shippingShare = 0;
            if ($item->parcel && $item->parcel->items->count() > 0) {
                $shippingShare = $item->parcel->shipping_cost / $item->parcel->items->count();
            }
            
            return $item->sell_price - $buyPrice - $shippingShare;
        });

        // Netto resultaat (cashflow): omzet - totale investering
        $netResult = $totalRevenue - $totalInvested;

        // Potentieel: Wat als je alles wat nu op voorraad ligt ook verkoopt?
        $potentialRevenue = $unsoldItems->whereNotNull('sell_price')->sum('sell_price'); 
        $potentialProfit = ($totalRevenue + $potentialRevenue) - $totalInvested;

        // Break-even: Hoeveel % van je totale investering heb je al terugverdiend met omzet?
        $breakEvenPercent = $totalInvested > 0 ? min(100, ($totalRevenue / $totalInvested) * 100) : 0;

        // 2. Operationele Stats
        $itemsSold = $soldItems->count();
        $itemsInStock = $unsoldItems->count();
        $totalParcels = $parcels->count();

        // Gemiddelde verkoopsnelheid
        $totalDays = 0;
        $countForDays = 0;
        foreach($soldItems as $item) {
            $created = Carbon::parse($item->created_at);
            $sold = $item->sold_date ? Carbon::parse($item->sold_date) : Carbon::parse($item->updated_at);
            $totalDays += $created->diffInDays($sold);
            $countForDays++;
        }
        $avgSellDays = $countForDays > 0 ? round($totalDays / $countForDays) : 0;

        // Winkeldochters (> 30 dagen op voorraad)
        $oldStockCount = 0;
        foreach($unsoldItems as $item) {
            if(Carbon::parse($item->created_at)->diffInDays(now()) > 30) {
                $oldStockCount++;
            }
        }

        // 3. Top Categorieën
        $categories = [];
        foreach($soldItemsWithPrice as $item) {
            $cat = $item->category ?: 'Overige';
            if(!isset($categories[$cat])) {
                $categories[$cat] = ['name' => $cat, 'sold' => 0, 'profit' => 0];
            }
            
            $shippingShare = 0;
            if ($item->parcel && $item->parcel->items->count() > 0) {
                $shippingShare = $item->parcel->shipping_cost / $item->parcel->items->count();
            }
            
            $profit = $item->sell_price - $item->buy_price - $shippingShare;
            
            $categories[$cat]['sold']++;
            $categories[$cat]['profit'] += $profit;
        }
        usort($categories, fn($a, $b) => $b['sold'] <=> $a['sold']);
        $topCategories = array_slice($categories, 0, 5);

        // 3b. Operationele grafiekdata (verkochte items) - dag/week/maand
        $soldByDay = $soldItems->groupBy(function ($item) {
            $dateCheck = $item->sold_date ? Carbon::parse($item->sold_date) : Carbon::parse($item->updated_at);
            return $dateCheck->format('Y-m-d');
        });

        $soldByWeek = $soldItems->groupBy(function ($item) {
            $dateCheck = $item->sold_date ? Carbon::parse($item->sold_date) : Carbon::parse($item->updated_at);
            return $dateCheck->format('o-W');
        });

        $soldByMonth = $soldItems->groupBy(function ($item) {
            $dateCheck = $item->sold_date ? Carbon::parse($item->sold_date) : Carbon::parse($item->updated_at);
            return $dateCheck->format('Y-m');
        });

        $operationalChartData = [
            'daily' => ['labels' => [], 'values' => []],
            'weekly' => ['labels' => [], 'values' => []],
            'monthly' => ['labels' => [], 'values' => []],
        ];

        // Dag (laatste 30 dagen)
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $operationalChartData['daily']['labels'][] = $date->format('d M');
            $operationalChartData['daily']['values'][] = $soldByDay->get($key, collect())->count();
        }

        // Week (laatste 12 weken)
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subWeeks($i);
            $key = $date->format('o-W');
            $weekLabel = 'W' . $date->format('W');
            $operationalChartData['weekly']['labels'][] = $weekLabel;
            $operationalChartData['weekly']['values'][] = $soldByWeek->get($key, collect())->count();
        }

        // Maand (laatste 12 maanden)
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $operationalChartData['monthly']['labels'][] = $date->format('M');
            $operationalChartData['monthly']['values'][] = $soldByMonth->get($key, collect())->count();
        }

        // 4. Chart Data (Laatste 6 maanden) - netto resultaat gebaseerd op omzet vs investering
        $chartData = [];
        $maxRevenue = 0;
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $label = $date->format('M');
            
            $monthSoldItems = $soldItemsWithPrice->filter(function($item) use ($monthKey) {
                $dateCheck = $item->sold_date ? Carbon::parse($item->sold_date) : Carbon::parse($item->updated_at);
                return $dateCheck->format('Y-m') === $monthKey;
            });

            $monthInvestItems = $allItems->filter(function($item) use ($monthKey) {
                return Carbon::parse($item->created_at)->format('Y-m') === $monthKey;
            });

            $revenue = $monthSoldItems->sum('sell_price');
            
            $investment = $monthInvestItems->sum(function ($item) {
                $shippingShare = 0;
                if ($item->parcel && $item->parcel->items->count() > 0) {
                    $shippingShare = $item->parcel->shipping_cost / $item->parcel->items->count();
                }
                return ($item->buy_price ?? 0) + $shippingShare;
            });
            
            $net = $revenue - $investment;
            $margin = $investment > 0 ? round(($net / $investment) * 100, 1) : 0;

            if($revenue > $maxRevenue) $maxRevenue = $revenue;

            $chartData[] = [
                'label' => $label,
                'revenue' => $revenue,
                'profit' => $net,
                'margin' => $margin
            ];
        }

        return view('dashboard', compact(
            'totalInvested', 'totalRevenue', 'realizedProfit', 'netResult', 'potentialProfit', 'breakEvenPercent',
            'itemsSold', 'itemsInStock', 'totalParcels', 'avgSellDays', 'oldStockCount',
            'topCategories', 'chartData', 'maxRevenue', 'operationalChartData'
        ));
    }

    public function toggleLayout(Request $request)
    {
        $user = Auth::user();
        $user->layout = ($user->layout ?? 'top') === 'sidebar' ? 'top' : 'sidebar';
        $user->save();

        return redirect()->back();
    }

    public function report()
    {
        $userId = Auth::id();
        $date = Carbon::now();

        // 1. Basis Data
        $soldItems = Item::where('user_id', $userId)->where('is_sold', true)->with('parcel.items')->latest('sold_date')->get();
        $unsoldItems = Item::where('user_id', $userId)->where('is_sold', false)->get();
        $parcels = Parcel::where('user_id', $userId)->get();

        // 2. Financiële Totalen
        $soldItemsWithPrice = $soldItems->filter(fn($i) => $i->sell_price > 0);
        
        $totalRevenue = $soldItemsWithPrice->sum('sell_price');
        $totalBuyCost = Item::where('user_id', $userId)->sum('buy_price');
        $totalShipping = $parcels->sum('shipping_cost');
        $totalInvested = $totalBuyCost + $totalShipping;
        
        // Winst berekening (Gerealiseerd)
        $realizedProfit = $soldItemsWithPrice->sum(function ($item) {
            $shippingShare = ($item->parcel && $item->parcel->items->count() > 0) 
                ? $item->parcel->shipping_cost / $item->parcel->items->count() 
                : 0;
            return $item->sell_price - $item->buy_price - $shippingShare;
        });

        // ROI (Return on Investment)
        $costOfGoodsSold = $soldItemsWithPrice->sum('buy_price') + 
                           $soldItemsWithPrice->sum(fn($i) => ($i->parcel ? $i->parcel->shipping_cost / max(1, $i->parcel->items->count()) : 0));
        
        $roi = $costOfGoodsSold > 0 ? ($realizedProfit / $costOfGoodsSold) * 100 : 0;
        $avgProfitPerItem = $soldItemsWithPrice->count() > 0 ? $realizedProfit / $soldItemsWithPrice->count() : 0;

        // 3. Voorraad Waarde (Huidig)
        $stockValue = $unsoldItems->sum('buy_price');
        $potentialRevenue = $unsoldItems->sum('sell_price'); // Verwachte verkoop

        // 4. Recente Verkopen (Tabel data) - Laatste 15
        $recentSales = $soldItemsWithPrice->take(15);

        // 5. Winkeldochters (Oudste onverkochte items)
        $oldStock = $unsoldItems->filter(fn($i) => $i->created_at->diffInDays(now()) > 30)
                                ->sortBy('created_at')
                                ->take(5);

        // FIX: 'potentialRevenue' toegevoegd aan de output!
        return view('report', [
            'date' => $date,
            'totalRevenue' => $totalRevenue,
            'totalInvested' => $totalInvested,
            'realizedProfit' => $realizedProfit,
            'roi' => $roi,
            'avgProfitPerItem' => $avgProfitPerItem,
            'stockValue' => $stockValue,
            'recentSales' => $recentSales,
            'oldStock' => $oldStock,
            'itemsSold' => $soldItems->count(),
            'itemsInStock' => $unsoldItems->count(),
            'potentialRevenue' => $potentialRevenue, // <--- Deze miste je
        ]);
    }
}