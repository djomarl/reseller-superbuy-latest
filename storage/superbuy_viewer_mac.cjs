/**
 * Superbuy Order Viewer (Alles-in-één)
 * * 1. Installeer Node.js
 * 2. Run: npm install express axios cheerio open
 * 3. Run: node superbuy_viewer_mac.cjs
 */

const express = require('express');
const axios = require('axios');
const cheerio = require('cheerio');
// We gebruiken dynamic import voor 'open' later om fouten te voorkomen

const app = express();
const PORT = 3000;

// CORS Middleware
app.use((req, res, next) => {
    res.header("Access-Control-Allow-Origin", "*");
    res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
    res.header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

// Zorg dat we grote cURL requests aankunnen
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

// --- FUNCTIES OM DATA TE LEZEN ---

function parseCurl(curlString) {
    const headers = {};
    let url = 'https://www.superbuy.com/order'; 

    // Pak de URL
    const urlMatch = curlString.match(/'(https?:\/\/[^']+)'/);
    if (urlMatch) url = urlMatch[1];

    // Pak de Headers
    const headerRegex = /-H '([^:]+): ([^']+)'/g;
    let match;
    while ((match = headerRegex.exec(curlString)) !== null) {
        const key = match[1].trim();
        const value = match[2].trim();
        if (key.toLowerCase() !== 'accept-encoding') { // Voorkom compressie problemen
            headers[key] = value;
        }
    }

    // Pak de Cookie apart (soms staat die bij -b of -H)
    const cookieMatch = curlString.match(/-b '([^']+)'/);
    if (cookieMatch) headers['Cookie'] = cookieMatch[1];

    // Fake een browser als die mist
    if (!headers['user-agent']) {
        headers['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    }

    return { url, headers };
}

function parseOrderHtml($, element) {
    // Zoek de tabel in de div
    const table = $(element).find('table.user_orderlist');
    if (table.length === 0) return null;

    const orderData = { items: [] };
    
    // Order Id from div id
    const divId = $(element).attr('id');
    if (divId) orderData.orderId = divId.replace('div', '');

    const headerText = table.find('thead').text();
    
    // Order Nummer & Datum
    const orderNoMatch = headerText.match(/Order No[：:]\s*([A-Z0-9]+)/i);
    orderData.orderNo = orderNoMatch ? orderNoMatch[1] : 'Onbekend';
    
    const dateMatch = headerText.match(/(\d{4}-\d{2}-\d{2})/);
    orderData.date = dateMatch ? dateMatch[1] : '-';

    // Totaalprijs (staat vaak in de laatste kolom met rowspan)
    const totalText = table.find('td[rowspan]').text().replace(/\s+/g, ' ');
    const totalMatch = totalText.match(/Total Amount:([A-Z\s€$£]+[\d\.]+)/i);
    orderData.total = totalMatch ? totalMatch[1].trim() : 'Zie details';
    orderData.totalRaw = orderData.total; // Alias for PHP compatibility

    const postageMatch = totalText.match(/Postage Inclusive:\s*([A-Z€$£\s]+[\d\.]+)/i);
    if(postageMatch) orderData.postageRaw = postageMatch[1].trim();

    // Items in de order
    table.find('tbody tr').each((i, tr) => {
        // We zoeken alleen rijen met een plaatje, dat zijn de echte items
        const imgEl = $(tr).find('.js-item-img');
        if (imgEl.length > 0) {
            const item = {};
            item.title = $(tr).find('.js-item-title').text().trim();
            item.link = $(tr).find('.js-item-title').attr('href');
            if (item.link && !item.link.startsWith('http')) item.link = 'https://www.superbuy.com' + item.link;
            
            item.image = imgEl.attr('src');
            item.options = $(tr).find('.user_orderlist_txt').text().trim(); // Kleur/Maat
            
            // Prijs staat meestal in de 2e kolom (index 1)
            item.price = $(tr).find('td').eq(1).text().trim().replace(/\s+/g, ' ');
            
            // Aantal
            item.qty = $(tr).find('.qty-div').text().trim();
            
            // Status
            item.status = $(tr).find('.show_status').text().trim();
            if (!item.status) {
                const text = $(tr).text();
                if (text.includes('Completed')) item.status = 'Completed';
                else if (text.includes('withdrawn')) item.status = 'Withdrawn';
                else if (text.includes('Stored in Warehouse')) item.status = 'Stored';
                else item.status = 'Check status';
            }

            // QC Photos
            item.qcPhotos = [];
            $(tr).find('.pic-list li a.lookPic').each((_, a) => {
                const href = $(a).attr('href');
                if(href) item.qcPhotos.push(href);
            });

            orderData.items.push(item);
        }
    });

    return orderData;
}

// --- SERVER ROUTES ---

// De HTML pagina serveren
app.get('/', (req, res) => {
    res.send(htmlPage);
});

// De API die de requests doet
app.post('/api/orders', async (req, res) => {
    const { curl, pages = 3 } = req.body;
    if (!curl) return res.status(400).json({ error: 'Geen cURL ingevuld' });

    try {
        const { url, headers } = parseCurl(curl);
        const baseUrl = url.split('?')[0]; // Strip oude params
        let allOrders = [];

        console.log(`Start scannen van ${pages} pagina's...`);

        // Loop door de pagina's
        for (let i = 1; i <= pages; i++) {
            try {
                const pageUrl = `${baseUrl}?page=${i}`;
                console.log(`Ophalen: ${pageUrl}`);
                
                const response = await axios.get(pageUrl, { headers, validateStatus: false });
                
                if (response.status !== 200) break; // Stop als pagina niet laadt

                const $ = cheerio.load(response.data);
                
                // Superbuy structuur: elke order zit in een div met id="div12345"
                const orderDivs = $('div[id^="div"]');
                
                if (orderDivs.length === 0) {
                    // Geen orders meer gevonden, stop
                    break; 
                }

                orderDivs.each((idx, el) => {
                    const order = parseOrderHtml($, el);
                    if (order && order.items.length > 0) {
                        allOrders.push(order);
                    }
                });

                // Even wachten om niet geblokkeerd te worden
                await new Promise(r => setTimeout(r, 800));

            } catch (err) {
                console.error(`Fout op pagina ${i}:`, err.message);
            }
        }

        res.json({ success: true, orders: allOrders });

    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// --- DE HTML PAGINA (FRONTEND) ---
const htmlPage = `
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superbuy Order Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; }
        .sb-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .status-badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        .status-green { background: #dcfce7; color: #166534; }
        .status-blue { background: #dbeafe; color: #1e40af; }
        .status-red { background: #fee2e2; color: #991b1b; }
        .status-gray { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body class="text-gray-800">

    <div class="max-w-6xl mx-auto p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-indigo-700">
                <i class="fa-solid fa-cart-shopping mr-2"></i> Superbuy Orders
            </h1>
            <div class="text-sm text-gray-500 bg-white px-4 py-2 rounded-lg shadow-sm">
                Status: <span class="text-green-600 font-bold">● Systeem Online</span>
            </div>
        </div>

        <!-- Input Box -->
        <div class="sb-card p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4 border-b pb-2">1. Plak je cURL</h2>
            <p class="text-sm text-gray-500 mb-2">Ga naar je Superbuy Order pagina, open DevTools (F12) > Network, ververs de pagina, klik rechts op de request naar 'order' > Copy > Copy as cURL.</p>
            
            <textarea id="curlInput" class="w-full h-32 p-3 border border-gray-300 rounded-lg text-xs font-mono bg-gray-50 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="curl 'https://www.superbuy.com/order' ..."></textarea>
            
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium">Pagina's scannen:</label>
                    <input type="number" id="pagesInput" value="3" min="1" max="50" class="w-20 border rounded-md p-2 text-center">
                </div>
                <button onclick="getOrders()" id="scanBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-bold transition shadow-md flex items-center">
                    <i class="fa-solid fa-search mr-2"></i> Zoek Orders
                </button>
            </div>
            <div id="statusMsg" class="mt-2 text-sm font-medium text-indigo-600 hidden">Bezig met ophalen... even geduld.</div>
        </div>

        <!-- Result Table -->
        <div id="resultSection" class="hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Gevonden Orders (<span id="countDisplay">0</span>)</h2>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Zoek op naam of ID..." class="p-2 border rounded-lg w-64 text-sm">
            </div>

            <div class="sb-card overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b">
                            <th class="p-4">Foto</th>
                            <th class="p-4">Product Info</th>
                            <th class="p-4">Opties</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Prijs</th>
                            <th class="p-4 text-right bg-gray-100">Order Totaal</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-gray-100 text-sm">
                        <!-- Rows komen hier -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Check of er al een cURL is opgeslagen
        window.onload = () => {
            const saved = localStorage.getItem('sb_curl');
            if(saved) document.getElementById('curlInput').value = saved;
        }

        async function getOrders() {
            const curl = document.getElementById('curlInput').value;
            const pages = document.getElementById('pagesInput').value;
            const btn = document.getElementById('scanBtn');
            const msg = document.getElementById('statusMsg');

            if(!curl) return alert("Plak eerst je cURL code!");
            
            // UI updates
            localStorage.setItem('sb_curl', curl);
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Laden...';
            msg.classList.remove('hidden');
            document.getElementById('tableBody').innerHTML = '';

            try {
                const req = await fetch('/api/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ curl, pages: parseInt(pages) })
                });
                
                const res = await req.json();
                
                if(res.error) {
                    alert('Fout: ' + res.error);
                } else {
                    renderTable(res.orders);
                }

            } catch(e) {
                alert('Er ging iets mis met de verbinding naar de lokale server.');
                console.error(e);
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = '<i class="fa-solid fa-search mr-2"></i> Zoek Orders';
                msg.classList.add('hidden');
            }
        }

        function renderTable(orders) {
            const tbody = document.getElementById('tableBody');
            let totalItems = 0;
            
            if(orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-500">Geen orders gevonden. Check je cURL of login status.</td></tr>';
                document.getElementById('resultSection').classList.remove('hidden');
                return;
            }

            orders.forEach(order => {
                // Eerste item van de order printen we met de Order Info (rowspan)
                order.items.forEach((item, index) => {
                    totalItems++;
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 transition-colors group';

                    // Bepaal status kleur
                    let badgeClass = 'status-gray';
                    const s = (item.status || '').toLowerCase();
                    if(s.includes('warehouse')) badgeClass = 'status-blue';
                    else if(s.includes('completed')) badgeClass = 'status-green';
                    else if(s.includes('withdrawn') || s.includes('refund')) badgeClass = 'status-red';

                    const itemHtml = \`
                        <td class="p-4 w-20">
                            <img src="\${item.image}" class="w-16 h-16 object-cover rounded border bg-gray-100" loading="lazy">
                        </td>
                        <td class="p-4 max-w-xs">
                            <a href="\${item.link}" target="_blank" class="text-indigo-600 font-medium hover:underline line-clamp-2" title="\${item.title}">\${item.title}</a>
                            <div class="text-xs text-gray-400 mt-1">ID: \${order.orderNo}</div>
                            <div class="text-xs text-gray-400">\${order.date}</div>
                        </td>
                        <td class="p-4 text-xs text-gray-500 max-w-xs">
                            <div class="bg-gray-100 p-2 rounded">\${item.options}</div>
                            <div class="mt-1 font-bold">Aantal: \${item.qty}</div>
                        </td>
                        <td class="p-4">
                            <span class="status-badge \${badgeClass}">\${item.status}</span>
                        </td>
                        <td class="p-4 text-right font-mono text-gray-700">
                            \${item.price}
                        </td>
                    \`;

                    // Voeg order totaal alleen toe aan de eerste rij van de order (soort rowspan effect)
                    let totalHtml = '';
                    if(index === 0) {
                        totalHtml = \`
                            <td class="p-4 text-right font-bold text-gray-900 bg-gray-50 align-top border-l" rowspan="\${order.items.length}">
                                \${order.total}
                            </td>
                        \`;
                    }

                    row.innerHTML = itemHtml + (index === 0 ? totalHtml : '');
                    tbody.appendChild(row);
                });
                
                // Divider line
                const divider = document.createElement('tr');
                divider.innerHTML = '<td colspan="6" class="border-b border-gray-200"></td>';
                tbody.appendChild(divider);
            });

            document.getElementById('countDisplay').innerText = totalItems;
            document.getElementById('resultSection').classList.remove('hidden');
        }

        function filterTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.getElementById('tableBody').getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if(row.innerHTML.includes('colspan')) continue; // Skip dividers
                
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            }
        }
    </script>
</body>
</html>
`;

// Start de server en open direct de browser
app.listen(PORT, async () => {
    const url = 'http://localhost:' + PORT;
    console.log('✅ Server gestart!');
    console.log('👉 Open deze link in je browser: ' + url);
    
    try {
        // Probeer browser te openen via dynamic import (werkt beter in nieuwere Node versies)
        const open = await import('open');
        if (open.default) {
            await open.default(url);
        } else {
            await open(url);
        }
    } catch (e) {
        console.log('⚠️  Kon browser niet automatisch openen. Klik handmatig op de link hierboven.');
    }
});
