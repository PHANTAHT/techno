<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ChatSession;
use App\Models\GoogleToken;
use App\Models\MoodleConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Google\Client;
use Google\Service\Classroom;
use Google\Service\Calendar;
use Carbon\Carbon;

class SettingsController extends Controller
{
    public function index()
    {
        $user           = Auth::user();
        $googleConnected = GoogleToken::where('user_id', $user->id)->exists();
        $moodleConfig   = MoodleConfig::where('user_id', $user->id)->first();

        // Ambil settings dari JSON di user (atau default)
        $settings           = json_decode($user->settings ?? '{}', true);
        $aiSettings         = $settings['ai']           ?? [];
        $notifSettings      = $settings['notifications'] ?? [];
        $appearanceSettings = $settings['appearance']    ?? [];
        $currentModel       = env('OPENROUTER_MODEL_ID', 'google/gemini-flash-1.5');

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
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Profil berhasil diupdate!');
    }

    // ── Update Password ─────────────────────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diupdate!');
    }

    // ── Update AI Settings ──────────────────────────────────────────
    public function updateAI(Request $request)
    {
        $user     = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['ai'] = [
            'language'         => $request->input('ai_language', 'id'),
            'ai_auto_summarize'=> $request->boolean('ai_auto_summarize'),
            'ai_task_reminder' => $request->boolean('ai_task_reminder'),
            'ai_context_aware' => $request->boolean('ai_context_aware'),
            'model'            => $request->input('ai_model'),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return back()->with('success', 'Pengaturan AI disimpan!');
    }

    // ── Update Notifikasi ───────────────────────────────────────────
    public function updateNotifications(Request $request)
    {
        $user     = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['notifications'] = [
            'notif_deadline_1day'  => $request->boolean('notif_deadline_1day'),
            'notif_deadline_3days' => $request->boolean('notif_deadline_3days'),
            'notif_deadline_1week' => $request->boolean('notif_deadline_1week'),
            'notif_new_task'       => $request->boolean('notif_new_task'),
            'notif_email_important'=> $request->boolean('notif_email_important'),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return back()->with('success', 'Pengaturan notifikasi disimpan!');
    }

    // ── Update Tampilan ─────────────────────────────────────────────
    public function updateAppearance(Request $request)
    {
        $user     = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['appearance'] = [
            'theme'     => $request->input('theme', 'light'),
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
        $user = Auth::user();
        $googleToken = GoogleToken::where('user_id', $user->id)->first();

        // Cek apakah user sudah menghubungkan akun Google
        if (!$googleToken) {
            return back()->with('error', 'Akun Google belum terhubung. Silakan login Google terlebih dahulu.');
        }

        // Inisialisasi Google Client
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken(json_decode($googleToken->access_token, true));

        // Refresh token jika sudah expired
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                // Simpan token baru ke database
                $googleToken->update([
                    'access_token' => json_encode($client->getAccessToken())
                ]);
            } else {
                return redirect('/google/redirect')->with('error', 'Sesi Google expired. Silakan hubungkan ulang.');
            }
        }

        try {
            // --- 1. SYNC GOOGLE CLASSROOM ---
            $serviceClassroom = new Classroom($client);
            $courses = $serviceClassroom->courses->listCourses(['courseStates' => ['ACTIVE']]);

            foreach ($courses->getCourses() as $course) {
                $courseWork = $serviceClassroom->courses_courseWork->listCoursesCourseWork($course->getId());
                
                foreach ($courseWork->getCourseWork() as $work) {
                    $deadline = null;

                    if ($work->getDueDate() && $work->getDueDate()->getYear()) {
                        $dueDate = $work->getDueDate();
                        $dueTime = $work->getDueTime();

                        $year = $dueDate->getYear();
                        $month = str_pad($dueDate->getMonth(), 2, '0', STR_PAD_LEFT);
                        $day = str_pad($dueDate->getDay(), 2, '0', STR_PAD_LEFT);
                        $hours = $dueTime ? str_pad($dueTime->getHours(), 2, '0', STR_PAD_LEFT) : '23';
                        $minutes = $dueTime ? str_pad($dueTime->getMinutes(), 2, '0', STR_PAD_LEFT) : '59';

                        $deadlineString = "{$year}-{$month}-{$day} {$hours}:{$minutes}:00";
                        $deadline = Carbon::createFromFormat('Y-m-d H:i:s', $deadlineString, 'UTC')
                                          ->setTimezone(config('app.timezone'));
                    }

                    Task::updateOrCreate(
                        ['source' => 'classroom', 'source_id' => $work->getId(), 'user_id' => $user->id],
                        [
                            'title' => $work->getTitle(),
                            'description' => $work->getDescription() ?? 'Tugas dari Google Classroom',
                            'course_name' => $course->getName(),
                            'deadline' => $deadline,
                            'priority' => 'high',
                            'status' => 'pending',
                            'file_url' => $work->getAlternateLink()
                        ]
                    );
                }
            }

            // --- 2. SYNC GOOGLE CALENDAR ---
            $serviceCalendar = new Calendar($client);
            $events = $serviceCalendar->events->listEvents('primary', [
                'timeMin' => now()->toRfc3339String(),
                'timeMax' => now()->addDays(30)->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]);

            foreach ($events->getItems() as $event) {
                $deadline = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();

                Task::updateOrCreate(
                    ['source' => 'calendar', 'source_id' => $event->getId(), 'user_id' => $user->id],
                    [
                        'title' => $event->getSummary(),
                        'deadline' => $deadline,
                        'priority' => 'medium',
                        'status' => 'pending',
                    ]
                );
            }

            return back()->with('success', 'Google Classroom & Calendar berhasil disinkronisasi! 🎉');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi Google: ' . $e->getMessage());
        }
    }   
}