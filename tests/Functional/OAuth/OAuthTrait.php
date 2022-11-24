<?php

namespace RZP\Tests\Functional\OAuth;

use Mockery;
use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use Lcobucci\JWT\Token as JWTToken;
use Razorpay\OAuth\Tests\Helpers\OAuthTestHelper;

use Carbon\Carbon;
use RZP\Services\Stork;
use RZP\Constants\Timezone;
use RZP\Services\AuthService;
use RZP\Models\Merchant\MerchantApplications\Entity;
use RZP\Models\Merchant\Constants as MerchantConstants;

trait OAuthTrait
{
    public function createOAuthApplication(array $attributes = [], bool $createMerchantApplication = true)
    {
        $partnerType = null;

        $deletedAt = null;

        if (empty($attributes['partner_type']) === false)
        {
            $partnerType = $attributes['partner_type'];

            unset($attributes['partner_type']);
        }

        if (empty($attributes['deleted_at']) === false)
        {
            $deletedAt = $attributes['deleted_at'];
        }

        // Create Application
        $application = Application\Entity::factory()->create($attributes);

        if ($createMerchantApplication === true)
        {
            $this->createMerchantApplication($application->merchant_id, $partnerType, $application->getId(), $deletedAt);
        }

        $clientAttributes = [
            Client\Entity::APPLICATION_ID => $application->id,
            Client\Entity::REDIRECT_URL   => ['http://www.example.com'],
        ];

        if (empty($attributes['merchant_id']) === false)
        {
            $clientAttributes['merchant_id'] = $attributes['merchant_id'];
        }

        // Create dev Client for the Application
        $clientAttributes['environment'] = 'dev';
        Client\Entity::factory()->create($clientAttributes);

        // Create prod Client for the Application
        $clientAttributes['environment'] = 'prod';
        Client\Entity::factory()->create($clientAttributes);

        return $application;
    }

    public function createOAuthApplicationAndGetClientByEnv(string $env = 'dev')
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        return $this->getAppClientByEnv($application, $env);
    }

    public function createMerchantApplication(string $merchantId, $type, string $appId, $deletedAt = null)
    {
        switch ($type)
        {
            case null:
                return;
            case MerchantConstants::RESELLER:
                $appType = Entity::REFERRED;
                break;
            case MerchantConstants::PURE_PLATFORM:
                $appType = Entity::OAUTH;
                break;
            default:
                $appType = Entity::MANAGED;
                break;
        }

        return $this->fixtures->create(
            'merchant_application',
            [
                'merchant_id'       => $merchantId,
                'type'              => $appType,
                'application_id'    => $appId,
                'deleted_at'        => $deletedAt,
            ]
        );
    }

    public function createPartnerApplicationAndGetClientByEnv(string $env = 'dev', array $attributes = [])
    {
        $attributes = array_merge($attributes, ['type' => 'partner']);

        $application = $this->createOAuthApplication($attributes);

        return $this->getAppClientByEnv($application, $env);
    }

    public function getAppClientByEnv(Application\Entity $application, string $env = 'dev')
    {
        return $application->clients()
                           ->get()
                           ->filter(
                               function($client, $key) use ($env)
                               {
                                   return $client->getEnvironment() === $env;
                               })
                           ->first();
    }

    public function generateOAuthAccessToken(array $attributes = [], string $env = 'dev')
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv($env);

        return $this->generateOAuthAccessTokenForClient($attributes, $client);
    }

    public function generateOAuthAccessTokenForPassport(array $attributes = [], string $env = 'dev')
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv($env);

        return $this->generateOAuthAccessTokenPassposrtForClient($attributes, $client);
    }

    public function generateOAuthAccessTokenPassposrtForClient(array $attributes = [], $client)
    {
        $defaultValues = $this->getDefaultAccessTokenValues($client);

        $attributes = array_merge($defaultValues, $attributes);

        $accessToken = Token\Entity::factory()->create($attributes);

        $jwt = (new OAuthTestHelper)->getJWT($accessToken);

        return [$jwt, $accessToken];
    }

    public function generateOAuthAccessTokenForClient(array $attributes = [], $client)
    {
        $defaultValues = $this->getDefaultAccessTokenValues($client);

        $attributes = array_merge($defaultValues, $attributes);

        $accessToken = Token\Entity::factory()->create($attributes);

        $jwt = (new OAuthTestHelper)->getJWT($accessToken);

        return $jwt;
    }

    public function tamperExpiryOfAccessToken(JWTToken $accessToken): string
    {
        $tokenComponents = explode('.', $accessToken->toString());

        $payload = json_decode(base64_decode($tokenComponents[1]), true);

        $payload['exp'] = Carbon::today(Timezone::IST)->addDays(40)->timestamp;

        $tokenComponents[1] = base64_encode(json_encode($payload));

        $accessToken = implode('.', $tokenComponents);

        return $accessToken;
    }

    protected function getDefaultAccessTokenValues(Client\Entity $client): array
    {
        return [
            'client_id'    => $client->getId(),
            'expires_at'   => Carbon::today(Timezone::IST)->addDays(30)->timestamp,
            'scopes'       => ['read_only'],
            'type'         => 'access_token',
            'public_token' => 'TheTestAuthKey',
        ];
    }

    /**
     * Creates a mock of AuthService and sets it to be used when invoked from app.
     * Also returns the same mock for setting expectations.
     *
     * @return object
     */
    protected function createAuthServiceMock(array $withMethods = [])
    {
        $authServiceMock = $this->getMockBuilder(AuthService::class)
                                ->setConstructorArgs([$this->app])
                                ->setMethods($withMethods)
                                ->getMock();

        $this->app->instance('authservice', $authServiceMock);

        return $authServiceMock;
    }

    /**
     * Sets mock details (arguments and return types) for sendRequest()
     * method of AuthService instance.
     * Only that method is mocked for better coverage of AuthService.
     *
     * @param string $route
     * @param string $method
     * @param array|null $requestParams
     * @param int $times
     * @param array $response
     */
    protected function setAuthServiceMockDetail(
        string $route,
        string $method,
        array  $requestParams = null,
        int    $times = 1,
        array  $response = [])
    {
        $res = $this->authServiceMock
             ->expects($this->exactly($times))
             ->method('sendRequest')
             ->with($route, $method, $requestParams)
             ->willReturn($response);

        //var_dump($res);

        return $res;
    }

    protected function getDefaultParamsForAuthServiceRequest()
    {
        return ['merchant_id' => '10000000000000'];
    }

    public function expectstorkInvalidateAffectedOwnersCacheRequest(string $merchantId, string $mode = null)
    {
        // 0. Expects in both modes if no mode arg is passed.
        if ($mode === null)
        {
            $this->expectstorkInvalidateAffectedOwnersCacheRequest($merchantId, 'live');
            $this->expectstorkInvalidateAffectedOwnersCacheRequest($merchantId, 'test');
            return;
        }

        // 1. Creates mock and replaces implementation, if not already.
        if ($this->app['stork_service'] instanceof Mockery\MockInterface === false)
        {
            $this->app->instance('stork_service', Mockery::mock(Stork::class)->makePartial());
        }

        // 2. Sets expectation.
        $matcher = function(array $arg) use ($merchantId, $mode)
        {
            $req = & $arg['invalidate_affected_owners_cache_request'];

            return (($merchantId === $req['owner_id']) and
                ($mode === str_after($req['service'], 'api-')) and
                ('merchant' === $req['owner_type']));
        };
        $this->app['stork_service']
            ->shouldReceive('publishOnSns')->once()->with(Mockery::on($matcher))->andReturn(null);
    }
}
