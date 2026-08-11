<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $event,
        Model $subject,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
