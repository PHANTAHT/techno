@extends('layouts.app')

@section('title', 'Email Intelligence')

@section('content')
<div class="mb-6 flex justify-between items-end">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">Email Intelligence</h2>
        <p class="text-gray-500 text-sm flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> 
            Sinkronisasi otomatis (7 hari terakhir)
        </p>
    </div>
    <div class="hidden md:block">
        <span class="text-[10px] font-bold text-gray-400 bg-gray-100 px-3 py-1 rounded-full uppercase tracking-widest">
            Gmail API v1
        </span>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex" style="height: calc(100vh - 200px);">
    
    {{-- Sidebar Daftar Email --}}
    <div class="w-1/3 border-r border-gray-100 flex flex-col bg-white">
        <div class="p-4 bg-gray-50/30 border-b border-gray-100 shrink-0">
            <div class="relative">
                <input type="text" placeholder="Cari di inbox..." class="w-full pl-9 pr-4 py-2 rounded-xl border-gray-200 text-sm focus:ring-[#1c5c41] focus:border-[#1c5c41] bg-white">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            @forelse($emails as $email)
                <div onclick="loadEmail('{{ $email['id'] }}', this)" 
                     class="email-card p-4 border-b border-gray-50 cursor-pointer hover:bg-[#dff0e6]/30 transition relative group"
                     data-subject="{{ addslashes($email['subject']) }}"
                     data-from="{{ addslashes($email['from']) }}"
                     data-snippet="{{ addslashes($email['snippet'] ?? '') }}">
                    <div class="flex justify-between items-start mb-1">
                        <h4 class="font-bold text-[13px] text-gray-800 truncate pr-2 group-hover:text-[#1c5c41] transition-colors">
                            {{ $email['subject'] }}
                        </h4>
                    </div>
                    <p class="text-[11px] text-[#1c5c41] font-semibold mb-1 truncate">{{ $email['from'] }}</p>
                    <div class="flex justify-between items-center">
                        <p class="text-[11px] text-gray-400 line-clamp-1 italic w-2/3">{{ $email['snippet'] ?? '' }}</p>
                        <span class="text-[9px] font-bold text-gray-400 whitespace-nowrap bg-gray-50 px-1.5 py-0.5 rounded">
                            {{ \Carbon\Carbon::parse($email['date'])->diffForHumans(null, true, true) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="text-gray-300 mb-2">📥</div>
                    <p class="text-gray-400 text-xs italic">Tidak ada email masuk minggu ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Panel Isi Email --}}
    <div class="flex-1 flex flex-col bg-gray-50/20 overflow-hidden" id="emailContentPanel">
        
        {{-- Tampilan saat belum ada email dipilih --}}
        <div id="placeholderView" class="flex-1 flex flex-col items-center justify-center text-gray-400">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-sm font-medium">Pilih pesan untuk ditampilkan</p>
            <p class="text-[11px] opacity-60">Gunakan sidebar kiri untuk navigasi</p>
        </div>

        {{-- Tampilan Detail Email --}}
        <div id="detailView" class="hidden h-full flex flex-col bg-white overflow-hidden relative">
            
            {{-- Header --}}
            <div class="p-6 border-b border-gray-100 flex justify-between items-start bg-white shrink-0">
                <div class="max-w-[70%]">
                    <h3 id="viewSubject" class="text-lg font-bold text-gray-800 mb-2 leading-tight"></h3>
                    <div class="flex items-center gap-2">
                        <span id="viewFrom" class="text-[12px] text-[#1c5c41] font-bold"></span>
                        <span class="text-gray-300 text-xs">•</span>
                        <span id="viewDate" class="text-[11px] text-gray-400 uppercase tracking-tighter font-semibold"></span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button id="btnSummarize" onclick="summarizeCurrentEmail()" 
                        class="px-3 py-1.5 bg-[#dff0e6] text-[#1c5c41] text-[10px] font-bold rounded-lg border border-[#a3e6cd] hover:bg-[#cbf0db] transition flex items-center gap-1">
                        ✨ AI SUMMARY
                    </button>
                    <button class="px-3 py-1.5 bg-gray-50 text-gray-600 text-[10px] font-bold rounded-lg border border-gray-100 hover:bg-gray-100">REPLY</button>
                </div>
            </div>
            
            {{-- Box AI Summary --}}
            <div id="aiSummaryBox" class="hidden m-6 mb-0 p-4 bg-[#f4fef8] border border-[#a3e6cd] rounded-xl relative shrink-0 max-h-[250px] overflow-y-auto custom-scrollbar">
                <button onclick="closeSummary()" class="absolute top-3 right-3 text-green-700 hover:text-green-900 transition bg-[#f4fef8] rounded-md p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <h4 class="font-bold text-[#1c5c41] text-xs mb-3 flex items-center gap-1 uppercase tracking-wider sticky top-0 bg-[#f4fef8] pb-1">
                    ✨ Ringkasan StudyPro AI
                </h4>
                <p id="aiSummaryText" class="text-sm text-green-900 leading-relaxed whitespace-pre-wrap pr-2"></p>
            </div>

            {{-- Body Email --}}
            <div class="p-6 overflow-y-auto flex-1 text-gray-700 leading-relaxed text-sm custom-scrollbar bg-white" id="viewBody">
            </div>
        </div>
    </div>
</div>

<script>
// Variabel untuk menyimpan email yang sedang aktif (dipakai oleh chat bubble)
let activeEmailData = null;

function loadEmail(id, element) {
    document.getElementById('placeholderView').classList.add('hidden');
    const detailView = document.getElementById('detailView');
    detailView.classList.remove('hidden');
    
    closeSummary();
    
    document.querySelectorAll('.email-card').forEach(card => {
        card.classList.remove('bg-[#dff0e6]/50', 'border-l-4', 'border-[#1c5c41]');
    });
    element.classList.add('bg-[#dff0e6]/50', 'border-l-4', 'border-[#1c5c41]');

    document.getElementById('viewSubject').innerText = "Syncing...";
    document.getElementById('viewBody').innerHTML = `
        <div class="flex flex-col items-center justify-center h-full py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1c5c41] mb-4"></div>
            <p class="text-gray-400 text-xs">Mengambil konten dari Google Server...</p>
        </div>
    `;

    fetch(`/google/email/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewSubject').innerText = data.subject;
            document.getElementById('viewFrom').innerText    = data.from;
            document.getElementById('viewDate').innerText    = data.date;
            document.getElementById('viewBody').innerHTML    = `<div class="prose prose-sm max-w-none">${data.body}</div>`;

            // ============================================================
            // Update window.emailContext supaya chat bubble tahu email ini
            // ============================================================
            activeEmailData = data;
            window.emailContext = `Email Subject: ${data.subject}
Dari: ${data.from}
Tanggal: ${data.date}
Isi Email (ringkasan): ${document.getElementById('viewBody').innerText.substring(0, 800)}`;

            // Update label di chat bubble kalau sudah terbuka
            const ctxLabel = document.getElementById('chatContextLabel');
            if (ctxLabel) ctxLabel.innerText = 'Konteks: Email ini';

            // Refresh quick action buttons di chat bubble
            refreshEmailQuickActions();
        })
        .catch(err => {
            document.getElementById('viewBody').innerHTML = `
                <div class="text-center py-20">
                    <p class="text-red-500 text-sm font-bold">Error Memuat Email</p>
                    <p class="text-gray-400 text-xs">Pastikan koneksi internet stabil.</p>
                </div>
            `;
        });
}

function refreshEmailQuickActions() {
    const container = document.getElementById('quickActions');
    if (!container) return;
    container.innerHTML = '';
    const actions = [
        { label: 'Rangkum email ini', msg: 'Tolong rangkum isi email ini' },
        { label: 'Ada action item?', msg: 'Apakah ada yang perlu aku lakukan dari email ini?' },
        { label: 'Tulis balasan', msg: 'Bantu aku tulis balasan untuk email ini' },
    ];
    actions.forEach(a => {
        const btn = document.createElement('button');
        btn.className = 'text-[11px] px-2 py-1 bg-violet-50 text-violet-700 border border-violet-200 rounded-full hover:bg-violet-100 transition';
        btn.innerText = a.label;
        btn.onclick   = () => {
            document.getElementById('chatInput').value = a.msg;
            // Buka chat kalau belum terbuka
            const panel = document.getElementById('chatPanel');
            if (panel.classList.contains('hidden')) toggleChat();
            sendChat();
        };
        container.appendChild(btn);
    });
}

function summarizeCurrentEmail() {
    const bodyContent = document.getElementById('viewBody').innerText;

    if (!bodyContent || bodyContent.trim() === '') {
        alert("Isi email belum termuat atau kosong.");
        return;
    }

    // Update konteks dulu dengan isi email yang sekarang
    window.emailContext = `Email Subject: ${document.getElementById('viewSubject').innerText}
Dari: ${document.getElementById('viewFrom').innerText}
Isi Email: ${bodyContent.substring(0, 1500)}`;

    // Buka chat bubble kalau belum terbuka
    const panel = document.getElementById('chatContainer');
    if (panel.classList.contains('hidden')) toggleChat();

    // Langsung kirim pesan ke chat
    document.getElementById('chatInput').value = 'Tolong rangkum isi email ini secara singkat dan jelas';
    sendChat();
}

function closeSummary() {
    document.getElementById('aiSummaryBox').classList.add('hidden');
}
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    #viewBody { font-family: 'Inter', sans-serif; }
    .animate-in { animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
