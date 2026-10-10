@extends('layouts.app')

@section('title', isset($task) ? 'Edit Tugas' : 'Tambah Tugas Manual')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">{{ isset($task) ? 'Edit Tugas' : 'Tambah Tugas Baru' }}</h2>
        <p class="text-gray-500">{{ isset($task) ? 'Perbarui detail dan lampiran tugasmu.' : 'Catat tugas manualmu di luar Moodle di sini.' }}</p>
    </div>
    <a href="{{ route('tasks.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition font-semibold">
        ⬅ Batal & Kembali
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 max-w-3xl">
    @if($errors->any())
        <ul class="mb-5 text-sm text-red-600">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif
    <form action="{{ isset($task) ? route('tasks.update', $task) : route('tasks.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @isset($task) @method('PATCH') @endisset
        
        <div class="mb-5">
            <label class="block text-gray-700 font-semibold mb-2">Judul Tugas <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $task->title ?? '') }}" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Contoh: Bikin Makalah Sistem Operasi">
        </div>
        
        <div class="mb-5">
            <label class="block text-gray-700 font-semibold mb-2">Mata Kuliah</label>
            <input type="text" name="course_name" value="{{ old('course_name', $task->course_name ?? '') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Contoh: Sistem Operasi (Opsional)">
        </div>

        <div class="mb-5">
            <label class="block text-gray-700 font-semibold mb-2">Tenggat Waktu (Deadline)</label>
            <input type="datetime-local" name="deadline" value="{{ old('deadline', isset($task) ? $task->deadline?->format('Y-m-d\TH:i') : '') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 font-semibold mb-2">Deskripsi / Catatan</label>
            <textarea name="description" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Catatan tambahan untuk tugas ini...">{{ old('description', isset($task) ? strip_tags($task->description ?? '') : '') }}</textarea>
        </div>
        @isset($task)
        <div class="grid grid-cols-2 gap-4 mb-6">
            <label>Status
                <select name="status" class="block w-full rounded-lg border-gray-300">
                    @foreach(['pending' => 'Belum dikerjakan', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Prioritas
                <select name="priority" class="block w-full rounded-lg border-gray-300">
                    @foreach(['urgent' => 'Mendesak', 'high' => 'Tinggi', 'medium' => 'Sedang', 'low' => 'Rendah'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $task->priority) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        @if($task->file_path)
            <label class="block mb-5 text-sm"><input type="checkbox" name="remove_file" value="1"> Hapus lampiran: {{ $task->file_name }}</label>
        @endif
        @endisset
        <div class="mb-6">
        <label class="block text-gray-700 font-semibold mb-2">File Lampiran <span class="text-gray-400 font-normal text-sm">(Opsional)</span></label>
        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-violet-400 transition">
            <input type="file" name="attachment" id="attachment"
                accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.txt,.csv"
                class="hidden"
                onchange="showFileName(this)">
            <label for="attachment" class="cursor-pointer">
                <div class="text-3xl mb-2">📎</div>
                <p class="text-sm font-semibold text-gray-600">Klik untuk upload file</p>
                <p class="text-xs text-gray-400 mt-1">PDF, DOCX, PNG, JPG, TXT, CSV — Maks. 10MB</p>
            </label>
            <p id="fileName" class="hidden mt-3 text-sm text-violet-600 font-semibold"></p>
        </div>
    </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-green-700 transition shadow-sm flex items-center gap-2">
                {{ isset($task) ? 'Simpan Perubahan' : 'Simpan Tugas Manual' }}
            </button>
        </div>
    </form>
</div>
<script>
function showFileName(input) {
    const label = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        label.textContent = '✅ ' + input.files[0].name;
        label.classList.remove('hidden');
    }
}
</script>
@endsection
