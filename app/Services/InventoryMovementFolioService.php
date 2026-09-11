<?php

namespace App\Services;

use App\Models\InventoryMovement;

class InventoryMovementFolioService
{
    /**
     * Genera el siguiente folio secuencial único para el tipo de movimiento indicado.
     * Ejemplos: ENT-2026-000001, SAL-2026-000001, AJU-2026-000001, TRA-2026-000001
     */
    public function generate(string $type): string
    {
        $prefix = match ($type) {
            'entry' => 'ENT',
            'exit' => 'SAL',
            'adjustment' => 'AJU',
            'transfer' => 'TRA',
            default => strtoupper(substr($type, 0, 3)),
        };

        $year = date('Y');
        $pattern = "{$prefix}-{$year}-";

        // Consultar el último movimiento registrado con este prefijo y año
        $lastMovement = InventoryMovement::where('folio', 'like', "{$pattern}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        if ($lastMovement && preg_match('/-(\d+)$/', $lastMovement->folio, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        $formattedNumber = str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        return "{$pattern}{$formattedNumber}";
    }
}
