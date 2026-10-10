<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminder extends Notification
{
    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat deadline: '.$this->task->title)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Tugas: '.$this->task->title)
            ->line('Deadline: '.$this->task->deadline?->format('d-m-Y H:i'))
            ->action('Lihat tugas', route('tasks.show', $this->task));
    }
}
