<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the brands that are authorized for this country.
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }
}
