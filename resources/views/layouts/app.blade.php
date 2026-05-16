<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - StudyPro</title>
    @vite('resources/css/app.css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════════════════
           CSS VARIABLES — LIGHT MODE (default)
        ══════════════════════════════════════════════════════════ */
        :root {
            --bg-base:        #f9fafb;
            --bg-card:        #ffffff;
            --bg-sidebar:     #ffffff;
            --bg-input:       #ffffff;
            --bg-hover:       #f3f4f6;
            --bg-active:      #dff0e6;

            --border:         #e5e7eb;
            --border-light:   #f0f0f0;

            --text-primary:   #1f2937;
            --text-secondary: #6b7280;
            --text-tertiary:  #9ca3af;
            --text-active:    #1c5c41;

            --accent:         #1c5c41;
            --accent-light:   #dff0e6;

            --shadow:         0 1px 4px rgba(0,0,0,.06);
            --shadow-md:      0 4px 16px rgba(0,0,0,.08);

            --chat-bg:        #ffffff;
            --chat-sidebar:   #f9fafb;
            --chat-msg-ai:    #f3f4f6;
            --chat-msg-text:  #1f2937;

            --table-head:     #f9fafb;
            --table-row-hover:#f9fafb;
        }

        /* ══════════════════════════════════════════════════════════
           CSS VARIABLES — DARK MODE
        ══════════════════════════════════════════════════════════ */
        html.dark {
            --bg-base:        #0f1117;
            --bg-card:        #1a1d27;
            --bg-sidebar:     #13151f;
            --bg-input:       #1e2130;
            --bg-hover:       #1e2130;
            --bg-active:      #1a3028;

            --border:         #2a2d3a;
            --border-light:   #232635;

            --text-primary:   #e8eaf0;
            --text-secondary: #8b92a8;
            --text-tertiary:  #555d78;
            --text-active:    #4ade80;

            --accent:         #4ade80;
            --accent-light:   #1a3028;

            --shadow:         0 1px 4px rgba(0,0,0,.4);
            --shadow-md:      0 4px 16px rgba(0,0,0,.5);

            --chat-bg:        #1a1d27;
            --chat-sidebar:   #13151f;
            --chat-msg-ai:    #1e2130;
            --chat-msg-text:  #e8eaf0;

            --table-head:     #13151f;
            --table-row-hover:#1e2130;
        }

        /* ══════════════════════════════════════════════════════════
           BASE STYLES — pakai CSS variables
        ══════════════════════════════════════════════════════════ */
        * { transition: background-color .2s ease, border-color .2s ease, color .15s ease; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-primary);
        }

        /* Sidebar */
        aside {
            background: var(--bg-sidebar) !important;
            border-color: var(--border) !important;
        }
        aside h1 { color: var(--text-primary) !important; }
        aside .profile-card {
            background: var(--bg-hover) !important;
            border-color: var(--border) !important;
        }
        aside h3 { color: var(--text-primary) !important; }
        aside p  { color: var(--text-secondary) !important; }

        /* Nav links */
        aside nav a, aside nav button {
            color: var(--text-secondary) !important;
        }
        aside nav a:hover, aside nav button:hover {
            background: var(--bg-hover) !important;
            color: var(--text-primary) !important;
        }
        aside nav a.active-nav {
            background: var(--bg-active) !important;
            color: var(--text-active) !important;
        }

        /* Main area */
        main { background: var(--bg-base) !important; }

        /* Cards / white boxes */
        .bg-white, .section-card {
            background: var(--bg-card) !important;
            border-color: var(--border-light) !important;
        }

        /* Gray backgrounds */
        .bg-gray-50, .bg-gray-50\/50, .bg-gray-50\/20, .bg-gray-50\/30 {
            background: var(--bg-hover) !important;
        }
        .bg-gray-100 { background: var(--bg-hover) !important; }

        /* Text colors */
        .text-gray-800, .text-gray-900 { color: var(--text-primary) !important; }
        .text-gray-700               { color: var(--text-primary) !important; }
        .text-gray-600               { color: var(--text-secondary) !important; }
        .text-gray-500               { color: var(--text-secondary) !important; }
        .text-gray-400               { color: var(--text-tertiary) !important; }
        .text-gray-300               { color: var(--text-tertiary) !important; }

        /* Borders */
        .border-gray-100, .border-gray-200 { border-color: var(--border) !important; }
        .border-b { border-color: var(--border) !important; }
        .divide-gray-100 > * + * { border-color: var(--border) !important; }

        /* Inputs & textareas */
        input, textarea, select {
            background: var(--bg-input) !important;
            color: var(--text-primary) !important;
            border-color: var(--border) !important;
        }
        input::placeholder, textarea::placeholder {
            color: var(--text-tertiary) !important;
        }

        /* Tables */
        thead tr { background: var(--table-head) !important; }
        thead th { color: var(--text-secondary) !important; }
        tbody tr:hover { background: var(--table-row-hover) !important; }
        tbody td { color: var(--text-primary) !important; }
        .border-collapse td, .border-collapse th {
            border-color: var(--border) !important;
        }

        /* Chat panel */
        #chatContainer {
            background: var(--chat-bg) !important;
            border-color: var(--border) !important;
            transition: transform .25s cubic-bezier(.4,0,.2,1), opacity .25s ease,
                        background-color .2s ease;
        }
        #chatContainer.hidden {
            transform: translateX(100%);
            opacity: 0;
            pointer-events: none;
        }
        #chatHistoryList { background: var(--chat-sidebar) !important; }
        #chatMessages .bg-gray-100 {
            background: var(--chat-msg-ai) !important;
            color: var(--chat-msg-text) !important;
        }
        #chatMessages textarea,
        #chatMessages input {
            background: var(--bg-input) !important;
        }

        /* History items */
        .hist-item { position: relative; }
        .hist-item .del-btn {
            display: none;
            position: absolute;
            right: 4px; top: 50%;
            transform: translateY(-50%);
        }
        .hist-item:hover .del-btn { display: flex; }

        /* Dot typing animation */
        .dot { display:inline-block;width:6px;height:6px;border-radius:50%;background:#9ca3af;animation:blink .9s infinite; }
        .dot:nth-child(2){animation-delay:.15s}
        .dot:nth-child(3){animation-delay:.3s}
        @keyframes blink{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-4px)}}

        /* Scrollbars */
        #chatMessages::-webkit-scrollbar,
        #chatHistoryList::-webkit-scrollbar { width:4px; }
        #chatMessages::-webkit-scrollbar-thumb,
        #chatHistoryList::-webkit-scrollbar-thumb { background:var(--border);border-radius:99px; }

        /* FullCalendar dark */
        html.dark .fc .fc-button-primary {
            background-color: var(--bg-hover) !important;
            border-color: var(--border) !important;
            color: var(--text-primary) !important;
        }
        html.dark .fc .fc-toolbar-title { color: var(--text-primary) !important; }
        html.dark .fc-theme-standard td,
        html.dark .fc-theme-standard th { border-color: var(--border) !important; }
        html.dark .fc .fc-daygrid-day-number { color: var(--text-secondary) !important; }
        html.dark .fc-day-today { background-color: var(--bg-active) !important; }
        html.dark .fc-col-header-cell-cushion { color: var(--text-secondary) !important; }

        /* ── Dashboard Cards (Task & Email) ── */
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

        /* Settings tabs dark */
        html.dark .settings-tab { color: var(--text-secondary) !important; }
        html.dark .settings-tab:hover { background: var(--bg-hover) !important; }
        html.dark .settings-tab.active {
            background: var(--bg-active) !important;
            color: var(--text-active) !important;
        }

        /* Toggle switch */
        .toggle-switch { position:relative;width:44px;height:24px;flex-shrink:0; }
        .toggle-switch input { opacity:0;width:0;height:0; }
        .toggle-slider { position:absolute;inset:0;background:#e5e7eb;border-radius:999px;cursor:pointer;transition:.25s; }
        .toggle-slider:before { content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:white;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
        input:checked + .toggle-slider { background: var(--accent); }
        input:checked + .toggle-slider:before { transform:translateX(20px); }

        /* Dark mode toggle button */
        #darkModeBtn {
            position: relative;
            width: 56px; height: 30px;
            border-radius: 999px;
            cursor: pointer;
            border: none;
            padding: 0;
            flex-shrink: 0;
            background: #fde68a;
            transition: background .35s ease;
        }
        html.dark #darkModeBtn {
            background: #0f2744;
        }

        #darkModeBtn .dm-thumb {
            position: absolute;
            top: 3px; left: 3px;
            width: 24px; height: 24px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 2px 8px rgba(245,158,11,.4);
            transition: transform .35s cubic-bezier(.4,0,.2,1),
                        background .35s ease,
                        box-shadow .35s ease;
            display: flex; align-items: center; justify-content: center;
        }
        html.dark #darkModeBtn .dm-thumb {
            transform: translateX(26px);
            background: #38bdf8;
            box-shadow: 0 2px 10px rgba(56,189,248,.4);
        }

        /* Sun icon */
        #dm-sun { display: block; }
        #dm-moon { display: none; }
        html.dark #dm-sun { display: none; }
        html.dark #dm-moon { display: block; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    {{-- ══════════════════════════════════════════ --}}
    {{-- SIDEBAR KIRI                               --}}
    {{-- ══════════════════════════════════════════ --}}
    <aside class="w-[260px] border-r flex flex-col shrink-0 h-full" style="background:var(--bg-sidebar);border-color:var(--border);">

        <div class="px-6 py-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight" style="color:var(--text-primary)">
                Study<span style="color:var(--accent)">Pro</span>
            </h1>
            {{-- Dark Mode Toggle --}}
            <button id="darkModeBtn" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="dm-thumb">
                    {{-- Sun --}}
                    <svg id="dm-sun" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    {{-- Moon --}}
                    <svg id="dm-moon" width="13" height="13" viewBox="0 0 24 24" fill="white" stroke="none">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </span>
            </button>
        </div>

        <div class="px-4 mb-6">
            <div class="rounded-2xl p-3 flex items-center gap-3 border" style="background:var(--bg-hover);border-color:var(--border);">
                <div class="w-10 h-10 rounded-full bg-[#2c3e50] text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-tight" style="color:var(--text-primary)">{{ Auth::user()->name }}</h3>
                    <p class="text-[11px] font-medium" style="color:var(--text-secondary)">Personal OS</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto">
            @php
                $navLinks = [
                    ['href' => '/dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>'],
                    ['href' => '/tasks',     'match' => 'tasks*',     'label' => 'Tasks',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    ['href' => '/calendar',  'match' => 'calendar*',  'label' => 'Calendar',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                    ['href' => '/emails',   'match' => 'email*',     'label' => 'Email',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
                ];
            @endphp

            @foreach($navLinks as $nav)
            @php $isActive = request()->is($nav['match']); @endphp
            <a href="{{ $nav['href'] }}"
                class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 {{ $isActive ? 'active-nav' : '' }}"
                style="{{ $isActive ? 'background:var(--bg-active);color:var(--text-active)' : 'color:var(--text-secondary)' }}"
                onmouseover="{{ $isActive ? '' : 'this.style.background=\'var(--bg-hover)\';this.style.color=\'var(--text-primary)\'' }}"
                onmouseout="{{ $isActive ? '' : 'this.style.background=\'\';this.style.color=\'var(--text-secondary)\'' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        style="color:{{ $isActive ? 'var(--text-active)' : 'var(--text-tertiary)' }}">
                        {!! $nav['icon'] !!}
                    </svg>
                    <span class="text-sm font-semibold">{{ $nav['label'] }}</span>
                </div>
            </a>
            @endforeach
        </nav>

        <div class="p-4 mt-auto space-y-1">
            <a href="/settings"
                class="flex items-center gap-3 px-3 py-2 rounded-xl transition-colors"
                style="color:var(--text-secondary)"
                onmouseover="this.style.background='var(--bg-hover)';this.style.color='var(--text-primary)'"
                onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text-tertiary)">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-sm font-medium">Settings</span>
            </a>
            <form action="/logout" method="POST" class="m-0">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl transition-colors"
                    style="color:var(--text-secondary)"
                    onmouseover="this.style.background='#fef2f2';this.style.color='#dc2626'"
                    onmouseout="this.style.background='';this.style.color='var(--text-secondary)'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text-tertiary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-sm font-medium">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- KONTEN UTAMA --}}
    <main class="flex-1 h-full overflow-y-auto" style="background:var(--bg-base)">
        <div class="p-6 md:p-8 w-full">
            @yield('content')
        </div>
    </main>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- AI CHAT PANEL                                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div id="chatContainer"
        class="fixed top-0 right-0 h-full w-[420px] md:w-[540px] shadow-2xl border-l flex hidden z-[9999]"
        style="background:var(--chat-bg);border-color:var(--border);transform:translateX(100%);opacity:0;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .25s ease;">

        {{-- History Sidebar --}}
        <div class="w-[160px] border-r flex flex-col shrink-0" style="background:var(--chat-sidebar);border-color:var(--border);">
            <div class="p-3 border-b" style="border-color:var(--border)">
                <p class="text-[10px] font-black uppercase tracking-widest mb-2" style="color:var(--text-tertiary)">StudyPro AI</p>
                <button onclick="newChat()"
                    class="w-full bg-violet-600 text-white text-xs py-2 rounded-lg hover:bg-violet-700 transition flex items-center justify-center gap-1 font-semibold">
                    <span class="text-base leading-none">+</span> New Chat
                </button>
            </div>
            <div id="chatHistoryList" class="flex-1 overflow-y-auto p-2 space-y-0.5" style="background:var(--chat-sidebar)">
                <p class="text-[10px] text-center pt-4 italic" style="color:var(--text-tertiary)">Memuat...</p>
            </div>
        </div>

        {{-- Main Chat --}}
        <div class="flex-1 flex flex-col min-w-0">
            <div class="flex items-center gap-3 px-4 py-3 border-b bg-violet-600 text-white shrink-0">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-base">🤖</div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm">StudyPro AI</p>
                    <p class="text-[11px] opacity-80 truncate" id="chatContextLabel">Tanya apa saja!</p>
                </div>
                <button onclick="toggleChat()" class="w-7 h-7 rounded-full hover:bg-white/20 flex items-center justify-center text-white transition text-sm">✕</button>
            </div>

            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 text-sm" style="background:var(--chat-bg)">
                <div class="flex gap-2">
                    <span class="shrink-0 mt-0.5">🤖</span>
                    <div class="rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%] leading-relaxed text-sm"
                        style="background:var(--chat-msg-ai);color:var(--chat-msg-text)">
                        Halo! Aku StudyPro AI. Mau tanya soal tugas, email, atau hal lain?
                    </div>
                </div>
            </div>

            <div id="quickActions" class="px-4 pb-2 flex gap-2 flex-wrap shrink-0"></div>

            <div class="border-t p-3 flex flex-col gap-2 shrink-0" style="border-color:var(--border);background:var(--chat-bg)">
                <div id="filePreview" class="hidden items-center gap-2 px-3 py-2 bg-violet-50 border border-violet-200 rounded-xl text-xs">
                    <span id="filePreviewIcon" class="text-base">📎</span>
                    <span id="filePreviewName" class="text-violet-700 font-semibold flex-1 truncate"></span>
                    <button onclick="removeFile()" class="text-gray-400 hover:text-red-500 transition font-bold">✕</button>
                </div>
                <div class="flex gap-2 items-end">
                    <label for="chatFileInput"
                        class="w-10 h-10 flex items-center justify-center rounded-full border cursor-pointer transition shrink-0 mb-0.5"
                        style="border-color:var(--border);color:var(--text-tertiary);background:var(--bg-hover)"
                        title="Lampirkan file">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </label>
                    <input type="file" id="chatFileInput" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt,.csv" class="hidden" onchange="handleChatFile(this)">
                    <textarea id="chatInput"
                        placeholder="Ketik pesan... (Enter kirim)"
                        onkeydown="handleKey(event)"
                        oninput="autoResize(this)"
                        rows="1"
                        class="flex-1 rounded-2xl px-3 py-2.5 text-sm focus:outline-none resize-none leading-relaxed border"
                        style="max-height:100px;overflow-y:auto;background:var(--bg-input);color:var(--text-primary);border-color:var(--border)"></textarea>
                    <button onclick="sendChat()"
                        class="w-10 h-10 bg-violet-600 text-white rounded-full flex items-center justify-center hover:bg-violet-700 transition shrink-0 mb-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <button onclick="toggleChat()"
        class="fixed bottom-6 right-6 w-14 h-14 bg-violet-600 text-white rounded-full shadow-xl text-xl z-[9998] hover:bg-violet-700 hover:scale-105 transition-all flex items-center justify-center">
        🤖
    </button>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- JAVASCRIPT                                                 --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <script>
    // ── Dark Mode ──────────────────────────────────────────────────
    function applyDarkMode(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
    }
    function toggleDarkMode() {
        const isDark = !document.documentElement.classList.contains('dark');
        localStorage.setItem('sp_dark_mode', isDark ? '1' : '0');
        applyDarkMode(isDark);
    }

    // Apply on load
    (function() {
        const saved = localStorage.getItem('sp_dark_mode');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyDarkMode(saved !== null ? saved === '1' : prefersDark);
    })();

    // ── Chat state ─────────────────────────────────────────────────
    let currentSessionId = null;
    let isCreatingSession = false;
    let chatFile = null;

    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    function getPageContext() {
        if (typeof window.emailContext !== 'undefined' && window.emailContext) return window.emailContext;
        if (typeof window.taskContext  !== 'undefined' && window.taskContext)  return window.taskContext;
        return '';
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    }

    function handleKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
    }

    function handleChatFile(input) {
        const file = input.files[0];
        if (!file) return;
        chatFile = file;
        const ext = file.name.split('.').pop().toLowerCase();
        const icon = ['jpg','jpeg','png','gif','webp'].includes(ext) ? '🖼️'
                   : ext === 'pdf' ? '📄' : ['doc','docx'].includes(ext) ? '📝' : '📎';
        document.getElementById('filePreviewIcon').textContent = icon;
        document.getElementById('filePreviewName').textContent = file.name;
        document.getElementById('filePreview').classList.remove('hidden');
        document.getElementById('filePreview').classList.add('flex');
    }

    function removeFile() {
        chatFile = null;
        document.getElementById('chatFileInput').value = '';
        document.getElementById('filePreview').classList.add('hidden');
        document.getElementById('filePreview').classList.remove('flex');
    }

    function toggleChat() {
        const el = document.getElementById('chatContainer');
        const isHidden = el.classList.contains('hidden');
        if (isHidden) {
            el.classList.remove('hidden');
            requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; el.style.opacity = '1'; });
            document.getElementById('chatInput').focus();
            loadSessions();
            updateContextLabel();
        } else {
            el.style.transform = 'translateX(100%)';
            el.style.opacity = '0';
            setTimeout(() => el.classList.add('hidden'), 260);
        }
    }

    function updateContextLabel() {
        const label = document.getElementById('chatContextLabel');
        if (typeof window.emailContext !== 'undefined' && window.emailContext) label.textContent = '📧 Konteks: Email ini';
        else if (typeof window.taskContext !== 'undefined' && window.taskContext) label.textContent = '📚 Konteks: Tugas ini';
        else label.textContent = 'Tanya apa saja!';
    }

    function newChat() {
        currentSessionId = null;
        document.getElementById('chatMessages').innerHTML = `
            <div class="flex gap-2">
                <span class="shrink-0 mt-0.5">🤖</span>
                <div class="rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%] leading-relaxed text-sm"
                    style="background:var(--chat-msg-ai);color:var(--chat-msg-text)">
                    Chat baru dimulai! Ada yang bisa aku bantu?
                </div>
            </div>`;
        document.getElementById('chatInput').focus();
        loadSessions();
        initQuickActions();
    }

    async function ensureSession() {
        if (currentSessionId) return currentSessionId;
        if (isCreatingSession) { await new Promise(r => setTimeout(r, 600)); return currentSessionId; }
        isCreatingSession = true;
        const res  = await fetch('/ai/new-session', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() } });
        const data = await res.json();
        currentSessionId  = data.id;
        isCreatingSession = false;
        return currentSessionId;
    }

    function loadSessions() {
        fetch('/ai/sessions').then(r => r.json()).then(renderHistoryList);
    }

    function renderHistoryList(sessions) {
        const list = document.getElementById('chatHistoryList');
        if (!sessions || sessions.length === 0) {
            list.innerHTML = `<p class="text-[10px] text-center pt-4 italic" style="color:var(--text-tertiary)">Belum ada history.</p>`;
            return;
        }
        list.innerHTML = sessions.map(s => `
            <div class="hist-item group relative">
                <button onclick="loadSession(${s.id})"
                    class="w-full text-left px-2 py-2 rounded-lg text-[11px] truncate transition"
                    style="${s.id === currentSessionId ? 'background:var(--bg-active);color:var(--text-active);font-weight:600' : 'color:var(--text-secondary)'}">
                    💬 ${escHtml(s.title || 'Chat')}
                </button>
                <button onclick="deleteSession(event, ${s.id})"
                    class="del-btn w-5 h-5 items-center justify-center rounded text-xs transition"
                    style="color:var(--text-tertiary)">✕</button>
            </div>
        `).join('');
    }

    function loadSession(id) {
        currentSessionId = id;
        fetch(`/ai/session/${id}`).then(r => r.json()).then(data => {
            const container = document.getElementById('chatMessages');
            container.innerHTML = '';
            if (!data.messages || data.messages.length === 0) {
                container.innerHTML = `<div class="flex gap-2"><span>🤖</span><div class="rounded-2xl px-3 py-2 text-sm" style="background:var(--chat-msg-ai);color:var(--chat-msg-text)">Sesi kosong. Mulai ngobrol!</div></div>`;
                return;
            }
            data.messages.forEach(msg => appendMessage(msg.role, msg.content, false));
            container.scrollTop = container.scrollHeight;
        });
        loadSessions();
    }

    function deleteSession(e, id) {
        e.stopPropagation();
        fetch(`/ai/session/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).then(() => { if (currentSessionId === id) newChat(); else loadSessions(); });
    }

    async function sendChat() {
        const input   = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message && !chatFile) return;

        const sessionId = await ensureSession();
        appendMessage('user', (chatFile ? `📎 ${chatFile.name}\n` : '') + (message || ''));
        input.value = '';
        autoResize(input);
        const loadingId = appendLoading();

        const formData = new FormData();
        formData.append('message',    message || `Tolong analisis file ini: ${chatFile?.name}`);
        formData.append('session_id', sessionId ?? '');
        formData.append('context',    getPageContext());
        if (chatFile) formData.append('file', chatFile);
        removeFile();

        try {
            const res  = await fetch('/ai/chat', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();
            document.getElementById(loadingId)?.remove();
            if (data.reply) {
                appendMessage('assistant', data.reply);
                if (data.session_id) currentSessionId = data.session_id;
                loadSessions();
            } else {
                appendMessage('assistant', 'Maaf, terjadi kesalahan: ' + (data.error ?? 'unknown'));
            }
        } catch {
            document.getElementById(loadingId)?.remove();
            appendMessage('assistant', 'Koneksi bermasalah, coba lagi ya!');
        }
    }

    function appendMessage(role, text, scroll = true) {
        const container = document.getElementById('chatMessages');
        const id        = 'msg-' + Date.now() + Math.random();
        const safe      = escHtml(text);
        const isUser    = role === 'user';
        const div       = document.createElement('div');
        div.id          = id;
        if (isUser) {
            div.className = 'flex justify-end';
            div.innerHTML = `<div class="bg-violet-600 text-white rounded-2xl rounded-tr-sm px-3 py-2 max-w-[85%] text-sm leading-relaxed whitespace-pre-line">${safe}</div>`;
        } else {
            div.className = 'flex gap-2';
            div.innerHTML = `
                <span class="shrink-0 mt-0.5">🤖</span>
                <div class="rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%] text-sm leading-relaxed whitespace-pre-line"
                    style="background:var(--chat-msg-ai);color:var(--chat-msg-text)">${safe}</div>`;
        }
        container.appendChild(div);
        if (scroll) container.scrollTop = container.scrollHeight;
        return id;
    }

    function appendLoading() {
        const container = document.getElementById('chatMessages');
        const id  = 'load-' + Date.now();
        const div = document.createElement('div');
        div.id    = id;
        div.className = 'flex gap-2';
        div.innerHTML = `
            <span class="shrink-0 mt-0.5">🤖</span>
            <div class="rounded-2xl rounded-tl-sm px-3 py-3 flex gap-1" style="background:var(--chat-msg-ai)">
                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            </div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    function initQuickActions() {
        const c = document.getElementById('quickActions');
        c.innerHTML = '';
        const actions = typeof window.taskContext !== 'undefined' && window.taskContext
            ? [{ label: '📋 Jelaskan tugas', msg: 'Tolong jelaskan tugas ini secara lengkap' },
               { label: '💡 Tips mengerjakan', msg: 'Apa tips terbaik untuk mengerjakan tugas ini?' }]
            : typeof window.emailContext !== 'undefined' && window.emailContext
            ? [{ label: '📝 Rangkum email', msg: 'Tolong rangkum isi email ini' },
               { label: '✅ Action item?', msg: 'Apakah ada hal yang perlu aku lakukan dari email ini?' },
               { label: '✉️ Tulis balasan', msg: 'Bantu aku menulis balasan untuk email ini' }]
            : [];
        actions.forEach(a => {
            const btn = document.createElement('button');
            btn.className = 'text-[11px] px-2.5 py-1.5 bg-violet-50 text-violet-700 border border-violet-200 rounded-full hover:bg-violet-100 transition font-medium';
            btn.innerText = a.label;
            btn.onclick   = () => { document.getElementById('chatInput').value = a.msg; sendChat(); };
            c.appendChild(btn);
        });
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    document.addEventListener('DOMContentLoaded', () => { initQuickActions(); });
    </script>

</body>
</html>
