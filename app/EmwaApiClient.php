<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmwaApiClient extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'email',
        'api_key',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function generateApiKey(): string
    {
        return Str::random(48);
    }
}
