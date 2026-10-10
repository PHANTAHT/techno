<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Classroom;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// <-- Tambahan Log

class GoogleController extends Controller
{
    private function getClient(): Client
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->addScope('https://www.googleapis.com/auth/calendar.events');
        $client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $client->addScope(Classroom::CLASSROOM_COURSES_READONLY);
        $client->addScope(Classroom::CLASSROOM_COURSEWORK_ME_READONLY);
        $client->addScope(Classroom::CLASSROOM_COURSEWORK_STUDENTS_READONLY);
        $client->addScope(Classroom::CLASSROOM_STUDENT_SUBMISSIONS_ME_READONLY);
        $client->addScope('email');
        $client->addScope('profile');

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    private function authenticatedClient(GoogleToken $token): Client
    {
        $client = $this->getClient();
        $data = json_decode($token->access_token, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Sesi Google tidak valid. Silakan hubungkan ulang.');
        }
        if (empty($data['refresh_token']) && $token->refresh_token) {
            $data['refresh_token'] = $token->refresh_token;
        }
        $client->setAccessToken($data);
        if ($client->isAccessTokenExpired()) {
            if (! $client->getRefreshToken()) {
                throw new \RuntimeException('Sesi Google berakhir. Silakan hubungkan ulang.');
            }
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            if (isset($newToken['error'])) {
                throw new \RuntimeException('Sesi Google ditolak. Silakan hubungkan ulang.');
            }
            $newToken['refresh_token'] = $newToken['refresh_token'] ?? $data['refresh_token'];
            $client->setAccessToken($newToken);
            $token->update(['access_token' => json_encode($newToken), 'refresh_token' => $newToken['refresh_token'],
                'expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600)]);
        }

        return $client;
    }

    public function syncAll()
    {
        $token = GoogleToken::where('user_id', Auth::id())->first();
        if (! $token) {
            return redirect('/google/redirect');
        }
        try {
            $this->runAutoSync($this->authenticatedClient($token));

            return back()->with('success', 'Google Classroom dan Calendar berhasil disinkronisasi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi Google gagal. Periksa koneksi atau hubungkan ulang akun.');
        }
    }

    private function parseDeadline($work): ?Carbon
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

    public function addToCalendar($taskId)
    {
        try {
            $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
            if (! $task->deadline) {
                return response()->json(['success' => false, 'message' => 'Tentukan deadline tugas terlebih dahulu.'], 422);
            }
            $googleToken = GoogleToken::where('user_id', Auth::id())->first();

            if (! $googleToken) {
                return response()->json(['success' => false, 'message' => 'Google account not connected.'], 401);
            }

            $client = $this->authenticatedClient($googleToken);

            $service = new Calendar($client);
            $event = new Event([
                'summary' => '📌 '.$task->title,
                'description' => 'Tugas dari Task Engine. Matkul: '.($task->course_name ?? 'Umum'),
                'start' => ['dateTime' => Carbon::parse($task->deadline)->toRfc3339String(), 'timeZone' => config('app.timezone')],
                'end' => ['dateTime' => Carbon::parse($task->deadline)->addHour()->toRfc3339String(), 'timeZone' => config('app.timezone')],
                'reminders' => ['useDefault' => true],
            ]);

            $service->events->insert('primary', $event);

            return response()->json(['success' => true, 'message' => 'Tugas berhasil ditambahkan ke Google Calendar!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal: '.$e->getMessage()], 500);
        }
    }

    public function redirect(Request $request)
    {
        // Mulai OAuth pada origin yang sama dengan callback agar cookie sesi ikut kembali.
        $callback = parse_url(config('services.google.redirect'));
        $origin = ($callback['scheme'] ?? 'http').'://'.($callback['host'] ?? 'localhost')
            .(isset($callback['port']) ? ':'.$callback['port'] : '');
        if ($request->getSchemeAndHttpHost() !== $origin) {
            return redirect()->away($origin.'/google/redirect');
        }
        $state = Str::random(64);
        $states = array_filter(session('google_oauth_states', []), fn ($created) => $created >= time() - 600);
        $states[$state] = time();
        session(['google_oauth_states' => array_slice($states, -5, null, true)]);
        $client = $this->getClient();
        $client->setState($state);

        return redirect($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $state = $request->input('state');
        $states = $request->session()->get('google_oauth_states', []);
        if (! is_string($state) || ! isset($states[$state]) || $states[$state] < time() - 600) {
            abort(403, 'Sesi login Google tidak valid. Silakan ulangi login.');
        }
        unset($states[$state]);
        $request->session()->put('google_oauth_states', $states);
        if ($request->has('error') || ! $request->filled('code')) {
            return redirect('/login')->with('error', 'Login Google dibatalkan atau tidak valid.');
        }
        $client = $this->getClient();
        try {
            $token = $client->fetchAccessTokenWithAuthCode($request->code);
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Login Google gagal. Silakan coba lagi.');
        }

        if (isset($token['error'])) {
            return redirect('/login')->with('error', 'Gagal login dari Google.');
        }

        $client->setAccessToken($token);

        try {
            $googleUserInfo = (new Oauth2($client))->userinfo->get();
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Profil Google tidak dapat dibaca. Silakan coba lagi.');
        }
        if (! $googleUserInfo->getVerifiedEmail()) {
            return redirect('/login')->with('error', 'Alamat email Google belum terverifikasi.');
        }

        $user = User::firstOrCreate(
            ['email' => $googleUserInfo->email],
            ['name' => $googleUserInfo->name, 'password' => Hash::make(Str::random(16))]
        );

        Auth::login($user);
        $request->session()->regenerate();

        $token['refresh_token'] = $token['refresh_token'] ?? GoogleToken::where('user_id', $user->id)->value('refresh_token');

        GoogleToken::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'access_token' => json_encode($token),
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($token['expires_in']),
            ]
        );

        try {
            $this->runAutoSync($client);
        } catch (\Exception $e) {
            return redirect('/dashboard')->with('success', 'Login Google berhasil.')
                ->with('error', 'Sinkronisasi belum berhasil. Silakan coba sync kembali.');
        }

        return redirect('/dashboard')->with('success', 'Berhasil Login! Tugas dan Jadwal otomatis disinkronkan.');
    }

    private function runAutoSync($client)
    {
        $serviceClassroom = new Classroom($client);
        $serviceCalendar = new Calendar($client);

        $courses = $serviceClassroom->courses->listCourses(['courseStates' => ['ACTIVE']]);

        foreach ($courses->getCourses() as $course) {
            $courseWork = $serviceClassroom->courses_courseWork->listCoursesCourseWork($course->getId());
            if (! $courseWork->getCourseWork()) {
                continue;
            }

            foreach ($courseWork->getCourseWork() as $work) {
                $deadline = $this->parseDeadline($work);

                if ($deadline && $deadline->lessThan(now()->subDays(60))) {
                    continue;
                }

                $isSubmitted = null;
                try {
                    $response = $serviceClassroom->courses_courseWork_studentSubmissions
                        ->listCoursesCourseWorkStudentSubmissions($course->getId(), $work->getId(), ['userId' => 'me']);

                    $subs = $response->getStudentSubmissions();
                    $isSubmitted = false;

                    if ($subs) {
                        foreach ($subs as $sub) {
                            if (in_array($sub->getState(), ['TURNED_IN', 'RETURNED'])) {
                                $isSubmitted = true;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Log error tanpa menghentikan proses login
                    Log::error('Classroom Sync Error di AutoSync: '.$e->getMessage());
                }

                Task::updateOrCreate(
                    [
                        'source' => 'classroom',
                        'source_id' => $work->getId(),
                        'user_id' => Auth::id(),
                    ],
                    [
                        'title' => $work->getTitle(),
                        'description' => $work->getDescription() ?? 'Tugas otomatis dari Google Classroom',
                        'course_name' => $course->getName(),
                        'deadline' => $deadline,
                        'priority' => 'high',
                        'status' => $isSubmitted === null ? (Task::where('user_id', Auth::id())->where('source', 'classroom')->where('source_id', $work->getId())->value('status') ?? 'pending') : ($isSubmitted ? 'done' : 'pending'),
                        'url' => $work->getAlternateLink(),
                    ]
                );
            }
        }

        $events = $serviceCalendar->events->listEvents('primary', [
            'timeMin' => now()->toRfc3339String(),
            'timeMax' => now()->addDays(30)->toRfc3339String(),
            'singleEvents' => true,
        ]);

        foreach ($events->getItems() as $event) {
            $deadline = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
            Task::updateOrCreate(
                ['source' => 'calendar', 'source_id' => $event->getId(), 'user_id' => Auth::id()],
                ['title' => $event->getSummary(), 'deadline' => $deadline]
            );
        }
    }

    public function syncClassroom()
    {
        $googleToken = GoogleToken::where('user_id', Auth::id())->first();

        if (! $googleToken) {
            return redirect('/google/redirect')->with('error', 'Silakan hubungkan akun Google terlebih dahulu.');
        }

        try {
            $client = $this->authenticatedClient($googleToken);
            $service = new Classroom($client);
            $courses = $service->courses->listCourses(['courseStates' => ['ACTIVE']]);
            $synced = 0;
            $done = 0;
            $skipped = 0;

            foreach ($courses->getCourses() as $course) {
                $courseWork = $service->courses_courseWork->listCoursesCourseWork($course->getId());
                if (! $courseWork->getCourseWork()) {
                    continue;
                }

                foreach ($courseWork->getCourseWork() as $work) {
                    $deadline = $this->parseDeadline($work);

                    if ($deadline && $deadline->lessThan(now()->subDays(60))) {
                        $skipped++;

                        continue;
                    }

                    $isSubmitted = null;
                    try {
                        $response = $service->courses_courseWork_studentSubmissions
                            ->listCoursesCourseWorkStudentSubmissions(
                                $course->getId(), $work->getId(), ['userId' => 'me']
                            );

                        $subs = $response->getStudentSubmissions();
                        $isSubmitted = false;

                        if ($subs) {
                            foreach ($subs as $sub) {
                                if (in_array($sub->getState(), ['TURNED_IN', 'RETURNED'])) {
                                    $isSubmitted = true;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // 🛑 ERROR TRAP: SENGAYA DIMUNCULKAN DI SINI 🛑
                        Log::error('Classroom Sync Error: '.$e->getMessage());
                    }

                    $status = $isSubmitted === null ? (Task::where('user_id', Auth::id())->where('source', 'classroom')->where('source_id', $work->getId())->value('status') ?? 'pending') : ($isSubmitted ? 'done' : 'pending');

                    Task::updateOrCreate(
                        [
                            'source' => 'classroom',
                            'source_id' => $work->getId(),
                            'user_id' => Auth::id(),
                        ],
                        [
                            'title' => $work->getTitle(),
                            'description' => Str::limit($work->getDescription() ?? 'Tugas dari Google Classroom', 250),
                            'course_name' => $course->getName(),
                            'deadline' => $deadline,
                            'priority' => 'high',
                            'status' => $status,
                            'url' => $work->getAlternateLink(),
                        ]
                    );

                    if ($isSubmitted) {
                        $done++;
                    } else {
                        $synced++;
                    }
                }
            }

            return redirect()->back()->with('success',
                "Sync selesai! {$synced} tugas pending diproses, {$done} ditandai selesai, {$skipped} kadaluarsa dilewati. 🎓"
            );

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal sinkronisasi Classroom: '.$e->getMessage());
        }
    }

    public function syncCalendar()
    {
        $googleToken = GoogleToken::where('user_id', Auth::id())->first();
        if (! $googleToken) {
            return redirect('/google/redirect');
        }

        $client = $this->authenticatedClient($googleToken);

        $service = new Calendar($client);
        $events = $service->events->listEvents('primary', [
            'timeMin' => now()->toRfc3339String(),
            'timeMax' => now()->addDays(30)->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        foreach ($events->getItems() as $event) {
            $deadline = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
            Task::updateOrCreate(
                ['source' => 'calendar', 'source_id' => $event->getId(), 'user_id' => Auth::id()],
                ['title' => $event->getSummary(), 'deadline' => $deadline, 'priority' => 'medium']
            );
        }

        return redirect('/tasks')->with('success', 'Calendar berhasil disinkronkan!');
    }

    public function fetchEmails()
    {
        $googleToken = GoogleToken::where('user_id', Auth::id())->first();
        if (! $googleToken) {
            return [];
        }

        $client = $this->authenticatedClient($googleToken);
        $service = new Gmail($client);

        $results = $service->users_messages->listUsersMessages('me', ['maxResults' => 20, 'q' => 'newer_than:7d']);
        $emails = [];

        foreach ($results->getMessages() as $message) {
            $msg = $service->users_messages->get('me', $message->getId());
            $payload = $msg->getPayload();
            $headers = $payload->getHeaders();

            $emails[] = [
                'id' => $message->getId(),
                'subject' => collect($headers)->firstWhere('name', 'Subject')?->getValue() ?? '(No Subject)',
                'from' => collect($headers)->firstWhere('name', 'From')?->getValue() ?? 'Unknown',
                'date' => collect($headers)->firstWhere('name', 'Date')?->getValue() ?? now(),
                'snippet' => $msg->getSnippet(),
            ];
        }

        return $emails;
    }

    public function indexEmails()
    {
        try {
            return view('emails.index', ['emails' => $this->fetchEmails()]);
        } catch (\Exception $e) {
            return redirect('/settings')->with('error', 'Email Google tidak dapat dibaca. Silakan hubungkan ulang akun.');
        }
    }

    public function getEmailDetail($id)
    {
        $googleToken = GoogleToken::where('user_id', Auth::id())->first();
        if (! $googleToken) {
            return response()->json(['error' => 'Hubungkan akun Google terlebih dahulu.'], 409);
        }
        $client = $this->authenticatedClient($googleToken);

        $service = new Gmail($client);
        $message = $service->users_messages->get('me', $id, ['format' => 'full']);
        $payload = $message->getPayload();

        $bodyHtml = '';
        $bodyPlain = '';

        $findBody = function ($parts) use (&$findBody, &$bodyHtml, &$bodyPlain) {
            foreach ($parts as $part) {
                if ($part->getMimeType() == 'text/html') {
                    $bodyHtml = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                } elseif ($part->getMimeType() == 'text/plain') {
                    $bodyPlain = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                } elseif ($part->getParts()) {
                    $findBody($part->getParts());
                }
            }
        };

        if ($payload->getParts()) {
            $findBody($payload->getParts());
        } else {
            if ($payload->getMimeType() == 'text/html') {
                $bodyHtml = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
            } else {
                $bodyPlain = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
            }
        }

        return response()->json([
            'subject' => collect($payload->getHeaders())->firstWhere('name', 'Subject')?->getValue(),
            'from' => collect($payload->getHeaders())->firstWhere('name', 'From')?->getValue(),
            'date' => collect($payload->getHeaders())->firstWhere('name', 'Date')?->getValue(),
            'body_text' => $bodyPlain ?: html_entity_decode(strip_tags(preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $bodyHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ]);
    }

    public function apiLatestEmails()
    {
        try {
            $emails = $this->fetchEmails();

            return response()->json(['success' => true, 'emails' => $emails]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'emails' => []]);
        }
    }
}
