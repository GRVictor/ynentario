<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Registra una actividad en el historial del sistema.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}
