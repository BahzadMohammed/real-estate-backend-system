<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class addresses extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'location' => 'array'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
