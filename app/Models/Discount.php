<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = [];
    use HasFactory;

    /**
     * Get the account that owns the discount.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the brand that this discount applies to.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
