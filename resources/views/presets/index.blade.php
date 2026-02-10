<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="presetHandler()">
        
        <div class="glass-panel p-8 rounded-3xl shadow-sm mb-8">
            <h2 class="font-heading font-bold text-xl flex items-center gap-3 text-slate-800 mb-6">
                <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div>
                Nieuwe Template
            </h2>
            
            <!-- FORMULIER -->
            <form action="{{ route('presets.store') }}" method="POST">
                @csrf
                <div class="flex flex-col gap-6">
                    <div class="flex flex-wrap gap-6 items-end">
                        <!-- Image Upload Area (Supports Paste) -->
                        <div 
                            @paste.window="handlePaste($event)"
                            class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-300 flex items-center justify-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50 transition-all bg-slate-50 overflow-hidden relative group"
                        >
                            <img x-show="imageUrl" :src="imageUrl" class="w-full h-full object-cover">
                            <span x-show="!imageUrl" class="text-slate-400 text-xs text-center px-2">Plak (Ctrl+V)</span>
                            <input type="hidden" name="image_url" :value="imageUrl">
                        </div>

                        <div class="flex-1 min-w-[200px] space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Naam</label>
                            <input type="text" name="name" required class="w-full p-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Bijv. Air Force 1">
                        </div>
                        <div class="w-40 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Merk</label>
                            <input type="text" name="brand" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                        <div class="w-40 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Categorie</label>
                            <select name="category" class="w-full p-3 rounded-xl border-slate-200 bg-white">
                                <option value="">Kies categorie</option>
                                <option value="Sneakers">Sneakers</option>
                                <option value="Kleding">Kleding</option>
                                <option value="Accessoire">Accessoire</option>
                                <option value="Overige">Overige</option>
                            </select>
                        </div>
                        <div class="w-24 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Maat</label>
                            <input type="text" name="size" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Standaard Inkoop</label>
                            <input type="number" step="0.01" name="default_buy_price" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                        <div class="flex-1 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Standaard Verkoop</label>
                            <input type="number" step="0.01" name="default_sell_price" class="w-full p-3 rounded-xl border-slate-200">
                        </div>
                        <button class="px-8 py-3 bg-slate-900 text-white rounded-xl font-bold shadow-lg hover:bg-slate-800 transition">Aanmaken</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $tpl)
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition flex items-center gap-4 group relative">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl overflow-hidden border border-slate-100">
                        @if($tpl->image_url)
                            <img src="{{ $tpl->image_url }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">{{ $tpl->name }}</h3>
                        <div class="text-xs text-slate-500 mt-1">{{ $tpl->brand }}</div>
                        <div class="text-xs text-slate-400 mt-2 font-mono">
                            In: {{ $tpl->default_buy_price ?? '-' }} / Uit: {{ $tpl->default_sell_price ?? '-' }}
                        </div>
                    </div>
                    
                    <form action="{{ route('presets.destroy', $tpl) }}" method="POST" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                        @csrf @method('DELETE')
                        <button class="text-slate-300 hover:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function presetHandler() {
            return {
                imageUrl: null,
                handlePaste(e) {
                    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                    for (let index in items) {
                        const item = items[index];
                        if (item.kind === 'file' && item.type.includes('image/')) {
                            const blob = item.getAsFile();
                            const reader = new FileReader();
                            reader.onload = (event) => {
                                this.imageUrl = event.target.result; // Base64 string
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                }
            }
        }
    </script>
</x-app-layout>