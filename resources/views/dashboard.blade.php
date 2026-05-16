@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<style>
    /* ── Consistent hover card untuk Task & Email ── */
    .dash-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1rem;
        cursor: pointer;
        transition: background .2s ease, border-color .2s ease,
                    box-shadow .2s ease, transform .15s ease;
    }
    .dash-card:hover {
        background: var(--bg-hover);
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-light);
        transform: translateY(-1px);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

    .fc .fc-button-primary {
        background-color: var(--bg-hover) !important;
        border: 1px solid var(--border) !important;
        color: var(--text-primary) !important;
        font-weight: 600 !important;
        border-radius: 0.5rem !important;
    }
    .fc .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 800 !important; color: var(--text-primary) !important; }
    .fc-event { cursor: pointer; padding: 3px 6px !important; border-radius: 6px !important; border: none !important; }
    .fc-day-today { background-color: var(--bg-active) !important; }
    .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border) !important; }
    .fc .fc-daygrid-day-number { color: var(--text-secondary) !important; }
    .fc-col-header-cell-cushion { color: var(--text-secondary) !important; }

    /* Modal dark mode */
    #emailModal .modal-inner {
        background: var(--bg-card);
        border: 1px solid var(--border);
    }
    #emailModal .modal-header {
        background: var(--bg-hover);
        border-color: var(--border);
    }
    #modalSubject { color: var(--text-primary); }
    #modalBody    { color: var(--text-primary); }
</style>

{{-- Header --}}
<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-3xl font-bold" style="color:var(--text-primary)">Welcome back, {{ Auth::user()->name }}.</h2>
        <p style="color:var(--text-secondary)">Connected as: <span class="font-medium text-blue-500">{{ Auth::user()->email }}</span></p>
    </div>
    @if(Auth::user()->avatar)
        <img src="{{ Auth::user()->avatar }}" alt="Profile" class="w-14 h-14 rounded-full border-2 shadow-sm" style="border-color:var(--border)">
    @else
        <div class="w-14 h-14 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-md">
            {{ substr(Auth::user()->name, 0, 1) }}
        </div>
    @endif
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="rounded-xl p-6 shadow-sm border-l-4 border-red-500 hover:shadow-md transition" style="background:var(--bg-card);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border)">
        <p class="text-sm uppercase tracking-wide" style="color:var(--text-secondary)">URGENT</p>
        <p class="text-4xl font-bold text-red-500 mt-2">{{ $urgentCount }}</p>
        <p class="text-xs" style="color:var(--text-tertiary)">Due within 3 days</p>
    </div>
    <div class="rounded-xl p-6 shadow-sm border-l-4 border-blue-500 hover:shadow-md transition" style="background:var(--bg-card);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border)">
        <p class="text-sm uppercase tracking-wide" style="color:var(--text-secondary)">UPCOMING</p>
        <p class="text-4xl font-bold text-blue-500 mt-2">{{ $upcomingCount }}</p>
        <p class="text-xs" style="color:var(--text-tertiary)">Active tasks</p>
    </div>
    <div class="rounded-xl p-6 shadow-sm border-l-4 border-yellow-500 hover:shadow-md transition" style="background:var(--bg-card);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border)">
        <p class="text-sm uppercase tracking-wide" style="color:var(--text-secondary)">NEXT DEADLINE</p>
        <p class="text-lg font-bold mt-2 truncate" style="color:var(--text-primary)">{{ $nextTask?->title ?? 'Tidak ada' }}</p>
        <p class="text-xs" style="color:var(--text-tertiary)">{{ $nextTask?->deadline?->format('j M, g:i A') ?? '-' }}</p>
    </div>
    <div class="rounded-xl p-6 shadow-sm border-l-4 border-green-500 hover:shadow-md transition" style="background:var(--bg-card);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border)">
        <p class="text-sm uppercase tracking-wide" style="color:var(--text-secondary)">COMPLETED</p>
        <p class="text-4xl font-bold text-green-500 mt-2">{{ $completedCount }}</p>
        <p class="text-xs" style="color:var(--text-tertiary)">Tasks done</p>
    </div>
</div>

