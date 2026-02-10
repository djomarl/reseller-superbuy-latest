<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="{
            showImport: false,
            showNew: false,
            viewMode: 'table',
            selectedItems: [],
            showBulkActions: false,
            // Hier laden we de templates in vanuit de controller
            templates: {{ Js::from($templates) }},
            toggleAll(event) {
                if (event.target.checked) {
                    this.selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).map(cb => parseInt(cb.value));
                } else {
                    this.selectedItems = [];
                }
            },
            toggleItem(id) {
                if (this.selectedItems.includes(id)) {
                    this.selectedItems = this.selectedItems.filter(i => i !== id);
                } else {
                    this.selectedItems.push(id);
                }
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
            }
         }"
         x-init="$watch('selectedItems', value => showBulkActions = value.length > 0)">

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

        <!-- Bulk Actions Bar -->
        <div x-show="showBulkActions" x-cloak class="bg-indigo-600 text-white rounded-2xl shadow-lg p-4 flex flex-wrap items-center gap-4 mb-6">
            <div class="font-bold">
                <span x-text="selectedItems.length"></span> item(s) geselecteerd
            </div>
            <form method="POST" action="{{ route('inventory.bulkAction') }}" class="flex flex-wrap gap-2 flex-1" onsubmit="return confirm('Weet je het zeker?')">
                @csrf
                <input type="hidden" name="items" :value="JSON.stringify(selectedItems)">
                <select name="action" required class="px-3 py-2 rounded-lg text-slate-800 text-sm font-bold">
                    <option value="">Kies actie...</option>
                    <option value="set_status">Status wijzigen</option>
                    <option value="set_parcel">Pakket wijzigen</option>
                    <option value="delete">Verwijderen</option>
                </select>
                <select name="status" class="px-3 py-2 rounded-lg text-slate-800 text-sm">
                    <option value="">Status...</option>
                    <option value="todo">To-do</option>
                    <option value="prep">Prep</option>
                    <option value="online">Online</option>
                    <option value="sold">Verkocht</option>
                </select>
                <select name="parcel_id" class="px-3 py-2 rounded-lg text-slate-800 text-sm">
                    <option value="">Pakket...</option>
                    @foreach($parcels as $p)
                        <option value="{{ $p->id }}">{{ $p->parcel_no }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-bold hover:bg-indigo-50 transition">Toepassen</button>
                <button type="button" @click="selectedItems = []" class="px-4 py-2 bg-indigo-500 text-white rounded-lg font-bold hover:bg-indigo-400 transition">Annuleren</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-show="viewMode === 'table'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="px-4 py-4 w-12">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-6 py-4">Item</th>
                            <th class="px-6 py-4">Order Nmr</th>
                            <th class="px-6 py-4">Categorie</th>
                            <th class="px-6 py-4">Maat</th>
                            <th class="px-6 py-4">Pakket</th>
                            <th class="px-6 py-4 text-right">Inkoop</th>
                            <th class="px-6 py-4 text-right">Verkoop</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-4 py-4">
                                <input type="checkbox" class="item-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    value="{{ $item->id }}"
                                    @change="toggleItem({{ $item->id }})"
                                    :checked="selectedItems.includes({{ $item->id }})">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-slate-100 flex-shrink-0 overflow-hidden border border-slate-200">
                                        @if($item->image_url)
                                            <img src="{{ $item->image_url }}" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ Str::limit($item->name, 30) }}</div>
                                        <div class="text-xs text-slate-400">{{ $item->brand }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-500">{{ $item->order_nmr ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->category ?? '-' }}</td>
                            <td class="px-6 py-4 font-mono text-slate-500">{{ $item->size ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $item->parcel ? ($item->parcel->parcel_no ?? 'Pakket #' . $item->parcel->id) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500">€ {{ number_format($item->buy_price, 2) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800">
                                @if($item->sell_price) € {{ number_format($item->sell_price, 2) }} @else - @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('inventory.update', $item) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold uppercase rounded-full px-3 py-1 border-none cursor-pointer shadow-sm transition {{ $item->status == 'sold' ? 'bg-emerald-100 text-emerald-700' : ($item->status == 'online' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600') }}">
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
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <a href="{{ route('inventory.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-400 hover:text-red-600 bg-red-50 p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
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
                                <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold uppercase rounded-full px-3 py-1 border-none cursor-pointer shadow-sm transition {{ $item->status == 'sold' ? 'bg-emerald-100 text-emerald-700' : ($item->status == 'online' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600') }}">
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

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <a href="{{ route('inventory.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-2 rounded-lg text-xs font-bold">Bewerk</a>
                            <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-2 rounded-lg text-xs font-bold">Verwijder</button>
                            </form>
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
