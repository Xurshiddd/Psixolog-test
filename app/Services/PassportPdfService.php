<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;

class PassportPdfService
{
    /**
     * Ijtimoiy-psixologik passport PDF'ini tayyorlaydi. Talaba, xodim va
     * nomzod uchun bir xil shablon ishlatiladi — farq `$profile` orqali keladi.
     *
     * @param  array{character_traits: array<int, string>, temperament_type: string|null, conclusion: string|null}  $passportData
     * @param  array{role_label: string, intro: string, conclusion_title: string, show_photo: bool, rows: list<array{label: string, value: string}>}  $profile
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePassportPdf(User $user, array $passportData, array $profile)
    {
        return Pdf::loadView('pdf.user-passport', [
            'user' => $user,
            'passportData' => $passportData,
            'profile' => $profile,
            'logoDataUri' => $this->imageToDataUri(public_path('logo-pic.svg')),
            'pictureDataUri' => $profile['show_photo']
                ? $this->resolvePictureDataUri($user->picture)
                : null,
            'personIconDataUri' => $this->personIconDataUri(),
        ])->setPaper('a4', 'landscape');
    }

    /**
     * Bazada rasm bo'lmaganda (yoki nomzodlarda — umuman olinmaganda)
     * rasm o'rnida ko'rinadigan person icon.
     */
    private function personIconDataUri(): string
    {
        $svg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="72" height="72">
            <circle cx="12" cy="8" r="3.6" fill="#98a4b6"/>
            <path d="M12 13.4c-4.1 0-7.4 2.1-7.4 4.7V20h14.8v-1.9c0-2.6-3.3-4.7-7.4-4.7z" fill="#98a4b6"/>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function resolvePictureDataUri(?string $picture): ?string
    {
        if (blank($picture)) {
            return null;
        }

        if (str_starts_with($picture, 'data:image/')) {
            return $picture;
        }

        if (filter_var($picture, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(10)->get($picture);

                if ($response->successful()) {
                    $mimeType = $response->header('Content-Type', 'image/jpeg');

                    return 'data:'.$mimeType.';base64,'.base64_encode($response->body());
                }
            } catch (\Throwable) {
                return null;
            }

            return null;
        }

        $normalizedPath = ltrim($picture, '/');
        $candidatePaths = [
            public_path($normalizedPath),
            storage_path('app/public/'.$normalizedPath),
        ];

        foreach ($candidatePaths as $path) {
            $dataUri = $this->imageToDataUri($path);

            if ($dataUri !== null) {
                return $dataUri;
            }
        }

        return null;
    }

    private function imageToDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
