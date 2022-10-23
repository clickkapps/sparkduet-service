<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAuthorization extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'code',
        'status' // opened / closed
    ];
}