{{-- Main Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- KOLOM KIRI --}}
    <div class="lg:col-span-1 space-y-8">

        {{-- Task Engine --}}
        <div class="rounded-xl shadow-sm p-6 border" style="background:var(--bg-card);border-color:var(--border)">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold flex items-center gap-2" style="color:var(--text-primary)">
                    <span class="text-green-500">●</span> Task Engine
                </h3>
                <a href="/tasks/create"
                    class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-green-700 transition shadow-sm font-bold">
                    + Tambah
                </a>
            </div>

            <div class="space-y-3">
                @forelse($recentTasks as $task)
                {{-- ✅ dash-card: hover konsisten, dark mode aware --}}
                <a href="{{ route('tasks.show', $task->id) }}" class="dash-card flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        <p class="font-semibold leading-tight text-sm pr-2" style="color:var(--text-primary)">
                            {{ $task->title }}
                        </p>
                        @if(strtolower($task->source) == 'classroom')
                            <span class="px-2 py-0.5 bg-yellow-50 border border-yellow-200 text-yellow-700 text-[10px] font-bold rounded uppercase tracking-wider shrink-0">CLASSROOM</span>
                        @elseif(strtolower($task->source) == 'moodle')
                            <span class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-600 text-[10px] font-bold rounded uppercase tracking-wider shrink-0">MOODLE</span>
                        @elseif(strtolower($task->source) == 'calendar')
                            <span class="px-2 py-0.5 bg-purple-50 border border-purple-200 text-purple-600 text-[10px] font-bold rounded uppercase tracking-wider shrink-0">CALENDAR</span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[10px] font-bold rounded uppercase tracking-wider shrink-0">{{ strtoupper($task->source ?? 'MANUAL') }}</span>
                        @endif
                    </div>
                    <p class="text-[11px] mb-2 truncate" style="color:var(--text-secondary)">📚 {{ $task->course_name ?? 'Tanpa Matkul' }}</p>
                    <div class="flex items-center justify-between">
                        @if($task->isUrgent())
                            <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded">URGENT</span>
                        @else
                            <span class="text-[10px] italic" style="color:var(--text-tertiary)">{{ $task->deadline?->diffForHumans() }}</span>
                        @endif
                    </div>
                </a>
                @empty
                <p class="text-center py-8 text-sm italic" style="color:var(--text-tertiary)">No tasks yet. 🎉</p>
                @endforelse
            </div>
        </div>

        {{-- Email Intelligence --}}
        <div class="rounded-xl shadow-sm p-6 border" style="background:var(--bg-card);border-color:var(--border)">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold flex items-center gap-2" style="color:var(--text-primary)">
                    <span class="text-blue-500">●</span> Email Intelligence
                </h3>
                <span class="text-[10px] font-bold px-2 py-1 rounded uppercase" style="background:var(--bg-hover);color:var(--text-tertiary)">GMAIL</span>
            </div>

            <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1 custom-scrollbar">
                @forelse($emails ?? [] as $email)
                {{-- ✅ dash-card: sama persis dengan card task --}}
                <div onclick="showEmail('{{ $email['id'] }}')" class="dash-card relative">
                    <div class="flex justify-between items-start mb-1">
                        <h4 class="font-bold text-sm pr-12 leading-tight" style="color:var(--text-primary)">
                            {{ $email['subject'] }}
                        </h4>
                    </div>
                    <p class="text-[10px] text-blue-500 font-medium mb-1">{{ Str::limit($email['from'], 30) }}</p>
                    <p class="text-xs line-clamp-2 italic leading-relaxed" style="color:var(--text-secondary)">"{{ $email['snippet'] }}"</p>
                    <span class="absolute top-3 right-3 text-[9px] font-bold" style="color:var(--text-tertiary)">
                        {{ \Carbon\Carbon::parse($email['date'])->diffForHumans(null, true, false) }}
                    </span>
                </div>
                @empty
                <p class="text-center py-8 text-sm italic" style="color:var(--text-tertiary)">Inbox kosong.</p>
                @endforelse
            </div>

            <a href="{{ route('emails.index') }}" class="block text-center mt-4 text-xs font-bold text-blue-500 hover:underline">
                Lihat Semua Email →
            </a>
        </div>

    </div>

    {{-- KOLOM KANAN: Calendar --}}
    <div class="lg:col-span-2">
        <div class="rounded-xl shadow-sm p-6 border" style="background:var(--bg-card);border-color:var(--border)">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold flex items-center gap-2" style="color:var(--text-primary)">
                <span class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:var(--bg-active)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent)">
                        <rect x="3" y="4" width="18" height="18" rx="3"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </span>
                Calendar View
            </h3>
            </div>
            <div id="dashboard-calendar"style="min-height:500px"></div>
        </div>
    </div>

