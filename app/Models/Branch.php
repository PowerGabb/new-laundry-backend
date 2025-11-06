<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'detail_address',
        'latitude',
        'longitude',
        'phone',
        'working_hours',
        'price_per_kg',
        'image_url',
        'pickup_gojek',
        'pickup_grab',
        'pickup_free',
        'pickup_free_schedule',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'working_hours' => 'integer',
            'price_per_kg' => 'integer',
            'pickup_gojek' => 'boolean',
            'pickup_grab' => 'boolean',
            'pickup_free' => 'boolean',
            'pickup_free_schedule' => 'array',
        ];
    }

    /**
     * Get the user that owns the branch.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders for the branch.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the laundry categories for the branch.
     */
    public function laundryCategories(): HasMany
    {
        return $this->hasMany(LaundryCategory::class);
    }

    /**
     * Get only active categories with active items.
     */
    public function activeCategoriesWithItems(): HasMany
    {
        return $this->laundryCategories()
            ->where('is_active', true)
            ->with('activeItems')
            ->orderBy('sort_order');
    }
}
