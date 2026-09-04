<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'slug',
        'description_en',
        'description_de',
        'description_fr',
        'description_it',
        // Manufacturer fields
        'mfr',
        'mfr_address',
        'mfr_city',
        'mfr_country',
        'mfr_postcode',
        'mfr_web',
        'mfr_email',
        'mfr_tel',
        'mfr_fax',
        // Importer fields
        'imp',
        'imp_address',
        'imp_city',
        'imp_country',
        'imp_postcode',
        'imp_web',
        'imp_email',
        'imp_tel',
        'imp_fax',
        // Office fields
        'off',
        'off_address',
        'off_city',
        'off_country',
        'off_postcode',
        'off_web',
        'off_email',
        'off_tel',
        'off_fax',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name when creating/updating
        static::saving(function ($brand) {
            if (empty($brand->slug) || $brand->isDirty('name')) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    /**
     * Get the products for the brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the articles associated with the brand.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    /**
     * Get the authorized countries for this brand.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    /**
     * Get unique parent categories that have products for this brand.
     * Only returns top-level categories (parent_id is null).
     */
    public function categories()
    {
        return Category::whereNull('parent_id')
            ->where(function ($query) {
                $query->whereHas('products', function ($q) {
                    $q->where('brand_id', $this->id)
                      ->where('published', true);
                })
                ->orWhereHas('children.products', function ($q) {
                    $q->where('brand_id', $this->id)
                      ->where('published', true);
                });
            })
            ->orderBy('name_de')
            ->get();
    }
}
