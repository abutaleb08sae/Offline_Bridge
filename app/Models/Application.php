<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'required_port',
        'cached_login_support',
        'offline_status',
        'notes',
    ];

    protected $casts = [
        'cached_login_support' => 'boolean',
    ];
}

