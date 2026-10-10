<?php

namespace App\Console\Commands;

use App\Services\AcademicAI;
use Illuminate\Console\Command;

class CheckAI extends Command
{
    protected $signature = 'ai:check';

    protected $description = 'Test koneksi dan respons AI tanpa mengirim data pengguna';

    public function handle(AcademicAI $ai): int
    {
        try {
            $response = $ai->complete([
                ['role' => 'user', 'content' => 'Jawab hanya: AI bekerja.'],
            ]);
            $this->info('Model: '.config('services.openrouter.model'));
            $this->info('Respons: '.$response->json('choices.0.message.content'));

            return self::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
