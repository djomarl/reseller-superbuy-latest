<x-app-layout>
    <div class="max-w-2xl mx-auto px-4 py-12">
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

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Afbeelding</label>
                    <input type="file" name="image" id="image_input" accept="image/*" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                    <p class="text-xs text-slate-400 mt-1">Je kunt ook een afbeelding plakken met Ctrl+V.</p>
                    <div class="mt-3">
                        <div class="text-xs text-slate-500 uppercase font-bold mb-1">Preview</div>
                        <div class="w-full h-40 rounded-xl border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center">
                            @if($item->image_url)
                                <img id="image_preview" src="{{ $item->image_url }}" class="w-full h-full object-cover" />
                            @else
                                <img id="image_preview" class="hidden w-full h-full object-cover" />
                                <span id="image_placeholder" class="text-slate-400 text-sm">Geen afbeelding</span>
                            @endif
                        </div>
                    </div>
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

<script>
    (function () {
        const input = document.getElementById('image_input');
        const preview = document.getElementById('image_preview');
        const placeholder = document.getElementById('image_placeholder');

        if (input) {
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.classList.remove('hidden');
                    if (placeholder) placeholder.classList.add('hidden');
                }
            });
        }

        window.addEventListener('paste', (event) => {
            const items = (event.clipboardData || window.clipboardData)?.items || [];
            for (const item of items) {
                if (item.type && item.type.startsWith('image/')) {
                    const file = item.getAsFile();
                    if (!file) return;
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    if (input) {
                        input.files = dataTransfer.files;
                        const url = URL.createObjectURL(file);
                        preview.src = url;
                        preview.classList.remove('hidden');
                        if (placeholder) placeholder.classList.add('hidden');
                    }
                    event.preventDefault();
                    break;
                }
            }
        });
    })();
</script>