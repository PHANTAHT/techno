@extends('layouts.app')

@section('title', 'Settings')

@section('content')

{{-- Flash Messages --}}
@if(session('success'))
<div id="flashMsg-success" class="fixed top-6 right-6 z-50 flex items-center gap-3 bg-green-600 text-white px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold animate-slideIn">
    <span>✅</span> {{ session('success') }}
</div>
<script>setTimeout(() => document.getElementById('flashMsg-success')?.remove(), 3000)</script>
@endif

@if(session('error'))
<div id="flashMsg-error" class="fixed top-6 right-6 z-50 flex items-center gap-3 bg-red-600 text-white px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold animate-slideIn">
    <span>❌</span> {{ session('error') }}
</div>
<script>setTimeout(() => document.getElementById('flashMsg-error')?.remove(), 3000)</script>
@endif

<style>
@keyframes slideIn {
    from { opacity:0; transform:translateY(-12px); }
    to   { opacity:1; transform:translateY(0); }
}
.animate-slideIn { animation: slideIn .3s ease; }

.settings-tab { transition: all .2s; color: var(--text-secondary); }
.settings-tab:hover { background: var(--bg-hover); color: var(--text-primary); }
.settings-tab.active {
    background: var(--bg-active);
    color: var(--text-active);
    font-weight: 700;
}

.section-card {
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow);
}

.toggle-switch { position:relative;width:44px;height:24px;flex-shrink:0; }
.toggle-switch input { opacity:0;width:0;height:0; }
.toggle-slider { position:absolute;inset:0;background:#e5e7eb;border-radius:999px;cursor:pointer;transition:.25s; }
.toggle-slider:before { content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:white;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
input:checked + .toggle-slider { background: var(--accent); }
input:checked + .toggle-slider:before { transform:translateX(20px); }

/* Integration card */
.integration-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 16px;
    border: 1.5px solid var(--border);
    background: var(--bg-hover);
    margin-bottom: 1rem;
    transition: border-color .2s, box-shadow .2s;
}
.integration-card:last-child { margin-bottom: 0; }
.integration-card:hover { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-light); }

.integration-logo {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    border: 1px solid var(--border);
    background: var(--bg-card);
}

