<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login — StudyPro</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background:
                radial-gradient(circle at top left, rgba(99,102,241,.10), transparent 30%),
                radial-gradient(circle at bottom right, rgba(168,85,247,.10), transparent 30%),
                #f5f7ff;
        }

        .font-urbanist {
            font-family: 'Urbanist', sans-serif;
        }

        .grid-bg {
            background-image:
                linear-gradient(rgba(99,102,241,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,.05) 1px, transparent 1px);

            background-size: 42px 42px;
        }
    </style>
</head>

<body class="grid-bg">

<div class="min-h-screen flex items-center justify-center px-6 py-10 relative overflow-hidden">

    <!-- GLOW -->
    <div class="absolute top-[-100px] left-[-100px] w-[350px] h-[350px] bg-indigo-400/20 blur-3xl rounded-full"></div>
    <div class="absolute bottom-[-100px] right-[-100px] w-[350px] h-[350px] bg-purple-400/20 blur-3xl rounded-full"></div>

    <!-- MAIN CARD -->
    <div class="relative z-10 w-full max-w-7xl bg-white/70 backdrop-blur-xl border border-white/60 rounded-[40px] shadow-[0_30px_100px_rgba(99,102,241,0.12)] overflow-hidden">

        <div class="grid lg:grid-cols-2 min-h-[850px]">

            <!-- LEFT SIDE -->
            <div class="hidden lg:flex flex-col justify-between relative overflow-hidden p-16 bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600">

                <!-- glow -->
                <div class="absolute top-0 right-0 w-[320px] h-[320px] bg-white/10 blur-3xl rounded-full"></div>
                <div class="absolute bottom-0 left-0 w-[250px] h-[250px] bg-black/10 blur-3xl rounded-full"></div>

                <!-- logo -->
                <div class="relative z-10">

                    <a href="/" class="flex items-center gap-4">

                        <div class="w-16 h-16 rounded-3xl bg-white/20 backdrop-blur-xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-brain text-white text-2xl"></i>
                        </div>

                        <h1 class="text-5xl font-black text-white tracking-tight font-urbanist">
                            StudyPro
                        </h1>

                    </a>

                    <!-- content -->
                    <div class="mt-24">

                        <span class="px-5 py-2 rounded-full bg-white/15 border border-white/20 text-sm text-white font-semibold tracking-wider uppercase">
                            AI Academic OS
                        </span>

                        <h2 class="mt-8 text-7xl leading-[0.95] font-black text-white font-urbanist">
                            Fokus
                            <br>
                            Kuliah.
                            <br>

                            <span class="text-indigo-100">
                                AI bantu
                                <br>
                                sisanya.
                            </span>
                        </h2>

                        <p class="mt-8 text-xl leading-relaxed text-indigo-100 max-w-lg">
                            Kelola deadline, email dosen,
                            jadwal kuliah, dan semua tugas
                            dalam satu platform pintar.
                        </p>

                    </div>
                </div>

                <!-- bottom stats -->
                <div class="relative z-10">

                    <div class="bg-white/15 backdrop-blur-xl rounded-[30px] border border-white/20 p-8">

                        <div class="flex items-center justify-between">

                            <div>
                                <p class="text-indigo-100 text-sm">
                                    Semester Progress
                                </p>

                                <h3 class="text-5xl font-black text-white mt-2 font-urbanist">
                                    78%
                                </h3>
                            </div>

                            <div class="w-16 h-16 rounded-3xl bg-white/20 flex items-center justify-center">
                                <i class="fa-solid fa-chart-line text-white text-xl"></i>
                            </div>

                        </div>

                        <div class="mt-8 h-4 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full w-[78%] rounded-full bg-white"></div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- RIGHT SIDE -->
            <div class="flex items-center justify-center px-8 lg:px-16 py-14">

                <div class="w-full max-w-xl">

                    <!-- mobile logo -->
                    <div class="lg:hidden text-center mb-10">

                        <a href="/" class="inline-flex items-center gap-3">

                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-500/20">
                                <i class="fa-solid fa-brain text-white"></i>
                            </div>

                            <h1 class="text-4xl font-black text-[#111827] font-urbanist">
                                Study<span class="text-indigo-600">Pro</span>
                            </h1>

                        </a>

                    </div>

                    <!-- heading -->
                    <div class="mb-10">

                        <h2 class="text-5xl font-black text-[#111827] font-urbanist leading-tight">
                            Welcome Back 👋
                        </h2>

                        <p class="mt-4 text-gray-500 text-lg leading-relaxed">
                            Login untuk melanjutkan aktivitas akademikmu.
                        </p>

                    </div>

                    <!-- session -->
                    <x-auth-session-status class="mb-5" :status="session('status')" />

                    <!-- FORM -->
                    <form method="POST"
                          action="{{ route('login') }}"
                          class="space-y-7">

                        @csrf

                        <!-- EMAIL -->
                        <div>

                            <label for="email"
                                   class="block text-sm font-bold text-gray-700 mb-3">
                                Email Kampus
                            </label>

                            <x-text-input
                                id="email"
                                class="w-full h-16 rounded-2xl border border-gray-200 bg-white/80 px-6 text-lg focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                type="email"
                                name="email"
                                :value="old('email')"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@kampus.ac.id"
                            />

                            <x-input-error :messages="$errors->get('email')" class="mt-2" />

                        </div>

                        <!-- PASSWORD -->
                        <div>

                            <div class="flex items-center justify-between mb-3">

                                <label for="password"
                                       class="block text-sm font-bold text-gray-700">
                                    Password
                                </label>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
                                        Lupa Password?
                                    </a>
                                @endif

                            </div>

                            <x-text-input
                                id="password"
                                class="w-full h-16 rounded-2xl border border-gray-200 bg-white/80 px-6 text-lg focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />

                        </div>

                        <!-- remember -->
                        <div class="flex items-center">

                            <label for="remember_me" class="inline-flex items-center">

                                <input id="remember_me"
                                       type="checkbox"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       name="remember">

                                <span class="ms-3 text-gray-600 font-medium">
                                    Ingat saya
                                </span>

                            </label>

                        </div>

                        <!-- login button -->
                        <button type="submit"
                                class="w-full h-16 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-lg font-black font-urbanist shadow-2xl shadow-indigo-500/20 hover:scale-[1.01] transition duration-300">

                            LOG IN

                        </button>

                        <!-- divider -->
                        <div class="relative py-2">

                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>

                            <div class="relative flex justify-center">
                                <span class="bg-[#f5f7ff] px-4 text-xs uppercase tracking-[0.3em] text-gray-400 font-bold">
                                    atau
                                </span>
                            </div>

                        </div>

                        <!-- google -->
                        <a href="{{ url('/google/redirect') }}"
                           class="w-full h-16 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 flex items-center justify-center gap-4 text-gray-700 font-bold text-lg transition duration-300 shadow-sm">

                            <svg class="w-6 h-6"
                                 viewBox="0 0 24 24"
                                 xmlns="http://www.w3.org/2000/svg">

                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                      fill="#4285F4"/>

                                <path d="M12 23c3.11 0 5.71-1.03 7.62-2.81l-3.57-2.77c-.99.66-2.23 1.06-3.75 1.06-2.88 0-5.33-1.94-6.21-4.54H2.46v2.85C4.37 20.19 7.92 23 12 23z"
                                      fill="#34A853"/>

                                <path d="M5.79 13.94c-.23-.66-.35-1.36-.35-2.09s.12-1.43.35-2.09V6.91H2.46C1.64 8.53 1.17 10.32 1.17 12s.47 3.47 1.29 5.09l3.32-2.15z"
                                      fill="#FBBC05"/>

                                <path d="M12 5.38c1.69 0 3.21.58 4.41 1.72l3.31-3.31C17.71 1.95 15.11 1 12 1 7.92 1 4.37 3.81 2.46 7.66l3.33 2.85c.88-2.6 3.33-4.54 12-4.54z"
                                      fill="#EA4335"/>

                            </svg>

                            Masuk dengan Google

                        </a>

                    </form>

                    <!-- footer -->
                    <div class="mt-10 text-center">

                        <p class="text-gray-500 text-base">

                            Belum punya akun?

                            <a href="{{ route('register') }}"
                               class="font-bold text-indigo-600 hover:text-indigo-700 transition">
                                Daftar Sekarang
                            </a>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>