<?php

namespace App\Models;

class Category
{
    public static function all()
    {
        return [
            'Bag',
            'Shoes',
            'T-Shirt',
            'Sweater',
            'Hoodie',
            'Jacket',
            'Hat',
            'Pants',
            'Accessories',
            'Other'
        ];
    }
}
