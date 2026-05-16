<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Register — StudyPro</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body{
            font-family:'Inter',sans-serif;
            background:
                radial-gradient(circle at top left, rgba(99,102,241,.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(168,85,247,.12), transparent 30%),
                #f5f7ff;
        }

        .font-urbanist{
            font-family:'Urbanist',sans-serif;
        }

        .glass{
            background:rgba(255,255,255,.78);
            backdrop-filter:blur(18px);
            border:1px solid rgba(255,255,255,.8);
            box-shadow:0 20px 60px rgba(99,102,241,.12);
        }

        .input{
            width:100%;
            height:56px;
            border-radius:18px;
            border:1px solid #dbe2f0;
            padding:0 20px;
            outline:none;
            transition:.2s;
        }

        .input:focus{
            border-color:#6366f1;
            box-shadow:0 0 0 4px rgba(99,102,241,.12);
        }

        .btn-gradient{
            background:linear-gradient(to right,#6366f1,#9333ea);
        }

        .gradient-text{
            background:linear-gradient(to right,#6366f1,#9333ea);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
    </style>
</head>

<body>

<div class="min-h-screen flex items-center justify-center px-6 py-10">

    <div class="w-full max-w-5xl glass rounded-[36px] overflow-hidden grid lg:grid-cols-2">

        <!-- LEFT -->
        <div class="hidden lg:flex bg-gradient-to-br from-indigo-500 via-violet-500 to-purple-600 p-14 text-white">

            <div class="flex flex-col justify-between h-full">

                <div>
                    <h1 class="text-5xl font-black font-urbanist">
                        StudyPro
                    </h1>

                    <h2 class="mt-16 text-6xl leading-[1.05] font-black font-urbanist">
                        Mulai Perjalanan Produktifmu.
                    </h2>

                    <p class="mt-8 text-lg text-white/80 leading-relaxed">
                        Satu platform AI untuk bantu mahasiswa mengatur tugas,
                        deadline, dan aktivitas kuliah lebih terorganisir.
                    </p>
                </div>

                <div class="bg-white/10 backdrop-blur-md rounded-[28px] p-6 border border-white/20">
                    <p class="text-white/70 text-sm">
                        AI Productivity Boost
                    </p>

                    <h3 class="text-5xl font-black mt-3">
                        +10x
                    </h3>

                    <p class="text-white/80 mt-2">
                        Lebih produktif setiap semester
                    </p>
                </div>

            </div>

        </div>

        <!-- RIGHT -->
        <div class="bg-white px-8 py-10 lg:px-16 lg:py-14 flex items-center">

            <div class="w-full">

                <div class="lg:hidden text-center mb-10">
                    <h1 class="text-5xl font-black font-urbanist text-slate-900">
                        Study<span class="gradient-text">Pro</span>
                    </h1>
                </div>

                <div class="mb-10">
                    <h2 class="text-5xl font-black font-urbanist text-slate-900">
                        Create Account
                    </h2>

                    <p class="mt-3 text-slate-500 text-lg">
                        Buat akun untuk mulai menggunakan StudyPro AI.
                    </p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block mb-2 font-bold text-slate-700">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            class="input"
                            placeholder="Nama lengkap"
                        >

                        @error('name')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 font-bold text-slate-700">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            class="input"
                            placeholder="nama@kampus.ac.id"
                        >

                        @error('email')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 font-bold text-slate-700">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            required
                            class="input"
                            placeholder="••••••••"
                        >

                        @error('password')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 font-bold text-slate-700">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            required
                            class="input"
                            placeholder="••••••••"
                        >
                    </div>

                    <button
                        type="submit"
                        class="btn-gradient w-full h-14 rounded-2xl text-white font-black text-lg shadow-xl shadow-indigo-500/20 hover:scale-[1.02] transition">
                        CREATE ACCOUNT
                    </button>

                    <p class="text-center text-slate-500 pt-4">
                        Sudah punya akun?

                        <a href="{{ route('login') }}"
                           class="font-bold text-indigo-600 hover:text-indigo-800">
                            Login
                        </a>
                    </p>

                </form>

            </div>

        </div>

    </div>

</div>

</body>
</html>