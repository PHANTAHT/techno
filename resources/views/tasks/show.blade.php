@extends('layouts.app')

@section('title', 'Detail Tugas')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">Detail Tugas</h2>
        <p class="text-gray-500 text-sm">Informasi lengkap tentang tugas akademikmu.</p>
    </div>
    <a href="{{ route('tasks.index') }}" class="bg-white text-gray-600 border border-gray-200 px-4 py-2 rounded-xl text-sm hover:bg-gray-50 transition font-bold shadow-sm">
        ⬅ Kembali
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">

    {{-- ── Header: Judul + Badge ── --}}
    <div class="mb-6 border-b pb-6 flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-3">{{ $task->title }}</h1>
            <div class="flex items-center gap-3">
                @if(strtolower($task->source) == 'classroom')
                    <span class="px-2 py-1 bg-yellow-50 border border-yellow-200 text-yellow-700 text-[10px] font-bold rounded uppercase tracking-wider">CLASSROOM</span>
                @elseif(strtolower($task->source) == 'moodle')
                    <span class="px-2 py-1 bg-blue-50 border border-blue-200 text-blue-600 text-[10px] font-bold rounded uppercase tracking-wider">MOODLE</span>
                @elseif(strtolower($task->source) == 'calendar')
                    <span class="px-2 py-1 bg-purple-50 border border-purple-200 text-purple-600 text-[10px] font-bold rounded uppercase tracking-wider">CALENDAR</span>
                @else
                    <span class="px-2 py-1 bg-gray-50 border border-gray-200 text-gray-600 text-[10px] font-bold rounded uppercase tracking-wider">{{ strtoupper($task->source ?? 'MANUAL') }}</span>
                @endif
                <p class="text-blue-600 font-bold flex items-center gap-2 text-sm">
                    <span class="p-1 bg-blue-50 rounded">📚</span> {{ $task->course_name ?? 'Mata Kuliah Tidak Diketahui' }}
                </p>
            </div>
        </div>

        @php $isOverdue = $task->deadline && $task->deadline->isPast(); @endphp
        @if($isOverdue)
            <span class="px-4 py-1.5 bg-red-50 text-red-600 text-xs font-black rounded-full border border-red-100">OVERDUE</span>
        @else
            <span class="px-4 py-1.5 bg-green-50 text-green-600 text-xs font-black rounded-full border border-green-100">ACTIVE</span>
        @endif
    </div>

    {{-- ── Deadline & Sisa Waktu ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tenggat Waktu</p>
            <p class="font-bold text-gray-800">
                {{ $task->deadline ? $task->deadline->format('l, d F Y - H:i') : 'Tidak ada deadline' }}
            </p>
        </div>
        <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Sisa Waktu</p>
            <p class="font-bold {{ $isOverdue ? 'text-red-500' : 'text-blue-600' }}">
                {{ $task->deadline ? $task->deadline->diffForHumans() : '-' }}
            </p>
        </div>
    </div>

    {{-- ── Deskripsi ── --}}
    <div class="mb-8">
        <h3 class="text-sm font-black text-gray-800 mb-3 flex items-center gap-2 uppercase tracking-tight">
            <span class="w-1.5 h-4 bg-blue-500 rounded-full"></span> Deskripsi Tugas
        </h3>
        <div class="prose max-w-none text-gray-700 bg-gray-50/30 p-6 rounded-2xl border border-gray-100 italic leading-relaxed">
            {!! $task->description ?? 'Tidak ada deskripsi tambahan.' !!}
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- FILE ATTACHMENT SECTION                                    --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($task->file_path || $task->file_url)
    <div class="mb-8">
        <h3 class="text-sm font-black text-gray-800 mb-3 flex items-center gap-2 uppercase tracking-tight">
            <span class="w-1.5 h-4 bg-green-500 rounded-full"></span> File Lampiran
        </h3>

        <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl">
            {{-- Icon berdasarkan tipe file --}}
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0
                {{ in_array($task->file_type, ['jpg','jpeg','png','gif','webp']) ? 'bg-blue-50' : 
                   ($task->file_type === 'pdf' ? 'bg-red-50' : 
                   (in_array($task->file_type, ['doc','docx']) ? 'bg-blue-50' : 'bg-gray-100')) }}">
                @if(in_array($task->file_type, ['jpg','jpeg','png','gif','webp']))
                    🖼️
                @elseif($task->file_type === 'pdf')
                    📄
                @elseif(in_array($task->file_type, ['doc','docx']))
                    📝
                @elseif(in_array($task->file_type, ['csv','txt']))
                    📊
                @else
                    📎
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-bold text-gray-800 text-sm truncate">
                    {{ $task->file_name ?? basename($task->file_path ?? $task->file_url) }}
                </p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-0.5">
                    {{ strtoupper($task->file_type ?? 'File') }}
                    @if($task->file_path)
                        · {{ number_format(Storage::disk('public')->size($task->file_path) / 1024, 1) }} KB
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                {{-- Tombol Download/Preview --}}
                <a href="{{ $task->file_url ?? asset('storage/' . $task->file_path) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-50 transition">
                    ⬇️ Download
                </a>

                {{-- Tombol AI Summarize File --}}
                @php
                    $supportedTypes = ['pdf','doc','docx','txt','csv','jpg','jpeg','png','gif','webp'];
                    $canSummarize   = in_array(strtolower($task->file_type ?? ''), $supportedTypes);
                @endphp

                @if($canSummarize)
                <button onclick="summarizeFile()"
                    id="btnSummarizeFile"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-violet-600 text-white rounded-xl text-xs font-bold hover:bg-violet-700 transition">
                    🤖 <span id="btnSummarizeFileText">AI Baca File</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Preview gambar kalau tipe image --}}
        @if(in_array($task->file_type, ['jpg','jpeg','png','gif','webp']) && $task->file_path)
        <div class="mt-3">
            <img src="{{ asset('storage/' . $task->file_path) }}"
                 alt="{{ $task->file_name }}"
                 class="max-h-64 rounded-xl border border-gray-200 object-contain bg-gray-50">
        </div>
        @endif
    </div>
    @endif

    {{-- ── Tombol Jelaskan Tugas ── --}}
    <div class="mb-8">
        <button onclick="explainTask()" id="btnExplain"
            class="inline-flex items-center gap-2 bg-gradient-to-r from-violet-600 to-blue-600 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
            <span>🤖</span> <span id="btnExplainText">Minta AI Jelaskan Tugas Ini</span>
        </button>
    </div>

    {{-- ── Aksi Lain ── --}}
    <div class="mt-8 pt-6 border-t border-dashed border-gray-200">
        <h3 class="text-sm font-black text-gray-800 mb-4 uppercase tracking-tight">⚙️ Aksi</h3>
        <div class="flex flex-wrap gap-3">
            @if($task->deadline)
            <button onclick="addToCalendarAPI({{ $task->id }}, this)"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl text-sm hover:bg-blue-50 hover:border-blue-200 transition font-bold shadow-sm group">
                <span class="group-hover:scale-110 transition">📅</span>
                <span id="btnText">Simpan ke Google Calendar</span>
            </button>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- JAVASCRIPT                                                 --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<script>
