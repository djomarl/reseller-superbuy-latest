<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcel extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'parcel_no', 'tracking_code', 'description', 'shipping_cost', 'status', 'last_checked_at'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}