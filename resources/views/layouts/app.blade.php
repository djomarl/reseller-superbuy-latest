<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reseller Pro v4.2</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #334155;
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, transparent 50%), radial-gradient(at 50% 0%, hsla(225,39%,30%,0) 0, transparent 50%), radial-gradient(at 100% 0%, hsla(339,49%,30%,0) 0, transparent 50%);
            background-attachment: fixed;
        }
        h1, h2, h3, h4, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased">
    @php
        $layoutMode = Auth::check() ? (Auth::user()->layout ?? 'top') : 'top';
    @endphp

    <div class="min-h-screen">
        @if($layoutMode === 'sidebar')
            <div class="flex min-h-screen">
                <!-- Sidebar -->

                <aside class="w-80 bg-white border-r border-slate-200 shadow-lg sticky top-0 h-screen flex flex-col">
                    <div class="p-7 border-b border-slate-100 flex items-center gap-4">
                        <img src="/logo.png" alt="Logo" class="w-20 h-20 object-contain" />
                        <span class="font-heading font-extrabold text-2xl text-slate-800 tracking-tight">Reseller Pro</span>
                    </div>

                    <nav class="flex-1 p-6 space-y-2">
                        <div class="text-xs font-bold text-slate-400 uppercase mb-2 tracking-widest">Navigatie</div>
                        <ul class="space-y-1">
                            <li>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition {{ request()->routeIs('dashboard*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 3h7v7H3zM14 3h7v5h-7zM14 10h7v11h-7zM3 12h7v9H3z" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition {{ request()->routeIs('inventory*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.5a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 3.77a2 2 0 0 0 2 0l7-3.77A2 2 0 0 0 21 16z" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.3 7.5L12 12l8.7-4.5M12 22V12" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Voorraad
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('parcels.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition {{ request()->routeIs('parcels*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 8.5V16a2 2 0 0 1-1.1 1.8l-7 3.5a2 2 0 0 1-1.8 0l-7-3.5A2 2 0 0 1 3 16V8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3 8.5l8.1-4.05a2 2 0 0 1 1.8 0L21 8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 22V12" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 5.5l10 5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Pakketten
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('presets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition {{ request()->routeIs('presets*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="8" cy="6" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="16" cy="12" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="10" cy="18" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Presets
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition {{ request()->routeIs('profile*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Account
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="mt-auto p-6 border-t border-slate-100 space-y-3">
                        <form method="POST" action="{{ route('dashboard.layout.toggle') }}">
                            @csrf
                            <button class="w-full text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 px-3 py-2 rounded-xl transition">Menu boven</button>
                        </form>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-xs font-bold text-red-500 hover:text-red-600 bg-red-50 px-3 py-2 rounded-xl transition">Uitloggen</button>
                        </form>
                    </div>
                </aside>

                <!-- Content -->
                <main class="flex-1 py-8">
                    {{ $slot }}
                </main>
            </div>
        @else
            <!-- Top Nav -->
            <nav class="glass-header sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center gap-3">
                            <img src="/logo.png" alt="Logo" class="w-16 h-16 object-contain" />
                            <span class="font-heading font-bold text-xl text-slate-800">Reseller Pro <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded border border-indigo-100">v4.2</span></span>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                @foreach([
                                    'dashboard' => 'Dashboard',
                                    'inventory.index' => 'Voorraad',
                                    'parcels.index' => 'Pakketten',
                                    'presets.index' => 'Presets',
                                    'profile.edit' => 'Account',
                                ] as $route => $label)
                                    <a href="{{ route($route) }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold leading-5 transition duration-150 ease-in-out {{ request()->routeIs($route.'*') ? 'border-indigo-500 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('dashboard.layout.toggle') }}">
                                @csrf
                                <button class="text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 px-3 py-2 rounded-xl transition">Menu links</button>
                            </form>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="text-sm text-slate-400 hover:text-red-500 font-bold uppercase transition">Uitloggen</button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <main class="py-8">
                {{ $slot }}
            </main>
        @endif
    </div>
</body>

<div id="update-notification" style="display: none; position: fixed; top: 20px; right: 20px; background-color: #10b981; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 50; align-items: center; gap: 12px; transform: translateY(-100px); transition: transform 0.3s ease-out;">
        <span style="font-size: 20px;">🔔</span>
        <div>
            <div style="font-weight: bold; font-size: 14px;">Nieuwe updates gevonden!</div>
            <div id="update-msg" style="font-size: 12px; opacity: 0.9;">Klik om te verversen</div>
        </div>
        <button onclick="window.location.reload()" style="margin-left: 12px; background: white; color: #10b981; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 12px;">
            Verversen
        </button>
        <button onclick="document.getElementById('update-notification').style.transform = 'translateY(-100px)'" style="background: none; border: none; color: white; margin-left: 8px; cursor: pointer; font-size: 16px; opacity: 0.8;">&times;</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let initialCount = null;
            let lastUpdate = null;

            // 1. Haal de start-status op zodra de pagina laadt
            fetchStatus(true);

            // 2. Check elke 5 seconden of er iets veranderd is
            setInterval(() => {
                fetchStatus(false);
            }, 5000);

            function fetchStatus(isFirstLoad) {
                fetch("{{ route('inventory.status') }}")
                    .then(response => response.json())
                    .then(data => {
                        if (isFirstLoad) {
                            initialCount = data.count;
                            lastUpdate = data.last_update;
                        } else {
                            // Als het aantal items is toegenomen OF de laatste update nieuwer is
                            if (data.count > initialCount || data.last_update !== lastUpdate) {
                                showNotification(data.count - initialCount);
                            }
                        }
                    })
                    .catch(error => console.error('Status check failed:', error));
            }

            function showNotification(diff) {
                const el = document.getElementById('update-notification');
                const msg = document.getElementById('update-msg');
                
                if (diff > 0) {
                    msg.innerText = `${diff} nieuwe item(s) geïmporteerd via Extensie.`;
                } else {
                    msg.innerText = "Er zijn wijzigingen in je voorraad.";
                }

                el.style.display = 'flex';
                // Kleine vertraging voor de animatie
                setTimeout(() => {
                    el.style.transform = 'translateY(0)';
                }, 10);
            }
        });
    </script>

</html>