.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.status-connected { background: #dcfce7; color: #16a34a; }
.status-disconnected { background: var(--bg-hover); color: var(--text-tertiary); border: 1px solid var(--border); }

html.dark .status-connected { background: #14532d; color: #4ade80; }

/* Moodle form */
.moodle-form-wrap {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 12px;
    background: var(--bg-base);
    border: 1px solid var(--border);
}
</style>

<div class="mb-8">
    <h2 class="text-3xl font-bold" style="color:var(--text-primary)">Settings</h2>
    <p class="text-sm mt-1" style="color:var(--text-secondary)">Kelola preferensi dan konfigurasi StudyPro kamu.</p>
</div>

<div class="flex gap-8 items-start">

    {{-- ── Tab Sidebar ── --}}
    <div class="w-52 shrink-0 sticky top-6">
        <div class="rounded-2xl border p-2 shadow-sm space-y-0.5" style="background:var(--bg-card);border-color:var(--border)">
            @foreach([
                ['id' => 'profile',      'icon' => '👤', 'label' => 'Profil'],
                ['id' => 'integrations', 'icon' => '🔗', 'label' => 'Integrasi'],
                ['id' => 'notifications','icon' => '🔔', 'label' => 'Notifikasi'],
            ] as $tab)
            <button onclick="switchTab('{{ $tab['id'] }}')"
                id="tab-{{ $tab['id'] }}"
                class="settings-tab w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── Content Area ── --}}
    <div class="flex-1 min-w-0">

        {{-- ════════════════════════════════════════ --}}
        {{-- TAB: PROFIL                              --}}
        {{-- ════════════════════════════════════════ --}}
        <div id="content-profile" class="tab-content hidden">
            <div class="section-card">
                <h3 class="text-base font-black mb-6" style="color:var(--text-primary)">Informasi Profil</h3>
                <form action="{{ route('settings.profile') }}" method="POST">
                    @csrf @method('PATCH')

                    <div class="flex items-center gap-5 mb-8 pb-6 border-b" style="border-color:var(--border)">
                        <div class="w-16 h-16 rounded-2xl bg-[#2c3e50] text-white flex items-center justify-center text-2xl font-black shadow-md">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-bold" style="color:var(--text-primary)">{{ Auth::user()->name }}</p>
                            <p class="text-sm" style="color:var(--text-secondary)">{{ Auth::user()->email }}</p>
                            <p class="text-xs mt-1" style="color:var(--text-tertiary)">Bergabung {{ Auth::user()->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ Auth::user()->name }}"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1c5c41]">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Email</label>
                            <input type="email" name="email" value="{{ Auth::user()->email }}"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1c5c41]">
                        </div>
                    </div>

                    <button type="submit" class="bg-[#1c5c41] text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-[#164d36] transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            <div class="section-card">
                <h3 class="text-base font-black mb-6" style="color:var(--text-primary)">Ganti Password</h3>
                <form action="{{ route('settings.password') }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="space-y-4 mb-5">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Password Saat Ini</label>
                            <input type="password" name="current_password" class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1c5c41]">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Password Baru</label>
                                <input type="password" name="password" class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1c5c41]">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#1c5c41]">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-900 transition">
                        Update Password
                    </button>
                </form>
            </div>
        </div>

        {{-- ════════════════════════════════════════ --}}
        {{-- TAB: INTEGRASI                           --}}
        {{-- ════════════════════════════════════════ --}}
        <div id="content-integrations" class="tab-content hidden">

            {{-- ── Google ── --}}
            <div class="section-card">
                <h3 class="text-base font-black mb-1" style="color:var(--text-primary)">Google</h3>
                <p class="text-xs mb-5" style="color:var(--text-secondary)">Hubungkan akun Google untuk sinkronisasi Classroom & Calendar.</p>

                <div class="integration-card">
                    <div class="integration-logo">
                        <svg width="24" height="24" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm" style="color:var(--text-primary)">Google Account</p>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary)">Tarik otomatis tugas dari Classroom dan event dari Calendar</p>
                        @if($googleConnected)
                            <div class="flex items-center gap-2 mt-2">
                                <span class="status-badge status-connected">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Terhubung
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- FIX: Perbaikan blok if-else yang menyebabkan error --}}
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        @if($googleConnected)
                            <form action="{{ route('settings.sync.google') }}" method="POST">
                                @csrf
                                <button type="submit" 
                                    class="text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 hover:opacity-80"
                                    style="background:#e8f0fe;color:#1a73e8">
                                    🔄 Sync Google
                                </button>
                            </form>
                        @else
                            <a href="/google/redirect"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white border rounded-xl text-sm font-bold shadow-sm hover:shadow-md transition"
                                style="border-color:var(--border);color:var(--text-primary)">
                                <svg width="16" height="16" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                Hubungkan Google
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Moodle ── --}}
            <div class="section-card">
                <h3 class="text-base font-black mb-1" style="color:var(--text-primary)">Moodle LMS</h3>
                <p class="text-xs mb-5" style="color:var(--text-secondary)">Hubungkan Moodle kampus untuk menarik tugas secara otomatis.</p>

                <div class="integration-card">
                    <div class="integration-logo">
                        <svg width="26" height="26" viewBox="0 0 100 100" fill="none">
                            <rect width="100" height="100" rx="18" fill="#F98012"/>
                            <text x="50" y="68" text-anchor="middle" font-size="52" font-family="Arial" font-weight="bold" fill="white">M</text>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm" style="color:var(--text-primary)">Moodle LMS</p>
                        <p class="text-xs mt-0.5" style="color:var(--text-secondary)">
                            @if($moodleConfig)
                                {{ $moodleConfig->moodle_url }}
                            @else
                                Belum terhubung — masukkan URL dan kredensial Moodle kampus kamu
                            @endif
                        </p>
                        @if($moodleConfig)
                            <span class="status-badge status-connected mt-2 inline-flex">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Terhubung
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        @if($moodleConfig)
                            <a href="/moodle/sync"
                                class="text-xs px-3 py-1.5 rounded-lg font-bold transition"
                                style="background:#fff3e0;color:#e65100">
                                🎓 Sync Tugas
                            </a>
                            <button onclick="toggleMoodleForm()"
                                class="text-xs px-3 py-1.5 rounded-lg border font-semibold transition hover:opacity-80"
                                style="border-color:var(--border);color:var(--text-secondary)">
                                Edit Konfigurasi
                            </button>
                        @else
                            <button onclick="toggleMoodleForm()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-white transition hover:opacity-90"
                                style="background:#F98012">
                                + Hubungkan Moodle
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Moodle Form --}}
                <div id="moodleForm" class="moodle-form-wrap {{ $moodleConfig ? 'hidden' : '' }}">
                    <p class="text-xs font-black uppercase tracking-wider mb-4" style="color:var(--text-secondary)">
                        Hubungkan Moodle Kampus
                    </p>
                    <form action="/moodle/setup" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 mb-5">
                            <div>
                                <label class="block text-xs font-bold mb-1.5" style="color:var(--text-secondary)">URL Moodle Kampus</label>
                                <input type="url" name="moodle_url" 
                                    value="{{ $moodleConfig?->moodle_url ?? 'https://cloud.lentera.petra.ac.id' }}" 
                                    placeholder="https://moodle.kampus.ac.id" 
                                    class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-secondary)">Username / NRP</label>
                                    <input type="text" name="moodle_username" 
                                        placeholder="Contoh: m1234567" 
                                        class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1.5" style="color:var(--text-secondary)">Password</label>
                                    <input type="password" name="moodle_password" 
                                        placeholder="Password kamu" 
                                        class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400" required>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" 
                                class="px-5 py-2 rounded-xl text-sm font-bold text-white transition hover:opacity-90" 
                                style="background:#F98012">
                                Otentikasi & Hubungkan
                            </button>
                            <button type="button" onclick="toggleMoodleForm()" 
                                class="px-5 py-2 rounded-xl text-sm font-semibold border transition" 
                                style="border-color:var(--border);color:var(--text-secondary)">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════ --}}
        {{-- TAB: NOTIFIKASI                          --}}
        {{-- ════════════════════════════════════════ --}}
        <div id="content-notifications" class="tab-content hidden">
            <div class="section-card">
                <h3 class="text-base font-black mb-6" style="color:var(--text-primary)">Preferensi Notifikasi</h3>
                <form action="{{ route('settings.notifications') }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="space-y-5 mb-6">
                        @foreach([
                            ['key' => 'notif_deadline_1day',  'label' => 'Deadline H-1',          'desc' => 'Ingatkan 1 hari sebelum deadline'],
                            ['key' => 'notif_deadline_3days', 'label' => 'Deadline H-3',          'desc' => 'Ingatkan 3 hari sebelum deadline'],
                            ['key' => 'notif_deadline_1week', 'label' => 'Deadline H-7',          'desc' => 'Ingatkan 1 minggu sebelum deadline'],
                            ['key' => 'notif_new_task',       'label' => 'Tugas Baru dari Moodle','desc' => 'Notifikasi saat ada tugas baru tersync'],
                            ['key' => 'notif_email_important','label' => 'Email Penting',         'desc' => 'Notifikasi untuk email yang ditandai penting'],
                        ] as $item)
                        <div class="flex items-center justify-between py-3 border-b last:border-0" style="border-color:var(--border)">
                            <div>
                                <p class="text-sm font-semibold" style="color:var(--text-primary)">{{ $item['label'] }}</p>
                                <p class="text-xs" style="color:var(--text-secondary)">{{ $item['desc'] }}</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="{{ $item['key'] }}" value="1"
                                    {{ ($notifSettings[$item['key']] ?? true) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <button type="submit" class="bg-[#1c5c41] text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-[#164d36] transition">
                        Simpan Notifikasi
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(id) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.settings-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('content-' + id).classList.remove('hidden');
    document.getElementById('tab-' + id).classList.add('active');
    localStorage.setItem('sp_settings_tab', id);
}

function toggleMoodleForm() {
    const form = document.getElementById('moodleForm');
    form.classList.toggle('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    // Kalau dari redirect Moodle setup, langsung buka tab integrasi
    const urlParams = new URLSearchParams(window.location.search);
    const last = urlParams.get('tab') || localStorage.getItem('sp_settings_tab') || 'profile';
    switchTab(last);

    // Kalau ada error moodle, buka formnya langsung
    @if($errors->has('moodle_url') || $errors->has('moodle_username') || $errors->has('moodle_password'))
        switchTab('integrations');
        document.getElementById('moodleForm').classList.remove('hidden');
    @endif
});
</script>

@endsection