<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilterCountry extends Model
{
    use HasFactory;
    protected $table = 'filter_countries';

    protected $fillable = [
        'filter_id',
        'country_id'
    ];

}
