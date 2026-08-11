<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyClosing extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'reference_year',
        'reference_month',
        'is_closed',
        'snapshot',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
    ];

    protected $casts = [
        'reference_year' => 'integer',
        'reference_month' => 'integer',
        'is_closed' => 'boolean',
        'snapshot' => 'array',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function referenceLabel(): string
    {
        return sprintf('%02d/%04d', $this->reference_month, $this->reference_year);
    }
}
