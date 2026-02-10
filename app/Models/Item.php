<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasFactory;

    // Velden die we mogen invullen
    protected $fillable = [
        'user_id', 'parcel_id', 'item_no', 'order_nmr', 'name', 'brand', 'size',
        'category', 'buy_price', 'sell_price', 'is_sold',
        'sold_date', 'status', 'image_url', 'qc_link', 'source_link', 'notes'
    ];

    protected $casts = [
        'is_sold' => 'boolean',
        'sold_date' => 'date',
        'buy_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}