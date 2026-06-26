<?php
namespace App\Http\Controllers;

use App\Services\HemisEmployeeAuthenticator;
use App\Services\HemisOAuthClientService;
use App\Services\HemisPasswordAuthService;
use App\Services\HemisStudentAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class HemisAuthController extends Controller
{
    public function __construct(
        private HemisOAuthClientService $service,
        private HemisPasswordAuthService $passwordAuthService,
        private HemisStudentAuthenticator $authenticator,
        private HemisEmployeeAuthenticator $employeeAuthenticator,
    )
    {
    }
    public function redirectToHemis()
    {
        $authorizationUrl = $this->service->provider()->getAuthorizationUrl();
        session(['oauth2state' => $this->service->provider()->getState()]);
        return redirect()->away($authorizationUrl);
    }
    public function login(Request $request)
    {
        try {
            if ($request->state !== session('oauth2state')) {
                return abort(403, 'Invalid state');
            }

            $accessToken = $this->service->provider()->getAccessToken('authorization_code', [
                'code' => $request->code,
            ]);

            $resourceOwner = $this->service->provider()->getResourceOwner($accessToken);
            $user = $this->authenticator->syncFromResourceOwner($resourceOwner->toArray());

            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->route('home')->withErrors($e->getMessage());
        }
    }

    public function redirectToHemisEmployee()
    {
        $authorizationUrl = $this->service->employeeProvider()->getAuthorizationUrl();
        session(['oauth2state_employee' => $this->service->employeeProvider()->getState()]);
        return redirect()->away($authorizationUrl);
    }

    public function employeeLogin(Request $request)
    {
        try {
            if ($request->state !== session('oauth2state_employee')) {
                return abort(403, 'Invalid state');
            }

            $accessToken = $this->service->employeeProvider()->getAccessToken('authorization_code', [
                'code' => $request->code,
            ]);

            $resourceOwner = $this->service->employeeProvider()->getResourceOwner($accessToken);
            $user = $this->employeeAuthenticator->syncFromResourceOwner($resourceOwner->toArray());

            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect()->route('home')->withErrors($e->getMessage());
        }
    }

    public function passwordLogin(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required'],
            'password' => ['required', 'string'],
        ]);

        try {
            $authData = $this->passwordAuthService->login($validated['login'], $validated['password']);
            $accountData = $this->passwordAuthService->me($authData['token']);

            $user = $this->authenticator->syncFromAccountMe(
                $accountData,
                $validated['login'],
                $validated['password']
            );

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        } catch (Exception $e) {
            Log::warning('HEMIS password login failed', [
                'login' => $validated['login'],
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'login' => $e->getMessage(),
            ]);
        }
    }
}
