<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Task;
use App\Services\AcademicAI;
use App\Services\MoodleHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class AIController extends Controller
{
    public function newSession(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => auth()->id(),
            'title' => 'Chat Baru',
        ]);

        return response()->json($session);
    }

    public function sessions()
    {
        return ChatSession::where('user_id', auth()->id())->latest()->get();
    }

    public function getSession($id)
    {
        $session = ChatSession::with('messages')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($session);
    }

    public function deleteSession($id)
    {
        $session = ChatSession::where('user_id', auth()->id())->findOrFail($id);
        $session->messages()->delete();
        $session->delete();

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // SUMMARIZE FILE ATTACHMENT DARI TASK (tombol di show.blade.php)
    // ══════════════════════════════════════════════════════════════
    public function summarizeTaskFile(Request $request, Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            return response()->json(['error' => 'Akses ditolak'], 403);
        }

        if (! $task->file_path && ! $task->file_url) {
            return response()->json(['error' => 'Tugas ini tidak memiliki file lampiran'], 400);
        }

        if (! $task->file_path) {
            if ($task->source !== 'moodle' || ! $task->getRawOriginal('file_url')) {
                return response()->json(['error' => 'Unggah file tugas terlebih dahulu untuk diringkas.'], 422);
            }
            $request->merge([
                'message' => 'Ringkas isi lampiran, kaitannya dengan tugas, dan hal penting yang perlu diperhatikan.',
                'context' => "Judul: {$task->title}\nDeskripsi: ".strip_tags($task->description ?? '')."\nfile_url: ".$task->getRawOriginal('file_url'),
            ]);
            $result = $this->chat($request);
            $data = $result->getData(true);
            if ($result->getStatusCode() !== 200) {
                return $result;
            }

            return response()->json(['summary' => $data['reply'], 'file_name' => $task->file_name]);
        }
        if (! Storage::disk('public')->exists($task->file_path)) {
            return response()->json(['error' => 'File lampiran tidak ditemukan. Silakan unggah ulang.'], 404);
        }

        $fileType = strtolower($task->file_type ?? '');
        $taskInfo = "Judul Tugas: {$task->title}\nMata Kuliah: {$task->course_name}\nDeadline: {$task->deadline}\nDeskripsi: ".strip_tags($task->description);

        try {
            if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (! $task->file_path) {
                    return response()->json(['error' => 'File gambar tidak ditemukan'], 400);
                }
                $filePath = Storage::disk('public')->path($task->file_path);
                $base64 = base64_encode(file_get_contents($filePath));
                $mime = mime_content_type($filePath);

                $messages = [
                    ['role' => 'system', 'content' => "Kamu adalah asisten akademik StudyPro. Analisis gambar berikut dalam konteks tugas:\n\n{$taskInfo}"],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'Analisis dan jelaskan isi gambar ini dalam konteks tugas akademik.'],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$base64}"]],
                    ]],
                ];

            } elseif ($fileType === 'pdf') {
                $text = $this->extractPdfText($task->file_path);
                if (! $text) {
                    return response()->json(['error' => 'Gagal membaca PDF. Pastikan tidak terproteksi.'], 400);
                }
                $messages = [
                    ['role' => 'system', 'content' => "Kamu adalah asisten akademik StudyPro. Analisis dokumen dalam konteks tugas:\n\n{$taskInfo}"],
                    ['role' => 'user', 'content' => "Isi PDF:\n\n".mb_substr($text, 0, 4000)."\n\nTolong: (1) Ringkas isi, (2) Jelaskan kaitan dengan tugas, (3) Hal penting yang perlu diperhatikan."],
                ];

            } elseif (in_array($fileType, ['doc', 'docx'])) {
                $text = $this->extractDocxText($task->file_path);
                if (! $text) {
                    return response()->json(['error' => 'Gagal membaca dokumen Word.'], 400);
                }
                $messages = [
                    ['role' => 'system', 'content' => "Kamu adalah asisten akademik StudyPro. Analisis dokumen dalam konteks tugas:\n\n{$taskInfo}"],
                    ['role' => 'user', 'content' => "Isi file Word:\n\n".mb_substr($text, 0, 4000)."\n\nTolong: (1) Ringkas isi, (2) Jelaskan kaitan dengan tugas, (3) Hal penting yang perlu diperhatikan."],
                ];

            } elseif (in_array($fileType, ['txt', 'csv'])) {
                $text = mb_substr(Storage::disk('public')->get($task->file_path) ?? '', 0, 4000);
                $messages = [
                    ['role' => 'system', 'content' => "Kamu adalah asisten akademik StudyPro. Analisis file dalam konteks tugas:\n\n{$taskInfo}"],
                    ['role' => 'user', 'content' => "Isi file {$fileType}:\n\n{$text}\n\nTolong ringkas dan jelaskan dalam konteks tugas."],
                ];

            } else {
                return response()->json(['error' => "Format .{$fileType} belum didukung AI."], 400);
            }

            $response = app(AcademicAI::class)->complete($messages);

            if (! $response->successful()) {
                return response()->json(['error' => 'AI gagal: '.$response->body()], 500);
            }

            $summary = $response->json()['choices'][0]['message']['content'] ?? null;
            if (! $summary) {
                return response()->json(['error' => 'Response AI tidak valid'], 500);
            }

            return response()->json(['summary' => $summary, 'file_name' => $task->file_name, 'file_type' => $task->file_type]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function extractPdfText(?string $filePath): ?string
    {
        if (! $filePath) {
            return null;
        }
        $fullPath = Storage::disk('public')->path($filePath);
        if (! file_exists($fullPath)) {
            return null;
        }
        try {
            $parser = new Parser;

            return $parser->parseFile($fullPath)->getText();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractDocxText(?string $filePath): ?string
    {
        if (! $filePath) {
            return null;
        }
        $fullPath = Storage::disk('public')->path($filePath);
        if (! file_exists($fullPath)) {
            return null;
        }
        try {
            $phpWord = IOFactory::load($fullPath, strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'doc' ? 'MsDoc' : 'Word2007');
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText()."\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText().' ';
                            }
                        }
                        $text .= "\n";
                    }
                }
            }

            return $text;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // SUMMARIZE EMAIL
    // ══════════════════════════════════════════════════════════════
    public function summarizeEmail(Request $request)
    {
        $emailContent = $request->input('content');
        if (! $emailContent) {
            return response()->json(['error' => 'Isi email tidak ditemukan'], 400);
        }

        try {
            $response = app(AcademicAI::class)->complete([
                ['role' => 'system', 'content' => 'Rangkum isi email berikut menjadi ringkasan singkat dan jelas dalam bahasa Indonesia.'],
                ['role' => 'user',   'content' => $emailContent],
            ]);
            if ($response->successful()) {
                return response()->json(['summary' => $response->json()['choices'][0]['message']['content']]);
            }

            return response()->json(['error' => 'Gagal menghubungi AI'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // EXPLAIN TASK
    // ══════════════════════════════════════════════════════════════
    public function explainTask(Request $request)
    {
        $title = $request->input('title');
        if (! $title) {
            return response()->json(['error' => 'Judul tugas tidak ditemukan'], 400);
        }

        $prompt = "Judul Tugas: {$title}\n";
        $prompt .= "Mata Kuliah: {$request->input('course_name')}\n";
        $prompt .= "Deadline: {$request->input('deadline')}\n";
        $prompt .= "Deskripsi: {$request->input('description')}\n";

        try {
            $response = app(AcademicAI::class)->complete([
                ['role' => 'system', 'content' => 'Kamu adalah asisten akademik StudyPro. Jelaskan: (1) apa yang perlu dikerjakan, (2) tips mengerjakan, (3) hal yang perlu diperhatikan. Bahasa Indonesia, ringkas, pakai poin-poin.'],
                ['role' => 'user',   'content' => $prompt],
            ]);
            if ($response->successful()) {
                return response()->json(['explanation' => $response->json()['choices'][0]['message']['content']]);
            }

            return response()->json(['error' => 'Gagal menghubungi AI'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // MAIN CHAT
    // ══════════════════════════════════════════════════════════════
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:20000|required_without:file',
            'context' => 'nullable|string|max:30000',
            'session_id' => 'nullable|integer',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,png,jpg,jpeg,gif,webp,txt,csv',
        ]);
        $userMessage = $request->input('message') ?: 'Tolong analisis file yang dilampirkan.';
        $sessionId = $request->input('session_id');
        $context = $request->input('context', '');
        $user = auth()->user();

        // ══════════════════════════════════════════════════════════
        // STEP 1: Proses file yang diupload dari chatbox
        // ══════════════════════════════════════════════════════════
        $fileContext = '';   // teks dari file (PDF, DOCX, TXT, CSV)
        $imageData = null; // ['mime' => ..., 'base64' => ..., 'name' => ...]
        $fileName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $fileName = $file->getClientOriginalName();

            try {
                // ── Gambar → simpan sebagai imageData, bukan teks ──
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $imageData = [
                        'mime' => $file->getMimeType(),
                        'base64' => base64_encode(file_get_contents($file->getRealPath())),
                        'name' => $fileName,
                    ];

                } elseif ($ext === 'pdf') {
                    $parser = new Parser;
                    $text = mb_substr($parser->parseFile($file->getRealPath())->getText(), 0, 3000);
                    $fileContext = "=== Isi file PDF '{$fileName}' yang dilampirkan user ===\n{$text}\n=== Akhir file ===";

                } elseif (in_array($ext, ['doc', 'docx'])) {
                    $phpWord = IOFactory::load($file->getRealPath(), $ext === 'doc' ? 'MsDoc' : 'Word2007');
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $el) {
                            if (method_exists($el, 'getText')) {
                                $text .= $el->getText()."\n";
                            } elseif (method_exists($el, 'getElements')) {
                                foreach ($el->getElements() as $child) {
                                    if (method_exists($child, 'getText')) {
                                        $text .= $child->getText().' ';
                                    }
                                }
                                $text .= "\n";
                            }
                        }
                    }
                    $fileContext = "=== Isi file Word '{$fileName}' yang dilampirkan user ===\n".mb_substr($text, 0, 3000)."\n=== Akhir file ===";

                } elseif (in_array($ext, ['txt', 'csv'])) {
                    $text = mb_substr(file_get_contents($file->getRealPath()), 0, 3000);
                    $fileContext = "=== Isi file '{$fileName}' yang dilampirkan user ===\n{$text}\n=== Akhir file ===";
                }

            } catch (\Exception $e) {
                $fileContext = "Catatan: User melampirkan file '{$fileName}' tapi gagal dibaca ({$e->getMessage()}).";
            }
        }

        // ══════════════════════════════════════════════════════════
        // STEP 2: Coba baca file dari Moodle (kalau ada di context)
        // ══════════════════════════════════════════════════════════
        if (empty($fileContext) && ! $imageData && str_contains($context, 'file_url:')) {
            preg_match('/file_url:\s*(\S+)/i', $context, $matches);
            $fileUrl = trim($matches[1] ?? '');

            if ($fileUrl && filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                $attachment = Task::where('user_id', $user->id)
                    ->where('source', 'moodle')->where('file_url', $fileUrl)->first();
                try {
                    if (! $attachment) {
                        return response()->json(['error' => 'Lampiran tidak ditemukan pada tugas milikmu.'], 422);
                    }
                    $dlResponse = app(MoodleHttp::class)->download($attachment);
                    if (! $dlResponse->successful()) {
                        return response()->json(['error' => 'Server Moodle gagal mengirim lampiran.'], 422);
                    }

                    if ($dlResponse->successful()) {
                        // Deteksi ekstensi dari URL (hapus query string)
                        $ext = strtolower(pathinfo(explode('?', $fileUrl)[0], PATHINFO_EXTENSION));
                        $tmpPath = tempnam(sys_get_temp_dir(), 'sp_');
                        file_put_contents($tmpPath, $dlResponse->body());

                        try {
                            if ($ext === 'pdf') {
                                $parser = new Parser;
                                $text = mb_substr($parser->parseFile($tmpPath)->getText(), 0, 3000);
                                $fileContext = "=== Isi file PDF dari Moodle ===\n{$text}\n=== Akhir file ===";

                            } elseif (in_array($ext, ['doc', 'docx'])) {
                                $phpWord = IOFactory::load($tmpPath, $ext === 'doc' ? 'MsDoc' : 'Word2007');
                                $text = '';
                                foreach ($phpWord->getSections() as $section) {
                                    foreach ($section->getElements() as $el) {
                                        if (method_exists($el, 'getText')) {
                                            $text .= $el->getText()."\n";
                                        }
                                    }
                                }
                                $fileContext = "=== Isi file Word dari Moodle ===\n".mb_substr($text, 0, 3000)."\n=== Akhir file ===";

                            } elseif (in_array($ext, ['txt', 'csv'])) {
                                $fileContext = mb_substr($dlResponse->body(), 0, 3000);
                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                $imageData = [
                                    'mime' => mime_content_type($tmpPath),
                                    'base64' => base64_encode(file_get_contents($tmpPath)),
                                    'name' => basename(explode('?', $fileUrl)[0]),
                                ];
                            } else {
                                return response()->json(['error' => 'Format lampiran Moodle belum didukung.'], 422);
                            }
                        } finally {
                            @unlink($tmpPath);
                        }
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Lampiran Moodle tidak dapat dibaca. Periksa koneksi dan akses file.'], 422);
                }
            }
        }

        // ══════════════════════════════════════════════════════════
        // Jangan kirim URL lampiran yang mengandung token Moodle ke penyedia AI.
        $context = preg_replace('/file_url:\s*\S+/i', 'Lampiran Moodle terlampir.', $context);

        // STEP 3: Auto-create session
        // ══════════════════════════════════════════════════════════
        if (! $sessionId) {
            $sessionModel = ChatSession::create(['user_id' => $user->id, 'title' => 'Chat Baru']);
            $sessionId = $sessionModel->id;
        } else {
            $sessionModel = ChatSession::where('user_id', $user->id)->find($sessionId);
            if (! $sessionModel) {
                $sessionModel = ChatSession::create(['user_id' => $user->id, 'title' => 'Chat Baru']);
                $sessionId = $sessionModel->id;
            }
        }

        // ══════════════════════════════════════════════════════════
        // STEP 4: Build context & messages
        // ══════════════════════════════════════════════════════════
        $tasks = Task::where('user_id', $user->id)->orderBy('deadline')->limit(10)->get();
        $taskContext = "Daftar Tugas User:\n";
        foreach ($tasks as $t) {
            $taskContext .= "- {$t->title} | Deadline: {$t->deadline} | Status: {$t->status}\n";
        }

        // fullContext masuk ke system prompt (tanpa isi file gambar)
        $fullContext = "User: {$user->name}\nWaktu sekarang: ".now()."\n\n{$taskContext}\n\n{$context}\n\n{$fileContext}";

        $history = ChatMessage::where('session_id', $sessionId)->latest()->take(10)->get()->reverse();
        $messages = [
            ['role' => 'system', 'content' => "Kamu adalah StudyPro AI, asisten akademik. Jawab dalam bahasa Indonesia, singkat dan jelas.\n\n{$fullContext}"],
        ];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        // ── ✅ FIX UTAMA: Pesan user — handle gambar vs teks biasa ──
        if ($imageData) {
            // Gambar dikirim sebagai multipart content (vision)
            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userMessage ?: "Tolong analisis gambar '{$imageData['name']}' ini dan jelaskan isinya.",
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$imageData['mime']};base64,{$imageData['base64']}",
                        ],
                    ],
                ],
            ];
        } else {
            // Teks biasa (PDF/DOCX sudah masuk ke fullContext via fileContext)
            $messages[] = ['role' => 'user', 'content' => $userMessage];
        }

        // ══════════════════════════════════════════════════════════
        // STEP 5: Panggil OpenRouter
        // ══════════════════════════════════════════════════════════
        try {
            $response = app(AcademicAI::class)->complete($messages);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        if (! $response->successful()) {
            return response()->json(['error' => 'AI gagal', 'detail' => $response->body()], 500);
        }

        $reply = $response->json()['choices'][0]['message']['content'] ?? null;
        if (! $reply) {
            return response()->json(['error' => 'Format response AI tidak valid'], 500);
        }

        // ══════════════════════════════════════════════════════════
        // STEP 6: Simpan ke DB & return
        // ══════════════════════════════════════════════════════════
        ChatMessage::create(['session_id' => $sessionId, 'role' => 'user',      'content' => $userMessage.($fileName ? " [file: {$fileName}]" : '')]);
        ChatMessage::create(['session_id' => $sessionId, 'role' => 'assistant', 'content' => $reply]);

        if ($sessionModel->title === 'Chat Baru') {
            $sessionModel->update(['title' => mb_substr($userMessage ?: "File: {$fileName}", 0, 35)]);
        }

        return response()->json(['reply' => $reply, 'session_id' => $sessionId]);
    }
}
