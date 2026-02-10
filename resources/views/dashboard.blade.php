<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* Shimmer (Glans over het glas) */
        @keyframes shimmer-fast {
            0% { transform: translateX(-150%) skewX(-20deg); }
            100% { transform: translateX(200%) skewX(-20deg); }
        }
        
        /* Aurora (Bewegende achtergrond kleuren) */
        @keyframes aurora {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Float (Zwevend effect voor tekst/iconen) */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }

        .animate-shimmer-fast { animation: shimmer-fast 3s infinite ease-in-out; }
        .animate-aurora { background-size: 200% 200%; animation: aurora 10s ease infinite; }
        .animate-float { animation: float 4s ease-in-out infinite; }
        
        .theme-profit { --shadow-color: 16, 185, 129; } /* Emerald */
        .theme-loss { --shadow-color: 239, 68, 68; }   /* Red */
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ dashboardView: 'financial' }">
        
        <div class="glass-card p-6 rounded-3xl shadow-sm relative overflow-hidden mb-8 bg-white border border-slate-200">
            <div class="flex justify-between items-end mb-3 relative z-10">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Break-even Status</h3>
                    <div class="flex items-baseline gap-2">
                        <div class="text-3xl font-heading font-black text-slate-800 tracking-tight">{{ number_format($breakEvenPercent, 0) }}<span class="text-lg text-slate-400 ml-1">%</span></div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Omzet / Investering</div>
                    <div class="font-bold text-slate-600">€ {{ number_format($totalRevenue, 2, ',', '.') }} / € {{ number_format($totalInvested, 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden relative z-10 shadow-inner">
                <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 transition-all duration-1000 ease-out relative" style="width: {{ $breakEvenPercent }}%">
                    <div class="absolute top-0 left-0 w-full h-full bg-white/20 animate-[pulse_2s_infinite]"></div>
                </div>
            </div>
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl"></div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8">
            <div class="bg-white/60 p-1.5 rounded-2xl border border-white/50 shadow-sm flex gap-1 backdrop-blur-sm">
                <button @click="dashboardView = 'financial'" 
                        :class="dashboardView === 'financial' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-100' : 'text-slate-500 hover:bg-white/50'"
                        class="px-6 py-2 text-xs font-bold rounded-xl transition-all">
                    Financieel 💶
                </button>
                <button @click="dashboardView = 'operational'" 
                        :class="dashboardView === 'operational' ? 'bg-white text-indigo-600 shadow-md shadow-indigo-100' : 'text-slate-500 hover:bg-white/50'"
                        class="px-6 py-2 text-xs font-bold rounded-xl transition-all">
                    Operationeel 📦
                </button>
            </div>
            <a href="{{ route('dashboard.report') }}" target="_blank" class="text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-white flex items-center gap-2 bg-white/50 border border-slate-200 px-5 py-2.5 rounded-xl transition-all cursor-pointer">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
    PDF Rapport
</a>
        </div>

        <div x-show="dashboardView === 'financial'" x-transition class="space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card p-6 rounded-3xl flex flex-col justify-between h-32 bg-white border border-slate-100 shadow-sm transition-transform hover:scale-[1.02]">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Totale Investering</h3>
                    <p class="text-3xl font-heading font-bold tracking-tight text-slate-800">€ {{ number_format($totalInvested, 2, ',', '.') }}</p>
                </div>
                <div class="glass-card p-6 rounded-3xl flex flex-col justify-between h-32 bg-white border border-slate-100 shadow-sm transition-transform hover:scale-[1.02]">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Totale Omzet</h3>
                    <p class="text-3xl font-heading font-bold tracking-tight text-blue-600">€ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
                </div>
                
                <div class="glass-card p-6 rounded-3xl flex flex-col justify-between h-32 relative overflow-hidden group transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl
                    {{ $netResult >= 0 ? 'theme-profit border-emerald-200/50' : 'theme-loss border-red-200/50' }} border">
                    <div class="absolute inset-0 opacity-20 animate-aurora
                        {{ $netResult >= 0 ? 'bg-gradient-to-br from-emerald-100 via-teal-100 to-cyan-100' : 'bg-gradient-to-br from-red-100 via-orange-100 to-rose-100' }}">
                    </div>
                    <div class="relative z-30 flex justify-between items-start">
                        <h3 class="animate-float text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 {{ $netResult >= 0 ? 'text-emerald-800' : 'text-red-800' }}">
                            Netto Resultaat
                        </h3>
                    </div>
                    <div class="relative z-30 mt-auto">
                        <p class="text-4xl font-heading font-black tracking-tighter drop-shadow-sm transition-all duration-300 group-hover:scale-105 origin-left
                            {{ $netResult >= 0 ? 'text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-800' : 'text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-rose-600 to-red-800' }}">
                            € {{ number_format($netResult, 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-3xl flex flex-col justify-between h-32 bg-indigo-50 border border-indigo-100 shadow-sm transition-transform hover:scale-[1.02]">
                    <h3 class="text-indigo-800 text-[10px] font-bold uppercase tracking-widest">Potentieel Totaal</h3>
                    <p class="text-3xl font-heading font-bold tracking-tight text-indigo-700">€ {{ number_format($potentialProfit, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="glass-panel p-6 rounded-3xl shadow-sm bg-white border border-slate-100">
                    <div class="mb-4">
                        <h3 class="font-heading font-bold text-lg text-slate-800">Gezonde Groei</h3>
                        <p class="text-xs text-slate-400">Verhouding tussen omzet (volume) en winstmarge (gezondheid)</p>
                    </div>
                    <div id="chart-growth" class="min-h-[300px]"></div>
                </div>

                <div class="glass-panel p-6 rounded-3xl shadow-sm bg-white border border-slate-100">
                    <div class="mb-4">
                        <h3 class="font-heading font-bold text-lg text-slate-800">Profit/Loss Trend</h3>
                        <p class="text-xs text-slate-400">Netto resultaat per maand (boven/onder break-even)</p>
                    </div>
                    <div id="chart-profit" class="min-h-[300px]"></div>
                </div>
            </div>

            <div class="glass-panel p-8 rounded-3xl shadow-sm bg-white border border-slate-100">
                <h3 class="font-heading font-bold text-xl mb-6 text-slate-800">Top Categorieën</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($topCategories as $index => $cat)
                        <div class="group flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between mb-1">
                                    <span class="font-bold text-slate-700">{{ $cat['name'] }}</span>
                                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">€ {{ number_format($cat['profit'], 0) }} winst</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    @php 
                                        $maxProfit = $topCategories[0]['profit'] > 0 ? $topCategories[0]['profit'] : 1;
                                        $percent = max(5, ($cat['profit'] / $maxProfit) * 100);
                                    @endphp
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">{{ $cat['sold'] }} verkopen</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-slate-400 text-sm italic text-center py-6">Nog geen data beschikbaar.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div x-show="dashboardView === 'operational'" x-cloak x-transition x-data="{ operationalRange: 'daily' }" class="space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="glass-card p-5 rounded-3xl border border-slate-100 bg-white shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Verkocht</h3>
                    <p class="text-3xl font-heading font-bold text-emerald-600">{{ $itemsSold }} <span class="text-sm font-medium text-slate-400">items</span></p>
                </div>
                <div class="glass-card p-5 rounded-3xl border border-slate-100 bg-white shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Voorraad</h3>
                    <p class="text-3xl font-heading font-bold text-slate-800">{{ $itemsInStock }} <span class="text-sm font-medium text-slate-400">items</span></p>
                </div>
                <div class="glass-card p-5 rounded-3xl border border-slate-100 bg-white shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Pakketten</h3>
                    <p class="text-3xl font-heading font-bold text-blue-600">{{ $totalParcels }}</p>
                </div>
                <div class="glass-card p-5 rounded-3xl border border-slate-100 bg-white shadow-sm">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Snelheid (Gem)</h3>
                    <p class="text-3xl font-heading font-bold text-slate-800">{{ $avgSellDays }} <span class="text-sm font-medium text-slate-400">dagen</span></p>
                </div>
                <div class="glass-card p-5 rounded-3xl border shadow-sm {{ $oldStockCount > 0 ? 'bg-red-50 border-red-100' : 'border-slate-100 bg-white' }}">
                    <h3 class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Winkeldochters</h3>
                    <p class="text-3xl font-heading font-bold {{ $oldStockCount > 0 ? 'text-red-500' : 'text-emerald-500' }}">{{ $oldStockCount }}</p>
                </div>
            </div>

            <div class="glass-panel p-6 rounded-3xl shadow-sm bg-white border border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-heading font-bold text-lg text-slate-800">Verkoopvolume</h3>
                        <p class="text-xs text-slate-400">Aantal verkochte items per periode</p>
                    </div>
                    <div class="bg-slate-50 p-1.5 rounded-2xl border border-slate-100 shadow-sm flex gap-1">
                        <button @click="operationalRange = 'daily'; window.setOperationalRange('daily')"
                                :class="operationalRange === 'daily' ? 'bg-white text-indigo-600 shadow' : 'text-slate-500 hover:bg-white/60'"
                                class="px-4 py-1.5 text-[11px] font-bold rounded-xl transition">
                            Per dag
                        </button>
                        <button @click="operationalRange = 'weekly'; window.setOperationalRange('weekly')"
                                :class="operationalRange === 'weekly' ? 'bg-white text-indigo-600 shadow' : 'text-slate-500 hover:bg-white/60'"
                                class="px-4 py-1.5 text-[11px] font-bold rounded-xl transition">
                            Per week
                        </button>
                        <button @click="operationalRange = 'monthly'; window.setOperationalRange('monthly')"
                                :class="operationalRange === 'monthly' ? 'bg-white text-indigo-600 shadow' : 'text-slate-500 hover:bg-white/60'"
                                class="px-4 py-1.5 text-[11px] font-bold rounded-xl transition">
                            Per maand
                        </button>
                    </div>
                </div>
                <div id="chart-operational" class="min-h-[300px]"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rawData = @json($chartData);
            const operationalData = @json($operationalChartData);
            const labels = rawData.map(d => d.label);
            const revenues = rawData.map(d => d.revenue);
            const profits = rawData.map(d => d.profit);
            const margins = rawData.map(d => d.margin);

            // 1. Growth & Health Chart (Combo)
            const growthOptions = {
                series: [{
                    name: 'Omzet',
                    type: 'column',
                    data: revenues
                }, {
                    name: 'Marge %',
                    type: 'line',
                    data: margins
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif'
                },
                stroke: { width: [0, 3], curve: 'smooth' },
                title: { text: undefined },
                dataLabels: { enabled: false },
                labels: labels,
                xaxis: { type: 'category' },
                yaxis: [{
                    title: { text: 'Omzet (€)', style: { fontSize: '10px', color: '#94a3b8' } },
                    labels: { formatter: (val) => '€' + val.toFixed(0) }
                }, {
                    opposite: true,
                    title: { text: 'Marge (%)', style: { fontSize: '10px', color: '#94a3b8' } },
                    labels: { formatter: (val) => val.toFixed(0) + '%' }
                }],
                colors: ['#6366f1', '#10b981'], // Indigo (Omzet), Emerald (Marge)
                plotOptions: {
                    bar: { borderRadius: 8, columnWidth: '40%' }
                },
                legend: { position: 'top' }
            };
            new ApexCharts(document.querySelector("#chart-growth"), growthOptions).render();

            // 2. Profit/Loss Trend Chart
            const profitOptions = {
                series: [{
                    name: 'Netto Resultaat',
                    data: profits
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '50%',
                        colors: {
                            ranges: [{
                                from: -100000,
                                to: -0.01,
                                color: '#ef4444' // Rood voor verlies
                            }, {
                                from: 0,
                                to: 100000,
                                color: '#10b981' // Groen voor winst
                            }]
                        }
                    }
                },
                dataLabels: { enabled: false },
                xaxis: { categories: labels },
                yaxis: {
                    labels: { formatter: (val) => '€' + val.toFixed(0) }
                },
                grid: {
                    yaxis: {
                        lines: { show: true }
                    },
                    // Maak een duidelijke 0-lijn
                    padding: { top: 0, right: 0, bottom: 0, left: 10 } 
                },
                annotations: {
                    yaxis: [{
                        y: 0,
                        borderColor: '#334155',
                        strokeDashArray: 0,
                        borderWidth: 1,
                        opacity: 0.5
                    }]
                }
            };
            new ApexCharts(document.querySelector("#chart-profit"), profitOptions).render();

            // 3. Operational Sales Volume Chart
            let operationalChart = null;
            const renderOperationalChart = (range) => {
                const labels = operationalData[range].labels;
                const values = operationalData[range].values;

                if (!operationalChart) {
                    const operationalOptions = {
                        series: [{ name: 'Verkocht', data: values }],
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            fontFamily: 'Inter, sans-serif'
                        },
                        stroke: { curve: 'smooth', width: 3 },
                        dataLabels: { enabled: false },
                        xaxis: { categories: labels },
                        yaxis: { labels: { formatter: (val) => val.toFixed(0) } },
                        colors: ['#6366f1'],
                        fill: {
                            type: 'gradient',
                            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
                        },
                        grid: { strokeDashArray: 4 }
                    };
                    operationalChart = new ApexCharts(document.querySelector("#chart-operational"), operationalOptions);
                    operationalChart.render();
                } else {
                    operationalChart.updateOptions({ xaxis: { categories: labels } });
                    operationalChart.updateSeries([{ name: 'Verkocht', data: values }]);
                }
            };

            window.setOperationalRange = (range) => {
                renderOperationalChart(range);
            };

            renderOperationalChart('daily');
        });
    </script>
</x-app-layout>