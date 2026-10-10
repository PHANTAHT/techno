<?php

namespace App\Console\Commands;

use App\Models\GoogleToken;
use App\Models\MoodleConfig;
use App\Models\Task;
use App\Services\MoodleHttp;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Classroom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class SyncAllUserTasks extends Command
{
    protected $signature = 'tasks:sync-all';

    protected $description = 'Auto sync Classroom & Moodle tasks for all users';

    public function handle()
    {
        $this->info('['.now().'] Memulai sync semua user...');

        $synced = 0;
        $errors = 0;

        // ── Sync Google Classroom untuk semua user ──────────────────
        GoogleToken::all()->each(function ($token) use (&$synced, &$errors) {
            try {
                $this->syncClassroomForUser($token);
                $synced++;
                $this->info("✅ Classroom synced: user_id {$token->user_id}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Classroom error user_id {$token->user_id}: ".$e->getMessage());
            }
        });

        // ── Sync Moodle untuk semua user ───────────────────────────
        MoodleConfig::all()->each(function ($config) use (&$synced, &$errors) {
            try {
                $this->syncMoodleForUser($config);
                $synced++;
                $this->info("✅ Moodle synced: user_id {$config->user_id}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Moodle error user_id {$config->user_id}: ".$e->getMessage());
            }
        });

        $this->info('['.now()."] Selesai. Berhasil: {$synced}, Error: {$errors}");

        return Command::SUCCESS;
    }

    // ── Sync Classroom untuk 1 user ─────────────────────────────────
    private function syncClassroomForUser(GoogleToken $googleToken)
    {
        $client = $this->buildGoogleClient($googleToken);
        $service = new Classroom($client);
        $userId = $googleToken->user_id;

        $courses = $service->courses->listCourses(['courseStates' => ['ACTIVE']]);
        if (! $courses->getCourses()) {
            return;
        }

        foreach ($courses->getCourses() as $course) {
            $courseWork = $service->courses_courseWork->listCoursesCourseWork($course->getId());
            if (! $courseWork->getCourseWork()) {
                continue;
            }

            foreach ($courseWork->getCourseWork() as $work) {
                $deadline = $this->parseClassroomDeadline($work);

                // Skip overdue > 60 hari
                if ($deadline && $deadline->lessThan(now()->subDays(60))) {
                    continue;
                }

                // Cek submission status
                $isSubmitted = null;
                try {
                    $subs = $service->courses_courseWork_studentSubmissions
                        ->listCoursesCourseWorkStudentSubmissions(
                            $course->getId(), $work->getId(),
                            ['userId' => 'me']
                        )->getStudentSubmissions();
                    $isSubmitted = false;

                    if ($subs && in_array($subs[0]->getState(), ['TURNED_IN', 'RETURNED'])) {
                        $isSubmitted = true;
                    }
                } catch (\Exception $e) {
                }

                Task::updateOrCreate(
                    ['source' => 'classroom', 'source_id' => $work->getId(), 'user_id' => $userId],
                    [
                        'title' => $work->getTitle(),
                        'description' => $work->getDescription() ?? 'Tugas dari Google Classroom',
                        'course_name' => $course->getName(),
                        'deadline' => $deadline,
                        'priority' => 'high',
                        'status' => $isSubmitted === null ? (Task::where('user_id', $userId)->where('source', 'classroom')->where('source_id', $work->getId())->value('status') ?? 'pending') : ($isSubmitted ? 'done' : 'pending'),
                        'url' => $work->getAlternateLink(),
                    ]
                );
            }
        }
    }

    // ── Sync Moodle untuk 1 user ────────────────────────────────────
    private function syncMoodleForUser(MoodleConfig $config)
    {
        $userId = $config->user_id;
        $moodleUrl = $config->moodle_url;
        $token = $config->token;

        $response = app(MoodleHttp::class)->client("{$moodleUrl}/webservice/rest/server.php")->get("{$moodleUrl}/webservice/rest/server.php", [
            'wstoken' => $token,
            'wsfunction' => 'mod_assign_get_assignments',
            'moodlewsrestformat' => 'json',
        ]);

        $data = $response->json();
        if (! $response->successful() || ! is_array($data['courses'] ?? null) || Validator::make($data, [
            'courses.*.id' => 'required',
            'courses.*.fullname' => 'required|string',
            'courses.*.assignments' => 'present|array',
            'courses.*.assignments.*.id' => 'required',
            'courses.*.assignments.*.name' => 'required|string',
            'courses.*.assignments.*.duedate' => 'required|integer|min:0',
            'courses.*.assignments.*.cmid' => 'required',
        ])->fails()) {
            throw new \RuntimeException('Respons Moodle gagal atau tidak lengkap; tugas lama tetap disimpan.');
        }

        $syncedIds = [];

        foreach ($data['courses'] ?? [] as $course) {
            foreach ($course['assignments'] ?? [] as $assignment) {
                $dueDate = $assignment['duedate'];

                // Skip overdue > 60 hari
                if ($dueDate && $dueDate < strtotime('-60 days')) {
                    continue;
                }

                $syncedIds[] = (string) $assignment['id'];
                $taskStatus = Task::where('user_id', $userId)->where('source', 'moodle')
                    ->where('source_id', (string) $assignment['id'])->value('status') ?? 'pending';

                // Cek submission status
                try {
                    usleep(200000);
                    $statusResponse = app(MoodleHttp::class)->client("{$moodleUrl}/webservice/rest/server.php")->get("{$moodleUrl}/webservice/rest/server.php", [
                        'wstoken' => $token,
                        'wsfunction' => 'mod_assign_get_submission_status',
                        'moodlewsrestformat' => 'json',
                        'assignid' => $assignment['id'],
                    ]);

                    if ($statusResponse->successful()) {
                        $moodleStatus = $statusResponse->json()['lastattempt']['submission']['status'] ?? null;
                        if ($moodleStatus !== null) {
                            $taskStatus = $moodleStatus === 'submitted' ? 'done' : 'pending';
                        }
                    }
                } catch (\Exception $e) {
                }

                $fileUrl = null;
                if (! empty($assignment['introattachments'])) {
                    $fileUrl = $assignment['introattachments'][0]['fileurl'].(str_contains($assignment['introattachments'][0]['fileurl'], '?') ? '&' : '?').'token='.rawurlencode($token);
                }

                Task::updateOrCreate(
                    ['user_id' => $userId, 'source' => 'moodle', 'source_id' => (string) $assignment['id']],
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
            }
        }

        // Hapus task Moodle lama yang tidak ada lagi
        if (! empty($syncedIds)) {
            Task::where('user_id', $userId)
                ->where('source', 'moodle')
                ->whereNotIn('source_id', $syncedIds)
                ->delete();
        }
    }

    // ── Helper: build Google Client dari token tersimpan ───────────
    private function buildGoogleClient(GoogleToken $googleToken): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope(Classroom::CLASSROOM_COURSES_READONLY);
        $client->addScope(Classroom::CLASSROOM_COURSEWORK_ME_READONLY);
        $client->addScope(Classroom::CLASSROOM_COURSEWORK_STUDENTS_READONLY);
        $client->setAccessType('offline');

        $tokenData = json_decode($googleToken->access_token, true);
        $client->setAccessToken($tokenData);

        // Refresh kalau expired
        if ($client->isAccessTokenExpired() && $googleToken->refresh_token) {
            $client->fetchAccessTokenWithRefreshToken($googleToken->refresh_token);

            // Simpan token baru
            $googleToken->update([
                'access_token' => json_encode($client->getAccessToken()),
                'expires_at' => now()->addSeconds(3600),
            ]);
        }

        return $client;
    }

    // ── Helper: parse deadline dari Classroom work ─────────────────
    private function parseClassroomDeadline($work): ?Carbon
    {
        if (! $work->getDueDate() || ! $work->getDueDate()->getYear()) {
            return null;
        }

        $dueDate = $work->getDueDate();
        $dueTime = $work->getDueTime();

        $year = $dueDate->getYear();
        $month = str_pad($dueDate->getMonth(), 2, '0', STR_PAD_LEFT);
        $day = str_pad($dueDate->getDay(), 2, '0', STR_PAD_LEFT);
        $hours = $dueTime ? str_pad($dueTime->getHours(), 2, '0', STR_PAD_LEFT) : '23';
        $minutes = $dueTime ? str_pad($dueTime->getMinutes(), 2, '0', STR_PAD_LEFT) : '59';

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$year}-{$month}-{$day} {$hours}:{$minutes}:00",
            'UTC'
        )->setTimezone(config('app.timezone'));
    }
}
