<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Speciality;
use App\Services\HemisOAuthClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class HemisAuthController extends Controller
{
    public function __construct(private HemisOAuthClientService $service)
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
            'code' => $request->code
        ]);

        $resourceOwner = $this->service->provider()->getResourceOwner($accessToken);
        $userData = $resourceOwner->toArray();
        if ($userData['data']['studentStatus']['name'] !== "O‘qimoqda") {
            return redirect()->route('home')->withErrors('Siz hozirda Institutda o‘qimayotganingiz uchun kira olmaysiz');
        }
        DB::beginTransaction();
        $group = Group::firstOrCreate(
            ['code' => $userData['groups'][0]['id']],
            [
                'name' => $userData['groups'][0]['name'],
                'education_lang' => $userData['groups'][0]['education_lang']['name'],
                'education_form' => $userData['groups'][0]['education_form']['name'],
                'education_type' => $userData['groups'][0]['education_type']['name']
            ]
        );
        $specialty = Speciality::firstOrCreate(
            ['code' => (int)$userData['data']['specialty']['code']],
            [
                'name' => $userData['data']['specialty']['name'],
            ]
        );
        $user = User::updateOrCreate(
            ['login' => (int)$userData['login']],
            [
                'name' => $userData['name'],
                'email' => $userData['email'] ?? $userData['login'] . '@ttysi.com',
                'phone' => $userData['phone'],
                'picture' => $userData['picture'],
                'birth_date' => $userData['birth_date'],
                'group_id' => $group->id,
                'password' => Hash::make($userData['passport_number']),
                'level' =>  $userData['data']['level']['name'],
                'speciality_id' => $specialty->id,
                'role' => 'student',
            ]
        );
        DB::commit();
        Auth::login($user);
        return redirect()->route('dashboard');    
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->route('home')->withErrors($e->getMessage());
        }
    }
}
