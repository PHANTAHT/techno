<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>StudyPro — AI Academic OS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(99,102,241,.10), transparent 25%),
                radial-gradient(circle at bottom right, rgba(168,85,247,.08), transparent 30%),
                #f6f8ff;

            color: #0f172a;
            overflow-x: hidden;
        }

        .font-urbanist {
            font-family: 'Urbanist', sans-serif;
        }

        .hero-bg {
            background:
                radial-gradient(circle at top left, rgba(99,102,241,.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(168,85,247,.10), transparent 30%),
                linear-gradient(to bottom, #f8faff, #eef2ff);
        }

        .glass {
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(99,102,241,0.08);
        }

        .card {
            background: linear-gradient(
                145deg,
                rgba(255,255,255,0.95),
                rgba(248,250,255,0.95)
            );

            border: 1px solid rgba(99,102,241,0.08);
            transition: .3s ease;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(99,102,241,.12);
        }

        .glow {
            box-shadow:
                0 0 80px rgba(99,102,241,.12),
                0 0 120px rgba(168,85,247,.10);
        }

        .hero-title-glow {
            text-shadow:
                0 0 20px rgba(99,102,241,.10),
                0 0 40px rgba(168,85,247,.08);
        }

        .gradient-text {
            background: linear-gradient(to right, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .floating {
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%,100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .smooth-divider {
            position: relative;
        }

        .smooth-divider::before {
            content: "";
            position: absolute;
            top: -80px;
            left: 0;
            width: 100%;
            height: 160px;

            background:
                linear-gradient(
                    to bottom,
                    rgba(246,248,255,0),
                    rgba(238,242,255,.95)
                );

            filter: blur(20px);
            pointer-events: none;
        }
    </style>
</head>

<body class="hero-bg grid-bg">

<!-- NAVBAR -->
<nav class="fixed top-0 w-full z-50 glass">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="h-20 flex items-center justify-between">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i class="fa-solid fa-brain text-white"></i>
                </div>

                <h1 class="text-2xl font-black font-urbanist tracking-tight text-slate-900">
                    Study<span class="gradient-text">Pro</span>
                </h1>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="/dashboard"
                       class="text-slate-600 hover:text-indigo-600 transition font-medium">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="text-slate-600 hover:text-indigo-600 transition font-medium">
                        Login
                    </a>

                    <a href="{{ route('register') }}"
                       class="px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold shadow-xl shadow-indigo-500/20 hover:scale-105 transition">
                        Get Started
                    </a>
                @endauth
            </div>

        </div>
    </div>
</nav>

<!-- HERO -->
<section class="relative min-h-screen flex items-center overflow-hidden">

    <div class="absolute top-40 left-20 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-20 right-10 w-[350px] h-[350px] bg-purple-500/10 rounded-full blur-[120px]"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full relative z-10">

        <div class="grid lg:grid-cols-2 gap-20 items-center">

            <!-- LEFT -->
            <div>

                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 text-indigo-500 text-sm font-semibold mb-8">
                    <i class="fa-solid fa-sparkles"></i>
                    AI Powered Academic Operating System
                </div>

                <h1 class="hero-title-glow text-5xl lg:text-7xl font-black leading-[1.05] tracking-tight font-urbanist text-slate-900">
                    Kelola Kuliah
                    <span class="gradient-text block">
                        Dengan AI.
                    </span>
                </h1>

                <p class="mt-8 text-lg text-slate-500 leading-relaxed max-w-xl">
                    StudyPro membantu mahasiswa mengelola tugas, email dosen,
                    jadwal kuliah, dan deadline menggunakan AI yang benar-benar memahami aktivitas akademikmu.
                </p>

                <div class="mt-10 flex flex-wrap gap-4">

                    <a href="{{ route('register') }}"
                       class="px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold text-lg shadow-2xl shadow-indigo-500/20 hover:scale-105 transition">
                        Mulai Gratis
                    </a>

                    <a href="#features"
                       class="px-8 py-4 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 transition font-semibold text-slate-700">
                        Explore Features
                    </a>

                </div>

                <!-- STATS -->
                <div class="mt-14 flex gap-10">

                    <div>
                        <h3 class="text-3xl font-black font-urbanist text-slate-900">10x</h3>
                        <p class="text-slate-500 text-sm">Lebih Produktif</p>
                    </div>

                    <div>
                        <h3 class="text-3xl font-black font-urbanist text-slate-900">AI</h3>
                        <p class="text-slate-500 text-sm">Smart Assistant</p>
                    </div>

                    <div>
                        <h3 class="text-3xl font-black font-urbanist text-slate-900">24/7</h3>
                        <p class="text-slate-500 text-sm">Always Active</p>
                    </div>

                </div>

            </div>

            <!-- RIGHT -->
            <div class="relative flex justify-center">

                <div class="glass rounded-[32px] p-6 w-full max-w-[520px] glow floating shadow-2xl">

                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="font-bold text-xl text-slate-900">StudyPro AI</h3>
                            <p class="text-sm text-slate-500">
                                Personal Academic Assistant
                            </p>
                        </div>

                        <div class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></div>
                    </div>

                    <div class="space-y-4">

                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <p class="text-sm text-slate-500 mb-1">
                                AI Reminder
                            </p>

                            <p class="font-semibold text-slate-800">
                                Deadline “Machine Learning Report”
                                besok jam 23:59
                            </p>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4">
                            <p class="text-indigo-500 text-sm mb-2 font-semibold">
                                AI Summary
                            </p>

                            <p class="text-slate-700 text-sm leading-relaxed">
                                Dosen meminta revisi laporan dan upload ulang
                                sebelum Jumat malam.
                            </p>
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-700">
                                    Progress Semester
                                </span>

                                <span class="text-indigo-500 text-sm font-bold">
                                    78%
                                </span>
                            </div>

                            <div class="w-full h-2 bg-slate-200 rounded-full mt-3 overflow-hidden">
                                <div class="w-[78%] h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"></div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
        <!-- Smooth Transition -->
    <div class="absolute bottom-0 left-0 w-full h-40 bg-gradient-to-b from-transparent via-[#eef2ff]/60 to-[#eef2ff] pointer-events-none"></div>

</section>

<!-- FEATURES -->
<section id="features" class="smooth-divider relative py-32 overflow-hidden bg-gradient-to-b from-[#eef2ff] via-[#f8faff] to-white">

    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-violet-400/10 blur-[140px] rounded-full"></div>

    <div class="relative max-w-7xl mx-auto px-6">

        <!-- Heading -->
        <div class="text-center mb-20">

            <span class="px-5 py-2 rounded-full border border-violet-200 bg-violet-100 text-violet-500 text-xs font-bold tracking-[0.3em] uppercase">
                Features
            </span>

            <h2 class="mt-6 text-5xl font-black text-slate-900 leading-tight">
                Semua Yang Kamu Butuhkan <br>

                <span class="bg-gradient-to-r from-violet-500 to-fuchsia-500 bg-clip-text text-transparent">
                    Dalam Satu Platform.
                </span>
            </h2>

            <p class="mt-6 text-slate-500 max-w-2xl mx-auto text-lg leading-relaxed">
                StudyPro bukan cuma task manager biasa.
                Ini personal academic operating system yang ngerti aktivitas kuliahmu.
            </p>

        </div>

        <!-- CARDS -->
        <div class="grid md:grid-cols-3 gap-8">

            <div class="card rounded-[32px] p-8 shadow-xl">
                <div class="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center text-violet-500 text-2xl mb-8">
                    <i class="fa-solid fa-robot"></i>
                </div>

                <h3 class="text-3xl font-bold text-slate-900 mb-4">
                    AI Assistant
                </h3>

                <p class="text-slate-500 leading-relaxed text-[15px]">
                    Tanya apa saja tentang tugas, deadline, email dosen,
                    bahkan minta AI bantu jelaskan tugas otomatis.
                </p>
            </div>

            <div class="card rounded-[32px] p-8 shadow-xl">
                <div class="w-16 h-16 rounded-2xl bg-fuchsia-100 flex items-center justify-center text-fuchsia-500 text-2xl mb-8">
                    <i class="fa-solid fa-envelope"></i>
                </div>

                <h3 class="text-3xl font-bold text-slate-900 mb-4">
                    Email Intelligence
                </h3>

                <p class="text-slate-500 leading-relaxed text-[15px]">
                    AI otomatis membaca email kampus, merangkum isi penting,
                    dan mendeteksi action item secara otomatis.
                </p>
            </div>

            <div class="card rounded-[32px] p-8 shadow-xl">
                <div class="w-16 h-16 rounded-2xl bg-pink-100 flex items-center justify-center text-pink-500 text-2xl mb-8">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>

                <h3 class="text-3xl font-bold text-slate-900 mb-4">
                    Smart Calendar
                </h3>

                <p class="text-slate-500 leading-relaxed text-[15px]">
                    Semua deadline, jadwal kelas, dan reminder otomatis sinkron
                    ke kalender personalmu secara real-time.
                </p>
            </div>

        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="border-t border-slate-200 py-10 text-center text-slate-500 text-sm bg-white">
    <p>
        © 2026 StudyPro — Built with AI for Students.
    </p>
</footer>

</body>
</html>