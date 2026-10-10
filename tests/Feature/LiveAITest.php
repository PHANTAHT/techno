<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiveAITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('RUN_LIVE_AI_TESTS') !== '1') {
            $this->markTestSkipped('Set RUN_LIVE_AI_TESTS=1 to call OpenRouter with synthetic data.');
        }
        $this->actingAs(User::factory()->create());
    }

    public function test_live_chat_returns_a_real_answer(): void
    {
        $this->postJson('/ai/chat', ['message' => 'Berapa hasil 2 + 2? Jawab singkat.'])
            ->assertOk()->assertJsonStructure(['reply', 'session_id']);
    }

    public function test_live_email_summary(): void
    {
        $this->postJson('/ai/summarize-email', ['content' => 'Rapat kelas hari Senin pukul 09:00. Bawa buku matematika.'])
            ->assertOk()->assertJsonStructure(['summary']);
    }

    public function test_live_task_explanation(): void
    {
        $this->postJson('/ai/explain-task', ['title' => 'Latihan penjumlahan', 'description' => 'Kerjakan 2 + 2.'])
            ->assertOk()->assertJsonStructure(['explanation']);
    }

    public function test_live_file_summary(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('live-check.txt', 'Fotosintesis menggunakan cahaya matahari untuk menghasilkan energi pada tumbuhan.');
        $task = Task::create(['user_id' => auth()->id(), 'title' => 'Biologi', 'file_path' => 'live-check.txt', 'file_type' => 'txt']);
        $this->postJson('/ai/summarize-file/'.$task->id)->assertOk()->assertJsonStructure(['summary']);
    }

    public function test_live_image_chat(): void
    {
        $file = UploadedFile::fake()->createWithContent('check.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII='
        ));
        $this->post('/ai/chat', ['message' => 'Jelaskan gambar ini dalam satu kalimat.', 'file' => $file], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonStructure(['reply']);
    }
}
