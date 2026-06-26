<?php

namespace App\Services;

use Illuminate\Support\Str;

class CaptchaService
{
    private const SESSION_KEY = 'guest_captcha_code';

    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Yangi capcha kodi yaratib, sessiyaga saqlaydi va SVG rasmni qaytaradi.
     */
    public function generateSvg(int $length = 5): string
    {
        $code = $this->randomCode($length);
        session([self::SESSION_KEY => $code]);

        return $this->renderSvg($code);
    }

    /**
     * Foydalanuvchi kiritgan qiymatni sessiyadagi kod bilan solishtiradi.
     */
    public function check(?string $value): bool
    {
        $expected = session(self::SESSION_KEY);

        if (blank($expected) || blank($value)) {
            return false;
        }

        return strtoupper(trim($value)) === strtoupper($expected);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function randomCode(int $length): string
    {
        $chars = self::CHARS;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    private function renderSvg(string $code): string
    {
        $width = 160;
        $height = 56;
        $colors = ['#1e3a8a', '#7c2d12', '#065f46', '#581c87', '#9d174d'];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<rect width="100%" height="100%" fill="#f1f5f9"/>';

        // Shovqin chiziqlari
        for ($i = 0; $i < 6; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $color = $colors[array_rand($colors)];
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="1" opacity="0.4"/>';
        }

        // Belgilar
        $length = strlen($code);
        $step = (int) (($width - 20) / max($length, 1));

        for ($i = 0; $i < $length; $i++) {
            $char = $code[$i];
            $x = 14 + ($i * $step);
            $y = random_int(36, 44);
            $rotate = random_int(-25, 25);
            $color = $colors[array_rand($colors)];
            $fontSize = random_int(26, 32);

            $svg .= '<text x="'.$x.'" y="'.$y.'" font-family="monospace" font-size="'.$fontSize.'" font-weight="bold" fill="'.$color.'" transform="rotate('.$rotate.' '.$x.' '.$y.')">'.e($char).'</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }
}
