<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\GoogleToken;
use App\Models\MoodleConfig;
use App\Models\Task;
use Google\Service\Calendar;
use Google\Service\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $googleConnected = GoogleToken::where('user_id', $user->id)->exists();
        $moodleConfig = MoodleConfig::where('user_id', $user->id)->first();

        // Ambil settings dari JSON di user (atau default)
        $settings = json_decode($user->settings ?? '{}', true);
        $aiSettings = $settings['ai'] ?? [];
        $notifSettings = $settings['notifications'] ?? [];
        $appearanceSettings = $settings['appearance'] ?? [];
        $currentModel = config('services.openrouter.model');

        return view('settings.index', compact(
            'googleConnected', 'moodleConfig',
            'aiSettings', 'notifSettings', 'appearanceSettings',
            'currentModel'
        ));
    }

    // ── Update Profil ───────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.Auth::id(),
        ]);

        $user = Auth::user();
        if ($user->email !== $request->email) {
            $user->email_verified_at = null;
        }
        $user->fill([
            'name' => $request->name,
            'email' => $request->email,
        ])->save();

        return back()->with('success', 'Profil berhasil diupdate!');
    }

    // ── Update Password ─────────────────────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diupdate!');
    }

    // ── Update AI Settings ──────────────────────────────────────────
    public function updateAI(Request $request)
    {
        $user = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['ai'] = [
            'language' => $request->input('ai_language', 'id'),
            'ai_auto_summarize' => $request->boolean('ai_auto_summarize'),
            'ai_task_reminder' => $request->boolean('ai_task_reminder'),
            'ai_context_aware' => $request->boolean('ai_context_aware'),
            'model' => $request->input('ai_model'),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return back()->with('success', 'Pengaturan AI disimpan!');
    }

    // ── Update Notifikasi ───────────────────────────────────────────
    public function updateNotifications(Request $request)
    {
        $user = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['notifications'] = [
            'notif_deadline_1day' => $request->boolean('notif_deadline_1day'),
            'notif_deadline_3days' => $request->boolean('notif_deadline_3days'),
            'notif_deadline_1week' => $request->boolean('notif_deadline_1week'),
            'notif_new_task' => $request->boolean('notif_new_task'),
            'notif_email_important' => $request->boolean('notif_email_important'),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return back()->with('success', 'Pengaturan notifikasi disimpan!');
    }

    // ── Update Tampilan ─────────────────────────────────────────────
    public function updateAppearance(Request $request)
    {
        $user = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['appearance'] = [
            'theme' => $request->input('theme', 'light'),
            'task_view' => $request->input('task_view', 'list'),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return back()->with('success', 'Preferensi tampilan disimpan!');
    }

    // ── Hapus Semua Riwayat Chat ────────────────────────────────────
    public function clearChat()
    {
        $sessions = ChatSession::where('user_id', Auth::id())->get();
        foreach ($sessions as $session) {
            $session->messages()->delete();
        }
        ChatSession::where('user_id', Auth::id())->delete();

        return back()->with('success', 'Semua riwayat chat berhasil dihapus!');
    }

    // ── Hapus Task Manual ───────────────────────────────────────────
    public function clearManualTasks()
    {
        Task::where('user_id', Auth::id())
            ->where('source', 'manual')
            ->delete();

        return back()->with('success', 'Semua task manual berhasil dihapus!');
    }

    // ── Hapus Akun ──────────────────────────────────────────────────
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        // Hapus semua data user
        Task::where('user_id', $user->id)->delete();
        ChatSession::where('user_id', $user->id)->each(function ($s) {
            $s->messages()->delete();
            $s->delete();
        });
        GoogleToken::where('user_id', $user->id)->delete();
        MoodleConfig::where('user_id', $user->id)->delete();

        Auth::logout();
        $user->delete();

        return redirect('/')->with('success', 'Akun berhasil dihapus.');
    }

    // ── Sinkronisasi Google (Classroom & Calendar) ──────────────────
    public function syncGoogle()
    {
        return app(GoogleController::class)->syncAll();
    }
}
