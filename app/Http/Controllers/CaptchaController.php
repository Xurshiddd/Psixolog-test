<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    public function __construct(private CaptchaService $captchaService) {}

    public function show(): Response
    {
        $svg = $this->captchaService->generateSvg();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
