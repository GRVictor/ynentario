<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Compute stock status for this specific warehouse inventory.
     */
    public function getStockStatusAttribute(): string
    {
        $qty = (float) $this->quantity;

        if ($qty <= 0) {
            return 'out_of_stock';
        }

        $min = (float) ($this->product->min_stock ?? 0);
        $reorder = (float) ($this->product->reorder_point ?? 0);
        $threshold = max($min, $reorder);

        if ($threshold > 0 && $qty <= $threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
