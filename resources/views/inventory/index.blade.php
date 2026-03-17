<script>
    window.inventoryTemplates = @json($templates);
    window.inventoryItems = @json($items->items());
</script>

<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
         x-data="inventoryManager"
         x-init="$watch('selectedItems', value => showBulkActions = value.length > 0); $watch('viewMode', () => setTimeout(() => initSortable(), 50)); initSortable();">
         
         <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('inventoryManager', () => ({
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
                    sellModalMode: 'sold',
                    qcPhotos: [],
                    qcItem: null,
                    currentQcIndex: 0,
                    showImageModal: false,
                    activeImageUrl: null,
                    templates: window.inventoryTemplates || [],
                    itemsStore: {},
                    contextMenu: { show: false, x: 0, y: 0, item: null },

                    openContextMenu(e, item) {
                        e.preventDefault();
                        this.contextMenu.item = item;
                        this.contextMenu.show = true;
                        
                        // Basic positioning, keeping it fully inside viewport
                        let x = e.clientX;
                        let y = e.clientY;
                        
                        // Assume menu is ~200px wide and ~250px tall
                        if (x + 200 > window.innerWidth) x = window.innerWidth - 220;
                        if (y + 250 > window.innerHeight) y = window.innerHeight - 270;

                        this.contextMenu.x = x;
                        this.contextMenu.y = y;
                    },

                    closeContextMenu() {
                        this.contextMenu.show = false;
                    },

                    init() {
                        if (window.inventoryItems) {
                            window.inventoryItems.forEach(item => {
                                this.itemsStore[item.id] = item;
                            });
                        }
                    },

                    openImage(url) {
                        if(url) {
                            this.activeImageUrl = url;
                            this.showImageModal = true;
                        }
                    },
                    
                    toggleAll(event) {
                        if (event.target.checked) {
                            this.selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).map(cb => parseInt(cb.value));
                            document.querySelectorAll('.item-checkbox').forEach(el => el.checked = true);
                        } else {
                            this.selectedItems = [];
                            document.querySelectorAll('.item-checkbox').forEach(el => el.checked = false);
                        }
                    },

                    toggleItem(id, event) {
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
                        if (event.target.tagName === 'BUTTON' || event.target.tagName === 'A' || event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.closest('button') || event.target.closest('a') || event.target.closest('.drag-handle')) {
                            return;
                        }
                        this.toggleItem(id, event);
                    },

                    applyPreset(event) {
                        const id = event.target.value;
                        const template = this.templates.find(t => t.id == id);
                        if (template) {
                            document.getElementById('new_name').value = template.name || '';
                            document.getElementById('new_brand').value = template.brand || '';
                            document.getElementById('new_category').value = template.category || 'Overige';
                            document.getElementById('new_size').value = template.size || '';
                            document.getElementById('new_buy_price').value = template.default_buy_price || '';
                            document.getElementById('new_sell_price').value = template.default_sell_price || '';
                        }
                    },

                    openEdit(item) {
                        const storedItem = this.itemsStore[item.id] || item;
                        this.editingItem = JSON.parse(JSON.stringify(storedItem));
                        this.showEditModal = true;
                    },

                    openSell(item, mode = 'sold') {
                        this.sellingItem = JSON.parse(JSON.stringify(item));
                        this.sellModalMode = mode;
                        if (mode === 'sold' && !this.sellingItem.sold_date) {
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

                    async submitEditModal(e) {
                        // Only use AJAX in Kanban view; otherwise submit normally
                        if (this.viewMode !== 'kanban') {
                            e.target.submit();
                            return;
                        }

                        const form = e.target;
                        const formData = new FormData(form);
                        formData.append('_method', 'PATCH');
                        
                        const id = this.editingItem.id;
                        
                        try {
                            const res = await fetch(`/inventory/${id}`, {
                                method: 'POST', 
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: formData
                            });

                            if (res.ok) {
                                const data = await res.json();
                                if(data.success) {
                                    const item = data.item;
                                    
                                    // Update local store
                                    this.itemsStore[id] = item;
                                    
                                    // DOM updates across all views (Table, Kanban, Cards)
                                    document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                                        // Name
                                        const nameEl = el.querySelector('.js-item-name');
                                        if(nameEl) nameEl.textContent = item.name;

                                        // Brand
                                        const brandEl = el.querySelector('.js-item-brand');
                                        if(brandEl) brandEl.textContent = item.brand || 'Onbekend';

                                        // Category
                                        const categoryEl = el.querySelector('.js-item-category');
                                        if(categoryEl) {
                                             // Preserve the icon if possible, or just update text. 
                                             // Simpler to just update the text node if it's mixed, but let's try to keep the icon.
                                             // The structure is <i></i> Text.
                                             // valid approach: categoryEl.innerHTML = `<i class="fa-solid fa-layer-group text-[9px] text-slate-400"></i> ${item.category || 'Overige'}`;
                                             // Check if it's the card view version (no icon inside the span directly, simplified) or table view
                                             if (categoryEl.querySelector('i')) {
                                                 categoryEl.innerHTML = `<i class="fa-solid fa-layer-group text-[9px] text-slate-400"></i> ${item.category || 'Overige'}`;
                                             } else {
                                                 categoryEl.textContent = item.category || 'Overige';
                                             }
                                        }

                                        // Size
                                        const sizeEl = el.querySelector('.js-item-size');
                                        if(sizeEl) {
                                            if(item.size) {
                                                sizeEl.textContent = item.size;
                                                sizeEl.style.display = '';
                                            } else {
                                                sizeEl.style.display = 'none';
                                            }
                                        }

                                        // Sell Price
                                        const sellEl = el.querySelector('.js-item-sell-price');
                                        if(sellEl) {
                                            // Format as currency
                                            const formatted = parseFloat(item.sell_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            sellEl.textContent = '€' + formatted;
                                        }

                                        // Buy Price
                                        const buyEl = el.querySelector('.js-item-buy-price');
                                        if(buyEl) {
                                             const formatted = parseFloat(item.buy_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                             buyEl.textContent = '€' + formatted;
                                        }

                                        // Image
                                        const imgEl = el.querySelector('.js-item-image');
                                        if(imgEl && item.image_url) {
                                            imgEl.src = item.image_url;
                                        }
                                    });

                                    this.showEditModal = false;
                                }
                            } else {
                                window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Er ging iets mis bij het opslaan.', type: 'error'}}));
                            }
                        } catch (error) {
                            console.error(error);
                            window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Netwerkfout.', type: 'error'}}));
                        }
                    },

                    async submitSellModal() {
                        if (!this.sellingItem) return;

                        const id = this.sellingItem.id;
                        const mode = this.sellModalMode;
                        let url = `/inventory/${id}`;
                        let method = 'PATCH';
                        let body = {};

                        if (mode === 'sold') {
                            url = `/inventory/${id}/sold`;
                            method = 'POST';
                            body = {
                                sell_price: this.sellingItem.sell_price,
                                sold_date: this.sellingItem.sold_date,
                                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            };
                        } else {
                            // Set price mode
                            body = {
                                _method: 'PATCH',
                                status: 'online',
                                sell_price: this.sellingItem.sell_price,
                                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            };
                            method = 'POST'; // Laravel spoofing
                        }

                        try {
                            const res = await fetch(url, {
                                method: method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(body)
                            });

                            if (res.ok) {
                                const data = await res.json();
                                // Update local store
                                if(this.itemsStore[id]) {
                                    this.itemsStore[id].sell_price = this.sellingItem.sell_price;
                                    this.itemsStore[id].status = mode === 'sold' ? 'sold' : 'online';
                                    if(mode === 'sold') this.itemsStore[id].sold_date = this.sellingItem.sold_date;
                                }

                                // Hide item from DOM visually across all views
                                if (this.viewMode !== 'archive') {
                                    document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                                        el.style.transition = 'all 0.5s ease';
                                        el.style.opacity = '0';
                                        el.style.transform = 'scale(0.9)';
                                        setTimeout(() => el.remove(), 500);
                                    });
                                }

                                this.showSellModal = false;
                                this.sellingItem = {};

                                if (mode === 'sold') {
                                    confetti({
                                      particleCount: 150,
                                      spread: 70,
                                      origin: { y: 0.6 },
                                      colors: ['#10B981', '#34D399', '#059669', '#A7F3D0'],
                                      zIndex: 2000
                                    });
                                    window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Item succesvol verkocht! 🎉', type: 'success'}}));
                                    setTimeout(() => window.location.reload(), 2500);
                                } else {
                                    window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Prijs succesvol ingesteld.', type: 'success'}}));
                                    setTimeout(() => window.location.reload(), 1500);
                                }
                            } else {
                                window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Er ging iets mis bij het opslaan.', type: 'error'}}));
                            }
                        } catch (e) {
                            console.error(e);
                            window.dispatchEvent(new CustomEvent('notify', {detail: {msg: 'Netwerkfout.', type: 'error'}}));
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
                                        updateSortOrder(); 
                                    }
                                });
                            }
                        } else if(this.viewMode === 'kanban') {
                             // Init Kanban Sortables
                             ['todo', 'prep', 'online', 'sold'].forEach(status => {
                                const el = document.getElementById('kanban-' + status);
                                if(el) {
                                    Sortable.create(el, {
                                        group: 'kanban',
                                        animation: 150,
                                        ghostClass: 'bg-indigo-50',
                                        onAdd: (evt) => {
                                            const itemEl = evt.item;
                                            const id = itemEl.getAttribute('data-id');
                                            const itemData = this.itemsStore[id];
                                            
                                            if (!itemData) {
                                                console.error('Item data not found for id:', id);
                                                return;
                                            }

                                            const price = parseFloat(itemData.sell_price) || 0;
                                            const newStatus = status;

                                            // Intercept 'sold' status
                                            if (newStatus === 'sold') {
                                                evt.from.appendChild(itemEl); // Revert
                                                this.openSell(itemData, 'sold');
                                                return;
                                            }

                                            // Intercept 'online' status (Te Koop) IF price is 0
                                            if (newStatus === 'online' && price <= 0) {
                                                evt.from.appendChild(itemEl); // Revert
                                                this.openSell(itemData, 'set_price');
                                                return;
                                            }
                                            
                                            // Update status via API normal flow
                                            fetch('/inventory/' + id, {
                                                method: 'POST', // Laravel method spoofing
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    _method: 'PATCH',
                                                    status: newStatus
                                                })
                                            }).then(res => res.json())
                                              .then(data => {
                                                  if(data.success) {
                                                      // Optional: Show toast
                                                      console.log('Status updated to ' + newStatus);
                                                  } else {
                                                      alert('Update mislukt');
                                                      evt.from.appendChild(itemEl); // Revert
                                                  }
                                              }).catch(err => {
                                                  console.error(err);
                                                  alert('Er ging iets mis');
                                                  evt.from.appendChild(itemEl); // Revert
                                              });
                                        }
                                    });
                                }
                             });
                        }
                    }
                }));
            });
         </script>
         
         <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
         <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
         
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

        <!-- Insights Banner (V2) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 justify-between mb-8"
             x-data="{
                show: false
             }"
             x-init="setTimeout(() => show = true, 50)"
             x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-500 delay-100"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
             
            <!-- Active Items -->
            <div class="bg-white/70 backdrop-blur-xl border border-white p-5 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group"
                 x-data="{ count: 0 }" x-init="let target = {{ (float)$insights['total_active'] }}; if(target===0){count=0;return;} let step = Math.ceil(target/20); let timer = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(timer); } }, 30)">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Actieve Voorraad</span>
                </div>
                <div class="text-2xl font-black text-slate-800 font-heading"><span x-text="count"></span> <span class="text-sm text-slate-400 font-normal">items</span></div>
            </div>

            <!-- Active Buy Value (Huidige Voorraadwaarde) -->
            <div class="bg-white/70 backdrop-blur-xl border border-white p-5 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group"
                 x-data="{ count: 0 }" x-init="let target = {{ (float)$insights['active_buy_value'] }}; if(target===0){count=0;return;} let step = target/20; let timer = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(timer); } }, 30)">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Voorraadwaarde</span>
                </div>
                <div class="text-2xl font-black text-slate-800 font-heading">€<span x-text="count.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
            </div>

            <!-- Total Invested (Totale Investering) -->
            <div class="bg-white/70 backdrop-blur-xl border border-white p-5 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group"
                 x-data="{ count: 0 }" x-init="let target = {{ (float)$insights['total_invested'] }}; if(target===0){count=0;return;} let step = target/20; let timer = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(timer); } }, 30)">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Totale Investering</span>
                </div>
                <div class="text-2xl font-black text-slate-800 font-heading">€<span x-text="count.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
            </div>

            <!-- Total Profit -->
            <div class="bg-white/70 backdrop-blur-xl border border-white p-5 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group relative overflow-hidden"
                 x-data="{ count: 0 }" x-init="let target = {{ (float)$insights['total_profit'] }}; if(target===0){count=0;return;} let step = target/20; let timer = setInterval(() => { count += step; if(target > 0 ? count >= target : count <= target) { count = target; clearInterval(timer); } }, 30)">
                <div class="absolute -right-4 -top-4 w-20 h-20 bg-purple-400 rounded-full opacity-10 blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-colors z-10">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest z-10">Totale Winst</span>
                </div>
                <div class="text-2xl font-black text-purple-600 font-heading z-10 relative">€<span x-text="count.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></div>
            </div>
        </div>

        <div class="flex flex-col gap-4 mb-8">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <h2 class="font-heading font-bold text-2xl text-slate-800">
                    {{ $view === 'archive' ? '📦 Archief (Verkocht)' : '📦 Mijn Voorraad' }}
                    <span class="text-slate-400 text-lg ml-2">({{ $items->total() }})</span>
                </h2>
                <div class="flex flex-wrap gap-2">
                    <div class="flex bg-white/50 backdrop-blur-md p-1 rounded-xl border border-slate-200/50 shadow-sm">
                        <button type="button" @click="viewMode = 'table'"
                            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300"
                            :class="viewMode === 'table' ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'">
                            Tabel
                        </button>
                        <button type="button" @click="viewMode = 'cards'"
                            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300"
                            :class="viewMode === 'cards' ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'">
                            Cards
                        </button>
                        <button type="button" @click="viewMode = 'kanban'"
                            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300"
                            :class="viewMode === 'kanban' ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'">
                            Bord
                        </button>
                    </div>
                    <a href="{{ route('superbuy.index') }}" class="px-5 py-2.5 bg-white border border-slate-200/60 text-indigo-600 rounded-xl text-sm font-bold shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center">
                        <i class="fa-solid fa-cloud-arrow-down mr-2 text-indigo-400"></i> Import Superbuy
                    </a>
                    <button @click="showImport = true" class="px-5 py-2.5 bg-white border border-slate-200/60 text-slate-600 rounded-xl text-sm font-bold shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fa-solid fa-upload mr-1.5 opacity-50"></i> Import CSV
                    </button>
                    <button @click="showNew = true" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fa-solid fa-plus mr-1.5"></i> Nieuw
                    </button>
                </div>
            </div>

            <div class="glass-card p-5 rounded-2xl flex flex-col lg:flex-row gap-4 justify-between items-start lg:items-center mb-8">
                <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col sm:flex-row flex-wrap gap-4 w-full lg:w-auto flex-1 items-center">
                    <input type="hidden" name="view" value="{{ $view }}">

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek op naam, merk, of id..."
                            class="w-full pl-11 pr-4 py-2.5 rounded-xl border-slate-200/60 bg-white/60 text-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium placeholder-slate-400 shadow-sm">
                        <div class="absolute left-4 top-3 text-indigo-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                    </div>

                    <div class="relative sm:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-filter text-slate-400 text-xs"></i>
                        </div>
                        <select name="category" onchange="this.form.submit()" class="w-full pl-9 pr-8 py-2.5 rounded-xl border-slate-200/60 bg-white/60 text-sm font-medium focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 cursor-pointer transition-all shadow-sm appearance-none">
                            <option value="">Alle Categorieën</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <div class="relative sm:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-tag text-slate-400 text-xs"></i>
                        </div>
                        <select name="brand" onchange="this.form.submit()" class="w-full pl-9 pr-8 py-2.5 rounded-xl border-slate-200/60 bg-white/60 text-sm font-medium focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 cursor-pointer transition-all shadow-sm appearance-none">
                            <option value="">Alle Merken</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <div class="relative sm:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-box text-slate-400 text-xs"></i>
                        </div>
                        <select name="parcel" onchange="this.form.submit()" class="w-full pl-9 pr-8 py-2.5 rounded-xl border-slate-200/60 bg-white/60 text-sm font-medium focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 cursor-pointer transition-all shadow-sm appearance-none">
                            <option value="">Alle Pakketten</option>
                            @foreach($parcels as $p)
                                <option value="{{ $p->id }}" {{ request('parcel') == $p->id ? 'selected' : '' }}>{{ $p->parcel_no }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    @if($view !== 'archive')
                    <div class="relative sm:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-circle-half-stroke text-slate-400 text-xs"></i>
                        </div>
                        <select name="status" onchange="this.form.submit()" class="w-full pl-9 pr-8 py-2.5 rounded-xl border-slate-200/60 bg-white/60 text-sm font-medium focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 cursor-pointer transition-all shadow-sm appearance-none">
                            <option value="">Statussen</option>
                            <option value="todo" {{ request('status') == 'todo' ? 'selected' : '' }}>To-do</option>
                            <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="prep" {{ request('status') == 'prep' ? 'selected' : '' }}>Prep</option>
                            <option value="personal" {{ request('status') == 'personal' ? 'selected' : '' }}>Eigen Gebruik</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                    @endif

                    @if(request()->hasAny(['search', 'category', 'brand', 'status', 'parcel']))
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
             x-transition:enter-start="opacity-0 translate-y-10 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-10 scale-95"
             class="fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-white/70 backdrop-blur-2xl border border-white/60 text-slate-800 rounded-3xl shadow-[0_20px_60px_-10px_rgba(0,0,0,0.1),0_10px_30px_-10px_rgba(0,0,0,0.1)] px-4 py-3 flex items-center gap-2 z-50 w-auto max-w-4xl ring-1 ring-slate-900/5">
            
            <div class="flex items-center gap-3 border-r border-slate-200/60 pr-4 mr-2">
                <span class="bg-indigo-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm shadow-indigo-300" x-text="selectedItems.length"></span>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Geselecteerd</span>
            </div>

            <form method="POST" action="{{ route('inventory.bulkAction') }}" class="flex items-center gap-1.5" onsubmit="return confirm('Weet je het zeker?')">
                @csrf
                <input type="hidden" name="items" :value="JSON.stringify(selectedItems)">
                <input type="hidden" name="action" id="bulkActionInput">
                <input type="hidden" name="status" id="bulkStatusInput">
                <input type="hidden" name="parcel_id" id="bulkParcelInput">
                <input type="hidden" name="category" id="bulkCategoryInput">

                <!-- Status Action -->
                <div class="relative group">
                    <button type="button" class="flex flex-col items-center justify-center w-16 h-14 rounded-2xl hover:bg-white/60 hover:shadow-sm text-xs font-medium transition-all duration-200 text-slate-600 hover:text-indigo-600 group-hover:-translate-y-1">
                        <i class="fa-solid fa-tag text-lg mb-1.5 text-slate-400 group-hover:text-indigo-400"></i> Status
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute bottom-full left-0 pb-2 hidden group-hover:block z-50">
                        <div class="w-40 bg-white rounded-xl shadow-xl border border-slate-100 p-1">
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='todo'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">To-do</button>
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='prep'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Prep</button>
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='online'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Online</button>
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='sold'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Verkocht</button>
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_status'; document.getElementById('bulkStatusInput').value='personal'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">Eigen Gebruik</button>
                        </div>
                    </div>
                </div>

                <!-- Category Action -->
                <div class="relative group">
                    <button type="button" class="flex flex-col items-center justify-center w-16 h-14 rounded-2xl hover:bg-white/60 hover:shadow-sm text-xs font-medium transition-all duration-200 text-slate-600 hover:text-indigo-600 group-hover:-translate-y-1">
                        <i class="fa-solid fa-layer-group text-lg mb-1.5 text-slate-400 group-hover:text-indigo-400"></i> Groep
                    </button>
                    <div class="absolute bottom-full left-0 pb-2 hidden group-hover:block z-50">
                        <div class="w-56 bg-white rounded-xl shadow-xl border border-slate-100 p-1 max-h-60 overflow-y-auto">
                            @foreach($categories as $cat)
                                 <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_category'; document.getElementById('bulkCategoryInput').value='{{ $cat }}'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">
                                    {{ $cat }}
                                 </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Parcel Action -->
                 <div class="relative group">
                    <button type="button" class="flex flex-col items-center justify-center w-16 h-14 rounded-2xl hover:bg-white/60 hover:shadow-sm text-xs font-medium transition-all duration-200 text-slate-600 hover:text-indigo-600 group-hover:-translate-y-1">
                        <i class="fa-solid fa-box text-lg mb-1.5 text-slate-400 group-hover:text-indigo-400"></i> Pakket
                    </button>
                    <div class="absolute bottom-full left-0 pb-2 hidden group-hover:block z-50">
                        <div class="w-56 bg-white rounded-xl shadow-xl border border-slate-100 p-1 max-h-60 overflow-y-auto">
                            @foreach($parcels as $p)
                                 <button type="submit" onclick="document.getElementById('bulkActionInput').value='set_parcel'; document.getElementById('bulkParcelInput').value='{{ $p->id }}'" class="w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg">
                                    {{ $p->parcel_no }}
                                 </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Delete Action -->
                <div class="relative group ml-2 border-l border-slate-200/60 pl-2">
                    <button type="submit" onclick="document.getElementById('bulkActionInput').value='delete'" class="flex flex-col items-center justify-center w-16 h-14 rounded-2xl hover:bg-white/60 hover:shadow-sm text-xs font-medium transition-all duration-200 text-red-500 hover:text-red-600 group-hover:-translate-y-1">
                        <i class="fa-solid fa-trash text-lg mb-1.5 text-red-400 group-hover:text-red-500"></i> Wissen
                    </button>
                </div>
            </form>

            <button @click="selectedItems = []; document.querySelectorAll('.item-checkbox').forEach(el => el.checked = false);" class="ml-4 flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-times text-xs"></i>
            </button>
        </div>

        <div class="glass-card rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden" x-show="viewMode === 'table'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50/50 backdrop-blur-sm border-b border-slate-200/60 text-slate-500">
                        <tr>
                            <th class="px-6 py-5 w-16">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer transition-colors shadow-sm">
                            </th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Item Details</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Status</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">QC</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Pakket</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 text-right">Inkoop</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 text-right">Verkoop</th>
                            <th class="px-6 py-5 text-[10px] font-extrabold uppercase tracking-widest text-slate-400 text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/60 bg-white/40 backdrop-blur-md" id="sortable-list">
                        @forelse($items as $item)
                        <tr class="item-row stagger-item hover:bg-white/80 transition-all duration-300 group border-l-4 border-l-transparent hover:border-l-indigo-400"
                            style="animation-delay: {{ $loop->index * 50 }}ms;"
                            :class="selectedItems.includes({{ $item->id }}) ? '!bg-indigo-50/80 !border-l-indigo-600 shadow-inner' : ''"
                            @click="toggleRow({{ $item->id }}, $event)"
                            @contextmenu.prevent="openContextMenu($event, itemsStore[{{ $item->id }}])"
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
                                            <span class="js-item-brand text-[10px] font-extrabold tracking-wider text-slate-500 uppercase">{{ $item->brand ?? 'Onbekend' }}</span>
                                            @if($item->size) 
                                                <span class="js-item-size text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">{{ $item->size }}</span> 
                                            @endif
                                        </div>
                                        <div class="js-item-name font-bold text-slate-800 text-sm leading-tight mb-1.5 hover:text-indigo-600 cursor-pointer line-clamp-1" @click.stop="openEdit({{ Js::from($item) }})">
                                            {{ $item->name }}
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2">
                                            <span class="js-item-category inline-flex items-center gap-1 text-[10px] font-medium text-slate-500 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-100">
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
                                            class="appearance-none pl-3 pr-8 py-2 text-[10px] font-extrabold uppercase tracking-wider rounded-xl border border-white/40 shadow-sm cursor-pointer focus:ring-2 focus:ring-offset-1 focus:ring-offset-white/50 transition-all w-32
                                            {{ $item->status == 'sold' ? 'bg-emerald-100/80 text-emerald-700 focus:ring-emerald-400 hover:bg-emerald-200/80' : 
                                               ($item->status == 'online' ? 'bg-indigo-100/80 text-indigo-700 focus:ring-indigo-400 hover:bg-indigo-200/80' :                                                ($item->status == 'prep' ? 'bg-amber-100/80 text-amber-700 focus:ring-amber-400 hover:bg-amber-200/80' : 
                                                ($item->status == 'personal' ? 'bg-purple-100/80 text-purple-700 focus:ring-purple-400 hover:bg-purple-200/80' :
                                                'bg-slate-100/80 text-slate-600 focus:ring-slate-400 hover:bg-slate-200/80'))) }}">
                                            @if($view === 'archive')
                                                @if($item->status == 'personal')
                                                    <option value="personal" selected>Eigen Gebruik</option>
                                                    <option value="online">Zet terug</option>
                                                @else
                                                    <option value="sold" selected>Verkocht</option>
                                                    <option value="online">Zet terug</option>
                                                @endif
                                            @else
                                                <option value="todo" {{ $item->status == 'todo' ? 'selected' : '' }}>To-do</option>
                                                <option value="prep" {{ $item->status == 'prep' ? 'selected' : '' }}>Prep</option>
                                                <option value="online" {{ $item->status == 'online' ? 'selected' : '' }}>Online</option>
                                                <option value="personal" {{ $item->status == 'personal' ? 'selected' : '' }}>Eigen Gebruik</option>
                                                <option value="sold">Markeer Verkocht</option>
                                            @endif
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400 mix-blend-multiply">
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
                                <div class="js-item-buy-price font-mono text-xs font-medium text-slate-500">
                                    €{{ number_format($item->buy_price, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="inline-block font-bold text-sm text-slate-800 bg-emerald-50/50 px-2 py-1 rounded border border-emerald-100/50">
                                    @if($item->sell_price) <span class="js-item-sell-price">€{{ number_format($item->sell_price, 2) }}</span> @else <span class="text-slate-400">-</span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="flex justify-end items-center gap-2">
                                    @if($item->status !== 'sold')
                                        <button @click.stop="openSell({{ Js::from($item) }})" 
                                            class="bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-600 hover:to-emerald-500 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center gap-1.5 border border-emerald-400">
                                            <i class="fa-solid fa-money-bill-wave"></i> Verkopen
                                        </button>
                                    @endif

                                    <button type="button" @click.stop="openEdit({{ Js::from($item) }})" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-indigo-600 bg-white/60 hover:bg-white border border-slate-200 hover:border-indigo-200 rounded-lg transition-all shadow-sm hover:shadow hover:-translate-y-0.5" title="Bewerken">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    
                                    <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Zeker weten?')">
                                        @csrf @method('DELETE')
                                        <button class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-red-500 bg-white/60 hover:bg-white border border-slate-200 hover:border-red-200 rounded-lg transition-all shadow-sm hover:shadow hover:-translate-y-0.5" title="Verwijderen">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-20">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 rounded-full bg-slate-100/50 flex items-center justify-center mb-4 border border-slate-200/50">
                                        <i class="fa-solid fa-box-open text-2xl opacity-50"></i>
                                    </div>
                                    <span class="text-sm font-medium">Geen items gevonden in deze weergave.</span>
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
                <div class="glass-card stagger-item group relative bg-white/60 backdrop-blur-sm rounded-3xl shadow-sm hover:shadow-xl transition-all duration-500 border border-slate-200/60 hover:border-indigo-200/60 flex flex-col h-full overflow-hidden hover:-translate-y-1" 
                     style="animation-delay: {{ $loop->index * 50 }}ms;"
                     data-id="{{ $item->id }}"
                     @contextmenu.prevent="openContextMenu($event, itemsStore[{{ $item->id }}])"
                     :class="selectedItems.includes({{ $item->id }}) ? 'ring-2 ring-indigo-500 border-transparent shadow-indigo-100/50 bg-indigo-50/30' : ''">
                    
                    <!-- Image Section -->
                    <div class="relative aspect-square bg-slate-50 overflow-hidden cursor-zoom-in" @click="openImage('{{ $item->image_url }}')">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" 
                                 class="js-item-image w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
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
                             @elseif($item->status == 'personal')
                                <span class="inline-flex items-center gap-1 bg-purple-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-user"></i> Eigen
                                </span>
                             @else
                                <span class="inline-flex items-center gap-1 bg-slate-500/90 backdrop-blur text-white text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm">
                                    <i class="fa-solid fa-list"></i> To-do
                                </span>
                             @endif
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-5 flex flex-col flex-1">
                        <div class="mb-2 flex justify-between items-start">
                            <span class="js-item-brand text-[10px] font-extrabold tracking-widest text-slate-400 uppercase">{{ $item->brand ?? 'Onbekend' }}</span>
                            @if($item->size)
                                <span class="js-item-size text-[10px] font-extrabold tracking-wider bg-slate-100/60 text-slate-500 px-2 py-0.5 rounded border border-slate-200/60 shadow-sm">{{ $item->size }}</span>
                            @endif
                        </div>
                        
                        <h3 class="js-item-name font-bold text-slate-800 text-lg leading-snug mb-3 line-clamp-2 min-h-[3rem] group-hover:text-indigo-600 transition-colors" title="{{ $item->name }}">{{ $item->name }}</h3>
                        
                        <div class="flex items-center gap-2 mb-4">
                            <span class="js-item-category text-[10px] font-bold bg-white/80 backdrop-blur-sm shadow-sm text-slate-500 border border-slate-100 px-2 py-1 rounded-lg truncate max-w-[120px]"><i class="fa-solid fa-layer-group text-slate-300 mr-1"></i> {{ $item->category ?? 'Overige' }}</span>
                            @if($item->order_nmr)
                                <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-50/80 shadow-sm border border-blue-100/50 px-2 py-1 rounded-lg truncate" title="Order #{{ $item->order_nmr }}">#{{ $item->order_nmr }}</span>
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
                                @if($item->status == 'personal')
                                    <div class="bg-purple-50 text-purple-700 rounded-lg py-1.5 text-xs font-bold flex items-center justify-center gap-1 border border-purple-100">
                                        <i class="fa-solid fa-user"></i> EIGEN
                                    </div>
                                @else
                                    <div class="bg-emerald-50 text-emerald-700 rounded-lg py-1.5 text-xs font-bold flex items-center justify-center gap-1 border border-emerald-100">
                                        <i class="fa-solid fa-check-circle"></i> VERKOCHT
                                    </div>
                                @endif
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



        <!-- Kanban View -->
        <div class="flex overflow-x-auto gap-6 pb-8 px-2" x-show="viewMode === 'kanban'" x-cloak>
            @php
                $configs = [
                    'todo' => ['label' => 'Warehouse', 'color' => 'slate', 'icon' => 'fa-warehouse'],
                    'prep' => ['label' => 'Shipped', 'color' => 'amber', 'icon' => 'fa-box-open'],
                    'online' => ['label' => 'Te Koop', 'color' => 'indigo', 'icon' => 'fa-globe'],
                    'sold' => ['label' => 'Verkocht', 'color' => 'emerald', 'icon' => 'fa-check-circle'],
                ];
            @endphp
            @foreach($configs as $status => $config)
                <div class="glass-card flex-shrink-0 w-80 flex flex-col h-full rounded-3xl bg-{{ $config['color'] }}-50/30 border border-{{ $config['color'] }}-100/40 shadow-sm">
                    <!-- Column Header -->
                    <div class="p-5 flex items-center justify-between border-b border-{{ $config['color'] }}-100/30 bg-white/20 rounded-t-3xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-{{ $config['color'] }}-100/50 text-{{ $config['color'] }}-500 flex items-center justify-center">
                                <i class="fa-solid {{ $config['icon'] }} text-lg"></i>
                            </div>
                            <h3 class="font-bold text-slate-800">{{ $config['label'] }}</h3>
                        </div>
                        <span class="text-xs font-bold bg-white px-2 py-1 rounded-md text-slate-500 shadow-sm">
                            {{ $items->where('status', $status)->count() }}
                        </span>
                    </div>

                    <!-- Column Content -->
                    <div id="kanban-{{ $status }}" class="p-3 flex-1 overflow-y-auto min-h-[500px] space-y-3" data-status="{{ $status }}">
                        @foreach($items as $item)
                            @if($item->status == $status)
                                <div class="kanban-item stagger-item bg-white/90 backdrop-blur border border-slate-200/50 p-3 rounded-2xl shadow-sm cursor-grab hover:shadow-md hover:border-indigo-200 transition-all duration-300 group relative hover:-translate-y-0.5" 
                                     style="animation-delay: {{ $loop->index * 50 }}ms;"
                                     data-id="{{ $item->id }}"
                                     @contextmenu.prevent="openContextMenu($event, itemsStore[{{ $item->id }}])">
                                    
                                    <div class="flex gap-3">
                                        <!-- Mini Image -->
                                        <div class="relative w-14 h-14 rounded-xl bg-slate-50 flex-shrink-0 overflow-hidden border border-slate-100/50 shadow-inner group-hover:shadow-sm transition-shadow">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                                                    <i class="fa-solid fa-image text-xs mb-1 opacity-50"></i>
                                                </div>
                                            @endif
                                            
                                            <!-- Checkbox overlay -->
                                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <input type="checkbox" class="item-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer shadow-sm"
                                                    value="{{ $item->id }}"
                                                    @change="toggleItem({{ $item->id }})"
                                                    :checked="selectedItems.includes({{ $item->id }})">
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0 flex flex-col py-0.5">
                                            <div class="flex justify-between items-start mb-1">
                                                <span class="js-item-brand text-[9px] font-extrabold text-slate-400 uppercase tracking-widest truncate">{{ $item->brand }}</span>
                                                @if($item->size)
                                                    <span class="js-item-size text-[9px] font-bold bg-slate-100/80 px-1.5 py-0.5 rounded text-slate-500 shadow-sm border border-slate-200/50">{{ $item->size }}</span>
                                                @endif
                                            </div>
                                            <div class="js-item-name font-bold text-xs text-slate-800 leading-tight mb-auto line-clamp-2 group-hover:text-indigo-600 transition-colors">{{ $item->name }}</div>
                                            <div class="flex justify-between items-end mt-2 pt-2 border-t border-slate-50">
                                                <span class="js-item-sell-price font-bold text-xs text-slate-900 bg-white shadow-sm border border-slate-100 px-2 py-0.5 rounded-lg flex items-center gap-1"><span class="text-[9px] font-normal text-slate-400">€</span> {{ number_format($item->sell_price ?? 0, 0) }}</span>
                                                <button @click.stop="openEdit({{ Js::from($item) }})" class="w-6 h-6 rounded-md bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 transition-all flex items-center justify-center border border-slate-100 group-hover:border-indigo-100">
                                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        
                        <!-- Dient als dropzone target ook al is hij leeg -->
                        <div class="h-10 border-2 border-dashed border-{{ $config['color'] }}-200/50 rounded-2xl flex items-center justify-center text-{{ $config['color'] }}-400 text-[10px] font-bold uppercase tracking-wider opacity-50 bg-white/30 backdrop-blur-sm transition-opacity hover:opacity-100">
                            Zet hier neer
                        </div>
                    </div>
                </div>
            @endforeach
            <!-- Spacer for visual cutoff fix -->
            <div class="w-1 flex-shrink-0"></div>
        </div>

        <div class="mt-6">
            {{ $items->links() }}
        </div>

        <!-- Quick Edit Modal -->
        <div x-show="showEditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showEditModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @click="showEditModal = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showEditModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white/95 backdrop-blur-xl rounded-[2rem] text-left overflow-hidden shadow-2xl border border-white transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">
                    <form :action="'/inventory/' + editingItem.id" method="POST" class="p-8" enctype="multipart/form-data" @submit.prevent="submitEditModal">
                        @csrf @method('PATCH')
                        <div class="mb-6 flex justify-between items-center bg-slate-50/50 -mt-8 -mx-8 px-8 py-5 border-b border-slate-100">
                            <h3 class="text-xl font-bold border-gray-900" id="modal-title">Snel Bewerken</h3>
                            <button type="button" @click="showEditModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 transition-colors">
                                <span class="sr-only">Sluiten</span>
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Naam</label>
                                <input type="text" name="name" x-model="editingItem.name" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm transition-colors px-4 py-2.5">
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Merk</label>
                                    <input type="text" name="brand" x-model="editingItem.brand" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm transition-colors px-4 py-2.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Maat</label>
                                    <input type="text" name="size" x-model="editingItem.size" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm transition-colors px-4 py-2.5">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Categorie</label>
                                <select name="category" x-model="editingItem.category" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm transition-colors px-4 py-2.5">
                                    <option value="">Selecteer Categorie</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Inkoop</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-2.5 text-slate-400 font-bold">€</span>
                                        <input type="number" step="0.01" name="buy_price" x-model="editingItem.buy_price" class="block w-full pl-9 pr-4 py-2.5 rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm font-medium transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Verkoop</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-2.5 text-slate-400 font-bold">€</span>
                                        <input type="number" step="0.01" name="sell_price" x-model="editingItem.sell_price" class="block w-full pl-9 pr-4 py-2.5 rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm font-medium transition-colors">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Notities</label>
                                <textarea name="notes" x-model="editingItem.notes" rows="2" class="block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white text-sm transition-colors px-4 py-3"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Afbeelding</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-indigo-400 transition-colors bg-slate-50/50">
                                    <div class="space-y-1 text-center">
                                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-3"></i>
                                        <div class="flex text-sm text-slate-600 justify-center">
                                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-transparent font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2">
                                                <span>Upload een bestand</span>
                                                <input id="file-upload" name="image" type="file" accept="image/*" class="sr-only">
                                            </label>
                                        </div>
                                        <p class="text-xs text-slate-500">PNG, JPG, GIF up to 5MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-slate-100">
                            <button type="button" @click="showEditModal = false" class="px-5 py-2.5 border border-slate-200 rounded-xl shadow-sm text-sm font-bold text-slate-600 bg-white hover:bg-slate-50 hover:text-slate-900 transition-colors">Annuleren</button>
                            <button type="submit" class="px-5 py-2.5 border border-transparent rounded-xl shadow-sm shadow-indigo-200 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 hover:shadow-md hover:-translate-y-0.5 transition-all">Wijzigingen Opslaan</button>
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

        <div x-show="showImport" x-cloak 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm" @click.self="showImport = false">
             
             <div x-show="showImport"
                  x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                  x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                  class="bg-white/95 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl border border-white w-full max-w-2xl m-4">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-heading font-bold text-2xl text-slate-900">Import CSV of Tekst</h3>
                    <button @click="showImport = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>
                <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-5">
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">Koppel aan Pakket</label>
                        <select name="parcel_id" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium">
                            <option value="">Geen pakket koppelen</option>
                            @foreach($parcels as $p)
                                <option value="{{$p->id}}">{{$p->parcel_no}}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-6 bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100/50">
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-indigo-800 mb-2">Upload Order PDF</label>
                        <input type="file" name="order_pdf" accept="application/pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 file:transition-colors file:cursor-pointer cursor-pointer bg-white rounded-xl border border-indigo-100 shadow-sm p-1.5">
                        <p class="text-xs text-indigo-500 mt-2 flex items-center gap-1.5"><i class="fa-solid fa-magic"></i> PDF wordt automatisch uitgelezen en toegevoegd aan de voorraad.</p>
                    </div>

                    <div class="mb-6 relative">
                         <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5 ">Of plak ruwe tekst</label>
                        <textarea name="import_text" class="w-full h-48 p-4 border border-slate-200 rounded-xl text-sm font-mono bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all shadow-inner placeholder-slate-400" placeholder="Plak hier de gekopieerde tekst rijen van je agent (bijv. Superbuy, Sugargoo)..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="showImport = false" class="px-6 py-3 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Annuleren</button>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold shadow-sm shadow-indigo-200 hover:shadow-md hover:bg-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                            <span>Importeren</span>
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </button>
                    </div>
                </form>
             </div>
        </div>

        <div x-show="showSellModal" x-cloak 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-md" @click.self="showSellModal = false">
             
             <div x-show="showSellModal"
                  x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90 translate-y-8" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                  x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                  class="bg-white/95 backdrop-blur-2xl p-8 rounded-[2rem] shadow-2xl border border-white w-full max-w-sm m-4">
                
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                            <i class="fa-solid fa-hand-holding-dollar text-white text-xl"></i>
                        </div>
                        <h3 class="font-heading font-bold text-2xl text-slate-900" x-text="sellModalMode === 'sold' ? 'Verkocht!' : 'Prijs Instellen'">Verkocht!</h3>
                    </div>
                    <button @click="showSellModal = false" class="text-slate-400 hover:text-slate-600 w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="bg-slate-50/80 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 mb-8 overflow-hidden relative">
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1.5">Item geselecteerd</p>
                    <p class="text-slate-800 font-bold text-lg leading-tight truncate z-10 relative" x-text="sellingItem.name"></p>
                    <i class="fa-solid fa-box text-6xl absolute -right-4 -bottom-4 text-slate-200/50 z-0 transform -rotate-12"></i>
                </div>

                <form @submit.prevent="submitSellModal">
                    <div class="space-y-6">
                        <div>
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-2" x-text="sellModalMode === 'sold' ? 'Voor hoeveel heb je het verkocht?' : 'Verkoopprijs Instellen'"></label>
                            <div class="relative mt-1">
                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-2xl">€</span>
                                <input type="number" step="0.01" name="sell_price" required x-model="sellingItem.sell_price" 
                                class="w-full pl-12 pr-6 py-4 rounded-2xl border border-slate-200 bg-white shadow-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-400 font-bold text-3xl text-slate-800 transition-all placeholder-slate-300" placeholder="0.00" autofocus>
                            </div>
                        </div>
                        
                        <div x-show="sellModalMode === 'sold'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-2">Datum van Verkoop</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-regular fa-calendar text-slate-400"></i>
                                </div>
                                <input type="date" name="sold_date" x-model="sellingItem.sold_date" class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-slate-200 bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-400 font-bold text-slate-700 shadow-sm transition-all text-sm">
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-black transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 mt-8 flex justify-center items-center gap-2 text-lg active:scale-95">
                           <i x-show="sellModalMode === 'sold'" class="fa-solid fa-check text-emerald-400"></i>
                           <i x-show="sellModalMode !== 'sold'" class="fa-solid fa-floppy-disk text-indigo-400"></i>
                           <span x-text="sellModalMode === 'sold' ? 'Bevestigen' : 'Prijs Opslaan'"></span>
                        </button>
                    </div>
                </form>
             </div>
        </div>

        <div x-show="showNew" x-cloak 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm" @click.self="showNew = false">
             
            <div x-show="showNew"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="bg-white/95 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl w-full max-w-xl m-4 border border-white max-h-[90vh] overflow-y-auto custom-scrollbar">
                
                <div class="flex justify-between items-center mb-8 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-100 text-indigo-600 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <h3 class="font-heading font-bold text-2xl text-slate-900">Nieuw Item</h3>
                    </div>
                    <button @click="showNew = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="space-y-5">
                        @if($templates->count() > 0)
                        <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50 relative overflow-hidden group">
                            <div class="absolute -right-4 -top-4 w-16 h-16 bg-indigo-500 rounded-full opacity-10 blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                            <label class="text-xs font-extrabold text-indigo-600 uppercase tracking-widest block mb-2"><i class="fa-solid fa-bolt mr-1"></i> Preset Gebruiken</label>
                            <div class="relative">
                                <select @change="applyPreset($event)" class="w-full pl-4 pr-10 py-3 border border-white bg-white/60 rounded-xl text-sm font-bold text-slate-800 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-400 appearance-none transition-all shadow-sm">
                                    <option value="">Selecteer om vliegensvlug in te vullen...</option>
                                    @foreach($templates as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-indigo-500">
                                    <i class="fa-solid fa-angle-down"></i>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Naam*</label>
                            <input type="text" id="new_name" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                        </div>

                        <div>
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Order Nmr</label>
                            <input type="text" id="new_order_nmr" name="order_nmr" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                        </div>

                        <div class="flex gap-4">
                            <div class="w-1/2">
                                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Merk</label>
                                <input type="text" id="new_brand" name="brand" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                            </div>
                            <div class="w-1/2">
                                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Maat</label>
                                <input type="text" id="new_size" name="size" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Categorie</label>
                            <select id="new_category" name="category" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                                <option value="">Automatisch bepalen</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                                <option value="Sneakers">Sneakers</option>
                                <option value="Kleding">Kleding</option>
                                <option value="Accessoires">Accessoires</option>
                            </select>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-1/2">
                                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Inkoop (€)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">€</span>
                                    <input type="number" step="0.01" id="new_buy_price" name="buy_price" class="w-full pl-9 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                                </div>
                            </div>
                            <div class="w-1/2">
                                <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Verkoop (€)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">€</span>
                                    <input type="number" step="0.01" id="new_sell_price" name="sell_price" class="w-full pl-9 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-extrabold text-slate-500 uppercase tracking-widest block mb-1.5">Pakket (Optioneel)</label>
                            <select name="parcel_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium text-slate-800">
                                <option value="">Geen</option>
                                @foreach($parcels as $p)
                                    <option value="{{ $p->id }}">{{ $p->parcel_no }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex gap-3">
                            <button type="button" @click="showNew = false" class="w-1/3 py-4 rounded-xl font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Annuleren</button>
                            <button type="submit" class="w-2/3 bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:shadow-xl hover:-translate-y-0.5 hover:bg-indigo-700 transition-all flex justify-center items-center gap-2">
                                <i class="fa-solid fa-plus"></i> Item Opslaan
                            </button>
                        </div>
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
        <!-- Global Context Menu -->
        <div x-show="contextMenu.show" 
             @click.outside="closeContextMenu()"
             @keydown.escape.window="closeContextMenu()"
             @scroll.window="closeContextMenu()"
             class="fixed z-[300] bg-white/95 backdrop-blur-xl border border-slate-200/60 shadow-2xl rounded-2xl w-56 p-1.5 flex flex-col gap-0.5 overflow-hidden"
             :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="display: none;">
             
             <!-- Item Header in Menu -->
             <div class="px-3 py-2 border-b border-slate-100/60 mb-1">
                 <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-0.5" x-text="contextMenu.item?.brand || 'Onbekend'"></div>
                 <div class="text-xs font-bold text-slate-800 line-clamp-1" x-text="contextMenu.item?.name"></div>
             </div>

            <template x-if="contextMenu.item?.status !== 'sold'">
                <button @click="closeContextMenu(); openSell(contextMenu.item)" class="w-full text-left px-3 py-2 text-sm font-medium text-slate-700 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors flex items-center justify-between group">
                    <span class="flex items-center gap-2.5"><i class="fa-solid fa-money-bill-wave text-slate-400 group-hover:text-emerald-500 w-4 text-center"></i> Verkopen</span>
                </button>
            </template>

            <button @click="closeContextMenu(); openEdit(contextMenu.item)" class="w-full text-left px-3 py-2 text-sm font-medium text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors flex items-center justify-between group">
                <span class="flex items-center gap-2.5"><i class="fa-solid fa-pen text-slate-400 group-hover:text-indigo-500 w-4 text-center"></i> Bewerken</span>
            </button>

            <button @click="closeContextMenu(); openImage(contextMenu.item?.image_url)" class="w-full text-left px-3 py-2 text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-colors flex items-center justify-between group">
                <span class="flex items-center gap-2.5"><i class="fa-solid fa-image text-slate-400 group-hover:text-blue-500 w-4 text-center"></i> Foto Bekijken</span>
            </button>
            
            <div class="h-px bg-slate-100/60 my-1"></div>

            <form :action="`/inventory/${contextMenu.item?.id}`" method="POST" @submit="if(!confirm('Zeker weten verwijderen?')) $event.preventDefault()" class="w-full">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-left px-3 py-2 text-sm font-medium text-slate-700 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors flex items-center justify-between group">
                    <span class="flex items-center gap-2.5"><i class="fa-solid fa-trash text-slate-400 group-hover:text-red-500 w-4 text-center"></i> Verwijderen</span>
                </button>
            </form>
        </div>

    </div>

    <!-- Global Toast Container -->
    <div x-data="{ toasts: [] }" 
         @notify.window="toasts.push({ id: Date.now(), msg: $event.detail.msg, type: $event.detail.type || 'success' }); setTimeout(() => { toasts.shift() }, 3000)"
         class="fixed bottom-5 right-5 z-[200] flex flex-col gap-3 pointer-events-none">
        
         <template x-for="toast in toasts" :key="toast.id">
             <div x-show="true" 
                  x-transition:enter="transition ease-out duration-300 transform" 
                  x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
                  x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                  x-transition:leave="transition ease-in duration-200 transform" 
                  x-transition:leave-start="opacity-100 scale-100" 
                  x-transition:leave-end="opacity-0 scale-95" 
                  class="px-5 py-3 rounded-2xl shadow-xl backdrop-blur-md border border-white/20 text-white font-bold text-sm tracking-wide flex items-center gap-3"
                  :class="toast.type === 'success' ? 'bg-emerald-500/90 shadow-emerald-500/20' : 'bg-red-500/90 shadow-red-500/20'">
                 <i class="fa-solid" :class="toast.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                 <span x-text="toast.msg"></span>
             </div>
         </template>
    </div>
</x-app-layout>
