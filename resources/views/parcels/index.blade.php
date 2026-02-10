<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pakketten') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ showModal: false, showEditModal: false, editParcel: {} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Knop Nieuw Pakket -->
            <div class="flex justify-end mb-6">
                <button @click="showModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-bold hover:bg-indigo-700 shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nieuw Pakket
                </button>
            </div>

            <!-- Grid met Pakketten -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($parcels as $parcel)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 hover:shadow-md transition relative group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-50 text-indigo-600 p-3 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-slate-800">{{ $parcel->parcel_no }}</h3>
                                    <span class="text-xs uppercase font-bold px-2 py-0.5 rounded 
                                        {{ $parcel->status == 'arrived' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $parcel->status == 'arrived' ? 'Ontvangen' : ($parcel->status == 'shipped' ? 'Onderweg' : 'Prep') }}
                                    </span>
                                </div>
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition flex items-center gap-2">
                                <button type="button"
                                        @click="editParcel = {{ Js::from($parcel) }}; showEditModal = true"
                                        class="text-slate-300 hover:text-indigo-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <form action="{{ route('parcels.destroy', $parcel) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                    @csrf @method('DELETE')
                                    <button class="text-slate-300 hover:text-red-500"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                                </form>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm text-slate-500">
                            <div class="flex justify-between">
                                <span>Tracking:</span>
                                <span class="font-mono text-slate-700 font-bold">
                                    @if($parcel->tracking_code)
                                        <a href="https://t.17track.net/nl#nums={{ $parcel->tracking_code }}" target="_blank" class="text-blue-500 hover:underline">{{ $parcel->tracking_code }}</a>
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span>Items:</span>
                                <span class="font-bold text-slate-700">{{ $parcel->items_count }}</span>
                            </div>
                            @if($parcel->items_count > 0)
                                <details class="rounded-xl bg-slate-50 p-3 border border-slate-100">
                                    <summary class="cursor-pointer text-xs font-bold uppercase tracking-wider text-slate-500">Bekijk items</summary>
                                    <div class="mt-3 space-y-2">
                                        @foreach($parcel->items as $item)
                                            <div class="flex items-center justify-between text-xs text-slate-600">
                                                <div class="font-semibold text-slate-700">
                                                    {{ $item->name }}
                                                    @if($item->brand)
                                                        <span class="text-slate-400">• {{ $item->brand }}</span>
                                                    @endif
                                                    @if($item->size)
                                                        <span class="text-slate-400">• {{ $item->size }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-slate-400">€ {{ number_format($item->buy_price ?? 0, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                            <div class="pt-3 mt-3 border-t border-slate-50 flex justify-between items-center">
                                <span class="uppercase text-[10px] font-bold tracking-wider">Kosten</span>
                                <span class="text-xl font-bold text-slate-800">
                                    @if(is_null($parcel->shipping_cost))
                                        —
                                    @else
                                        € {{ number_format($parcel->shipping_cost, 2) }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        
                        <!-- Update Status Knoppen -->
                        <div class="mt-4 flex gap-2">
                            @if($parcel->status !== 'arrived')
                                <form action="{{ route('parcels.update', $parcel) }}" method="POST" class="w-full">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="arrived">
                                    <button class="w-full py-2 bg-green-50 text-green-600 font-bold text-xs rounded-lg hover:bg-green-100 border border-green-100 transition">Markeer Ontvangen</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.away="showModal = false">
                <h3 class="text-lg font-bold mb-4">Nieuw Pakket</h3>
                <form action="{{ route('parcels.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Parcel No</label>
                            <input type="text" name="parcel_no" required class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Bijv. PN123456">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Tracking</label>
                            <input type="text" name="tracking_code" class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Tracking nummer">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Beschrijving</label>
                            <textarea name="description" rows="3" class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Bijv. leverancier, notities..."></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Verzendkosten (€)</label>
                            <input type="number" step="0.01" name="shipping_cost" class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Optioneel">
                        </div>
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.away="showEditModal = false">
                <h3 class="text-lg font-bold mb-4">Pakket bewerken</h3>
                <form :action="`/parcels/${editParcel.id}`" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Parcel No</label>
                            <input type="text" name="parcel_no" x-model="editParcel.parcel_no" required class="w-full p-2.5 rounded-xl border-slate-200 mt-1">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Tracking</label>
                            <input type="text" name="tracking_code" x-model="editParcel.tracking_code" class="w-full p-2.5 rounded-xl border-slate-200 mt-1">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Beschrijving</label>
                            <textarea name="description" rows="3" x-model="editParcel.description" class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Bijv. leverancier, notities..."></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Verzendkosten (€)</label>
                            <input type="number" step="0.01" name="shipping_cost" x-model="editParcel.shipping_cost" class="w-full p-2.5 rounded-xl border-slate-200 mt-1" placeholder="Optioneel">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
                            <select name="status" x-model="editParcel.status" class="w-full p-2.5 rounded-xl border-slate-200 mt-1">
                                <option value="prep">Prep</option>
                                <option value="shipped">Onderweg</option>
                                <option value="arrived">Ontvangen</option>
                            </select>
                        </div>
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold">Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>