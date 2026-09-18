<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductOnOrder extends Pivot
{
    /** @use HasFactory<\Database\Factories\ProductOnOrderFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'product_id',
        'order_id',
        'quantity',
        'price',
    ];

    public $incrementing = false;

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];
}
