<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Auto sync Classroom & Moodle setiap 30 menit ──────────────────
Schedule::command('tasks:sync-all')
    ->everyThirtyMinutes()
    ->withoutOverlapping()   // tidak jalan dobel kalau sync sebelumnya belum selesai
    ->runInBackground();     // tidak block proses lain

// ── Reminder deadline (sudah ada sebelumnya) ──────────────────────
Schedule::command('reminders:send')->hourly();