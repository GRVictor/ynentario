<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'type',
        'product_id',
        'warehouse_id',
        'destination_warehouse_id',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'unit_cost',
        'user_id',
        'reason',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'previous_quantity' => 'decimal:2',
            'new_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
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

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable label in Spanish.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'entry' => 'Entrada',
            'exit' => 'Salida',
            'adjustment' => 'Ajuste',
            'transfer' => 'Transferencia',
            default => ucfirst($this->type),
        };
    }
}
