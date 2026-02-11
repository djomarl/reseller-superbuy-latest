<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SuperbuyService
{
    /**
     * Parse raw cURL string to extract URL, Headers and Cookies
     */
    public function parseCurl(string $curlString): array
    {
        $headers = [];
        $url = "https://www.superbuy.com/order";

        // Clean up newlines and carets
        $cleanCurl = str_replace(
            ["\\\r\n", "\\\n", "^\r\n", "^\n", "^"],
            [" ", " ", " ", " ", ""],
            $curlString
        );
        $cleanCurl = str_replace(["\r", "\n"], " ", $cleanCurl);

        // Extract URL
        if (preg_match('/[\'"](https?:\/\/[^\'"]+)[\'"]/', $cleanCurl, $matches))
        {
            $url = $matches[1];
        }
        elseif (preg_match('/(https?:\/\/www\.superbuy\.com[^\s]*)/', $cleanCurl, $matches))
        {
            $url = $matches[1];
        }

        // Extract Headers (-H "Key: Value")
        if (preg_match_all('/-H\s+[\'"]([^:]+):\s+((?:[^\'"\\\\]|\\\\.)*)[\'"]/', $cleanCurl, $matches, PREG_SET_ORDER))
        {
            foreach ($matches as $match)
            {
                $key = trim($match[1]);
                $value = trim(str_replace(['\\"', "\\'"], ['"', "'"], $match[2]));
                if (strtolower($key) !== 'accept-encoding')
                {
                    $headers[$key] = $value;
                }
            }
        }

        // Extract Cookies (-b "cookie_string")
        if (preg_match('/-b\s+[\'"]((?:[^\'"\\\\]|\\\\.)*)[\'"]/', $cleanCurl, $matches))
        {
            $headers['Cookie'] = str_replace(['\\"', "\\'"], ['"', "'"], $matches[1]);
        }

        // Default User-Agent if not present
        $hasUserAgent = false;
        foreach ($headers as $k => $v)
        {
            if (strtolower($k) === 'user-agent')
            {
                $hasUserAgent = true;
                break;
            }
        }
        if (!$hasUserAgent)
        {
            $headers['User-Agent'] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
        }

        return ['url' => $url, 'headers' => $headers];
    }

    /**
     * Fetch orders using the cURL string (for auth)
     */
    public function getOrdersFromCurl(string $curlString, int $pages = 3): array
    {
        $curlData = $this->parseCurl($curlString);
        $headers = $curlData['headers'];
        $baseUrl = explode('?', $curlData['url'])[0];

        if (empty($headers['Cookie']))
        {
            throw new \Exception("Geen cookie gevonden in cURL.");
        }

        $allOrders = [];
        $failedLogin = false;

        for ($i = 1; $i <= $pages; $i++)
        {
            if ($failedLogin) break;

            try
            {
                $pageUrl = $baseUrl . "?page=" . $i;

                $response = Http::withHeaders($headers)
                    ->withoutRedirecting()
                    ->get($pageUrl);

                if ($response->status() !== 200)
                {
                    if ($response->status() >= 300 && $response->status() < 400)
                    {
                        $failedLogin = true;
                        break;
                    }
                    break;
                }

                $html = $response->body();

                if (Str::contains($html, '<title>Login') || Str::contains($html, 'loginUser'))
                {
                    $failedLogin = true;
                    if ($i === 1)
                    {
                        throw new \Exception("Sessie verlopen. Haal nieuwe cURL op.");
                    }
                    break;
                }

                $crawler = new Crawler($html);
                $orderDivs = $crawler->filter('div[id^="div"]');

                if ($orderDivs->count() === 0)
                {
                    break;
                }

                $orderDivs->each(function (Crawler $node) use (&$allOrders)
                {
                    $order = $this->parseOrderHtml($node);
                    if ($order && count($order['items']) > 0)
                    {
                        $allOrders[] = $order;
                    }
                });

                usleep(800000);
            }
            catch (\Exception $e)
            {
                if (Str::contains($e->getMessage(), "Sessie verlopen"))
                {
                    throw $e;
                }
            }
        }

        return $allOrders;
    }

    /**
     * Parse a single order element (div)
     */
    private function parseOrderHtml(Crawler $node): ?array
    {
        $table = $node->filter('table.user_orderlist');
        if ($table->count() === 0) return null;

        $orderData = ['items' => []];
        $headerText = $table->filter('thead')->text('');

        // Order No
        if (preg_match('/Order No[:：]\s*([A-Z0-9]+)/i', $headerText, $matches))
        {
            $orderData['orderNo'] = $matches[1];
        }
        else
        {
            $orderData['orderNo'] = 'Unknown';
        }

        $orderData['orderId'] = str_replace('div', '', $node->attr('id') ?? '');

        // Date
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $headerText, $matches))
        {
            $orderData['date'] = $matches[1];
        }
        else
        {
            $orderData['date'] = '-';
        }

        // Totals
        $totalCell = $table->filter('td[rowspan]');
        $totalText = '';
        if ($totalCell->count() > 0)
        {
            $totalText = preg_replace('/\s+/', ' ', $totalCell->text(''));
        }

        // Postage
        if (preg_match('/Postage Inclusive:\s*([A-Z€$£\s]+[\d\.]+)/i', $totalText, $matches))
        {
            $orderData['postageRaw'] = trim($matches[1]);
        }
        else
        {
            $orderData['postageRaw'] = null;
        }

        // Total Amount
        if (preg_match('/Total Amount:([A-Z\s€$£]+[\d\.]+)/i', $totalText, $matches))
        {
            $orderData['totalRaw'] = trim($matches[1]);
        }
        else
        {
            $orderData['totalRaw'] = '0.00';
        }

        // Items
        $table->filter('tbody tr')->each(function (Crawler $tr) use (&$orderData, $table)
        {
            $imgEl = $tr->filter('.js-item-img');

            if ($imgEl->count() > 0)
            {
                $item = [];

                // Status
                $statusText = '';
                $showStatus = $tr->filter('.show_status');
                if ($showStatus->count() > 0)
                {
                    $statusText = trim($showStatus->text());
                }

                $fullRowText = $tr->text();

                if ($statusText)
                {
                    $item['status'] = $statusText;
                }
                elseif (Str::contains($fullRowText, 'Completed'))
                {
                    $item['status'] = 'Completed';
                }
                elseif (Str::contains($fullRowText, 'withdrawn') || Str::contains($fullRowText, 'Order withdrawn'))
                {
                    $item['status'] = 'Withdrawn';
                }
                elseif (Str::contains($fullRowText, 'Stored in Warehouse'))
                {
                    $item['status'] = 'Stored in Warehouse';
                }
                else
                {
                    $item['status'] = 'Unknown';
                }

                if (Str::contains(strtolower($item['status']), 'withdrawn'))
                {
                    return;
                }

                // Title
                $titleEl = $tr->filter('.js-item-title');
                $item['title'] = $titleEl->count() > 0 ? trim($titleEl->text()) : '';

                // Link
                $link = $titleEl->count() > 0 ? $titleEl->attr('href') : '';
                if ($link && !Str::startsWith($link, 'http'))
                {
                    $link = "https://www.superbuy.com" . $link;
                }
                $item['link'] = $link;

                // Image
                $item['image'] = $imgEl->attr('src');

                // Options
                $optionsEl = $tr->filter('.user_orderlist_txt');
                $item['options'] = $optionsEl->count() > 0 ? trim($optionsEl->text()) : '';

                // Price (2nd column usually)
                $tds = $tr->filter('td');
                $item['price'] = $tds->count() > 1 ? trim(preg_replace('/\s+/', ' ', $tds->eq(1)->text())) : '';

                // Qty
                $qtyDiv = $tr->filter('.qty-div');
                $item['qty'] = $qtyDiv->count() > 0 ? trim($qtyDiv->text()) : '1';

                // QC Photos
                $item['qcPhotos'] = [];
                $tr->filter('.pic-list li')->each(function (Crawler $li) use (&$item)
                {
                    $lookPic = $li->filter('a.lookPic');
                    $picUrl = $lookPic->count() > 0 ? $lookPic->attr('href') : null;

                    if (!$picUrl || Str::contains($picUrl, 'javascript'))
                    {
                        $img = $li->filter('img');
                        if ($img->count() > 0)
                        {
                            $src = $img->attr('src');
                            if ($src)
                            {
                                $picUrl = explode('?', $src)[0];
                            }
                        }
                    }

                    if ($picUrl)
                    {
                        if (Str::startsWith($picUrl, '//'))
                        {
                            $picUrl = "https:" . $picUrl;
                        }
                        if (!Str::startsWith($picUrl, 'http'))
                        {
                            $picUrl = "https://" . preg_replace('/^\/+/', '', $picUrl);
                        }

                        if (!in_array($picUrl, $item['qcPhotos']))
                        {
                            $item['qcPhotos'][] = $picUrl;
                        }
                    }
                });

                $orderData['items'][] = $item;
            }
        });

        return $orderData;
    }

    /**
     * Import a single item into the database
     */
    public function importItem(User $user, array $itemData, string $orderNo): Item
    {
        preg_match('/([\d\.,]+)/', $itemData['price'], $matches);
        $price = isset($matches[1]) ? floatval(str_replace(',', '.', $matches[1])) : 0.00;

        $status = 'todo';
        $itemStatusLower = strtolower($itemData['status']);
        
        // Haal het unieke ID op (DI...)
        $subId = $itemData['subId'] ?? null;

        // Bepaal waarop we zoeken om duplicaten te voorkomen
        $matchAttributes = [
            'user_id' => $user->id,
            'order_nmr' => $orderNo,
        ];

        // BELANGRIJK: Als we een subId hebben, gebruiken we die voor strikte uniekheid.
        // Dit fixt de bug dat items met hetzelfde ordernummer als duplicaat worden gezien.
        if ($subId) {
            $matchAttributes['item_no'] = $subId;
        } else {
            // Fallback voor oude imports: uniek op basis van naam en maat
            $matchAttributes['name'] = $itemData['title'];
            $matchAttributes['size'] = substr($itemData['options'], 0, 190);
        }

        return Item::firstOrCreate(
            $matchAttributes, // Zoek op deze velden
            [
                // Vul deze velden in als het item nieuw is
                'item_no' => $subId ?? '-', 
                'name' => $itemData['title'],
                'size' => substr($itemData['options'], 0, 190),
                'buy_price' => $price,
                'status' => $status,
                'image_url' => $itemData['image'],
                'qc_photos' => $itemData['qcPhotos'] ?? [],
                'source_link' => $itemData['link'],
                'notes' => $itemData['options'],
            ]
        );
    }
}