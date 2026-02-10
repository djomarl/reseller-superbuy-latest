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
            currentQcIndex: 0,
            
            // Hier laden we de templates in vanuit de controller
            templates: {{ Js::from($templates) }},
            
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
            openQc(photos) {
                this.qcPhotos = photos || [];
                this.currentQcIndex = 0;
                this.showQcModal = true;
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

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-show="viewMode === 'table'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-4 py-4 w-16">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 ml-6">
                            </th>
                            <th class="px-6 py-4">Item</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">QC</th>
                            <th class="px-6 py-4">Pakket</th>
                            <th class="px-6 py-4 text-right">Inkoop</th>
                            <th class="px-6 py-4 text-right">Verkoop</th>
                            <th class="px-6 py-4 text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="sortable-list">
                        @forelse($items as $item)
                        <tr class="item-row hover:bg-indigo-50/30 transition-colors group cursor-pointer border-b border-transparent"
                            :class="selectedItems.includes({{ $item->id }}) ? '!bg-indigo-50 border-indigo-100' : ''"
                            @click="toggleRow({{ $item->id }}, $event)"
                            data-id="{{ $item->id }}">
                            <td class="px-4 py-4 w-12 align-top">
                                <div class="cursor-grab drag-handle text-slate-300 hover:text-slate-500 mr-2 inline-block">
                                   <i class="fa-solid fa-grip-vertical"></i>
                                </div>
                                <input type="checkbox" class="item-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mt-1"
                                    value="{{ $item->id }}"
                                    @click.stop="toggleItem({{ $item->id }}, $event)"
                                    :checked="selectedItems.includes({{ $item->id }})">
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex gap-4">
                                    <div class="w-16 h-16 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden border border-slate-200">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-300 text-xs">Geen img</div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-800 text-sm leading-tight mb-1 hover:text-indigo-600 hover:underline cursor-pointer" @click.stop="openEdit({{ Js::from($item) }})">{{ Str::limit($item->name, 50) }}</div>
                                        <div class="text-xs text-slate-500 font-medium mb-2">{{ $item->brand ?? 'Onbekend merk' }}</div>
                                        
                                        <div class="flex flex-wrap gap-2 text-[10px] uppercase font-bold tracking-wide">
                                            @if($item->size) 
                                                <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">{{ $item->size }}</span> 
                                            @endif
                                            <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">{{ $item->category ?? 'Overige' }}</span>
                                            @if($item->order_nmr)
                                                <span class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded border border-blue-100 font-mono">{{ $item->order_nmr }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <form action="{{ route('inventory.update', $item) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold uppercase rounded-lg px-2 py-1 border-none cursor-pointer shadow-sm transition ring-1 ring-inset w-24 block
                                        {{ $item->status == 'sold' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : ($item->status == 'online' ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10' : 'bg-slate-50 text-slate-600 ring-slate-500/10') }}">
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
                                </form>
                            </td>
                            <td class="px-6 py-4 align-top">
                                @if(!empty($item->qc_photos))
                                    <button type="button" @click="openQc({{ Js::from($item->qc_photos) }})" class="text-[10px] font-bold bg-purple-50 text-purple-700 px-2.5 py-1 rounded-lg border border-purple-100 hover:bg-purple-100 transition flex items-center gap-1.5 whitespace-nowrap">
                                        <i class="fa-solid fa-camera"></i> QC ({{ count($item->qc_photos) }})
                                    </button>
                                @else
                                    <span class="text-slate-300 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="text-xs text-slate-600 font-medium whitespace-nowrap">
                                    {{ $item->parcel ? ($item->parcel->parcel_no ?? 'Pakket #' . $item->parcel->id) : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top text-right text-xs font-mono text-slate-500">
                                € {{ number_format($item->buy_price, 2) }}
                            </td>
                            <td class="px-6 py-4 align-top text-right">
                                <span class="font-bold text-sm text-slate-800">
                                    @if($item->sell_price) € {{ number_format($item->sell_price, 2) }} @else <span class="text-slate-300">-</span> @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top text-right">
                                <div class="flex justify-end items-center gap-2">
                                    @if($item->status !== 'sold')
                                        <button @click.stop="openSell({{ Js::from($item) }})" 
                                            class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-[10px] font-bold uppercase rounded-lg px-2 py-1 transition flex items-center gap-1 shadow-sm ring-1 ring-emerald-600/10">
                                            <i class="fa-solid fa-money-bill-wave"></i> Verkocht
                                        </button>
                                    @endif
                                    <button type="button" @click.stop="openEdit({{ Js::from($item) }})" class="flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-lg transition text-xs font-bold ring-1 ring-indigo-200" title="Bewerken">
                                        <i class="fa-solid fa-pen"></i> Bewerk
                                    </button>
                                    <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                        @csrf @method('DELETE')
                                        <button class="text-slate-300 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition" title="Verwijderen">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-slate-400 italic">Geen items gevonden.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" x-show="viewMode === 'cards'" x-cloak>
            @forelse($items as $item)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" :class="selectedItems.includes({{ $item->id }}) ? 'ring-2 ring-indigo-500' : ''">
                    <div class="absolute top-3 left-3 z-10">
                        <input type="checkbox" class="item-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 bg-white shadow-lg"
                            value="{{ $item->id }}"
                            @change="toggleItem({{ $item->id }})"
                            :checked="selectedItems.includes({{ $item->id }})">
                    </div>
                    <div class="h-44 bg-slate-100 overflow-hidden relative">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-sm">Geen afbeelding</div>
                        @endif
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-slate-800">{{ Str::limit($item->name, 40) }}</div>
                                <div class="text-xs text-slate-400">{{ $item->brand ?? '—' }}</div>
                                <div class="text-[11px] text-slate-400">Order Nmr: <span class="font-mono">{{ $item->order_nmr ?? '-' }}</span></div>
                            </div>
                            <span class="text-[10px] font-bold uppercase rounded-full px-2 py-1 border border-slate-200 text-slate-500">{{ $item->category ?? 'Overige' }}</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <div class="text-slate-500">Maat: <span class="font-mono">{{ $item->size ?? '-' }}</span></div>
                            <div class="text-slate-800 font-bold">€ {{ number_format($item->sell_price ?? 0, 2) }}</div>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <div class="text-slate-400">Inkoop: € {{ number_format($item->buy_price ?? 0, 2) }}</div>
                            <form action="{{ route('inventory.update', $item) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold uppercase rounded-lg px-2 py-1 border-none cursor-pointer shadow-sm transition ring-1 ring-inset w-24
                                        {{ $item->status == 'sold' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : ($item->status == 'online' ? 'bg-indigo-50 text-indigo-700 ring-indigo-700/10' : 'bg-slate-50 text-slate-600 ring-slate-500/10') }}">
                                    @if($view === 'archive')
                                        <option value="sold" selected>Verkocht</option>
                                        <option value="online">Zet terug</option>
                                    @else
                                        <option value="todo" {{ $item->status == 'todo' ? 'selected' : '' }}>To-do</option>
                                        <option value="prep" {{ $item->status == 'prep' ? 'selected' : '' }}>Prep</option>
                                        <option value="online" {{ $item->status == 'online' ? 'selected' : '' }}>Online</option>
                                        <option value="sold">Verkocht</option>
                                    @endif
                                </select>
                            </form>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t border-slate-100 gap-2">
                            <div>
                                @if(!empty($item->qc_photos))
                                    <button @click="openQc({{ Js::from($item->qc_photos) }})" class="text-[10px] font-bold bg-purple-50 text-purple-700 px-2 py-1 rounded border border-purple-100 hover:bg-purple-100 transition flex items-center gap-1">
                                        <i class="fa-solid fa-camera"></i> QC
                                    </button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <button @click="openEdit({{ Js::from($item) }})" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded-lg text-xs font-bold transition">Bewerk</button>
                                <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1.5 rounded-lg text-xs font-bold transition">Verwijder</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-slate-400 italic">Geen items gevonden.</div>
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
                    <form :action="'/inventory/' + editingItem.id" method="POST" class="p-6">
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
                        <button type="button" @click="showQcModal = false" class="absolute top-4 right-4 text-white hover:text-gray-300 z-50 bg-black/50 rounded-full p-2">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        
                        <div class="flex items-center justify-center h-[80vh] relative">
                             <img :src="qcPhotos[currentQcIndex]" class="max-h-full max-w-full object-contain">
                             
                             <button x-show="qcPhotos.length > 1" @click="currentQcIndex = (currentQcIndex - 1 + qcPhotos.length) % qcPhotos.length" class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black/30 hover:bg-black/60 rounded-full p-3 transition">
                                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                             </button>
                             <button x-show="qcPhotos.length > 1" @click="currentQcIndex = (currentQcIndex + 1) % qcPhotos.length" class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black/30 hover:bg-black/60 rounded-full p-3 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                        
                        <div class="bg-slate-900 p-4 flex gap-2 overflow-x-auto justify-center">
                            <template x-for="(photo, index) in qcPhotos" :key="index">
                                <img :src="photo" @click="currentQcIndex = index" 
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
             <div class="bg-white p-6 rounded-3xl shadow-2xl w-full max-w-sm m-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-heading font-bold text-xl text-emerald-700">Money Time! 🤑</h3>
                    <button @click="showSellModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <p class="text-slate-500 text-sm mb-4">Je staat op het punt <strong x-text="sellingItem.name"></strong> als verkocht te markeren.</p>
                <form :action="'/inventory/' + sellingItem.id + '/sold'" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Verkoopprijs (€)*</label>
                            <input type="number" step="0.01" name="sell_price" required x-model="sellingItem.sell_price" class="w-full p-3 rounded-xl border-emerald-200 mt-1 focus:ring-emerald-500 focus:border-emerald-500 font-bold text-lg text-emerald-800 bg-emerald-50" placeholder="0.00" autofocus>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Verkoopdatum</label>
                            <input type="date" name="sold_date" x-model="sellingItem.sold_date" class="w-full p-3 rounded-xl border-slate-200 mt-1">
                        </div>
                        <button class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg mt-2 flex justify-center items-center gap-2">
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

    </div>
</x-app-layout>
