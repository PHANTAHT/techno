<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::where('user_id', Auth::id())
            ->where('status', '!=', 'done')
                     // Tampilkan task yang:
                     // - Tidak ada deadline, ATAU
                     // - Deadlinenya belum lewat, ATAU
                     // - Baru lewat max 7 hari (masih relevan)
            ->where(function ($q) {
                $q->whereNull('deadline')
                    ->orWhere('deadline', '>=', now()->subDays(60));
            })
            ->orderBy('deadline', 'asc')
            ->get();

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        return view('tasks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,png,jpg,jpeg,txt,csv',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['source'] = 'manual';
        $validated['status'] = 'pending';
        $validated['priority'] = 'medium';

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('task-files/'.auth()->id(), 'public');

            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        unset($validated['attachment']);
        Task::create($validated);

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil ditambahkan!');
    }

    public function show(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak');
        }

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        return view('tasks.create', compact('task'));
    }

    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'status' => 'nullable|in:pending,in_progress,done',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,png,jpg,jpeg,txt,csv',
        ]);

        if ($request->hasFile('attachment')) {
            if ($task->file_path) {
                Storage::disk('public')->delete($task->file_path);
            }

            $file = $request->file('attachment');
            $path = $file->store('task-files/'.auth()->id(), 'public');

            $validated['file_path'] = $path;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        if (! $request->hasFile('attachment') && $request->boolean('remove_file') && $task->file_path) {
            Storage::disk('public')->delete($task->file_path);
            $validated['file_path'] = null;
            $validated['file_name'] = null;
            $validated['file_type'] = null;
        }

        unset($validated['attachment']);
        $task->update($validated);

        return redirect()->route('tasks.show', $task)->with('success', 'Tugas berhasil diupdate!');
    }

    public function destroy(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }

        if ($task->file_path) {
            Storage::disk('public')->delete($task->file_path);
        }
        $task->delete();

        return redirect('/tasks')->with('success', 'Tugas dihapus!');
    }

    public function complete(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403);
        }
        $task->update(['status' => 'done']);

        return back()->with('success', 'Tugas berhasil ditandai selesai!');
    }
}
