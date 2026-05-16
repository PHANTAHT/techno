@extends('layouts.app')

@section('title', 'Daftar Tugas')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800">Daftar Tugas Aktif</h2>
    <p class="text-gray-500">Kelola semua tugas dari Moodle maupun tugas manualmu di sini.</p>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-gray-800">● Tabel Tugas Pending</h3>
        <a href="/tasks/create" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
            + Tambah Tugas
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm border-b">
                    <th class="p-4 font-semibold rounded-tl-lg">Tugas</th>
                    <th class="p-4 font-semibold">Mata Kuliah</th>
                    <th class="p-4 font-semibold">Deadline</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-center rounded-tr-lg">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 font-medium">
                            <div class="flex flex-col items-start gap-1">
                                <a href="{{ route('tasks.show', $task->id) }}" class="text-blue-600 hover:text-blue-800 hover:underline transition text-base">
                                    {{ $task->title }}
                                </a>
                                @if(strtolower($task->source) == 'classroom')
                                    <span class="px-2 py-0.5 bg-yellow-50 border border-yellow-200 text-yellow-700 text-[10px] font-bold rounded uppercase tracking-wider">
                                        CLASSROOM
                                    </span>
                                @elseif(strtolower($task->source) == 'moodle')
                                    <span class="px-2 py-0.5 bg-blue-50 border border-blue-200 text-blue-600 text-[10px] font-bold rounded uppercase tracking-wider">
                                        MOODLE
                                    </span>
                                @elseif(strtolower($task->source) == 'calendar')
                                    <span class="px-2 py-0.5 bg-purple-50 border border-purple-200 text-purple-600 text-[10px] font-bold rounded uppercase tracking-wider">
                                        CALENDAR
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-600 text-[10px] font-bold rounded uppercase tracking-wider">
                                        {{ strtoupper($task->source ?? 'MANUAL') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="p-4 text-gray-600">{{ $task->course_name ?? '-' }}</td>
                        <td class="p-4 text-gray-600">
                            {{ $task->deadline ? $task->deadline->format('d M Y, H:i') : 'Tidak ada' }}
                        </td>
                        <td class="p-4">
                            @if($task->isOverdue())
                                <span class="inline-block w-24 py-1.5 text-center bg-red-100 text-red-800 text-xs font-bold rounded-full">
                                    OVERDUE
                                </span>
                            @else
                                <span class="inline-block w-24 py-1.5 text-center bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                                    PENDING
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            {{-- Tombol Mark as Done --}}
                            <form action="{{ route('tasks.complete', $task->id) }}" method="POST" class="m-0 flex justify-center">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="group flex items-center gap-1.5 text-gray-500 bg-white border border-gray-300 hover:bg-green-500 hover:text-white hover:border-green-500 font-semibold px-3 py-1.5 rounded-lg text-xs transition-all" title="Tandai Selesai">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Mark as done
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400">
                            Belum ada tugas aktif. Santai dulu! 🎉
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection