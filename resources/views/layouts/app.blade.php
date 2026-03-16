<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reseller Pro v4.2</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        @keyframes meshAnimation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes stagger-enter {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .stagger-item {
            opacity: 0;
            animation: stagger-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            background-image: 
                radial-gradient(at 0% 0%, hsla(242, 90%, 85%, 0.4) 0px, transparent 50%),
                radial-gradient(at 50% 100%, hsla(280, 80%, 85%, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 0%, hsla(200, 90%, 85%, 0.4) 0px, transparent 50%);
            background-attachment: fixed;
            background-size: 200% 200%;
            animation: meshAnimation 15s ease infinite;
        }
        
        h1, h2, h3, h4, h5, h6, .font-heading { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            letter-spacing: -0.02em;
        }
        
        .glass-header {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02);
        }
        
        .glass-sidebar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.02);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        [x-cloak] { display: none !important; }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.02);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }
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

                <aside class="w-80 glass-sidebar flex flex-col relative z-40 transition-all duration-300">
                    <div class="p-7 border-b border-slate-100/50 flex items-center gap-4">
                        <img src="/logo.png" alt="Logo" class="w-16 h-16 object-contain" />
                        <span class="font-heading font-extrabold text-2xl text-slate-800 tracking-tight">Reseller Pro</span>
                    </div>

                    <nav class="flex-1 p-6 space-y-2 overflow-y-auto">
                        <div class="text-[10px] font-extrabold text-slate-400 uppercase mb-3 tracking-widest px-2">Navigatie</div>
                        <ul class="space-y-1.5">
                            <li>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('dashboard*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white inset-0 hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('dashboard*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 3h7v7H3zM14 3h7v5h-7zM14 10h7v11h-7zM3 12h7v9H3z" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('inventory*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('inventory*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.5a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 3.77a2 2 0 0 0 2 0l7-3.77A2 2 0 0 0 21 16z" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.3 7.5L12 12l8.7-4.5M12 22V12" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Voorraad</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('parcels.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('parcels*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('parcels*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 8.5V16a2 2 0 0 1-1.1 1.8l-7 3.5a2 2 0 0 1-1.8 0l-7-3.5A2 2 0 0 1 3 16V8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3 8.5l8.1-4.05a2 2 0 0 1 1.8 0L21 8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 22V12" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 5.5l10 5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Pakketten</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('presets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('presets*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('presets*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="8" cy="6" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="16" cy="12" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="10" cy="18" r="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Presets</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('export.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('export*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('export*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 7V5a2 2 0 00-2-2H5a2 2 0 00-2 2v2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Export/Import</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-bold transition-all duration-200 group {{ request()->routeIs('profile*') ? 'bg-indigo-600 shadow-md shadow-indigo-200 text-white' : 'text-slate-600 hover:bg-white hover:shadow-sm hover:text-indigo-600' }}">
                                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('profile*') ? 'text-indigo-200' : 'text-slate-400 group-hover:text-indigo-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="mt-0.5">Account</span>
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="mt-auto p-6 border-t border-slate-100/50 space-y-3">
                        <form method="POST" action="{{ route('dashboard.layout.toggle') }}">
                            @csrf
                            <button class="w-full text-xs font-bold text-slate-500 hover:text-indigo-600 bg-white hover:bg-indigo-50 hover:shadow-sm px-4 py-2.5 rounded-xl transition-all border border-slate-100">Menu boven</button>
                        </form>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 hover:shadow-sm px-4 py-2.5 rounded-xl transition-all"><i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Uitloggen</button>
                        </form>
                    </div>
                </aside>

                <!-- Content -->
                <main class="flex-1 p-8 overflow-y-auto h-screen">
                    {{ $slot }}
                </main>
            </div>
        @else
            <!-- Top Nav -->
            <nav class="glass-header sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-20 items-center">
                        <div class="flex items-center gap-4">
                            <img src="/logo.png" alt="Logo" class="w-14 h-14 object-contain" />
                            <span class="font-heading font-extrabold text-2xl text-slate-800 tracking-tight flex items-center gap-3">
                                Reseller Pro 
                                <span class="text-[10px] bg-gradient-to-r from-indigo-500 to-purple-500 text-white px-2 py-0.5 rounded-full font-bold shadow-sm">v4.2</span>
                            </span>

                            <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex">
                                @foreach([
                                    'dashboard' => 'Dashboard',
                                    'inventory.index' => 'Voorraad',
                                    'parcels.index' => 'Pakketten',
                                    'presets.index' => 'Presets',
                                    'export.index' => 'Export/Import',
                                    'profile.edit' => 'Account',
                                ] as $route => $label)
                                    <a href="{{ route($route) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-bold transition-all duration-300 rounded-xl hover:bg-white/60 hover:shadow-sm {{ request()->routeIs($route.'*') ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-800' }}">
                                        {{ $label }}
                                        @if(request()->routeIs($route.'*'))
                                            <span class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <form method="POST" action="{{ route('dashboard.layout.toggle') }}">
                                @csrf
                                <button class="text-xs font-bold text-slate-500 hover:text-indigo-600 bg-white/50 hover:bg-white hover:shadow-sm px-4 py-2.5 rounded-xl transition-all border border-slate-200/50">Menu links</button>
                            </form>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="text-xs text-slate-400 hover:text-red-500 font-bold uppercase tracking-wider transition-all"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Uitloggen</button>
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

    <!-- Global Command Palette -->
    <div x-data="{ 
        isOpen: false, 
        search: '',
        get filteredRoutes() {
            const routes = [
                { name: 'Dashboard', icon: 'fa-home', url: '/dashboard', shortcut: 'G D' },
                { name: 'Voorraad (Inventory)', icon: 'fa-box', url: '/inventory', shortcut: 'G I' },
                { name: 'Nieuw Item Toevoegen', icon: 'fa-plus', url: '/inventory?action=new', shortcut: 'N I' },
                { name: 'Pakketten (Parcels)', icon: 'fa-boxes-stacked', url: '/parcels', shortcut: 'G P' },
                { name: 'Nieuw Pakket', icon: 'fa-box-open', url: '/parcels/create', shortcut: 'N P' },
                { name: 'Presets', icon: 'fa-bolt', url: '/presets', shortcut: 'G R' },
                { name: 'Export / Import', icon: 'fa-file-export', url: '/export', shortcut: 'G E' },
                { name: 'Account Instellingen', icon: 'fa-user', url: '/profile', shortcut: 'G A' }
            ];
            if (this.search === '') return routes;
            return routes.filter(route => route.name.toLowerCase().includes(this.search.toLowerCase()));
        }
    }"
    @keydown.window.prevent.cmd.k="isOpen = true"
    @keydown.window.prevent.ctrl.k="isOpen = true"
    @keydown.escape.window="isOpen = false">

        <div x-show="isOpen" x-cloak
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[200] flex items-start justify-center pt-24 sm:pt-32 px-4 pb-20 bg-slate-900/40 backdrop-blur-sm sm:px-6 lg:px-8"
             @click.self="isOpen = false">
            
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="relative w-full max-w-2xl bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl ring-1 ring-slate-200 overflow-hidden">
                
                <!-- Search Input -->
                <div class="relative border-b border-slate-100">
                    <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                    <input type="text" 
                           x-model="search"
                           x-ref="searchInput"
                           x-init="$watch('isOpen', value => { if(value) setTimeout(() => $refs.searchInput.focus(), 50) })"
                           class="w-full bg-transparent border-0 py-6 pl-16 pr-6 text-xl text-slate-800 focus:ring-0 placeholder-slate-300 font-medium outline-none" 
                           placeholder="Zoek applicatie of actie...">
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 flex items-center gap-2">
                         <span class="text-[10px] font-extrabold text-slate-400 bg-slate-100 px-2 flex items-center h-6 rounded shadow-sm border border-slate-200 uppercase tracking-widest">ESC</span>
                    </div>
                </div>

                <!-- Results -->
                <div class="max-h-96 overflow-y-auto p-3 custom-scrollbar">
                    <template x-for="(route, index) in filteredRoutes" :key="index">
                        <a :href="route.url" class="flex items-center justify-between p-4 rounded-xl hover:bg-slate-50 group transition-all duration-200 border border-transparent hover:border-slate-100">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-white shadow-sm border border-slate-200 group-hover:bg-indigo-600 group-hover:border-indigo-600 text-slate-500 group-hover:text-white flex items-center justify-center transition-colors">
                                    <i class="fa-solid text-lg" :class="route.icon"></i>
                                </div>
                                <span class="font-bold text-slate-700 group-hover:text-indigo-900 transition-colors" x-text="route.name"></span>
                            </div>
                            <span class="hidden sm:flex text-[10px] items-center h-6 font-extrabold tracking-widest text-slate-400 group-hover:text-indigo-500 bg-white px-2 rounded shadow-sm border border-slate-200 group-hover:border-indigo-100 transition-colors uppercase" x-text="route.shortcut"></span>
                        </a>
                    </template>

                    <div x-show="filteredRoutes.length === 0" class="p-10 text-center">
                        <i class="fa-regular fa-face-frown text-4xl text-slate-300 mb-4"></i>
                        <p class="text-slate-500 font-medium">Geen acties gevonden voor <span class="font-bold text-slate-700" x-text="search"></span></p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

    <!-- Global Toast Notifications -->
    <div x-data="{
        notifications: [],
        add(e) {
            this.notifications.push({
                id: Date.now(),
                type: e.detail.type || 'info',
                title: e.detail.title || 'Melding',
                message: e.detail.message,
                show: true
            });
            setTimeout(() => this.remove(Date.now()), 5000);
        },
        remove(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index > -1) {
                this.notifications[index].show = false;
                setTimeout(() => this.notifications.splice(index, 1), 300);
            }
        }
    }" @notify.window="add($event)" class="fixed bottom-4 right-4 z-[250] flex flex-col gap-3 items-end pointer-events-none">
        
        <template x-for="notification in notifications" :key="notification.id">
            <div x-show="notification.show"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="pointer-events-auto bg-white/90 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-4 w-80 flex gap-4 items-start relative overflow-hidden group">
                 <div class="mt-0.5" :class="{'text-emerald-500': notification.type === 'success', 'text-rose-500': notification.type === 'error', 'text-blue-500': notification.type === 'update'}">
                    <i class="fa-solid" :class="{'fa-circle-check': notification.type === 'success', 'fa-circle-xmark': notification.type === 'error', 'fa-arrows-rotate': notification.type === 'update', 'fa-circle-info': !['success', 'error', 'update'].includes(notification.type)}"></i>
                 </div>
                 <div class="flex-1">
                    <h4 class="text-sm font-bold text-slate-800 mb-0.5" x-text="notification.title"></h4>
                    <p class="text-xs font-medium text-slate-500" x-text="notification.message"></p>
                 </div>
                 <button @click="remove(notification.id)" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><i class="fa-solid fa-times"></i></button>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let initialCount = null;
            let lastUpdate = null;

            @if(session('success'))
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', title: 'Succes', message: "{{ session('success') }}" }}));
            @endif
            @if(session('error'))
                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', title: 'Let op', message: "{{ session('error') }}" }}));
            @endif

            fetchStatus(true);
            setInterval(() => fetchStatus(false), 5000);

            function fetchStatus(isFirstLoad) {
                fetch("{{ route('inventory.status') }}")
                    .then(response => response.json())
                    .then(data => {
                        if (isFirstLoad) {
                            initialCount = data.count;
                            lastUpdate = data.last_update;
                        } else {
                            if (data.count > initialCount || data.last_update !== lastUpdate) {
                                let msg = data.count > initialCount ? `${data.count - initialCount} nieuwe item(s) gevonden.` : "Voorraad gewijzigd.";
                                window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'update', title: 'Sync', message: msg }}));
                                initialCount = data.count; lastUpdate = data.last_update;
                            }
                        }
                    })
                    .catch(e => console.error(e));
            }
        });
    </script>
</html>
