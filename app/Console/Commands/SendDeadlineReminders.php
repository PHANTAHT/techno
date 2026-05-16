<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\DeadlineReminder;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Kirim reminder untuk deadline yang mendekat';

    public function handle()
    {
        // Cari semua tugas yang deadlinenya dalam 24 jam ke depan
        $tasks = Task::where('status', '!=', 'done')
                     ->whereBetween('deadline', [now(), now()->addHours(24)])
                     ->with('user')
                     ->get();

        foreach ($tasks as $task) {
            $task->user->notify(new DeadlineReminder($task));
            $this->info("Reminder dikirim untuk: {$task->title}");
        }

        $this->info("Total: {$tasks->count()} reminder dikirim.");
    }
}