<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Management Rapportage - {{ \Carbon\Carbon::now()->format('d-m-Y') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            body { padding: 0; background: white; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        .stat-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; page-break-inside: avoid; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 p-8 max-w-[210mm] mx-auto min-h-screen">

    <div class="no-print fixed top-4 right-4 flex gap-2">
        <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2 rounded-full font-bold shadow-lg hover:bg-slate-700 transition">🖨️ Print PDF</button>
        <button onclick="window.close()" class="bg-white text-slate-500 px-6 py-2 rounded-full font-bold shadow hover:bg-slate-50 transition">Sluiten</button>
    </div>

    <div class="flex justify-between items-start mb-10 border-b-2 border-slate-900 pb-6">
        <div>
            <h1 class="text-3xl font-black uppercase tracking-tight text-slate-900">Performance Report</h1>
            <p class="text-slate-500 font-medium mt-1">Reseller Pro Analytics</p>
        </div>
        <div class="text-right">
            <div class="font-bold text-lg">{{ Auth::user()->name }}</div>
            <div class="text-sm text-slate-400">Gegenereerd op: {{ $date->format('d-m-Y H:i') }}</div>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Financiële Gezondheid</h2>
        <div class="grid grid-cols-3 gap-6">
            <div class="stat-card bg-slate-900 text-white">
                <div class="text-xs font-bold uppercase opacity-60">Netto Resultaat</div>
                <div class="text-3xl font-black mt-1">€ {{ number_format($realizedProfit, 2, ',', '.') }}</div>
                <div class="text-[10px] mt-2 opacity-60">Gerealiseerde winst na kosten</div>
            </div>
            
            <div class="stat-card bg-white">
                <div class="text-xs font-bold uppercase text-slate-400">Return on Investment (ROI)</div>
                <div class="text-3xl font-black mt-1 text-indigo-600">{{ number_format($roi, 1) }}%</div>
                <div class="text-[10px] mt-2 text-slate-400">Winst per geïnvesteerde euro</div>
            </div>

            <div class="stat-card bg-white">
                <div class="text-xs font-bold uppercase text-slate-400">Gem. Winst / Item</div>
                <div class="text-3xl font-black mt-1 text-emerald-600">€ {{ number_format($avgProfitPerItem, 2, ',', '.') }}</div>
                <div class="text-[10px] mt-2 text-slate-400">Over {{ $itemsSold }} verkochte items</div>
            </div>
        </div>
    </div>

    <div class="mb-10">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Voorraad Waardering</h2>
        <div class="grid grid-cols-2 gap-6">
            <div class="stat-card bg-white flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold uppercase text-slate-400">Huidige Inkoopwaarde</div>
                    <div class="text-2xl font-black text-slate-800">€ {{ number_format($stockValue, 2, ',', '.') }}</div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black text-slate-200">{{ $itemsInStock }}</div>
                    <div class="text-[10px] font-bold uppercase text-slate-400">Items op voorraad</div>
                </div>
            </div>
            <div class="stat-card bg-white border-indigo-100 bg-indigo-50/50">
                <div class="text-xs font-bold uppercase text-indigo-400">Totale Investering (All-time)</div>
                <div class="text-2xl font-black text-indigo-900">€ {{ number_format($totalInvested, 2, ',', '.') }}</div>
                <div class="text-[10px] text-indigo-400 mt-1">Omzet tot nu toe: € {{ number_format($totalRevenue, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Laatste 15 Transacties</h2>
        <table class="w-full text-sm text-left border border-slate-200 rounded-lg overflow-hidden">
            <thead class="bg-slate-100 text-slate-500 text-[10px] uppercase font-bold">
                <tr>
                    <th class="px-4 py-3">Datum</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3 text-right">Inkoop*</th>
                    <th class="px-4 py-3 text-right">Verkoop</th>
                    <th class="px-4 py-3 text-right">Winst</th>
                    <th class="px-4 py-3 text-right">Marge</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach($recentSales as $item)
                    @php
                        $shippingShare = ($item->parcel && $item->parcel->items->count() > 0) ? $item->parcel->shipping_cost / $item->parcel->items->count() : 0;
                        $totalCost = $item->buy_price + $shippingShare;
                        $profit = $item->sell_price - $totalCost;
                        $margin = $item->sell_price > 0 ? ($profit / $item->sell_price) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $item->sold_date ? $item->sold_date->format('d-m-Y') : $item->updated_at->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 font-bold text-slate-700">
                            {{ Str::limit($item->name, 30) }}
                            <span class="block text-[10px] font-normal text-slate-400">{{ $item->brand }} - {{ $item->size }}</span>
                        </td>
                        <td class="px-4 py-2 text-right text-slate-500">€ {{ number_format($totalCost, 2) }}</td>
                        <td class="px-4 py-2 text-right font-bold">€ {{ number_format($item->sell_price, 2) }}</td>
                        <td class="px-4 py-2 text-right font-bold {{ $profit >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            € {{ number_format($profit, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right text-xs text-slate-400">{{ round($margin) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-[10px] text-slate-400 mt-2 italic">* Inkoop is inclusief geschatte verzendkosten per item.</p>
    </div>

    @if($oldStock->count() > 0)
    <div class="mt-8 page-break">
        <h2 class="text-xs font-bold uppercase tracking-widest text-red-500 mb-4 flex items-center gap-2">
            ⚠️ Aandacht Punten (Oude Voorraad)
        </h2>
        <div class="bg-red-50 border border-red-100 rounded-xl p-4">
            <table class="w-full text-sm text-left">
                <thead class="text-red-400 text-[10px] uppercase font-bold">
                    <tr>
                        <th class="pb-2">Item</th>
                        <th class="pb-2">Dagen op voorraad</th>
                        <th class="pb-2 text-right">Huidige Prijs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                    @foreach($oldStock as $item)
                        <tr>
                            <td class="py-2 font-medium text-red-900">{{ $item->name }}</td>
                            <td class="py-2 text-red-700">{{ $item->created_at->diffInDays(now()) }} dagen</td>
                            <td class="py-2 text-right text-red-900 font-bold">€ {{ number_format($item->sell_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mt-12 pt-6 border-t border-slate-200 text-center">
        <p class="text-xs text-slate-400">Reseller Pro Analytics © {{ date('Y') }} - {{ Auth::user()->email }}</p>
    </div>

    <script>
        // Auto-print prompt bij openen
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>