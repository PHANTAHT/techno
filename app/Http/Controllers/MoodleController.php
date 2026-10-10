<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\MoodleHttp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MoodleController extends Controller
{
    /**
     * 1. PROSES LOGIN & AMBIL TOKEN DARI MOODLE
     * Fungsi ini dipanggil dari form Moodle di halaman Settings
     */
    public function setup(Request $request)
    {
        $request->validate([
            'moodle_url' => 'required|url',
            'moodle_username' => 'required',
            'moodle_password' => 'required',
        ]);

        // Hapus garis miring di akhir URL jika user iseng mengetiknya
        $baseUrl = rtrim($request->moodle_url, '/');

        // Request Token ke Moodle Lenteraku
        try {
            $response = app(MoodleHttp::class)->client($baseUrl.'/login/token.php')->asForm()->post($baseUrl.'/login/token.php', [
                'username' => $request->moodle_username,
                'password' => $request->moodle_password,
                'service' => 'moodle_mobile_app',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Tidak dapat menghubungi server Moodle. Periksa URL dan koneksi.');
        }
        $data = $response->json();

        // Jika Moodle menolak login (password/username salah)
        if (isset($data['error'])) {
            return back()->with('error', 'Gagal menghubungkan: Username atau Password Lenteraku salah.');
        }

        // Jika berhasil dapat token, simpan ke Database (JANGAN SIMPAN PASSWORD)
        if (isset($data['token'])) {
            Auth::user()->moodleConfig()->updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'moodle_url' => $baseUrl,
                    'token' => $data['token'],
                ]
            );

            return back()->with('success', 'Moodle berhasil dihubungkan! Klik Sync untuk menarik tugas.');
        }

        return back()->with('error', 'Terjadi kesalahan saat mengambil token dari server kampus.');
    }

    /**
     * 2. SINKRONISASI TUGAS
     * Fungsi ini dipanggil ketika user menekan tombol "Sync Tugas"
     */
    public function sync()
    {
        // 1. PERPANJANG BATAS WAKTU EKSEKUSI (Beri waktu 5 menit / 300 detik)
        set_time_limit(300);

        $moodleConfig = Auth::user()->moodleConfig;

        if (! $moodleConfig || ! $moodleConfig->moodle_url || ! $moodleConfig->token) {
            return redirect('/settings')->with('error', 'Kamu belum menghubungkan Moodle.');
        }

        $moodleUrl = $moodleConfig->moodle_url;
        $token = $moodleConfig->token;

        try {
            $response = app(MoodleHttp::class)->client("{$moodleUrl}/webservice/rest/server.php")->get("{$moodleUrl}/webservice/rest/server.php", [
                'wstoken' => $token,
                'wsfunction' => 'mod_assign_get_assignments',
                'moodlewsrestformat' => 'json',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi Moodle gagal; tugas lama tetap disimpan.');
        }
        $data = $response->json();

        if (isset($data['exception'])) {
            // Keep configuration on transient permission/server errors.
            return redirect('/settings')->with('error', 'Sesi Moodle berakhir. Silakan hubungkan ulang.');
        }

        if (! $response->successful() || ! is_array($data['courses'] ?? null) || Validator::make($data, [
            'courses.*.id' => 'required',
            'courses.*.fullname' => 'required|string',
            'courses.*.assignments' => 'present|array',
            'courses.*.assignments.*.id' => 'required',
            'courses.*.assignments.*.name' => 'required|string',
            'courses.*.assignments.*.duedate' => 'required|integer|min:0',
            'courses.*.assignments.*.cmid' => 'required',
        ])->fails()) {
            return back()->with('error', 'Respons Moodle gagal atau tidak lengkap; tugas lama tetap disimpan.');
        }

        $count = 0;
        $syncedTaskIds = [];

        foreach ($data['courses'] ?? [] as $course) {
            foreach ($course['assignments'] ?? [] as $assignment) {

                $assignId = $assignment['id'];
                $dueDate = $assignment['duedate'];

                // 2. OPTIMASI: Abaikan tugas yang deadlinenya sudah lewat lebih dari 60 hari
                if ($dueDate && $dueDate < strtotime('-60 days')) {
                    continue; // Langsung lompat ke tugas berikutnya di loop
                }

                $syncedTaskIds[] = (string) $assignId;
                $taskStatus = Task::where('user_id', Auth::id())->where('source', 'moodle')
                    ->where('source_id', (string) $assignId)->value('status') ?? 'pending';

                // --- CEK STATUS PENGUMPULAN ---
                // Menggunakan try-catch dan usleep agar tidak di-banned server kampus
                try {
                    // Beri jeda 0.2 detik (200.000 microsecond) biar server kampus nggak mengira kita nge-spam
                    usleep(200000);

                    // Tambahkan timeout agar kalau server kampus lemot, gak bikin nyangkut kelamaan
                    $statusResponse = app(MoodleHttp::class)->client("{$moodleUrl}/webservice/rest/server.php")->get("{$moodleUrl}/webservice/rest/server.php", [
                        'wstoken' => $token,
                        'wsfunction' => 'mod_assign_get_submission_status',
                        'moodlewsrestformat' => 'json',
                        'assignid' => $assignId,
                    ]);

                    if ($statusResponse->successful()) {
                        $statusData = $statusResponse->json();
                        $moodleStatus = $statusData['lastattempt']['submission']['status'] ?? null;
                        if ($moodleStatus !== null) {
                            $taskStatus = $moodleStatus === 'submitted' ? 'done' : 'pending';
                        }
                    }
                } catch (\Exception $e) {
                    // Kalau koneksi tiba-tiba diputus kampus, biarkan saja status tugas ini 'pending'
                    // Lanjut proses tugas berikutnya agar tidak error 500
                }

                // --- CEK FILE ---
                $fileUrl = null;
                if (! empty($assignment['introattachments']) && count($assignment['introattachments']) > 0) {
                    $attachment = $assignment['introattachments'][0];
                    $fileUrl = $attachment['fileurl'].(str_contains($attachment['fileurl'], '?') ? '&' : '?').'token='.rawurlencode($token);
                }

                // --- SIMPAN KE DATABASE ---
                Task::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'source' => 'moodle',
                        'source_id' => (string) $assignId,
                    ],
                    [
                        'title' => $assignment['name'],
                        'description' => strip_tags($assignment['intro'] ?? ''),
                        'deadline' => $dueDate ? Carbon::createFromTimestamp($dueDate)->setTimezone(config('app.timezone')) : null,
                        'course_name' => $course['fullname'],
                        'url' => "{$moodleUrl}/mod/assign/view.php?id={$assignment['cmid']}",
                        'file_url' => $fileUrl,
                        'priority' => 'medium',
                        'status' => $taskStatus,
                    ]
                );
                $count++;
            }
        }

        // Hapus tugas Moodle lama yang tidak ada di $syncedTaskIds
        Task::where('user_id', Auth::id())
            ->where('source', 'moodle')
            ->whereNotIn('source_id', $syncedTaskIds)
            ->delete();

        return redirect('/tasks')->with('success', "Sinkronisasi selesai! $count tugas berhasil di-sync.");
    }
}
