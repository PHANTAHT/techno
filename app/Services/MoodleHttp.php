<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MoodleHttp
{
    public function client(string $url): PendingRequest
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        if (($parts['scheme'] ?? '') !== 'https' || ! $host || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new \RuntimeException('URL Moodle harus HTTPS pada port 443.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);
        if (! $addresses) {
            throw new \RuntimeException('Alamat server Moodle tidak ditemukan.');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException('Alamat Moodle harus mengarah ke server publik.');
            }
        }

        return Http::connectTimeout(10)->timeout(30)->withOptions([
            'allow_redirects' => false,
            'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$addresses[0]]],
        ]);
    }

    public function download(Task $task): Response
    {
        $config = $task->user->moodleConfig;
        $url = $task->getRawOriginal('file_url');
        if (! $config || ! $url || parse_url($url, PHP_URL_HOST) !== parse_url($config->moodle_url, PHP_URL_HOST)) {
            throw new \RuntimeException('Domain lampiran tidak cocok dengan server Moodle.');
        }

        return $this->client($url)->withOptions([
            'on_headers' => function ($response) {
                if ((int) $response->getHeaderLine('Content-Length') > 10 * 1024 * 1024) {
                    throw new \RuntimeException('Lampiran melebihi 10 MB.');
                }
            },
            'progress' => function ($total, $downloaded) {
                if ($downloaded > 10 * 1024 * 1024) {
                    throw new \RuntimeException('Lampiran melebihi 10 MB.');
                }
            },
        ])->get($url);
    }
}
