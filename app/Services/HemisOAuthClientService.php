<?php

namespace App\Services;
use League\OAuth2\Client\Provider\GenericProvider;
class HemisOAuthClientService
{
    /**
     * Create a new class instance.
     */
    private $provider;

    private $employeeProvider;

    public function __construct()
    {
        $this->provider = new GenericProvider([
            'clientId' => config('services.hemis.client_id'),
            'clientSecret'            => config('services.hemis.client_secret'),
            'redirectUri'             => config('services.hemis.redirect'),
            'urlAuthorize'            => config('services.hemis.authorize_url'),
            'urlAccessToken'          => config('services.hemis.token_url'),
            'urlResourceOwnerDetails' => config('services.hemis.resource_url'),
        ]);
    }

    public function provider()
    {
        return $this->provider;
    }

    public function employeeProvider()
    {
        if ($this->employeeProvider === null) {
            $this->employeeProvider = new GenericProvider([
                'clientId'                => config('services.hemis.employee.client_id'),
                'clientSecret'            => config('services.hemis.employee.client_secret'),
                'redirectUri'             => config('services.hemis.employee.redirect'),
                'urlAuthorize'            => config('services.hemis.employee.authorize_url'),
                'urlAccessToken'          => config('services.hemis.employee.token_url'),
                'urlResourceOwnerDetails' => config('services.hemis.employee.resource_url'),
            ]);
        }

        return $this->employeeProvider;
    }
}
