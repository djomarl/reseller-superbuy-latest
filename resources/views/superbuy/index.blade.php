<x-app-layout>
    <div class="max-w-7xl mx-auto p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-white flex items-center">
                <i class="fa-solid fa-layer-group mr-2 text-indigo-400"></i> Superbuy Import <span class="ml-2 text-xs bg-indigo-500 text-white px-2 py-1 rounded">PRO</span>
            </h1>
            <div class="flex gap-2">
                <a href="{{ route('inventory.index') }}" class="text-sm text-gray-400 hover:text-white">Terug naar Voorraad</a>
            </div>
        </div>

        <!-- Input Box -->
        <div id="inputCard" class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6 shadow-lg">
            <div class="flex justify-between items-center mb-2">
                <label class="font-semibold text-sm text-gray-300">cURL Command (van Superbuy /order pagina)</label>
                <button onclick="clearCurl()" class="text-xs text-red-400 hover:underline">Wissen</button>
            </div>

            <textarea id="curlInput" class="w-full h-24 p-3 border border-gray-600 rounded-lg text-xs font-mono bg-gray-900 text-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none transition mb-4" placeholder="curl 'https://www.superbuy.com/order' ..."></textarea>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-gray-900 p-2 rounded-lg border border-gray-600 text-gray-300">
                    <span class="text-sm font-medium">Pagina's:</span>
                    <input type="number" id="pagesInput" value="3" min="1" max="20" class="w-12 bg-transparent text-center font-bold outline-none border-none focus:ring-0">
                </div>

                <div class="flex items-center gap-2 bg-gray-900 p-2 rounded-lg border border-gray-600 text-gray-300">
                    <span class="text-sm font-medium"><i class="fa-brands fa-apple"></i> Mode:</span>
                    <select id="modeInput" class="bg-transparent text-sm font-bold outline-none border-none focus:ring-0 text-white cursor-pointer w-28">
                        <option value="php" class="bg-gray-800">Server (PHP)</option>
                        <option value="mac" class="bg-gray-800">Mac (Node)</option>
                    </select>
                </div>

                <button onclick="getOrders()" id="scanBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold text-sm transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-sync"></i> Ophalen
                </button>
                <div id="msgBox" class="text-sm font-medium text-gray-400 hidden"></div>
            </div>
        </div>

        <!-- Action Bar (Import) -->
        <div id="actionBar" class="hidden mb-4 flex justify-between items-center bg-gray-800 p-4 rounded-lg border border-gray-700">
            <div class="text-white text-sm">
                <span id="selectedCount" class="font-bold text-indigo-400">0</span> items geselecteerd
            </div>
            <button onclick="importSelected()" id="importBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold text-sm transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-down"></i> Importeer Selectie
            </button>
        </div>

        <!-- Orders Table -->
        <div id="resultSection" class="hidden">
            <div class="mb-4 relative">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Zoek op product, ID of status..." class="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-900 text-gray-400 text-xs uppercase font-bold tracking-wider border-b border-gray-700">
                                <th class="p-4 w-10">
                                    <input type="checkbox" onclick="toggleAll(this)" class="rounded bg-gray-700 border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="p-4 w-24">Afbeelding</th>
                                <th class="p-4">Product Details</th>
                                <th class="p-4">Variant & Aantal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Stukprijs</th>
                                <th class="p-4 text-right bg-gray-900/50 border-l border-gray-700">Berekend Totaal</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-gray-700 text-sm text-gray-300">
                            <!-- JS vult dit -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- QC Modal -->
    <div id="qcModal" class="fixed w-full h-full top-0 left-0 flex items-center justify-center hidden z-50">
        <div class="absolute w-full h-full bg-black opacity-80" onclick="closeQcModal()"></div>
        <div class="bg-gray-800 w-11/12 md:max-w-5xl mx-auto rounded shadow-lg z-50 overflow-y-auto max-h-[90vh] border border-gray-600">
            <div class="py-4 text-left px-6">
                <!-- Title -->
                <div class="flex justify-between items-center pb-3 border-b border-gray-700">
                    <p class="text-2xl font-bold text-white">QC Inspectie Foto's</p>
                    <div class="cursor-pointer z-50 text-gray-400 hover:text-white" onclick="closeQcModal()">
                        <i class="fa-solid fa-times text-2xl"></i>
                    </div>
                </div>

                <!-- Body -->
                <div id="qcModalBody" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <!-- Foto's komen hier -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentData = [];
        let selectedItems = new Set(); // Stores indices e.g. "orderIdx-itemIdx"

        window.onload = () => {
            const saved = localStorage.getItem('sb_curl_win');
            if(saved) document.getElementById('curlInput').value = saved;
        };

        function clearCurl() {
            document.getElementById('curlInput').value = '';
            localStorage.removeItem('sb_curl_win');
        }

        async function getOrders() {
            const curl = document.getElementById('curlInput').value;
            const pages = document.getElementById('pagesInput').value;
            const mode = document.getElementById('modeInput').value;
            const btn = document.getElementById('scanBtn');
            const msg = document.getElementById('msgBox');

            if(!curl.trim()) return alert("Plak eerst je cURL!");
            localStorage.setItem('sb_curl_win', curl);

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bezig...';
            msg.innerText = "Data ophalen (" + mode + ")...";
            msg.classList.remove('hidden');

            try {
                let res;
                if (mode === 'mac') {
                     try {
                        res = await fetch("http://localhost:3000/api/orders", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ curl, pages: parseInt(pages) })
                        });
                     } catch(err) {
                        throw new Error("Kan Node server (localhost:3000) niet bereiken. Run: 'node storage/superbuy_viewer_mac.cjs'");
                     }
                } else {
                    res = await fetch("{{ route('superbuy.fetch') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ curl, pages: parseInt(pages) })
                    });
                }
                
                const data = await res.json();

                if(data.error) {
                    msg.innerText = "Fout: " + data.error;
                    msg.className = "text-red-500 font-bold ml-4";
                } else {
                    currentData = data.orders;
                    renderTable(data.orders);
                    msg.innerText = "Klaar!";
                    msg.className = "text-green-500 font-bold ml-4";
                }
            } catch (err) {
                console.error(err);
                alert('Netwerkfout.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-sync"></i> Ophalen';
            }
        }

        function renderTable(orders) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            selectedItems.clear();
            updateSelectionDisplay();

            if(!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-gray-500 italic">Geen orders gevonden.</td></tr>';
                document.getElementById('resultSection').classList.remove('hidden');
                return;
            }

            orders.forEach((order, orderIdx) => {
                let orderSum = 0;
                let orderCur = "€";

                // Calculate total
                order.items.forEach(item => {
                    const match = item.price.match(/([^\\d]+)([\\d\\.]+)/);
                    if(match) {
                        orderCur = match[1].trim();
                        const priceVal = parseFloat(match[2]);
                        const qty = parseInt(item.qty || 1);
                        orderSum += (priceVal * qty);
                    }
                });

                let postageVal = 0;
                if (order.postageRaw) {
                    const pMatch = order.postageRaw.match(/([^\\d]+)([\\d\\.]+)/);
                    if (pMatch) {
                        postageVal = parseFloat(pMatch[2]);
                    }
                }
                orderSum += postageVal;

                const formattedOrderTotal = orderCur + " " + orderSum.toFixed(2);
                const postageDisplay = postageVal > 0 ? `<div class="text-[10px] text-gray-500 font-normal">+ ${order.postageRaw} verzending</div>` : '';

                // Render items
                order.items.forEach((item, itemIdx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-700 group transition border-t border-gray-800';

                    // Unique ID for selection
                    const uniqueId = `${orderIdx}-${itemIdx}`;

                    let statusClass = 'bg-gray-700 text-gray-300';
                    const s = (item.status || '').toLowerCase();
                    if(s.includes('stored')) statusClass = 'bg-blue-900/50 text-blue-200 border border-blue-800';
                    else if(s.includes('completed')) statusClass = 'bg-green-900/50 text-green-200 border border-green-800';
                    else if(s.includes('shipped')) statusClass = 'bg-yellow-900/50 text-yellow-200 border border-yellow-800';

                    const orderLink = order.orderId ?
                        `<a href="https://www.superbuy.com/en/page/account/order/detail?orderId=${order.orderId}" target="_blank" class="text-xs text-indigo-400 hover:underline block mt-1">${order.orderNo}</a>` :
                        `<span class="text-xs text-gray-500 block mt-1">${order.orderNo}</span>`;

                    let qcBtn = '';
                    if (item.qcPhotos && item.qcPhotos.length > 0) {
                        qcBtn = `<button onclick="showQc(${orderIdx}, ${itemIdx})" class="mt-2 text-xs bg-purple-900/40 text-purple-300 px-2 py-1 rounded border border-purple-800 hover:bg-purple-800/60 transition flex items-center gap-1 font-semibold">
                            <i class="fa-solid fa-camera"></i> QC Foto's (${item.qcPhotos.length})
                        </button>`;
                    }

                    tr.innerHTML = `
                        <td class="p-4 align-top">
                            <input type="checkbox" value="${uniqueId}" onchange="toggleItem('${uniqueId}')" class="rounded bg-gray-700 border-gray-600 text-indigo-600 focus:ring-indigo-500 item-checkbox">
                        </td>
                        <td class="p-4 align-top">
                            <div class="w-16 h-16 bg-gray-700 border border-gray-600 rounded-md overflow-hidden">
                                <img src="${item.image}" class="w-full h-full object-cover" referrerpolicy="no-referrer" onerror="this.src='https://placehold.co/100?text=No+Img'">
                            </div>
                        </td>
                        <td class="p-4 align-top max-w-xs">
                            <a href="${item.link}" target="_blank" class="text-sm font-semibold text-gray-200 hover:text-indigo-400 line-clamp-2" title="${item.title}">
                                ${item.title}
                            </a>
                            ${itemIdx === 0 ? orderLink : ''}
                            ${itemIdx === 0 ? `<div class="text-[10px] text-gray-500">${order.date}</div>` : ''}
                            ${qcBtn}
                        </td>
                        <td class="p-4 align-top text-xs">
                            <div class="bg-gray-700 border border-gray-600 rounded p-1.5 inline-block text-gray-300 mb-1 max-w-[200px] truncate">
                                ${item.options}
                            </div>
                            <div class="font-bold text-gray-400">x ${item.qty}</div>
                        </td>
                        <td class="p-4 align-top whitespace-nowrap">
                            <span class="px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider ${statusClass}">${item.status}</span>
                        </td>
                        <td class="p-4 align-top text-right font-mono text-gray-400">${item.price}</td>
                        ${itemIdx === 0 ? `<td class="p-4 align-top text-right font-bold text-gray-200 bg-gray-800 border-l border-gray-700" rowspan="${order.items.length}">
                            ${formattedOrderTotal}
                            ${postageDisplay}
                        </td>` : ''}
                    `;
                    tbody.appendChild(tr);
                });
            });

            document.getElementById('resultSection').classList.remove('hidden');
            document.getElementById('actionBar').classList.remove('hidden');
        }

        function toggleItem(id) {
            if (selectedItems.has(id)) {
                selectedItems.delete(id);
            } else {
                selectedItems.add(id);
            }
            updateSelectionDisplay();
        }

        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = source.checked;
                if(source.checked) selectedItems.add(cb.value);
                else selectedItems.delete(cb.value);
            });
            updateSelectionDisplay();
        }

        function updateSelectionDisplay() {
            document.getElementById('selectedCount').innerText = selectedItems.size;
        }

        async function importSelected() {
            if(selectedItems.size === 0) return alert('Selecteer ten minste één item.');

            const btn = document.getElementById('importBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bezig...';

            // Collect data
            const itemsToImport = [];
            selectedItems.forEach(id => {
                const [orderIdx, itemIdx] = id.split('-').map(Number);
                const order = currentData[orderIdx];
                const item = order.items[itemIdx];

                // Add order info to item
                const payload = { ...item, orderNo: order.orderNo };
                itemsToImport.push(payload);
            });

            try {
                const res = await fetch("{{ route('superbuy.import') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ items: itemsToImport })
                });
                const result = await res.json();

                if(result.success) {
                    alert(result.message);
                    // Optional: remove imported items from view or mark them?
                } else {
                    alert('Fout bij importeren.');
                }
            } catch (e) {
                console.error(e);
                alert('Server fout.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function showQc(orderIdx, itemIdx) {
            const item = currentData[orderIdx].items[itemIdx];
            const modalBody = document.getElementById('qcModalBody');
            const modal = document.getElementById('qcModal');

            modalBody.innerHTML = '';

            item.qcPhotos.forEach(photoUrl => {
                const div = document.createElement('div');
                div.className = 'border border-gray-600 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition bg-gray-900';
                div.innerHTML = `
                    <a href="${photoUrl}" target="_blank">
                        <img src="${photoUrl}" class="w-full h-48 object-cover cursor-pointer hover:opacity-90" referrerpolicy="no-referrer">
                    </a>
                    <div class="p-2 bg-gray-800 text-center border-t border-gray-700">
                        <a href="${photoUrl}" target="_blank" class="text-xs text-indigo-400 hover:underline">Full Size <i class="fa-solid fa-external-link-alt ml-1"></i></a>
                    </div>
                `;
                modalBody.appendChild(div);
            });

            modal.classList.remove('hidden');
        }

        function closeQcModal() {
            const modal = document.getElementById('qcModal');
            modal.classList.add('hidden');
        }

        function filterTable() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            rows.forEach(row => {
                if(row.querySelector('td[rowspan]') && !row.querySelector('.item-checkbox')) return; // skip stand-alone spacer or total rows if any (layout differs slightly here)

                // Our layout: every row has items. Total cell is generic.
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
