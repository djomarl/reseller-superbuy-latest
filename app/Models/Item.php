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
        'sold_date', 'status', 'sort_order', 'image_url', 'qc_photos', 'source_link', 'notes'
    ];

    protected $casts = [
        'is_sold' => 'boolean',
        'sold_date' => 'date',
        'buy_price' => 'decimal:2',
        'qc_photos' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            // Default sort_order to id so new items appear at top (sorted DESC)
            if ($item->sort_order === 0 || $item->sort_order === null) {
                $item->sort_order = $item->id;
                $item->saveQuietly();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_sold', false)->where('status', '!=', 'personal');
    }

    public function scopePersonal($query)
    {
        return $query->where('status', 'personal');
    }

    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }
}