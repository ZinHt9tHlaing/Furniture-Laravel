<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'code',
        'total_price',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // many to many relationship with products through pivot table product_on_orders
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_on_orders')
            ->using(ProductOnOrder::class)
            ->withPivot('id', 'quantity', 'price')
            ->withTimestamps();
    }
}
