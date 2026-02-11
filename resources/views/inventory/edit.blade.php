<x-app-layout>
    {{-- We hernoemen de functie en variabelen om conflicten met oude cache te voorkomen --}}
    <div class="max-w-2xl mx-auto px-4 py-12" 
         x-data="{
            currentUrl: '{{ $item->image_url }}',

            {{-- NIEUWE NAAM: pickQcImage i.p.v. setMainImage --}}
            pickQcImage(url) {
                // Update direct de variable
                this.currentUrl = url;
                
                // Reset de file input
                const fileInput = document.getElementById('image_input');
                if(fileInput) fileInput.value = ''; 
            },

            fileChosen(event) {
                const file = event.target.files[0];
                if (file) {
                    this.currentUrl = URL.createObjectURL(file);
                }
            },

            init() {
                // Plakken (Ctrl+V) functionaliteit
                window.addEventListener('paste', (event) => {
                    const items = (event.clipboardData || window.clipboardData)?.items || [];
                    for (const item of items) {
                        if (item.type && item.type.startsWith('image/')) {
                            const file = item.getAsFile();
                            if (!file) return;
                            
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            const input = document.getElementById('image_input');
                            if (input) {
                                input.files = dataTransfer.files;
                                this.currentUrl = URL.createObjectURL(file);
                            }
                            event.preventDefault();
                            break;
                        }
                    }
                });
            }
         }">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="font-heading font-bold text-2xl text-slate-800">Item Bewerken</h2>
                <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Definitief verwijderen?')">
                    @csrf @method('DELETE')
                    <button class="text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg text-sm font-bold transition">Verwijderen</button>
                </form>
            </div>

            <form action="{{ route('inventory.update', $item) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                @csrf @method('PATCH')
                
                {{-- Hidden input stuurt de URL mee --}}
                <input type="hidden" name="image_url" :value="currentUrl">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Naam</label>
                    <input type="text" name="name" value="{{ old('name', $item->name) }}" class="w-full p-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 font-bold text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Order Nmr</label>
                    <input type="text" name="order_nmr" value="{{ old('order_nmr', $item->order_nmr) }}" class="w-full p-3 rounded-xl border-slate-200">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Preset</label>
                        <select name="preset_id" id="preset_id" class="w-full p-3 rounded-xl border-slate-200 bg-white" onchange="if(this.value){window.location='?preset_id='+this.value;}">
                            <option value="">Kies preset...</option>
                            @foreach($templates as $preset)
                                <option value="{{ $preset->id }}" {{ request('preset_id') == $preset->id ? 'selected' : '' }}>{{ $preset->name }} ({{ $preset->brand }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Merk</label>
                        <input type="text" name="brand" value="{{ old('brand', $item->brand) }}" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categorie</label>
                        <select name="category" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                            @foreach(['Sneakers', 'Kleding', 'Accessoires', 'Overige'] as $cat)
                                <option value="{{ $cat }}" {{ $item->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Maat</label>
                        <input type="text" name="size" value="{{ old('size', $item->size) }}" class="w-full p-3 rounded-xl border-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Inkoop</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-slate-400">€</span>
                            <input type="number" step="0.01" name="buy_price" value="{{ old('buy_price', $item->buy_price) }}" class="w-full pl-8 p-3 rounded-xl border-slate-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Verkoop</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-slate-400">€</span>
                            <input type="number" step="0.01" name="sell_price" value="{{ old('sell_price', $item->sell_price) }}" class="w-full pl-8 p-3 rounded-xl border-slate-200">
                        </div>
                    </div>
                </div>

                {{-- AFBEELDINGEN SECTIE --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hoofdafbeelding</label>
                        <input type="file" name="image" id="image_input" accept="image/*" @change="fileChosen" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                        <p class="text-xs text-slate-400 mt-1">Je kunt ook een afbeelding plakken met Ctrl+V.</p>
                        
                        <div class="mt-3">
                            <div class="w-full h-64 rounded-xl border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center relative group">
                                <template x-if="currentUrl">
                                    <img :src="currentUrl" class="w-full h-full object-contain" referrerpolicy="no-referrer" />
                                </template>
                                <template x-if="!currentUrl">
                                    <span class="text-slate-400 text-sm">Geen afbeelding</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- QC PHOTOS GRID --}}
                    @if(isset($item->qc_photos) && count($item->qc_photos) > 0)
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Beschikbare QC Foto's (Klik om in te stellen)</label>
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                                @foreach($item->qc_photos as $photo)
                                    {{-- We gebruiken nu de nieuwe functienaam: pickQcImage --}}
                                    <button type="button" 
                                            @click="pickQcImage('{{ $photo }}')"
                                            class="relative aspect-square rounded-lg overflow-hidden border-2 transition hover:opacity-80 focus:outline-none"
                                            :class="currentUrl === '{{ $photo }}' ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent'">
                                        <img src="{{ $photo }}" class="w-full h-full object-cover" referrerpolicy="no-referrer" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                            <option value="todo" {{ $item->status == 'todo' ? 'selected' : '' }}>To-do</option>
                            <option value="prep" {{ $item->status == 'prep' ? 'selected' : '' }}>Prep</option>
                            <option value="online" {{ $item->status == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="sold" {{ $item->status == 'sold' ? 'selected' : '' }}>Verkocht</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pakket</label>
                        <select name="parcel_id" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                            <option value="">Geen</option>
                            @foreach($parcels as $parcel)
                                <option value="{{ $parcel->id }}" {{ $item->parcel_id == $parcel->id ? 'selected' : '' }}>
                                    {{ $parcel->parcel_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('inventory.index') }}" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-50 transition">Annuleren</a>
                    <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition">Opslaan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>