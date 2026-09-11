<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'internal_code',
        'barcode',
        'name',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'supplier_id',
        'cost_price',
        'selling_price',
        'min_stock',
        'max_stock',
        'reorder_point',
        'default_location',
        'image_path',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'max_stock' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Determine if product has any movements recorded.
     */
    public function hasMovements(): bool
    {
        return $this->movements()->exists();
    }

    /**
     * Compute current overall stock across all warehouses.
     */
    public function getTotalStockAttribute(): float
    {
        return (float) ($this->inventories_sum_quantity ?? $this->inventories()->sum('quantity'));
    }

    /**
     * Calcula el estado de existencias del producto.
     * in_stock | low_stock | out_of_stock | inactive
     */
    public function getStockStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $stock = $this->total_stock;

        if ($stock <= 0) {
            return 'out_of_stock';
        }

        $threshold = max((float) $this->min_stock, (float) $this->reorder_point);

        if ($threshold > 0 && $stock <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Calcula el porcentaje de margen de ganancia actual basado en el costo y precio de venta.
     */
    public function getProfitMarginAttribute(): float
    {
        $cost = (float) $this->cost_price;
        $selling = (float) $this->selling_price;

        if ($cost <= 0) {
            return 0.0;
        }

        return round((($selling - $cost) / $cost) * 100, 2);
    }
}
