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

// Start alleen de server, geen browser openen
app.listen(PORT, () => {
    console.log('✅ Superbuy API server gestart op http://localhost:' + PORT);
});
