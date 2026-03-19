<?php
namespace App\Http\Controllers;

use App\Services\HemisOAuthClientService;
use App\Services\HemisStudentAuthenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class HemisAuthController extends Controller
{
    public function __construct(
        private HemisOAuthClientService $service,
        private HemisStudentAuthenticator $authenticator,
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
}
