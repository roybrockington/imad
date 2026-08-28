<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryDiscount extends Model
{
    protected $guarded = [];

    use HasFactory;

    /**
     * Get the account that owns the category discount.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the brand that this category discount applies to.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category that this discount applies to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
