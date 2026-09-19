<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTP extends Model
{
    /** @use HasFactory<\Database\Factories\OTPFactory> */
    use HasFactory, HasUlids;

    protected $table = 'otps';

    protected $fillable = [
        'phone',
        'otp',
        'remember_token',
        'verify_token',
        'count',
        'error',
    ];
}
