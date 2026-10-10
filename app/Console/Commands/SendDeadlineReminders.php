<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\DeadlineReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDeadlineReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Kirim reminder untuk deadline yang mendekat';

    public function handle()
    {
        // Cari semua tugas yang deadlinenya dalam 24 jam ke depan
        $tasks = Task::where('status', '!=', 'done')
            ->whereBetween('deadline', [now(), now()->addDays(7)])
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($tasks as $task) {
            $settings = json_decode($task->user->settings ?? '{}', true);
            $days = (int) ceil(now()->diffInHours($task->deadline) / 24);
            $preference = [1 => 'notif_deadline_1day', 3 => 'notif_deadline_3days', 7 => 'notif_deadline_1week'][$days] ?? null;
            if (! $preference || ! ($settings['notifications'][$preference] ?? true)) {
                continue;
            }
            $key = 'deadline-reminder:'.$task->id.':'.$task->deadline->timestamp.':'.$days;
            if (Cache::has($key)) {
                continue;
            }
            $task->user->notify(new DeadlineReminder($task));
            Cache::put($key, true, $task->deadline);
            $sent++;
            $this->info("Reminder dikirim untuk: {$task->title}");
        }

        $this->info("Total: {$sent} reminder dikirim.");
    }
}
