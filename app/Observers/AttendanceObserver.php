<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Attendance;
use App\Services\Attendance\AttendanceService;

class AttendanceObserver
{
    public function __construct(private readonly AttendanceService $service) {}

    public function created(Attendance $attendance): void
    {
        $this->service->syncDailyFee($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        if ($attendance->wasChanged('attended')) {
            $this->service->syncDailyFee($attendance);
        }
    }
}
