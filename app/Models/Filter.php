<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'min_age',
        'max_age',
        'gender',
        'countries_option'
    ];

    public function countries(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Country::class, FilterCountry::class);
    }
}
