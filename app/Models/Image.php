<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['image_url', 'public_id', 'order'];

    /**
     * Parent model of the image
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
