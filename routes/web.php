<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MoodleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return view('welcome');
});

// 🟢 INI DIKELUARKAN DARI MIDDLEWARE AGAR BISA DIPAKAI LOGIN
Route::get('/google/redirect', [GoogleController::class, 'redirect']);
Route::get('/google/callback', [GoogleController::class, 'callback']);

// 🔴 INI WAJIB LOGIN
// 🔴 INI WAJIB LOGIN
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', function () {
        // Ambil semua tugas milik user yang sedang login
        $tasks = \App\Models\Task::where('user_id', auth()->id())->get();
        return view('calendar.index', compact('tasks'));
    })->name('calendar');
    Route::get('/google/sync-classroom', [App\Http\Controllers\GoogleController::class, 'syncClassroom'])->name('google.sync-classroom');
    Route::resource('tasks', TaskController::class);
    Route::get('/google/email/{id}', [GoogleController::class, 'getEmailDetail'])->name('google.email.detail');
    Route::post('/google/add-task-calendar/{id}', [GoogleController::class, 'addToCalendar'])->name('google.add_calendar');
    // 👇 Tambahkan rute ini untuk tombol Mark as Done
    Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::get('/emails', [GoogleController::class, 'indexEmails'])->name('emails.index');
    // Sync Calendar tetap butuh login
    Route::get('/google/sync-calendar', [GoogleController::class, 'syncCalendar']);
    Route::post('/ai/chat', [AIController::class, 'chat'])->middleware('auth');
    Route::post('/ai/explain-task', [AIController::class, 'explainTask'])->middleware('auth');
    Route::post('/ai/summarize-email', [AIController::class, 'summarizeEmail'])->name('ai.summarize');
    Route::post('/ai/new-session', [AIController::class, 'newSession']);
    Route::get('/ai/sessions', [AIController::class, 'sessions']);
    Route::get('/ai/session/{id}', [AIController::class, 'getSession']);
    Route::post('/ai/summarize-file/{task}', [AIController::class, 'summarizeTaskFile']);
    Route::delete('/ai/session/{id}', [AIController::class, 'deleteSession']);
    // Moodle Integrations
    Route::get('/moodle/setup', [MoodleController::class, 'setup']);
    Route::post('/moodle/setup', [MoodleController::class, 'setup']);
    Route::get('/moodle/sync', [MoodleController::class, 'sync']);

    // ── Settings ──────────────────────────────────────────────────────
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile',       [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/password',      [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/sync-google', [SettingsController::class, 'syncGoogle'])->name('settings.sync.google');
    Route::get('/api/emails/latest', [GoogleController::class, 'apiLatestEmails']);
    Route::get('/api/tasks/latest',  [DashboardController::class, 'apiLatestTasks']);
    Route::get('/reset-tasks', function () {
    // Menghapus SEMUA tugas yang ada di tabel tasks agar kembali kosong & bersih
    \App\Models\Task::truncate(); 
    return redirect('/dashboard')->with('success', 'Data lama berhasil dibersihkan! Silakan Sync ulang.');
});
    });

require __DIR__.'/auth.php';