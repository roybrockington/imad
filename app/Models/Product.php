<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'available_for_sale' => 'boolean',
        'freight' => 'boolean',
        'embargo' => 'boolean',
        'eta' => 'date',
    ];

    /**
     * Scope to hide embargoed products from guests.
     * Authenticated users can see all published products including embargoed ones.
     */
    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            $query->where('embargo', false);
        }
        return $query;
    }

    /**
     * Get the product description for this product.
     */
    public function description()
    {
        return $this->hasOne(ProductDescription::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the xware (B-stock) entries for this product.
     */
    public function xware()
    {
        return $this->hasMany(Xware::class);
    }

    /**
     * Get the manufacturer supplier for this product.
     */
    public function manufacturerSupplier()
    {
        return $this->belongsTo(Supplier::class, 'manufacturer_supplier_id');
    }

    /**
     * Get the office (responsible entity) supplier for this product.
     */
    public function officeSupplier()
    {
        return $this->belongsTo(Supplier::class, 'office_supplier_id');
    }

    /**
     * Get the importer supplier for this product.
     */
    public function importerSupplier()
    {
        return $this->belongsTo(Supplier::class, 'importer_supplier_id');
    }
}
