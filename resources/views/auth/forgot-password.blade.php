<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Forgot Password — StudyPro</title>

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

<div class="min-h-screen flex items-center justify-center px-6">

    <div class="w-full max-w-xl glass rounded-[36px] p-10 lg:p-14">

        <div class="text-center mb-10">

            <h1 class="text-5xl font-black font-urbanist text-slate-900">
                Study<span class="gradient-text">Pro</span>
            </h1>

            <h2 class="mt-8 text-4xl font-black font-urbanist text-slate-900">
                Forgot Password?
            </h2>

            <p class="mt-4 text-slate-500 leading-relaxed">
                Masukkan email akunmu dan kami akan mengirim link reset password.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-2xl bg-green-50 border border-green-200 text-green-700 px-5 py-4 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block mb-2 font-bold text-slate-700">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="input"
                    placeholder="nama@kampus.ac.id"
                >

                @error('email')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="btn-gradient w-full h-14 rounded-2xl text-white font-black text-lg shadow-xl shadow-indigo-500/20 hover:scale-[1.02] transition">
                SEND RESET LINK
            </button>

            <div class="text-center pt-3">
                <a href="{{ route('login') }}"
                   class="font-bold text-indigo-600 hover:text-indigo-800">
                    ← Back to Login
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>