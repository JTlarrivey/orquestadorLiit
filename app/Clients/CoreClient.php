<?php
// orquestadorLiit/app/Clients/CoreClient.php
namespace App\Clients;

class CoreClient
{
    private string $base;
    private int $timeout;

    public function __construct(?string $base = null, int $timeout = 10)
    {
        $this->base = rtrim($base ?? getenv('BACKEND_CORE_URL') ?? 'http://localhost:8081', '/');
        $this->timeout = $timeout;
    }

    public function post(string $path, array $payload): array
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Core error: $err");
        }
        curl_close($ch);

        $data = json_decode($res, true);
        if ($status >= 400) throw new \RuntimeException("Core HTTP $status: " . $res);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) throw new \RuntimeException("Core JSON: " . json_last_error_msg());
        return $data;
    }
}
