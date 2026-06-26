<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GuestRegisterController extends Controller
{
    public function __construct(private CaptchaService $captchaService) {}

    public function create()
    {
        return Inertia::render('auth/GuestRegister');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'desired_position' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'captcha' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if (! $this->captchaService->check($validated['captcha'])) {
            throw ValidationException::withMessages([
                'captcha' => 'Tasdiqlash kodi noto‘g‘ri.',
            ]);
        }

        $picture = $request->hasFile('image')
            ? $request->file('image')->store('guests', 'public')
            : null;

        $user = DB::transaction(function () use ($validated, $picture): User {
            $fullName = trim("{$validated['last_name']} {$validated['first_name']} {$validated['father_name']}");

            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'picture' => $picture,
                'password' => Hash::make($validated['password']),
                'role' => 'guest',
            ]);

            Guest::create([
                'user_id' => $user->id,
                'father_name' => $validated['father_name'],
                'address' => $validated['address'],
                'desired_position' => $validated['desired_position'],
                'application_status' => 'pending',
                'applied_at' => now(),
            ]);

            return $user;
        });

        $this->captchaService->forget();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
