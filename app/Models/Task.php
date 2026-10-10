<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'source', 'source_id',
        'priority', 'status', 'deadline', 'course_name', 'url', 'file_url',
        'file_path',  // Path file lokal di storage
        'file_name',  // Nama asli file
        'file_type',  // Extension file
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'done';
    }

    public function isUrgent(): bool
    {
        return $this->status !== 'done' && $this->deadline
            && $this->deadline->betweenIncluded(now(), now()->addDays(3));
    }

    // Cek apakah task punya file attachment
    public function hasFile(): bool
    {
        return ! empty($this->file_path) || ! empty($this->file_url);
    }

    // 3. Di fungsi getFileUrlAttribute(), ganti fallback-nya menjadi $this->url
    public function getFileUrlAttribute($value): ?string
    {
        if (! empty($this->file_path)) {
            return asset('storage/'.$this->file_path);
        }

        return $value ?: $this->url;
    }

    public function getGoogleCalendarUrl(): string
    {
        if (! $this->deadline) {
            return '#';
        }

        $title = urlencode($this->title);
        $course = urlencode($this->course_name ?? 'Tugas');
        $details = urlencode("Mata Kuliah: {$this->course_name}\n\n".strip_tags($this->description));
        $dateStart = $this->deadline->copy()->subHour()->format('Ymd\THis');
        $dateEnd = $this->deadline->format('Ymd\THis');

        return "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}+({$course})&dates={$dateStart}/{$dateEnd}&details={$details}";
    }
}
