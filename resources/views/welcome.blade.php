<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ResellerOS - De Ultieme SaaS voor Resellers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700|outfit:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            heading: ['Outfit', 'sans-serif'],
                        },
                        colors: {
                            primary: '#6366f1',
                            secondary: '#ec4899',
                            dark: '#0B0F19',
                            darker: '#06090F'
                        },
                        animation: {
                            'blob': 'blob 7s infinite',
                            'marquee': 'marquee 25s linear infinite',
                            'marquee-fast': 'marquee 15s linear infinite',
                            'float': 'float 6s ease-in-out infinite',
                            'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        },
                        keyframes: {
                            blob: {
                                '0%': { transform: 'translate(0px, 0px) scale(1)' },
                                '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                                '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                                '100%': { transform: 'translate(0px, 0px) scale(1)' },
                            },
                            marquee: {
                                '0%': { transform: 'translateX(0%)' },
                                '100%': { transform: 'translateX(-100%)' },
                            },
                            float: {
                                '0%, 100%': { transform: 'translateY(0)' },
                                '50%': { transform: 'translateY(-20px)' },
                            }
                        }
                    }
                }
            }
        </script>
    @endif

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #06090F; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Outfit', sans-serif; }
        
        /* Advanced Glassmorphism */
        .glass-nav {
            background: rgba(11, 15, 25, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .glass-nav.scrolled {
            background: rgba(6, 9, 15, 0.85);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .glass-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .glass-card:hover {
            border: 1px solid rgba(99, 102, 241, 0.3);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.15);
        }

        /* Animated Text Gradients */
        .text-gradient {
            background: linear-gradient(to right, #818cf8, #c084fc, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            background-size: 300% auto;
            animation: textShine 5s linear infinite;
        }

        .text-gradient-gold {
            background: linear-gradient(to right, #fbbf24, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            background-size: 200% auto;
            animation: textShine 4s linear infinite;
        }

        @keyframes textShine {
            to { background-position: 300% center; }
        }

        /* 3D Dashboard Scene */
        .scene {
            perspective: 1200px;
            transform-style: preserve-3d;
        }

        .dashboard-wrapper {
            transform: rotateX(10deg) rotateY(-5deg) translateZ(0);
            transition: transform 0.8s ease-out;
        }

        .scene:hover .dashboard-wrapper {
            transform: rotateX(2deg) rotateY(-2deg) translateZ(20px);
        }

        /* Scroll Reveal Utility */
        .reveal {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }

        .reveal-left.active, .reveal-right.active {
            opacity: 1;
            transform: translateX(0);
        }

        /* Delay Utilities */
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }

        /* Glowing Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
            mix-blend-mode: screen;
        }
    </style>
</head>
<body class="antialiased text-slate-300 min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">

    <!-- Ambient Background Orbs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-20">
        <div class="orb bg-indigo-600/30 w-[600px] h-[600px] -top-[200px] -left-[100px] animate-blob"></div>
        <div class="orb bg-purple-600/30 w-[500px] h-[500px] top-[20%] -right-[100px] animate-blob animation-delay-2000"></div>
        <div class="orb bg-sky-600/20 w-[600px] h-[600px] -bottom-[200px] left-[20%] animate-blob animation-delay-4000"></div>
        
        <!-- Grid overlay -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAwIDQwIEwgNDAgNDAgNDAgMCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDMpIiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]"></div>
    </div>

    <!-- Navigation -->
    <nav id="navbar" class="fixed w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3 group cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-[0_0_15px_rgba(99,102,241,0.5)] group-hover:shadow-[0_0_25px_rgba(99,102,241,0.8)] transition-all duration-300 group-hover:scale-110">
                        R
                    </div>
                    <span class="font-heading font-bold text-2xl tracking-tight text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-indigo-400 group-hover:to-purple-400 transition-all">Reseller<span class="text-indigo-400">OS</span></span>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-10">
                    <a href="#features" class="text-sm font-medium text-slate-300 hover:text-white transition-colors relative group">
                        Features
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-indigo-500 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="#prijzen" class="text-sm font-medium text-slate-300 hover:text-white transition-colors relative group">
                        Prijzen
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-indigo-500 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                    <a href="#integraties" class="text-sm font-medium text-slate-300 hover:text-white transition-colors relative group">
                        Integraties
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-indigo-500 transition-all duration-300 group-hover:w-full"></span>
                    </a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/10 px-6 py-2.5 rounded-full transition-all backdrop-blur-md">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:inline-block text-sm font-medium text-slate-300 hover:text-white transition-colors">
                                Inloggen
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 rounded-full transition-all shadow-[0_0_15px_rgba(79,70,229,0.4)] hover:shadow-[0_0_30px_rgba(79,70,229,0.7)] transform hover:-translate-y-0.5 border border-indigo-500 border-t-indigo-400 relative overflow-hidden group">
                                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></span>
                                    <span>Probeer Gratis</span>
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="flex-grow pt-32 pb-16 sm:pt-44 sm:pb-24 lg:pb-32 overflow-hidden relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
            
            <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-500/10 text-indigo-300 text-sm font-medium mb-10 border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.15)] backdrop-blur-md">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                ResellerOS 2.0 is live
            </div>

            <h1 class="reveal delay-100 text-5xl sm:text-6xl lg:text-7xl xl:text-8xl font-black font-heading tracking-tight mb-8 leading-[1.1] text-white">
                Schaal Je Verkopen. <br class="hidden sm:block" />
                <span class="text-gradient">
                    Automatiseer Alles.
                </span>
            </h1>
            
            <p class="reveal delay-200 mt-6 max-w-2xl text-lg sm:text-xl text-slate-400 mx-auto mb-12 leading-relaxed font-light">
                Vergeet spreadsheets. Beheer je inventory, importeer direct uit Superbuy, track zendingen en zie je Vinted marge in <strong class="text-slate-200 font-medium">real-time</strong>.
            </p>
            
            <div class="reveal delay-300 mt-10 flex justify-center gap-5 flex-col sm:flex-row items-center">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="w-full sm:w-auto flex items-center justify-center px-8 py-4 text-lg font-semibold rounded-full text-white bg-indigo-600 hover:bg-indigo-500 shadow-[0_0_30px_rgba(79,70,229,0.5)] hover:shadow-[0_0_40px_rgba(79,70,229,0.8)] transition-all transform hover:-translate-y-1 relative group overflow-hidden border border-indigo-500 border-t-indigo-400">
                        <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></span>
                        <span class="relative flex items-center gap-2">
                            Start Nu Gratis
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                    </a>
                @endif
                <div class="flex items-center gap-4 text-sm text-slate-400 font-medium">
                    <span class="flex items-center gap-1">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        14 Dagen Gratis
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Snel Opgezet
                    </span>
                </div>
            </div>
            
            <!-- 3D Dashboard Preview -->
            <div class="reveal delay-300 mt-24 relative max-w-5xl mx-auto scene">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>
                
                <div class="dashboard-wrapper rounded-2xl border border-slate-700/80 bg-[#0B0F19]/90 shadow-[0_20px_60px_-15px_rgba(79,70,229,0.5)] backdrop-blur-xl p-2 sm:p-3 relative">
                    <!-- Glow behind mock -->
                    <div class="absolute -inset-1 bg-gradient-to-b from-indigo-500 to-purple-600 rounded-3xl opacity-20 blur-2xl -z-10"></div>
                    
                    <div class="rounded-xl overflow-hidden border border-slate-800 bg-[#06090F] relative">
                        <!-- Mac OS Window Dots -->
                        <div class="absolute top-0 left-0 w-full flex items-center justify-between px-4 py-3 bg-slate-900/80 border-b border-slate-800 backdrop-blur-sm z-10">
                            <div class="flex space-x-2">
                                <div class="w-3 h-3 rounded-full bg-slate-700 border border-slate-600"></div>
                                <div class="w-3 h-3 rounded-full bg-slate-700 border border-slate-600"></div>
                                <div class="w-3 h-3 rounded-full bg-slate-700 border border-slate-600"></div>
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono tracking-wider">app.reselleros.com</div>
                            <div class="w-12"></div>
                        </div>
                        
                        <!-- Mock App Structure -->
                        <div class="flex h-[400px] sm:h-[600px] mt-10">
                            <!-- Sidebar -->
                            <div class="hidden sm:flex flex-col w-64 border-r border-slate-800/80 bg-[#0B0F19]/50 p-4">
                                <div class="space-y-3 mb-8">
                                    <div class="h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center px-3 gap-3">
                                        <div class="w-5 h-5 rounded bg-indigo-400/80"></div>
                                        <div class="h-2 w-24 bg-indigo-300/80 rounded"></div>
                                    </div>
                                    <div class="h-10 rounded-lg flex items-center px-3 gap-3">
                                        <div class="w-5 h-5 rounded bg-slate-700/80"></div>
                                        <div class="h-2 w-16 bg-slate-600/80 rounded"></div>
                                    </div>
                                    <div class="h-10 rounded-lg flex items-center px-3 gap-3">
                                        <div class="w-5 h-5 rounded bg-slate-700/80"></div>
                                        <div class="h-2 w-20 bg-slate-600/80 rounded"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Main Content Area with Live Preview Animation -->
                            <div class="flex-1 p-6 relative overflow-hidden bg-gradient-to-br from-[#0B0F19] to-[#06090F] flex flex-col" id="live-preview-container">
                                <!-- Decor elements inside mock -->
                                <div class="absolute top-10 right-10 w-64 h-64 bg-indigo-600/10 rounded-full blur-3xl"></div>
                                
                                <!-- Header -->
                                <div class="flex justify-between items-end mb-6 relative z-10 transition-all duration-500" id="lp-header">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="h-5 w-5 rounded bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs">⚡</div>
                                            <h4 class="text-white font-medium text-sm">Nieuwe Order Import</h4>
                                        </div>
                                        <p class="text-slate-400 text-[10px] sm:text-xs">Plak hier je inkoop order gegevens</p>
                                    </div>
                                    <div class="h-8 px-4 bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-lg shadow-indigo-500/20 text-white text-[10px] sm:text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer" id="lp-import-btn">
                                        Importeer Nu
                                    </div>
                                </div>

                                <!-- Step 1: Text Area (Paste) -->
                                <div id="lp-step-1" class="flex-1 bg-black/40 rounded-xl border border-white/[0.05] p-4 relative z-10 font-mono text-[10px] sm:text-xs text-slate-300 overflow-hidden flex flex-col transition-all duration-500 shadow-inner">
                                    <div class="flex items-center justify-between border-b border-white/[0.05] pb-2 mb-2">
                                        <span class="text-slate-500 tracking-wider text-[10px]">RAW DATA INPUT</span>
                                        <div class="flex gap-1.5">
                                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500/50"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-amber-500/50"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/50"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 relative">
                                        <!-- Blinking cursor initially, then types -->
                                        <div id="lp-textarea" class="w-full h-full text-indigo-200/80 leading-relaxed whitespace-pre-wrap" style="outline: none;"></div>
                                        <div id="lp-cursor" class="absolute top-0 left-0 w-2 h-4 bg-indigo-400 animate-pulse"></div>
                                    </div>
                                </div>

                                <!-- Step 2: Animated Results Table -->
                                <div id="lp-step-2" class="absolute inset-0 top-[88px] p-6 pt-0 flex flex-col z-20 opacity-0 translate-y-8 pointer-events-none transition-all duration-700 ease-out">
                                    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
                                        <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 sm:p-4 backdrop-blur-md">
                                            <div class="text-indigo-300/70 text-[8px] sm:text-[10px] uppercase font-bold tracking-wider mb-1">Items</div>
                                            <div class="text-xl sm:text-2xl font-bold text-white lp-counter" data-target="3">0</div>
                                        </div>
                                        <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-3 sm:p-4 backdrop-blur-md">
                                            <div class="text-purple-300/70 text-[8px] sm:text-[10px] uppercase font-bold tracking-wider mb-1">Inkoop</div>
                                            <div class="text-xl sm:text-2xl font-bold text-white flex items-center">€<span class="lp-counter ml-1" data-target="84.00" data-decimals="2">0.00</span></div>
                                        </div>
                                        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 sm:p-4 backdrop-blur-md relative overflow-hidden">
                                            <div class="absolute right-0 bottom-0 w-16 h-16 bg-emerald-500/20 rounded-full blur-xl"></div>
                                            <div class="text-emerald-300/70 text-[8px] sm:text-[10px] uppercase font-bold tracking-wider mb-1 relative z-10">Winst</div>
                                            <div class="text-xl sm:text-2xl font-bold text-emerald-400 relative z-10 flex items-center">+€<span class="lp-counter ml-1" data-target="156.00" data-decimals="2">0.00</span></div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white/[0.03] border border-white/[0.05] rounded-xl flex-1 backdrop-blur-md p-3 sm:p-4 overflow-hidden flex flex-col">
                                        <div class="flex text-[8px] sm:text-[10px] uppercase tracking-wider text-slate-500 pb-2 border-b border-white/[0.05] mb-2 sm:mb-3">
                                            <div class="flex-1">Product</div>
                                            <div class="w-16 sm:w-20 text-right">Inkoop</div>
                                            <div class="w-16 sm:w-20 text-right hidden sm:block">Verkoop</div>
                                            <div class="w-16 sm:w-20 text-right">Marge</div>
                                        </div>
                                        <div class="space-y-2 sm:space-y-3 flex-1 overflow-hidden">
                                            <!-- Row 1 -->
                                            <div class="lp-row opacity-0 translate-x-4 flex items-center text-xs sm:text-sm transition-all duration-500">
                                                <div class="flex-1 flex items-center gap-2 sm:gap-3">
                                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded bg-slate-800 flex items-center justify-center text-[10px] sm:text-xs border border-white/10 shrink-0">👟</div>
                                                    <div class="min-w-0">
                                                        <div class="text-white font-medium text-[10px] sm:text-xs truncate">J4 Retro Military</div>
                                                        <div class="text-[8px] sm:text-[10px] text-slate-500 truncate">Size 44 • DO9369</div>
                                                    </div>
                                                </div>
                                                <div class="w-16 sm:w-20 text-right text-slate-400 text-[10px] sm:text-xs shrink-0">€32.50</div>
                                                <div class="w-16 sm:w-20 text-right text-slate-200 text-[10px] sm:text-xs hidden sm:block shrink-0">€95.00</div>
                                                <div class="w-16 sm:w-20 text-right text-emerald-400 font-medium text-[10px] sm:text-xs shrink-0">+€62.50</div>
                                            </div>
                                            <!-- Row 2 -->
                                            <div class="lp-row opacity-0 translate-x-4 flex items-center text-xs sm:text-sm transition-all duration-500 delay-100">
                                                <div class="flex-1 flex items-center gap-2 sm:gap-3">
                                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded bg-slate-800 flex items-center justify-center text-[10px] sm:text-xs border border-white/10 shrink-0">👕</div>
                                                    <div class="min-w-0">
                                                        <div class="text-white font-medium text-[10px] sm:text-xs truncate">Zip-Up Hoodie Tech</div>
                                                        <div class="text-[8px] sm:text-[10px] text-slate-500 truncate">Size M • Black</div>
                                                    </div>
                                                </div>
                                                <div class="w-16 sm:w-20 text-right text-slate-400 text-[10px] sm:text-xs shrink-0">€28.00</div>
                                                <div class="w-16 sm:w-20 text-right text-slate-200 text-[10px] sm:text-xs hidden sm:block shrink-0">€75.00</div>
                                                <div class="w-16 sm:w-20 text-right text-emerald-400 font-medium text-[10px] sm:text-xs shrink-0">+€47.00</div>
                                            </div>
                                            <!-- Row 3 -->
                                            <div class="lp-row opacity-0 translate-x-4 flex items-center text-xs sm:text-sm transition-all duration-500 delay-200">
                                                <div class="flex-1 flex items-center gap-2 sm:gap-3">
                                                    <div class="w-6 h-6 sm:w-8 sm:h-8 rounded bg-slate-800 flex items-center justify-center text-[10px] sm:text-xs border border-white/10 shrink-0">💼</div>
                                                    <div class="min-w-0">
                                                        <div class="text-white font-medium text-[10px] sm:text-xs truncate">Classic Wallet</div>
                                                        <div class="text-[8px] sm:text-[10px] text-slate-500 truncate">Brown</div>
                                                    </div>
                                                </div>
                                                <div class="w-16 sm:w-20 text-right text-slate-400 text-[10px] sm:text-xs shrink-0">€23.50</div>
                                                <div class="w-16 sm:w-20 text-right text-slate-200 text-[10px] sm:text-xs hidden sm:block shrink-0">€70.00</div>
                                                <div class="w-16 sm:w-20 text-right text-emerald-400 font-medium text-[10px] sm:text-xs shrink-0">+€46.50</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Logo Marquee Section -->
    <section id="integraties" class="py-12 border-y border-white/[0.05] bg-black/20 backdrop-blur-sm overflow-hidden flex flex-col items-center">
        <p class="text-xs font-bold tracking-widest text-slate-500 uppercase mb-8">Naadloze integratie met jouw favoriete platforms</p>
        
        <!-- Marquee Container -->
        <div class="w-full flex space-x-16 overflow-hidden relative" style="-webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);">
            
            <div class="flex space-x-24 animate-marquee whitespace-nowrap items-center shrink-0 min-w-full justify-around">
                <!-- Logos -->
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-white transition-colors duration-300 mx-8">SUPERBUY</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#09B1BA] transition-colors duration-300 mx-8">VINTED</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#EFA22F] transition-colors duration-300 mx-8">MARKTPLAATS</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#FF4500] transition-colors duration-300 mx-8">ALIEXPRESS</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#F3AE4E] transition-colors duration-300 mx-8">CSSBUY</div>
                
                <!-- Duplicate for seamless loop -->
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-white transition-colors duration-300 mx-8">SUPERBUY</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#09B1BA] transition-colors duration-300 mx-8">VINTED</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#EFA22F] transition-colors duration-300 mx-8">MARKTPLAATS</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#FF4500] transition-colors duration-300 mx-8">ALIEXPRESS</div>
                <div class="text-3xl font-black font-heading text-slate-400/60 hover:text-[#F3AE4E] transition-colors duration-300 mx-8">CSSBUY</div>
            </div>
            
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="reveal text-indigo-400 font-bold tracking-[0.2em] uppercase text-sm mb-4">Features</h2>
                <h3 class="reveal delay-100 text-4xl md:text-5xl font-bold font-heading mb-6 text-white leading-tight">
                    Ontworpen voor <span class="text-gradient">Maximale Marges</span>
                </h3>
                <p class="reveal delay-200 text-lg text-slate-400 font-light">
                    Ontdek de tools waarmee top resellers hun uren halveren en winst verdubbelen.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="reveal glass-card p-8 rounded-3xl group">
                    <div class="w-14 h-14 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20 text-indigo-400 mb-8 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-500 shadow-[0_0_15px_rgba(99,102,241,0)] group-hover:shadow-[0_0_30px_rgba(99,102,241,0.5)] group-hover:-translate-y-2">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold font-heading mb-3 text-white group-hover:text-indigo-300 transition-colors">1-Klik Import</h4>
                    <p class="text-slate-400 leading-relaxed font-light text-sm">
                        Plak je order data uit platforms als Superbuy, en wij vullen alles automatisch perfect in: items, prijzen, SKU's.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="reveal delay-100 glass-card p-8 rounded-3xl group relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-500/20 rounded-full blur-3xl group-hover:bg-purple-500/40 transition-all duration-500"></div>
                    <div class="w-14 h-14 bg-purple-500/10 rounded-xl flex items-center justify-center border border-purple-500/20 text-purple-400 mb-8 group-hover:bg-purple-500 group-hover:text-white transition-all duration-500 shadow-[0_0_15px_rgba(168,85,247,0)] group-hover:shadow-[0_0_30px_rgba(168,85,247,0.5)] group-hover:-translate-y-2 relative z-10">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold font-heading mb-3 text-white group-hover:text-purple-300 transition-colors relative z-10">Exacte ROI & Marges</h4>
                    <p class="text-slate-400 leading-relaxed font-light text-sm relative z-10">
                        Bereken supersnel je winst na aftrek van Vinted fees, gesplitste internationale verzendkosten en inklaringskosten.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="reveal delay-200 glass-card p-8 rounded-3xl group">
                    <div class="w-14 h-14 bg-sky-500/10 rounded-xl flex items-center justify-center border border-sky-500/20 text-sky-400 mb-8 group-hover:bg-sky-500 group-hover:text-white transition-all duration-500 shadow-[0_0_15px_rgba(14,165,233,0)] group-hover:shadow-[0_0_30px_rgba(14,165,233,0.5)] group-hover:-translate-y-2">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold font-heading mb-3 text-white group-hover:text-sky-300 transition-colors">Slimme Status Tracking</h4>
                    <p class="text-slate-400 leading-relaxed font-light text-sm">
                        Zie direct welke items onderweg zijn, op voorraad liggen of verkocht zijn. Behoud full control over je pijplijn.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="prijzen" class="py-32 relative z-20">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-900/40 rounded-full blur-[120px] -z-10"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="reveal text-4xl md:text-5xl font-bold font-heading mb-6 text-white">Focus Op Omzet. <br />Wij Doen De Rest.</h2>
            </div>
            
            <div class="reveal glass-card rounded-[2.5rem] p-1 relative overflow-hidden max-w-sm mx-auto group">
                <!-- Rotating border glow effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-sky-500 opacity-30 group-hover:opacity-100 transition-opacity duration-700 blur"></div>
                
                <div class="bg-[#0B0F19] rounded-[2.4rem] p-8 md:p-10 relative z-10 border border-white/[0.05] h-full flex flex-col">
                    <div class="absolute -top-4 right-8 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg shadow-indigo-500/30">Pro</div>
                    
                    <h3 class="text-3xl font-bold font-heading mb-2 text-white">All Access</h3>
                    <p class="text-slate-400 text-sm mb-6">De software voor power-resellers</p>
                    
                    <div class="mb-8 flex items-baseline">
                        <span class="text-5xl font-extrabold text-white">€15</span>
                        <span class="text-slate-500 ml-2">/maand</span>
                    </div>
                    
                    <ul class="space-y-4 mb-10 mt-auto">
                         <li class="flex items-start">
                            <svg class="w-5 h-5 text-indigo-400 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-slate-300 text-sm">Onbeperkt items importeren via 1-Klik Copy Paste</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-indigo-400 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-slate-300 text-sm">Uitgebreide profit berekeningen inclusief invoerkosten & fees</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-indigo-400 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-slate-300 text-sm">Data export naar CSV/Excel</span>
                        </li>
                    </ul>
                    
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="block w-full py-4 px-4 rounded-2xl font-bold text-center bg-white text-black hover:bg-slate-200 transition-all shadow-[0_0_20px_rgba(255,255,255,0.1)] hover:shadow-[0_0_30px_rgba(255,255,255,0.2)] transform hover:-translate-y-1">
                            Start 14 Dagen Gratis Trail
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#04060A] border-t border-white/[0.05] py-12 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                    R
                </div>
                <span class="font-heading font-bold text-xl text-slate-300">ResellerOS</span>
            </div>
            
            <div class="text-sm text-slate-500">
                &copy; {{ date('Y') }} ResellerOS. Alle rechten voorbehouden.
            </div>
            
            <div class="flex gap-8">
                <a href="#" class="text-sm text-slate-500 hover:text-white transition-colors">Privacy</a>
                <a href="#" class="text-sm text-slate-500 hover:text-white transition-colors">Terms</a>
            </div>
        </div>
    </footer>

    <!-- Animations Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Navbar Scroll Effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Intersection Observer for Reveal Animations
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        // Optional: Stop observing after reveal
                        // observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
            revealElements.forEach(el => observer.observe(el));

            // Live Preview Animation Sequence
            const runLivePreview = () => {
                const cursor = document.getElementById('lp-cursor');
                const textarea = document.getElementById('lp-textarea');
                const step1 = document.getElementById('lp-step-1');
                const step2 = document.getElementById('lp-step-2');
                const btn = document.getElementById('lp-import-btn');
                
                if(!cursor || !textarea || !step1 || !step2) return;

                // Reset state
                textarea.innerHTML = '';
                cursor.style.display = 'block';
                step1.classList.remove('opacity-0', '-translate-y-8', 'pointer-events-none');
                step2.classList.add('opacity-0', 'translate-y-8', 'pointer-events-none');
                step2.classList.remove('opacity-100', 'translate-y-0');
                
                const rows = document.querySelectorAll('.lp-row');
                rows.forEach(r => {
                    r.classList.remove('opacity-100', 'translate-x-0');
                    r.classList.add('opacity-0', 'translate-x-4');
                });

                document.querySelectorAll('.lp-counter').forEach(c => c.innerHTML = c.dataset.decimals ? '0.00' : '0');
                btn.innerHTML = 'Importeer Nu';
                btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-400');
                btn.classList.add('bg-indigo-600', 'hover:bg-indigo-500');

                const rawText = `DO9369-106 J4 Retro Military Blue\nSize: 44  Qty: 1  Price: ￥240.00\n\nZip-Up Hoodie Tech Black\nSize: M   Qty: 1  Price: ￥210.00\n\nGoyard Classic Wallet\nColor: Black/Brown Qty: 1 Price: ￥175.00`;
                
                let i = 0;
                
                // Typing effect (Simulates pasting or quick typing)
                setTimeout(() => {
                    cursor.classList.remove('animate-pulse');
                    const typingInterval = setInterval(() => {
                        textarea.innerHTML += rawText.charAt(i);
                        i++;
                        if (i >= rawText.length) {
                            clearInterval(typingInterval);
                            cursor.classList.add('animate-pulse');
                            
                            // Highlight button
                            setTimeout(() => {
                                btn.classList.add('animate-pulse');
                                setTimeout(() => {
                                    btn.classList.remove('animate-pulse');
                                    // Click button
                                    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Analyzing...';
                                    
                                    // Transition to step 2 (Results)
                                    setTimeout(() => {
                                        step1.classList.add('opacity-0', '-translate-y-8', 'pointer-events-none');
                                        step2.classList.remove('opacity-0', 'translate-y-8', 'pointer-events-none');
                                        step2.classList.add('opacity-100', 'translate-y-0');
                                        
                                        btn.innerHTML = 'Succes';
                                        btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
                                        btn.classList.add('bg-emerald-500', 'hover:bg-emerald-400');

                                        // Animate rows
                                        setTimeout(() => {
                                            rows.forEach((r, idx) => {
                                                setTimeout(() => {
                                                    r.classList.remove('opacity-0', 'translate-x-4');
                                                    r.classList.add('opacity-100', 'translate-x-0');
                                                }, idx * 150);
                                            });
                                            
                                            // Animate Counters
                                            document.querySelectorAll('.lp-counter').forEach(counter => {
                                                const target = parseFloat(counter.getAttribute('data-target'));
                                                const decimals = parseInt(counter.getAttribute('data-decimals') || 0);
                                                const duration = 1500;
                                                const steps = 30;
                                                const step = target / steps;
                                                let current = 0;
                                                let cv = setInterval(() => {
                                                    current += step;
                                                    if(current >= target) {
                                                        current = target;
                                                        clearInterval(cv);
                                                    }
                                                    counter.innerHTML = decimals ? current.toFixed(decimals) : Math.round(current);
                                                }, duration / steps);
                                            });
                                            
                                            // Loop back after 8 seconds
                                            setTimeout(runLivePreview, 8000);
                                            
                                        }, 400);
                                        
                                    }, 1000); // Analyze time
                                }, 500); // button pulse time
                            }, 500); // button wait
                        }
                    }, 15); // typing speed
                }, 1000); // initial delay
            };
            
            // Start the sequence when in view
            const sceneObserver = new IntersectionObserver((entries) => {
                if(entries[0].isIntersecting) {
                    runLivePreview();
                    sceneObserver.disconnect();
                }
            }, { threshold: 0.5 });
            
            const sceneContainer = document.querySelector('.scene');
            if(sceneContainer) sceneObserver.observe(sceneContainer);
        });
    </script>
</body>
</html>
