<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Term;

class Account extends Model
{
    protected $guarded = [];
    use HasFactory;

    /**
     * Get the region that the account belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the discounts for the account.
     */
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get the category discounts for the account.
     */
    public function categoryDiscounts()
    {
        return $this->hasMany(CategoryDiscount::class);
    }

    /**
     * Get the country that the account belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the currency that the account uses.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the payment term for the account.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
