/**
 * Superbuy Order Viewer (Pro + QC Photos)
 * * 1. Zorg dat nodejs is geinstalleerd
 * 2. Run: npm install express axios cheerio open
 * 3. Run: node superbuy_viewer.js
 */

const express = require("express");
const axios = require("axios");
const cheerio = require("cheerio");

const app = express();
const PORT = 3000;

app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ extended: true, limit: "50mb" }));

// --- CURL PARSER ---
function parseCurl(curlString) {
    const headers = {};
    let url = "https://www.superbuy.com/order";

    let cleanCurl = curlString
        .replace(/\\\r?\n/g, " ")
        .replace(/\^\r?\n/g, " ")
        .replace(/\^/g, "")
        .replace(/\r?\n/g, " ")
        .trim();

    const urlMatch = cleanCurl.match(/['"](https?:\/\/[^'"]+)['"]/);
    if (urlMatch) url = urlMatch[1];
    else {
        const simpleMatch = cleanCurl.match(
            /(https?:\/\/www\.superbuy\.com[^\s]*)/,
        );
        if (simpleMatch) url = simpleMatch[1];
    }

    const headerRegex = /-H\s+['"]([^:]+):\s+((?:[^'"\\]|\\.)*)['"]/g;
    let match;
    while ((match = headerRegex.exec(cleanCurl)) !== null) {
        const key = match[1].trim();
        const value = match[2].replace(/\\"/g, '"').replace(/\\'/g, "'").trim();
        if (key.toLowerCase() !== "accept-encoding") headers[key] = value;
    }

    const cookieRegex = /-b\s+['"]((?:[^'"\\]|\\.)*)['"]/;
    const cookieMatch = cleanCurl.match(cookieRegex);
    if (cookieMatch) {
        headers["Cookie"] = cookieMatch[1]
            .replace(/\\"/g, '"')
            .replace(/\\'/g, "'");
    }

    if (!headers["user-agent"] && !headers["User-Agent"]) {
        headers["User-Agent"] =
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
    }

    return { url, headers };
}

// --- HTML PARSER ---
function parseOrderHtml($, element) {
    const table = $(element).find("table.user_orderlist");
    if (table.length === 0) return null;

    const orderData = { items: [] };
    const headerText = table.find("thead").text();

    const orderNoMatch = headerText.match(/Order No[：:]\s*([A-Z0-9]+)/i);
    orderData.orderNo = orderNoMatch ? orderNoMatch[1] : "Unknown";
    orderData.orderId = $(element).attr("id")?.replace("div", "") || "";

    const dateMatch = headerText.match(/(\d{4}-\d{2}-\d{2})/);
    orderData.date = dateMatch ? dateMatch[1] : "-";

    // Extract Total en Postage
    const totalCell = table.find("td[rowspan]");
    const totalText = totalCell.text().replace(/\s+/g, " ");

    const postageMatch = totalText.match(
        /Postage Inclusive:\s*([A-Z€$£\s]+[\d\.]+)/i,
    );
    orderData.postageRaw = postageMatch ? postageMatch[1].trim() : null;

    const totalMatch = totalText.match(/Total Amount:([A-Z\s€$£]+[\d\.]+)/i);
    orderData.totalRaw = totalMatch ? totalMatch[1].trim() : "0.00";

    table.find("tbody tr").each((i, tr) => {
        const imgEl = $(tr).find(".js-item-img");
        if (imgEl.length > 0) {
            const item = {};

            let statusText = $(tr).find(".show_status").text().trim();
            const fullRowText = $(tr).text();

            if (statusText) item.status = statusText;
            else if (fullRowText.includes("Completed"))
                item.status = "Completed";
            else if (
                fullRowText.includes("withdrawn") ||
                fullRowText.includes("Order withdrawn")
            )
                item.status = "Withdrawn";
            else if (fullRowText.includes("Stored in Warehouse"))
                item.status = "Stored in Warehouse";
            else item.status = "Unknown";

            if (item.status.toLowerCase().includes("withdrawn")) {
                return;
            }

            item.title = $(tr).find(".js-item-title").text().trim();
            let link = $(tr).find(".js-item-title").attr("href");
            if (link && !link.startsWith("http"))
                link = "https://www.superbuy.com" + link;
            item.link = link;

            item.image = imgEl.attr("src");
            item.options = $(tr).find(".user_orderlist_txt").text().trim();
            item.price = $(tr)
                .find("td")
                .eq(1)
                .text()
                .trim()
                .replace(/\s+/g, " ");
            item.qty = $(tr).find(".qty-div").text().trim();

            // --- QC PHOTOS EXTRACTION ---
            item.qcPhotos = [];
            // Zoek in de pic-list sectie in dezelfde rij
            $(tr)
                .find(".pic-list li")
                .each((idx, li) => {
                    // Soms zit de HD link in de 'a.lookPic', soms moet je de src aanpassen
                    let picUrl = $(li).find("a.lookPic").attr("href");

                    // Als href leeg is, probeer de img src en strip resize params
                    if (!picUrl || picUrl.includes("javascript")) {
                        const imgSrc = $(li).find("img").attr("src");
                        if (imgSrc) picUrl = imgSrc.split("?")[0]; // Verwijder ?resize parameters
                    }

                    if (picUrl) {
                        if (picUrl.startsWith("//")) picUrl = "https:" + picUrl;
                        if (!picUrl.startsWith("http"))
                            picUrl = "https://" + picUrl.replace(/^\/+/, ""); // Clean start

                        if (!item.qcPhotos.includes(picUrl))
                            item.qcPhotos.push(picUrl);
                    }
                });

            orderData.items.push(item);
        }
    });

    return orderData;
}

// --- SERVER ROUTES ---
app.get("/", (req, res) => {
    res.send(htmlPage);
});

app.post("/api/orders", async (req, res) => {
    const { curl, pages = 3 } = req.body;
    if (!curl) return res.status(400).json({ error: "Geen cURL ingevuld" });

    try {
        const { url, headers } = parseCurl(curl);
        if (!headers["Cookie"])
            return res
                .status(400)
                .json({ error: "Geen cookie gevonden in cURL." });

        const baseUrl = url.split("?")[0];
        let allOrders = [];
        let failedLogin = false;

        console.log(`Start scannen van ${pages} pagina's...`);

        for (let i = 1; i <= pages; i++) {
            if (failedLogin) break;

            try {
                const pageUrl = `${baseUrl}?page=${i}`;
                const response = await axios.get(pageUrl, {
                    headers,
                    validateStatus: false,
                    maxRedirects: 5,
                });

                if (response.status !== 200) break;

                const html = response.data;
                const $ = cheerio.load(html);

                if (
                    $("title").text().includes("Login") ||
                    html.includes("loginUser")
                ) {
                    failedLogin = true;
                    if (i === 1)
                        throw new Error(
                            "Sessie verlopen. Haal nieuwe cURL op.",
                        );
                    break;
                }

                const orderDivs = $('div[id^="div"]');
                if (orderDivs.length === 0) break;

                orderDivs.each((idx, el) => {
                    const order = parseOrderHtml($, el);
                    if (order && order.items.length > 0) {
                        allOrders.push(order);
                    }
                });

                await new Promise((r) => setTimeout(r, 800));
            } catch (err) {
                if (err.message.includes("Sessie verlopen")) throw err;
            }
        }

        res.json({ success: true, orders: allOrders });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// --- FRONTEND ---
const htmlPage = `
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superbuy Viewer Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-stored { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .status-completed { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pending { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }

        /* Modal Styles */
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="text-slate-800">

    <div class="max-w-7xl mx-auto p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-layer-group mr-2 text-indigo-600"></i> Superbuy Viewer <span class="ml-2 text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">QC EDITIE</span>
            </h1>
            <button onclick="toggleInput()" class="text-sm text-slate-500 hover:text-indigo-600 underline">
                Instellingen tonen/verbergen
            </button>
        </div>

        <!-- Input Box -->
        <div id="inputCard" class="card p-6 mb-6">
            <div class="flex justify-between items-center mb-2">
                <label class="font-semibold text-sm text-slate-700">cURL Command</label>
                <button onclick="clearCurl()" class="text-xs text-red-500 hover:underline">Wissen</button>
            </div>

            <textarea id="curlInput" class="w-full h-24 p-3 border border-slate-300 rounded-lg text-xs font-mono bg-slate-50 focus:ring-2 focus:ring-indigo-500 outline-none transition mb-4" placeholder="curl 'https://www.superbuy.com/order' ..."></textarea>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
                    <span class="text-sm font-medium text-slate-600">Pagina's:</span>
                    <input type="number" id="pagesInput" value="3" min="1" max="20" class="w-12 bg-transparent text-center font-bold outline-none">
                </div>

                <button onclick="getOrders()" id="scanBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold text-sm transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-sync"></i> Ophalen
                </button>
                <div id="msgBox" class="text-sm font-medium text-slate-500 hidden"></div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div id="statsBar" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card p-4 flex items-center justify-between bg-white">
                <div>
                    <div class="text-xs text-slate-500 uppercase font-bold">Aantal Items</div>
                    <div class="text-2xl font-bold text-slate-800" id="countDisplay">0</div>
                </div>
                <div class="bg-blue-100 p-3 rounded-full text-blue-600"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <div class="card p-4 flex items-center justify-between bg-white">
                <div>
                    <div class="text-xs text-slate-500 uppercase font-bold">Totaal (incl. verzendkosten)</div>
                    <div class="text-2xl font-bold text-emerald-600" id="totalDisplay">€ 0.00</div>
                </div>
                <div class="bg-emerald-100 p-3 rounded-full text-emerald-600"><i class="fa-solid fa-wallet"></i></div>
            </div>
            <div class="card p-4 flex items-center justify-center bg-white cursor-pointer hover:bg-slate-50 transition" onclick="exportToCsv()">
                <div class="flex flex-col items-center">
                    <i class="fa-solid fa-file-csv text-2xl text-green-600 mb-1"></i>
                    <span class="text-sm font-bold text-slate-700">Download CSV</span>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div id="resultSection" class="hidden">
            <div class="mb-4 relative">
                <i class="fa-solid fa-search absolute left-3 top-3 text-slate-400"></i>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Zoek op product, ID of status..." class="w-full pl-10 pr-4 py-2 card text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider border-b border-slate-200">
                                <th class="p-4 w-24">Afbeelding</th>
                                <th class="p-4">Product Details</th>
                                <th class="p-4">Variant & Aantal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Stukprijs</th>
                                <th class="p-4 text-right bg-slate-100 border-l border-slate-200">Berekend Totaal</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100 text-sm">
                            <!-- JS vult dit -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- QC Modal -->
    <div id="qcModal" class="modal fixed w-full h-full top-0 left-0 flex items-center justify-center hidden z-50">
        <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-75" onclick="closeQcModal()"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-5xl mx-auto rounded shadow-lg z-50 overflow-y-auto max-h-[90vh]">
            <div class="modal-content py-4 text-left px-6">
                <!-- Title -->
                <div class="flex justify-between items-center pb-3 border-b">
                    <p class="text-2xl font-bold text-gray-800">QC Inspectie Foto's</p>
                    <div class="modal-close cursor-pointer z-50" onclick="closeQcModal()">
                        <i class="fa-solid fa-times text-2xl text-gray-500 hover:text-black"></i>
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

        window.onload = () => {
            const saved = localStorage.getItem('sb_curl_win');
            if(saved) document.getElementById('curlInput').value = saved;
        };

        function toggleInput() {
            const el = document.getElementById('inputCard');
            el.classList.toggle('hidden');
        }

        function clearCurl() {
            document.getElementById('curlInput').value = '';
            localStorage.removeItem('sb_curl_win');
        }

        async function getOrders() {
            const curl = document.getElementById('curlInput').value;
            const pages = document.getElementById('pagesInput').value;
            const btn = document.getElementById('scanBtn');
            const msg = document.getElementById('msgBox');

            if(!curl.trim()) return alert("Plak eerst je cURL!");
            localStorage.setItem('sb_curl_win', curl);

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bezig...';
            msg.innerText = "Data ophalen...";
            msg.classList.remove('hidden');

            try {
                const res = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ curl, pages: parseInt(pages) })
                });
                const data = await res.json();

                if(data.error) {
                    msg.innerText = "Fout: " + data.error;
                    msg.className = "text-red-600 font-bold ml-4";
                } else {
                    currentData = data.orders;
                    renderTable(data.orders);
                    msg.innerText = "Klaar!";
                    msg.className = "text-green-600 font-bold ml-4";
                }
            } catch (err) {
                alert('Netwerkfout.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-sync"></i> Ophalen';
            }
        }

        function renderTable(orders) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            let itemCount = 0;
            let grandTotal = 0;
            let currency = "€";

            if(!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-slate-500 italic">Geen orders gevonden.</td></tr>';
                document.getElementById('resultSection').classList.remove('hidden');
                return;
            }

            orders.forEach((order, orderIdx) => {
                let orderSum = 0;
                let orderCur = "€";

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
                        if(orderCur === "€" && pMatch[1]) orderCur = pMatch[1].trim();
                    }
                }
                orderSum += postageVal;
                grandTotal += orderSum;
                if(orderCur.includes('$')) currency = "$";

                const formattedOrderTotal = orderCur + " " + orderSum.toFixed(2);
                const postageDisplay = postageVal > 0 ? \`<div class="text-[10px] text-slate-400 font-normal">+ \${order.postageRaw} verzending</div>\` : '';

                order.items.forEach((item, idx) => {
                    itemCount++;
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 group transition';

                    let statusClass = 'bg-slate-100 text-slate-600';
                    const s = (item.status || '').toLowerCase();
                    if(s.includes('stored')) statusClass = 'status-stored';
                    else if(s.includes('completed')) statusClass = 'status-completed';
                    else if(s.includes('shipped')) statusClass = 'status-pending';

                    const orderLink = order.orderId ?
                        \`<a href="https://www.superbuy.com/en/page/account/order/detail?orderId=\${order.orderId}" target="_blank" class="text-xs text-indigo-500 hover:underline block mt-1">\${order.orderNo}</a>\` :
                        \`<span class="text-xs text-slate-400 block mt-1">\${order.orderNo}</span>\`;

                    // QC Button Logic
                    let qcBtn = '';
                    if (item.qcPhotos && item.qcPhotos.length > 0) {
                        qcBtn = \`<button onclick="showQc(\${orderIdx}, \${idx})" class="mt-2 text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded border border-purple-200 hover:bg-purple-200 transition flex items-center gap-1 font-semibold">
                            <i class="fa-solid fa-camera"></i> QC Foto's (\${item.qcPhotos.length})
                        </button>\`;
                    }

                    tr.innerHTML = \`
                        <td class="p-4 align-top">
                            <div class="w-16 h-16 bg-white border border-slate-200 rounded-md overflow-hidden">
                                <img src="\${item.image}" class="w-full h-full object-cover" referrerpolicy="no-referrer" onerror="this.src='https://placehold.co/100?text=No+Img'">
                            </div>
                        </td>
                        <td class="p-4 align-top max-w-xs">
                            <a href="\${item.link}" target="_blank" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 line-clamp-2" title="\${item.title}">
                                \${item.title}
                            </a>
                            \${idx === 0 ? orderLink : ''}
                            \${idx === 0 ? \`<div class="text-[10px] text-slate-400">\${order.date}</div>\` : ''}
                            \${qcBtn}
                        </td>
                        <td class="p-4 align-top text-xs">
                            <div class="bg-white border border-slate-200 rounded p-1.5 inline-block text-slate-600 mb-1 max-w-[200px] truncate">
                                \${item.options}
                            </div>
                            <div class="font-bold text-slate-700">x \${item.qty}</div>
                        </td>
                        <td class="p-4 align-top whitespace-nowrap">
                            <span class="status-badge \${statusClass}">\${item.status}</span>
                        </td>
                        <td class="p-4 align-top text-right font-mono text-slate-600">\${item.price}</td>
                        \${idx === 0 ? \`<td class="p-4 align-top text-right font-bold text-slate-800 bg-slate-50 border-l border-slate-200" rowspan="\${order.items.length}">
                            \${formattedOrderTotal}
                            \${postageDisplay}
                        </td>\` : ''}
                    \`;
                    tbody.appendChild(tr);
                });

                const divider = document.createElement('tr');
                divider.innerHTML = '<td colspan="6" class="h-1 bg-slate-100 border-y border-slate-200"></td>';
                tbody.appendChild(divider);
            });

            document.getElementById('countDisplay').innerText = itemCount;
            document.getElementById('totalDisplay').innerText = currency + " " + grandTotal.toFixed(2);

            document.getElementById('resultSection').classList.remove('hidden');
            document.getElementById('statsBar').classList.remove('hidden');
        }

        function showQc(orderIdx, itemIdx) {
            const item = currentData[orderIdx].items[itemIdx];
            const modalBody = document.getElementById('qcModalBody');
            const modal = document.getElementById('qcModal');

            modalBody.innerHTML = '';

            item.qcPhotos.forEach(photoUrl => {
                const div = document.createElement('div');
                div.className = 'border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition';
                div.innerHTML = \`
                    <a href="\${photoUrl}" target="_blank">
                        <img src="\${photoUrl}" class="w-full h-48 object-cover cursor-pointer hover:opacity-90" referrerpolicy="no-referrer">
                    </a>
                    <div class="p-2 bg-gray-50 text-center">
                        <a href="\${photoUrl}" target="_blank" class="text-xs text-indigo-600 hover:underline">Full Size <i class="fa-solid fa-external-link-alt ml-1"></i></a>
                    </div>
                \`;
                modalBody.appendChild(div);
            });

            modal.classList.remove('hidden');
            document.body.classList.add('modal-active');
        }

        function closeQcModal() {
            const modal = document.getElementById('qcModal');
            modal.classList.add('hidden');
            document.body.classList.remove('modal-active');
        }

        function filterTable() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            rows.forEach(row => {
                if(row.querySelector('td[colspan]')) return;
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        }

        function exportToCsv() {
            if(!currentData.length) return alert("Geen data om te exporteren.");

            let csv = "OrderNr,Datum,Product,Variant,Aantal,Prijs,Status,Verzendkosten,OrderTotaal,QCLinks\\n";

            currentData.forEach(order => {
                let orderSum = 0;
                let orderCur = "";
                let postageVal = 0;

                order.items.forEach(item => {
                    const match = item.price.match(/([^\\d]+)([\\d\\.]+)/);
                    if(match) {
                        orderCur = match[1].trim();
                        orderSum += parseFloat(match[2]) * parseInt(item.qty || 1);
                    }
                });

                if (order.postageRaw) {
                    const pMatch = order.postageRaw.match(/([^\\d]+)([\\d\\.]+)/);
                    if (pMatch) postageVal = parseFloat(pMatch[2]);
                }
                orderSum += postageVal;

                const calcTotal = orderCur + " " + orderSum.toFixed(2);
                const postageText = order.postageRaw || "0.00";

                order.items.forEach(item => {
                    const cleanTitle = '"' + item.title.replace(/"/g, '""') + '"';
                    const cleanOpt = '"' + item.options.replace(/"/g, '""') + '"';
                    const qcLinks = item.qcPhotos ? '"' + item.qcPhotos.join(';') + '"' : '';

                    csv += \`\${order.orderNo},\${order.date},\${cleanTitle},\${cleanOpt},\${item.qty},\${item.price},\${item.status},\${postageText},\${calcTotal},\${qcLinks}\\n\`;
                });
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "superbuy_orders_complete.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>
`;

app.listen(PORT, async () => {
    const url = "http://localhost:" + PORT;
    console.log("✅ Server gestart!");
    console.log("👉 Open deze link in je browser: " + url);
    try {
        const open = await import("open");
        if (open.default) await open.default(url);
        else await open(url);
    } catch (e) {
        console.log(
            "⚠️  Kan browser niet automatisch openen. Klik op de link.",
        );
    }
});
