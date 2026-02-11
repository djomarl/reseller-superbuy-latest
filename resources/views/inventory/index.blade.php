<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="{
            showImport: false,
            showNew: false,
            showEditModal: false,
            showSellModal: false,
            showQcModal: false,
            viewMode: 'table',
            selectedItems: [],
            lastChecked: null,
            showBulkActions: false,
            editingItem: {},
            sellingItem: {},
            qcPhotos: [],
            qcItem: null,
            currentQcIndex: 0,
            showImageModal: false,
            activeImageUrl: null,
            
            // Hier laden we de templates in vanuit de controller
            templates: {{ Js::from($templates) }},

            openImage(url) {
                if(url) {
                    this.activeImageUrl = url;
                    this.showImageModal = true;
                }
            },
            
            toggleAll(event) {
                if (event.target.checked) {
                    this.selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).map(cb => parseInt(cb.value));
                    // Check all checkboxes in DOM manually to ensure visual sync
                    document.querySelectorAll('.item-checkbox').forEach(el => el.checked = true);
                } else {
                    this.selectedItems = [];
                    document.querySelectorAll('.item-checkbox').forEach(el => el.checked = false);
                }
            },
            toggleItem(id, event) {
                // Handle Shift-Click for range selection
                if (event && event.shiftKey && this.lastChecked) {
                    const checkboxes = Array.from(document.querySelectorAll('.item-checkbox'));
                    const start = checkboxes.findIndex(cb => parseInt(cb.value) === this.lastChecked);
                    const end = checkboxes.findIndex(cb => parseInt(cb.value) === id);
                    
                    const subset = checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1);
                    subset.forEach(cb => {
                        const val = parseInt(cb.value);
                        if (!this.selectedItems.includes(val)) {
                            this.selectedItems.push(val);
                            cb.checked = true;
                        }
                    });
                } else {
                    if (this.selectedItems.includes(id)) {
                        this.selectedItems = this.selectedItems.filter(i => i !== id);
                    } else {
                        this.selectedItems.push(id);
                    }
                }
                this.lastChecked = id;
            },
            toggleRow(id, event) {
                // Prevent toggling if clicked on button or link or input inside row
                if (event.target.tagName === 'BUTTON' || event.target.tagName === 'A' || event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.closest('button') || event.target.closest('a') || event.target.closest('.drag-handle')) {
                    return;
                }
                this.toggleItem(id, event);
            },
            applyPreset(event) {
                const id = event.target.value;
                const template = this.templates.find(t => t.id == id);
                if (template) {
                    // Vul de velden in het formulier (via ID's)
                    document.getElementById('new_name').value = template.name || '';
                    document.getElementById('new_brand').value = template.brand || '';
                    document.getElementById('new_category').value = template.category || 'Overige';
                    document.getElementById('new_size').value = template.size || '';
                    document.getElementById('new_buy_price').value = template.default_buy_price || '';
                    document.getElementById('new_sell_price').value = template.default_sell_price || '';
                }
            },
            openEdit(item) {
                this.editingItem = JSON.parse(JSON.stringify(item));
                this.showEditModal = true;
            },
            openSell(item) {
                this.sellingItem = JSON.parse(JSON.stringify(item));
                if (!this.sellingItem.sold_date) {
                    this.sellingItem.sold_date = new Date().toISOString().split('T')[0];
                }
                this.showSellModal = true;
            },
            openQc(photos, item) {
                this.qcPhotos = photos || [];
                this.qcItem = item || null;
                this.currentQcIndex = 0;
                this.showQcModal = true;
            },
            async setMainImage() {
                if (!this.qcItem || this.qcPhotos.length === 0) return;
                const imageUrl = this.qcPhotos[this.currentQcIndex];

                if(!confirm('Wil je deze foto instellen als hoofdafbeelding?')) return;

                try {
                    const response = await fetch(`/inventory/${this.qcItem.id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'PATCH',
                            image_url: imageUrl
                        })
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Kon afbeelding niet instellen.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Er ging iets mis.');
                }
            },
            initSortable() {
                if(this.viewMode === 'table') {
                    const el = document.querySelector('tbody#sortable-list');
                    if(el) {
                        Sortable.create(el, {
                            handle: '.drag-handle',
                            animation: 150,
                            onEnd: function (evt) {
                                // Logic to save order via API
                                // Call a function to update order
                                updateSortOrder(); 
                            }
                        });
                    }
                }
            }
         }"
         x-init="$watch('selectedItems', value => showBulkActions = value.length > 0); initSortable();">
         
         <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
         
         <script>
            function updateSortOrder() {
                 const ids = Array.from(document.querySelectorAll('.item-row')).map(row => row.getAttribute('data-id'));
                 fetch('{{ route("inventory.reorder") }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
                     },
                     body: JSON.stringify({ ids: ids })
                 }).then(res => res.json()).then(data => {
                     console.log('Order updated');
                 });
            }
         </script>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl relative shadow-sm">
                <strong class="font-bold">Succes!</strong> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative shadow-sm">
                <strong class="font-bold">Fout!</strong> {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 mb-8">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <h2 class="font-heading font-bold text-2xl text-slate-800">
                    {{ $view === 'archive' ? '📦 Archief (Verkocht)' : '📦 Mijn Voorraad' }}
                    <span class="text-slate-400 text-lg ml-2">({{ $items->total() }})</span>
                </h2>
                <div class="flex flex-wrap gap-2">
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <button type="button" @click="viewMode = 'table'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Tabel
                        </button>
                        <button type="button" @click="viewMode = 'cards'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === 'cards' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Cards
                        </button>
                    </div>
                    <a href="{{ route('superbuy.index') }}" class="px-4 py-2 bg-white border border-indigo-100 text-indigo-600 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-50 transition flex items-center">
                        <i class="fa-solid fa-layer-group mr-2"></i> Import Superbuy
                    </a>
                    <button @click="showImport = true" class="px-4 py-2 bg-white border border-indigo-100 text-indigo-600 rounded-xl text-sm font-bold shadow-sm hover:bg-indigo-50 transition">
                        Import
                    </button>
                    <button @click="showNew = true" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-slate-800 transition">
                        + Nieuw
                    </button>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col lg:flex-row gap-4 justify-between items-center">
                <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto flex-1">
                    <input type="hidden" name="view" value="{{ $view }}">

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek op naam, merk..."
                            class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <div class="absolute left-3 top-2.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                    </div>

                    <select name="category" onchange="this.form.submit()" class="w-full sm:w-40 py-2 rounded-xl border-slate-200 text-sm cursor-pointer">
                        <option value="">Alle Categorieën</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>

                    <select name="brand" onchange="this.form.submit()" class="w-full sm:w-40 py-2 rounded-xl border-slate-200 text-sm cursor-pointer">
                        <option value="">Alle Merken</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                        @endforeach
                    </select>

                    @if($view !== 'archive')
                    <select name="status" onchange="this.form.submit()" class="w-full sm:w-40 py-2 rounded-xl border-slate-200 text-sm cursor-pointer">
                        <option value="">Alle Statussen</option>
                        <option value="todo" {{ request('status') == 'todo' ? 'selected' : '' }}>To-do</option>
                        <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                        <option value="prep" {{ request('status') == 'prep' ? 'selected' : '' }}>Prep</option>
                    </select>
                    @endif

                    @if(request()->hasAny(['search', 'category', 'brand', 'status']))
                        <a href="{{ route('inventory.index', ['view' => $view]) }}" class="flex items-center justify-center px-3 py-2 text-slate-400 hover:text-red-500 transition">✕</a>
                    @endif
                </form>

                <div class="flex bg-slate-100 p-1 rounded-xl">
                    <a href="{{ route('inventory.index', ['view' => 'active']) }}"
                       class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $view !== 'archive' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                       Voorraad
                    </a>
                    <a href="{{ route('inventory.index', ['view' => 'archive']) }}"
                       class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $view === 'archive' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                       Archief
                    </a>
                </div>
            </div>
        </div>

        <!-- Sticky Bulk Actions Bar -->
        <div x-show="showBulkActions" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-10"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-10"
             class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white border border-slate-200 text-slate-800 rounded-full shadow-2xl px-6 py-3 flex items-center gap-6 z-40 w-auto max-w-4xl ring-1 ring-slate-900/5">
            
            <div class="flex items-center gap-2 border-r border-slate-200 pr-6">
                <span class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="selectedItems.length"></span>
                <span class="text-sm font-semibold text-slate-600">Geselecteerd</span>
            </div>

            <form method="POST" action="{{ route('inventory.bulkAction') }}" class="flex items-center gap-3" onsubmit="return confirm('Weet je het zeker?')">
                @csrf
                <input type="hidden" name="items" :value="JSON.stringify(selectedItems)">
                <input type="hidden" name="action" id="bulkActionInput">
                <input type="hidden" name="status" id="bulkStatusInput">
                <input type="hidden" name="parcel_id" id="bulkParcelInput">

                <!-- Status Action -->
                <div class="relative group">
                    <button type="button" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 text-sm font-semibold transition">
                        <i class="fa-solid fa-tag text-slate-400"></i> Status
                        <i class="fa-solid fa-chevron-down text-xs text-slate-300"></i>
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute bottom-full left-0 mb-2 w-40 bg-white rounded-xl shadow-xl border border-slate-100 p-1 hidden group-hover:block">
                        <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='todo'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">To-do</button>
                        <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='prep'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Prep</button>
                        <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='online'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Online</button>
                        <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='sold'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Verkocht</button>
                    </div>
                </div>

                <!-- Parcel Action -->
                 <div class="relative group">
                    <button type="button" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 text-sm font-semibold transition">
                        <i class="fa-solid fa-box text-slate-400"></i> Pakket
                        <i class="fa-solid fa-chevron-down text-xs text-slate-300"></i>
                    </button>
                    <div class="absolute bottom-full left-0 mb-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 p-1 hidden group-hover:block max-h-60 overflow-y-auto">
                        @foreach($parcels as $p)
                             <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_parcel'; document.getElementById('bulkParcelInput').value='{{ $p->id }}'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">
                                {{ $p->parcel_no }}
                             </button>
                        @endforeach
                    </div>
                </div>

                <!-- Delete Action -->
                <button type="submit" onclick="document.getElementById('bulkActionInput').value='delete'" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-red-50 text-red-600 text-sm font-semibold transition ml-2">
                    <i class="fa-solid fa-trash"></i> Verwijderen
                </button>
            </form>

            <button @click="selectedItems = []; document.querySelectorAll('.item-checkbox').forEach(el => el.checked = false);" class="ml-auto text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" x-show="viewMode === 'table'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 w-16">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                            </th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Item Details</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">QC</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Pakket</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Inkoop</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Verkoop</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="sortable-list">
                        @forelse($items as $item)
                        <tr class="item-row hover:bg-slate-50/80 transition-all duration-200 group border-l-4 border-l-transparent hover:border-l-indigo-500"
                            :class="selectedItems.includes({{ $item->id }}) ? '!bg-indigo-50/50 !border-l-indigo-600' : ''"
                            @click="toggleRow({{ $item->id }}, $event)"
                            data-id="{{ $item->id }}">
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="cursor-grab drag-handle text-slate-300 hover:text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                       <i class="fa-solid fa-grip-vertical"></i>
                                    </div>
                                    <input type="checkbox" class="item-checkbox rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                                        value="{{ $item->id }}"
                                        @click.stop="toggleItem({{ $item->id }}, $event)"
                                        :checked="selectedItems.includes({{ $item->id }})">
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-5 items-center">
                                    <div class="relative w-16 h-16 rounded-xl bg-slate-100 flex-shrink-0 overflow-hidden border border-slate-200 group/img cursor-zoom-in shadow-sm" @click.stop="openImage('{{ $item->image_url }}')">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}" class="w-full h-full object-cover transition-transform duration-500 group-hover/img:scale-110" referrerpolicy="no-referrer">
                                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center">
                                                <i class="fa-solid fa-magnifying-glass text-white text-xs drop-shadow-md"></i>
                                            </div>
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                <i class="fa-solid fa-image text-lg"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0 py-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-extrabold tracking-wider text-slate-500 uppercase">{{ $item->brand ?? 'Onbekend' }}</span>
                                            @if($item->size) 
                                                <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">{{ $item->size }}</span> 
                                            @endif
                                        </div>
                                        <div class="font-bold text-slate-800 text-sm leading-tight mb-1.5 hover:text-indigo-600 cursor-pointer line-clamp-1" @click.stop="openEdit({{ Js::from($item) }})">
                                            {{ $item->name }}
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-500 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-100">
                                                <i class="fa-solid fa-layer-group text-[9px] text-slate-400"></i> {{ $item->category ?? 'Overige' }}
                                            </span>
                                            @if($item->order_nmr)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-mono text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                                    #{{ $item->order_nmr }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <form action="{{ route('inventory.update', $item) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <div class="relative">
                                        <select name="status" onchange="this.form.submit()" 
                                            class="appearance-none pl-3 pr-8 py-1.5 text-xs font-bold uppercase rounded-lg border-0 cursor-pointer focus:ring-2 focus:ring-offset-1 transition shadow-sm w-32
                                            {{ $item->status == 'sold' ? 'bg-emerald-100 text-emerald-700 focus:ring-emerald-500' : 
                                               ($item->status == 'online' ? 'bg-indigo-100 text-indigo-700 focus:ring-indigo-500' : 
                                               ($item->status == 'prep' ? 'bg-amber-100 text-amber-700 focus:ring-amber-500' : 
                                               'bg-slate-100 text-slate-600 focus:ring-slate-500')) }}">
                                            @if($view === 'archive')
                                                <option value="sold" selected>Verkocht</option>
                                                <option value="online">Zet terug</option>
                                            @else
                                                <option value="todo" {{ $item->status == 'todo' ? 'selected' : '' }}>To-do</option>
                                                <option value="prep" {{ $item->status == 'prep' ? 'selected' : '' }}>Prep</option>
                                                <option value="online" {{ $item->status == 'online' ? 'selected' : '' }}>Online</option>
                                                <option value="sold">Markeer Verkocht</option>
                                            @endif
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                            <svg class="h-3 w-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                                        </div>
                                    </div>
                                </form>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if(!empty($item->qc_photos))
                                    <button type="button" @click="openQc({{ Js::from($item->qc_photos) }}, {{ Js::from($item) }})" class="group flex items-center gap-2 bg-white hover:bg-purple-50 border border-slate-200 hover:border-purple-200 text-slate-600 hover:text-purple-700 px-3 py-1.5 rounded-lg transition shadow-sm">
                                        <div class="relative">
                                            <i class="fa-solid fa-camera text-slate-400 group-hover:text-purple-500 transition-colors"></i>
                                            <span class="absolute -top-1 -right-1 flex h-2 w-2">
                                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                                              <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                                            </span>
                                        </div>
                                        <span class="text-xs font-bold">{{ count($item->qc_photos) }}</span>
                                    </button>
                                @else
                                    <span class="inline-block w-8 h-1 bg-slate-100 rounded-full"></span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle">
                                @if($item->parcel)
                                    <a href="#" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-50 text-slate-600 text-xs font-medium border border-slate-100 hover:bg-slate-100 transition">
                                        <i class="fa-solid fa-box text-slate-400"></i>
                                        {{ $item->parcel->parcel_no ?? '#' . $item->parcel->id }}
                                    </a>
                                @else
                                    <span class="text-slate-300 text-lg">&middot;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="font-mono text-xs font-medium text-slate-500">
                                    €{{ number_format($item->buy_price, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="inline-block font-bold text-sm text-slate-800 bg-emerald-50/50 px-2 py-1 rounded border border-emerald-100/50">
                                    @if($item->sell_price) €{{ number_format($item->sell_price, 2) }} @else <span class="text-slate-400">-</span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="flex justify-end items-center gap-2">
                                    @if($item->status !== 'sold')
                                        <button @click.stop="openSell({{ Js::from($item) }})" 
                                            class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition shadow-sm hover:shadow-md flex items-center gap-1.5 border border-emerald-600">
                                            <i class="fa-solid fa-money-bill-wave"></i> Verkopen
                                        </button>
                                    @endif

                                    <button type="button" @click.stop="openEdit({{ Js::from($item) }})" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-indigo-600 bg-white hover:bg-indigo-50 border border-slate-200 rounded-lg transition shadow-sm" title="Bewerken">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    
                                    <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                        @csrf @method('DELETE')
                                        <button class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-red-500 bg-white hover:bg-red-50 border border-slate-200 rounded-lg transition shadow-sm" title="Verwijderen">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-20">
                                <div class="flex flex-col items-center justify-center text-slate-300">
                                    <i class="fa-solid fa-box-open text-4xl mb-3 opacity-50"></i>
                                    <span class="text-sm font-medium italic">Geen items gevonden in deze weergave.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" x-show="viewMode === 'cards'" x-cloak>
            @forelse($items as $item)
                <div class="group relative bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 hover:border-indigo-100 flex flex-col h-full overflow-hidden" 
                     :class="selectedItems.includes({{ $item->id }}) ? 'ring-2 ring-indigo-500 border-transparent shadow-indigo-100' : ''">
                    
                    <!-- Image Section -->
                    <div class="relative aspect-square bg-slate-50 overflow-hidden cursor-zoom-in" @click="openImage('{{ $item->image_url }}')">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" 
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
                                 referrerpolicy="no-referrer"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                                <span class="text-white font-medium text-sm flex items-center gap-2"><i class="fa-solid fa-magnifying-glass-plus"></i> Vergroten</span>
                            </div>
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                                <i class="fa-solid fa-image text-3xl mb-2 opacity-50"></i>
                                <span class="text-xs font-medium">Geen afbeelding</span>
                            </div>
                        @endif

                        <!-- Top Controls -->
                        <div class="absolute top-3 left-3 z-10" @click.stop>
                            <input type="checkbox" class="item-checkbox w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 bg-white/90 backdrop-blur shadow-sm cursor-pointer"
                                value="{{ $item->id }}"
                                @change="toggleItem({{ $item->id }})"
                                :checked="selectedItems.includes({{ $item->id }})">
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3 z-10 pointer-events-none">
                             @if($view === 'archive' || $item->status == 'sold')
                                <span class="inline-flex items-center gap-1 bg-emerald-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-check"></i> Verkocht
                                </span>
                             @elseif($item->status == 'online')
                                <span class="inline-flex items-center gap-1 bg-indigo-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-globe"></i> Online
                                </span>
                             @elseif($item->status == 'prep')
                                <span class="inline-flex items-center gap-1 bg-amber-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-box-open"></i> Prep
                                </span>
                             @else
                                <span class="inline-flex items-center gap-1 bg-slate-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-list"></i> To-do
                                </span>
                             @endif
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-4 flex flex-col flex-1">
                        <div class="mb-1 flex justify-between items-start">
                            <span class="text-[10px] font-extrabold tracking-wider text-slate-400 uppercase">{{ $item->brand ?? 'Onbekend' }}</span>
                            @if($item->size)
                                <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">{{ $item->size }}</span>
                            @endif
                        </div>
                        
                        <h3 class="font-bold text-slate-900 leading-tight mb-2 line-clamp-2 min-h-[2.5rem]" title="{{ $item->name }}">{{ $item->name }}</h3>
                        
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-[10px] font-bold bg-slate-50 text-slate-500 px-2 py-1 rounded-lg border border-slate-100 truncate max-w-[100px]">{{ $item->category ?? 'Overige' }}</span>
                            @if($item->order_nmr)
                                <span class="text-[10px] font-mono text-blue-500 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100 truncate" title="Order #{{ $item->order_nmr }}">#{{ $item->order_nmr }}</span>
                            @endif
                        </div>

                        <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Verkoop</span>
                                <span class="text-lg font-bold text-slate-900">€ {{ number_format($item->sell_price ?? 0, 2) }}</span>
                            </div>
                            @if($item->buy_price > 0)
                            <div class="flex flex-col items-end text-right">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Inkoop</span>
                                <span class="text-xs font-semibold text-slate-500">€ {{ number_format($item->buy_price, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Footer -->
                    <div class="bg-slate-50/50 p-2 border-t border-slate-100">
                        @if($item->status !== 'sold')
                            <div class="grid grid-cols-5 gap-2">
                                <button @click="openSell({{ Js::from($item) }})" class="col-span-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg py-2 text-xs font-bold shadow-sm shadow-emerald-200 transition flex items-center justify-center gap-1.5 transform hover:-translate-y-0.5 active:translate-y-0">
                                    <i class="fa-solid fa-money-bill-wave"></i> VERKOPEN
                                </button>
                                
                                <button @click="openEdit({{ Js::from($item) }})" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-indigo-600 rounded-lg py-2 text-xs font-bold transition flex items-center justify-center" title="Bewerken">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>

                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.outside="open = false" class="w-full h-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-slate-700 rounded-lg text-xs font-bold transition flex items-center justify-center">
                                        <i class="fa-solid fa-ellipsis text-sm"></i>
                                    </button>
                                    
                                    <div x-show="open" class="absolute bottom-full right-0 mb-2 w-36 bg-white rounded-xl shadow-xl border border-slate-100 p-1 z-20 origin-bottom-right" style="display: none;">
                                        @if(!empty($item->qc_photos))
                                            <button @click="openQc({{ Js::from($item->qc_photos) }}, {{ Js::from($item) }}); open=false" class="w-full text-left px-3 py-2 text-xs font-bold text-purple-600 hover:bg-purple-50 rounded-lg flex items-center gap-2">
                                                <i class="fa-solid fa-camera"></i> QC Foto's
                                            </button>
                                        @endif
                                        <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                            @csrf @method('DELETE')
                                            <button class="w-full text-left px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2">
                                                <i class="fa-solid fa-trash"></i> Verwijder
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Sold State Toolbar -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-emerald-50 text-emerald-700 rounded-lg py-1.5 text-xs font-bold flex items-center justify-center gap-1 border border-emerald-100">
                                    <i class="fa-solid fa-check-circle"></i> VERKOCHT
                                </div>
                                <div class="flex gap-1">
                                    <button @click="openEdit({{ Js::from($item) }})" class="flex-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-400 hover:text-indigo-600 rounded-lg text-xs transition flex items-center justify-center p-2">
                                        <i class="fa-solid fa-pen text-sm"></i>
                                    </button>
                                    <form action="{{ route('inventory.destroy', $item) }}" method="POST" class="flex-1" onsubmit="return confirm('Zeker weten?')">
                                            @csrf @method('DELETE')
                                        <button class="w-full h-full bg-white border border-slate-200 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-lg text-xs transition flex items-center justify-center p-2">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-24 text-slate-400">
                    <div class="bg-slate-50 p-6 rounded-full mb-4">
                        <i class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                    </div>
                    <p class="font-medium">Geen items gevonden.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $items->links() }}
        </div>

        <!-- Quick Edit Modal -->
        <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showEditModal" @click="showEditModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <form :action="'/inventory/' + editingItem.id" method="POST" class="p-6" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        <div class="mb-5 flex justify-between items-center">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Snel Bewerken</h3>
                            <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-500">
                                <span class="sr-only">Sluiten</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Naam</label>
                                <input type="text" name="name" x-model="editingItem.name" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Merk</label>
                                    <input type="text" name="brand" x-model="editingItem.brand" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Maat</label>
                                    <input type="text" name="size" x-model="editingItem.size" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Inkoop</label>
                                    <input type="number" step="0.01" name="buy_price" x-model="editingItem.buy_price" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Verkoop</label>
                                    <input type="number" step="0.01" name="sell_price" x-model="editingItem.sell_price" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Notities</label>
                                <textarea name="notes" x-model="editingItem.notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Afbeelding</label>
                                <input type="file" name="image" accept="image/*" class="mt-1 block w-full text-slate-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="showEditModal = false" class="bg-white py-2 px-4 border border-slate-300 rounded-xl shadow-sm text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none transition">Annuleren</button>
                            <button type="submit" class="bg-indigo-600 py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">Opslaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- QC Photos Modal -->
        <div x-show="showQcModal" style="display: none;" class="fixed inset-0 z-[110] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showQcModal" @click="showQcModal = false" class="fixed inset-0 bg-slate-900 bg-opacity-90 transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-transparent rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-5xl">
                     <div class="relative bg-black rounded-lg overflow-hidden">
                        <button type="button" @click="setMainImage()" class="absolute top-4 left-4 text-xs font-bold bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 z-50 shadow-lg flex items-center gap-2">
                            <i class="fa-solid fa-image"></i> Stel in als hoofdfoto
                        </button>

                        <button type="button" @click="showQcModal = false" class="absolute top-4 right-4 text-white hover:text-gray-300 z-50 bg-black/50 rounded-full p-2">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        
                        <div class="flex items-center justify-center h-[80vh] relative">
                             <img :src="qcPhotos[currentQcIndex]" class="max-h-full max-w-full object-contain" referrerpolicy="no-referrer">
                             
                             <button x-show="qcPhotos.length > 1" @click="currentQcIndex = (currentQcIndex - 1 + qcPhotos.length) % qcPhotos.length" class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black/30 hover:bg-black/60 rounded-full p-3 transition">
                                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                             </button>
                             <button x-show="qcPhotos.length > 1" @click="currentQcIndex = (currentQcIndex + 1) % qcPhotos.length" class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black/30 hover:bg-black/60 rounded-full p-3 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                        
                        <div class="bg-slate-900 p-4 flex gap-2 overflow-x-auto justify-center">
                            <template x-for="(photo, index) in qcPhotos" :key="index">
                                <img :src="photo" referrerpolicy="no-referrer" @click="currentQcIndex = index" 
                                class="h-16 w-16 object-cover rounded cursor-pointer border-2 transition opacity-70 hover:opacity-100"
                                :class="currentQcIndex === index ? 'border-indigo-500 opacity-100' : 'border-transparent'">
                            </template>
                        </div>
                     </div>
                </div>
            </div>
        </div>

        <div x-show="showImport" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showImport = false">
             <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-2xl m-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-heading font-bold text-xl">Import Text</h3>
                    <button @click="showImport = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="text-xs font-bold text-slate-500 uppercase">Koppel aan Pakket</label>
                        <select name="parcel_id" class="w-full p-2.5 border-slate-200 rounded-xl mt-1 bg-slate-50">
                            <option value="">Geen</option>
                            @foreach($parcels as $p)
                                <option value="{{$p->id}}">{{$p->parcel_no}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold text-slate-500 uppercase">Upload Order PDF</label>
                        <input type="file" name="order_pdf" accept="application/pdf" class="w-full p-3 border-slate-200 rounded-xl mt-1 bg-slate-50">
                        <p class="text-[11px] text-slate-400 mt-1">PDF wordt automatisch uitgelezen en toegevoegd aan voorraad.</p>
                    </div>
                    <textarea name="import_text" class="w-full h-40 p-4 border-slate-200 rounded-xl mb-4 text-xs font-mono bg-slate-50 focus:bg-white transition" placeholder="Of plak hier de tekst van de order..."></textarea>
                    <button class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold w-full hover:bg-indigo-700 transition">Importeren 🚀</button>
                </form>
             </div>
        </div>

        <div x-show="showSellModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showSellModal = false">
             <div class="bg-white p-6 rounded-3xl shadow-2xl w-full max-w-sm m-4 transform transition-all scale-100">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-emerald-100 p-2 rounded-full">
                            <i class="fa-solid fa-hand-holding-dollar text-emerald-600 text-xl"></i>
                        </div>
                        <h3 class="font-heading font-bold text-xl text-slate-800">Verkocht!</h3>
                    </div>
                    <button @click="showSellModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition">✕</button>
                </div>
                
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-6">
                    <p class="text-slate-600 text-sm font-medium">Item:</p>
                    <p class="text-slate-900 font-bold text-lg leading-tight" x-text="sellingItem.name"></p>
                </div>

                <form :action="'/inventory/' + sellingItem.id + '/sold'" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Voor hoeveel is het verkocht?</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-emerald-600 font-bold text-lg">€</span>
                                <input type="number" step="0.01" name="sell_price" required x-model="sellingItem.sell_price" 
                                class="w-full pl-10 pr-4 py-3 rounded-xl border-slate-200 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-bold text-xl text-slate-800 shadow-sm transition" placeholder="0.00" autofocus>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Wanneer?</label>
                            <input type="date" name="sold_date" x-model="sellingItem.sold_date" class="w-full p-3 rounded-xl border-slate-200 mt-1 focus:ring-indigo-500 focus:border-indigo-500 font-bold text-slate-700">
                        </div>
                        <button class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-black transition shadow-xl shadow-slate-200 mt-4 flex justify-center items-center gap-2 transform active:scale-[0.98]">
                           <i class="fa-solid fa-check"></i> Bevestigen
                        </button>
                    </div>
                </form>
             </div>
        </div>

        <div x-show="showNew" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showNew = false">
            <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-lg m-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-heading font-bold text-xl">Nieuw Item</h3>
                    <button @click="showNew = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        @if($templates->count() > 0)
                        <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                            <label class="text-xs font-bold text-indigo-800 uppercase block mb-1">⚡️ Vul snel in met Preset</label>
                            <select @change="applyPreset($event)" class="w-full p-2 border-indigo-200 rounded-lg text-sm focus:ring-indigo-500">
                                <option value="">Kies een template...</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Naam*</label>
                            <input type="text" id="new_name" name="name" required class="w-full p-3 rounded-xl border-slate-200 mt-1">
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Order Nmr</label>
                            <input type="text" id="new_order_nmr" name="order_nmr" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                        </div>

                        <div class="flex gap-3">
                            <div class="w-1/2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Merk</label>
                                <input type="text" id="new_brand" name="brand" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                            </div>
                            <div class="w-1/2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Maat</label>
                                <input type="text" id="new_size" name="size" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Categorie</label>
                            <select id="new_category" name="category" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                                <option value="">Automatisch bepalen</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                                <option value="Sneakers">Sneakers</option>
                                <option value="Kleding">Kleding</option>
                                <option value="Accessoires">Accessoires</option>
                            </select>
                        </div>

                        <div class="flex gap-3">
                            <div class="w-1/2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Inkoop (€)</label>
                                <input type="number" step="0.01" id="new_buy_price" name="buy_price" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                            </div>
                            <div class="w-1/2">
                                <label class="text-xs font-bold text-slate-500 uppercase">Verkoop (€)</label>
                                <input type="number" step="0.01" id="new_sell_price" name="sell_price" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Pakket (Optioneel)</label>
                            <select name="parcel_id" class="w-full p-3 rounded-xl border-slate-200 mt-1 bg-white">
                                <option value="">Geen</option>
                                @foreach($parcels as $p)
                                    <option value="{{ $p->id }}">{{ $p->parcel_no }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-slate-800 transition shadow-lg mt-2">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Image Lightbox Modal -->
        <div x-show="showImageModal" 
             style="display: none;" 
             class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="relative w-full max-w-5xl h-full flex flex-col items-center justify-center" @click.outside="showImageModal = false">
                <button @click="showImageModal = false" class="absolute top-0 right-0 z-50 p-4 text-white hover:text-gray-300 transition">
                    <svg class="w-10 h-10 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <img :src="activeImageUrl" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl ring-1 ring-white/10" @click.stop>
            </div>
        </div>

    </div>
</x-app-layout>
