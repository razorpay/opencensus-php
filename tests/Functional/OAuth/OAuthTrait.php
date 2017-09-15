<?php

namespace RZP\Tests\Functional\OAuth;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use Lcobucci\JWT\Token as JWTToken;
use Razorpay\OAuth\Tests\Helpers\OAuthTestHelper;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Services\AuthService;

trait OAuthTrait
{
    public function createOAuthApplication(array $attributes = [])
    {
        // Create Application
        $application = factory(Application\Entity::class)
                        ->create($attributes);

        // Create dev Client for the Application
        factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'environment'    => 'dev'
            ]);

        // Create prod Client for the Application
        factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'prod'
            ]);

        return $application;
    }

    public function createOAuthApplicationAndGetClientByEnv(string $env = 'dev')
    {
        $application = $this->createOAuthApplication();

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

        $defaultValues = $this->getDefaultAccessTokenValues($client);

        $attributes = array_merge($defaultValues, $attributes);

        $accessToken = factory(Token\Entity::class)->create($attributes);

        $jwt = (new OAuthTestHelper)->getJWT($accessToken);

        return $jwt;
    }

    public function tamperExpiryOfAccessToken(JWTToken $accessToken): string
    {
        $tokenComponents = explode('.', $accessToken);

        $payload = json_decode(base64_decode($tokenComponents[1]), true);

        $payload['exp'] = Carbon::today(Timezone::IST)->addDays(40)->timestamp;

        $tokenComponents[1] = base64_encode(json_encode($payload));

        $accessToken = implode('.', $tokenComponents);

        return $accessToken;
    }

    protected function getDefaultAccessTokenValues(Client\Entity $client): array
    {
        return [
            'client_id'  => $client->getId(),
            'expires_at' => Carbon::today(Timezone::IST)->addDays(30)->timestamp,
            'scopes'     => ['read_only']
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
     * @param string     $route
     * @param string     $method
     * @param array|null $requestParams
     */
    protected function setAuthServiceMockDetail(
        string $route,
        string $method,
        array $requestParams = null)
    {
        $this->authServiceMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with($route, $method, $requestParams)
             ->willReturn([]);
    }

    protected function getDefaultParamsForAuthServiceRequest()
    {
        return ['merchant_id' => '10000000000000'];
    }
}
