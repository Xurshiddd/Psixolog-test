<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HemisPasswordAuthService
{
    public function login(string|int $login, string $password): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post('auth/login', [
                'login' => $login,
                'password' => $password,
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($this->errorMessage($response->json()) ?? 'HEMIS login yoki parolni qabul qilmadi.');
        }

        $token = $response->json('data.token');

        if (blank($token)) {
            throw new RuntimeException('HEMIS token qaytarmadi.');
        }

        return [
            'token' => $token,
        ];
    }

    /**
     * @throws ConnectionException
     */
    public function me(string $token): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withToken($token)
            ->timeout(20)
            ->get('account/me');

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException($this->errorMessage($response->json()) ?? 'HEMIS talaba maʼlumotlarini qaytarmadi.');
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('HEMIS talaba maʼlumotlari noto‘g‘ri formatda qaytdi.');
        }

        return $data;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.hemis.api_base_url', 'https://student.ttyesi.uz/rest/v1'), '/');
    }

    private function errorMessage(?array $payload): ?string
    {
        $error = data_get($payload, 'error');

        if (is_string($error) && filled($error)) {
            return $error;
        }

        $message = data_get($payload, 'message');

        return is_string($message) && filled($message) ? $message : null;
    }
}