// Konteks tugas untuk chat bubble
window.taskContext = `Judul Tugas: {{ addslashes($task->title) }}
Mata Kuliah: {{ addslashes($task->course_name ?? 'Tidak diketahui') }}
Sumber: {{ strtoupper($task->source ?? 'Manual') }}
Deadline: {{ $task->deadline ? $task->deadline->format('d F Y, H:i') : 'Tidak ada deadline' }}
Status: {{ $isOverdue ? 'OVERDUE' : 'Active' }}
Deskripsi: {{ addslashes(strip_tags($task->description ?? 'Tidak ada deskripsi')) }}
File Lampiran: {{ $task->file_name ?? 'Tidak ada' }}`;

// ── AI Jelaskan Tugas → buka chat bubble ──────────────────────
function explainTask() {
    const panel = document.getElementById('chatContainer');
    if (panel.classList.contains('hidden')) toggleChat();
    document.getElementById('chatInput').value = 'Tolong jelaskan tugas ini secara lengkap dan berikan tips mengerjakannya';
    sendChat();
}

// ── AI Baca & Summarize File Attachment ───────────────────────
function summarizeFile() {
    const btn     = document.getElementById('btnSummarizeFile');
    const btnText = document.getElementById('btnSummarizeFileText');

    btn.disabled  = true;
    btnText.textContent = 'Membaca file...';

    fetch('/ai/summarize-file/{{ $task->id }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept':       'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled    = false;
        btnText.textContent = 'AI Baca File';

        if (data.summary) {
            // Buka chat bubble dan kirim hasil summary sebagai pesan AI
            const panel = document.getElementById('chatContainer');
            if (panel.classList.contains('hidden')) toggleChat();

            // Tambahkan pesan "user" dan langsung tampilkan jawaban AI
            appendMessage('user', `📎 Tolong analisis file lampiran: ${data.file_name}`);
            appendMessage('assistant', data.summary);

        } else {
            alert('Gagal membaca file: ' + (data.error ?? 'Unknown error'));
        }
    })
    .catch(() => {
        btn.disabled    = false;
        btnText.textContent = 'AI Baca File';
        alert('Terjadi kesalahan koneksi.');
    });
}

// ── Google Calendar ────────────────────────────────────────────
function addToCalendarAPI(taskId, btn) {
    const btnText      = btn.querySelector('#btnText');
    const originalText = btnText.innerText;

    btn.disabled = true;
    btnText.innerText = 'Menghubungkan ke Google...';
    btn.classList.add('opacity-70');

    fetch(`/google/add-task-calendar/${taskId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept':       'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btnText.innerText = 'Berhasil Ditambahkan! ✅';
            btn.classList.replace('text-gray-700', 'text-green-600');
            btn.classList.add('bg-green-50', 'border-green-200');
        } else {
            alert('Gagal: ' + data.message);
            btn.disabled = false;
            btnText.innerText = originalText;
            btn.classList.remove('opacity-70');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btnText.innerText = originalText;
        btn.classList.remove('opacity-70');
        alert('Terjadi kesalahan jaringan.');
    });
}
</script>
@endsection
