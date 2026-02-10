<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'size',
        'category',
        'default_buy_price',
        'default_sell_price',
        'default_qc_link',
        'image_url' // We slaan base64 of path op
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}