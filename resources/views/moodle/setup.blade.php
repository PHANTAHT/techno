@extends('layouts.app')

@section('title', 'Hubungkan Moodle / Lentera')

@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa fa-graduation-cap"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 font-urbanist">Hubungkan ke Moodle Kampus</h2>
            <p class="text-gray-500 mt-2 text-sm">Masukkan URL Lentera dan Token API kamu agar StudyPro bisa menarik deadline tugas secara otomatis.</p>
        </div>

        @if(session('error'))
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="/moodle/setup" method="POST">
            @csrf
            
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL Moodle / Lentera</label>
                <input type="url" name="moodle_url" placeholder="https://cloud.lentera.petra.ac.id" 
                       value="{{ $config->moodle_url ?? 'https://cloud.lentera.petra.ac.id' }}" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Web Services Token</label>
                <input type="text" name="token" placeholder="Contoh: 8416968a27b587c7e273e50ae8990144" 
                       value="{{ $config->token ?? '' }}" required
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition font-mono text-sm">
                <p class="text-xs text-gray-400 mt-2">Dapatkan token ini dari menu Preferences > Security Keys di akun Lentera-mu.</p>
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition duration-200 transform active:scale-95">
                Simpan & Sinkronisasi
            </button>
        </form>
    </div>
</div>
@endsection