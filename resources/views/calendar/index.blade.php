@extends('layouts.app')

@section('title', 'Calendar')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">Academic Calendar</h2>
        <p class="text-gray-500">Pantau semua jadwal dan tenggat waktu tugasmu di sini.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100">
    <div id="full-calendar"></div>
</div>

@php
    $calendarEvents = [];
    foreach($tasks ?? [] as $task) {
        if($task->deadline) {
            // Amankan parsing deadline ke objek Carbon
            $deadline = \Carbon\Carbon::parse($task->deadline);
            
            // Tentukan warna berdasarkan status dan tanggal
            if ($task->status === 'done') {
                $bgColor = '#10b981'; // Hijau jika sudah selesai
            } else {
                $bgColor = $deadline->isPast() ? '#ef4444' : '#3b82f6'; // Merah jika telat, Biru jika pending
            }

            $calendarEvents[] = [
                'title'           => html_entity_decode($task->title),
                'start'           => $deadline->format("Y-m-d\TH:i:s"),
                'url'             => route("tasks.show", $task->id),
                'backgroundColor' => $bgColor,
                'borderColor'     => 'transparent',
                'textColor'       => '#ffffff',
                'allDay'          => true 
            ];
        }
    }
@endphp

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('full-calendar');
        
        // Ambil data JSON dari PHP yang sudah di-encode dengan aman
        var eventsData = {{ Illuminate\Support\Js::from($calendarEvents) }};
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 750, 
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: '' 
            },
            themeSystem: 'standard',
            
            displayEventTime: false,  
            eventDisplay: 'block',    
            dayMaxEvents: 4,          

            // Masukkan data ke sini
            events: eventsData, 
            
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            }
        });
        
        calendar.render();
    });
</script>

<style>
    /* Styling agar kalender lebih rapi dan cocok dengan Tailwind */
    .fc .fc-button-primary {
        background-color: #f3f4f6 !important;
        border-color: #d1d5db !important;
        color: #374151 !important;
        text-transform: capitalize !important;
        font-weight: 500 !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        padding: 0.5rem 1rem !important; 
    }
    .fc .fc-button-primary:hover {
        background-color: #e5e7eb !important;
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: #e5e7eb;
    }
    .fc .fc-toolbar-title {
        font-size: 1.5rem !important; 
        font-weight: 700 !important;
        color: #1f2937;
    }
    .fc .fc-daygrid-day-number {
        color: #4b5563;
        font-weight: 600;
        padding: 8px !important;
    }
    .fc-day-today {
        background-color: #f0fdf4 !important; 
    }

    /* Kerapian Teks Tugas */
    .fc-event-title {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.8rem !important; 
        font-weight: 600 !important;
        display: block !important;
    }
    .fc-event {
        cursor: pointer;
        padding: 4px 8px !important;
        border-radius: 6px !important;
        margin-bottom: 4px !important;
        border: none !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .fc-daygrid-more-link {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        color: #6b7280 !important;
        padding: 4px;
        display: block;
        text-align: center;
        text-decoration: none !important;
    }
    .fc-daygrid-more-link:hover {
        background-color: #f3f4f6 !important;
        border-radius: 4px;
    }
</style>
@endsection
