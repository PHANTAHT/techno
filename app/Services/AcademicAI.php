<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AcademicAI
{
    public function complete(array $messages): Response
    {
        $key = config('services.openrouter.api_key');
        $model = config('services.openrouter.model');
        if (! $key || ! $model) {
            throw new \RuntimeException('OPENROUTER_API_KEY dan OPENROUTER_MODEL_ID harus diisi.');
        }

        try {
            $response = Http::connectTimeout(10)->timeout(60)->withToken($key)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Koneksi AI gagal atau timeout. Periksa koneksi server.', previous: $e);
        }

        if (! $response->successful()) {
            $message = match ($response->status()) {
                401, 403 => 'API key AI ditolak. Periksa key dan izin OpenRouter.',
                402 => 'Saldo OpenRouter tidak cukup.',
                400, 404 => 'Model AI atau permintaan tidak didukung. Periksa OPENROUTER_MODEL_ID.',
                429 => 'Batas permintaan AI tercapai. Coba lagi nanti.',
                default => 'Layanan AI sedang gagal. Coba lagi nanti.',
            };
            throw new \RuntimeException($message);
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('AI mengembalikan respons kosong atau tidak valid.');
        }

        return $response;
    }
}