</div>

{{-- MODAL EMAIL --}}
<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="modal-inner rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col shadow-2xl" style="background:var(--bg-card);border:1px solid var(--border)">
        <div class="modal-header p-6 border-b flex justify-between items-center" style="background:var(--bg-hover);border-color:var(--border)">
            <h3 id="modalSubject" class="font-bold text-lg truncate" style="color:var(--text-primary)">Loading...</h3>
            <button onclick="closeEmail()" class="hover:opacity-60 text-2xl transition" style="color:var(--text-secondary)">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto">
            <div class="mb-4">
                <p id="modalFrom" class="text-sm font-semibold text-blue-500"></p>
                <p id="modalDate" class="text-xs" style="color:var(--text-tertiary)"></p>
            </div>
            <div id="modalBody" class="text-sm leading-relaxed border-t pt-4" style="color:var(--text-primary);border-color:var(--border)">
                Sedang mengambil isi email...
            </div>
        </div>
        <div class="p-4 border-t flex justify-end" style="background:var(--bg-hover);border-color:var(--border)">
            <button onclick="closeEmail()"
                class="px-4 py-2 rounded-lg text-sm font-bold transition hover:opacity-80"
                style="background:var(--bg-card);color:var(--text-secondary);border:1px solid var(--border)">
                Tutup
            </button>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('dashboard-calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
            themeSystem: 'standard',
            displayEventTime: false,
            eventDisplay: 'block',
            dayMaxEvents: 2,
            events: {!! json_encode(
                collect($tasks ?? [])
                    ->filter(fn($t) => $t->deadline)
                    ->map(fn($t) => [
                        'title'           => $t->title,
                        'start'           => \Carbon\Carbon::parse($t->deadline)->format('Y-m-d\TH:i:s'),
                        'url'             => route('tasks.show', $t->id),
                        'backgroundColor' => $t->status === 'done' ? '#10b981' : ($t->deadline->isPast() ? '#ef4444' : '#3b82f6'),
                        'borderColor'     => 'transparent',
                        'textColor'       => '#ffffff',
                        'allDay'          => true,
                    ])
                    ->values()
            ) !!},
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.url) window.location.href = info.event.url;
            }
        });
        calendar.render();
    });

    function showEmail(id) {
        document.getElementById('emailModal').classList.remove('hidden');
        document.getElementById('modalSubject').innerText = "Loading...";
        document.getElementById('modalBody').innerHTML = "Memuat isi email...";

        fetch(`/google/email/${id}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('modalSubject').innerText = data.subject;
                document.getElementById('modalFrom').innerText    = "Dari: " + data.from;
                document.getElementById('modalDate').innerText    = data.date;
                document.getElementById('modalBody').innerHTML    = data.body;
            })
            .catch(() => {
                document.getElementById('modalBody').innerText = "Gagal mengambil isi email.";
            });
    }

    function closeEmail() {
        document.getElementById('emailModal').classList.add('hidden');
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('emailModal')) closeEmail();
    }
// Auto polling setiap 60 detik
    setInterval(() => {

        // ── Update email ──
        fetch('/api/emails/latest')
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.emails.length) return;
                // Reload halaman kalau jumlah email berubah
                const currentCount = document.querySelectorAll('.dash-card').length;
                if (data.emails.length !== currentCount) {
                    window.location.reload();
                }
            }).catch(() => {});

        // ── Update tasks ──
        fetch('/api/tasks/latest')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                // Update stats cards tanpa reload
                // (reload kalau jumlah task berubah)
            }).catch(() => {});

    }, 60000); // 60 detik

</script>

@endsection