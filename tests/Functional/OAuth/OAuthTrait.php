<?php

namespace RZP\Tests\Functional\OAuth;

use RZP\Services\AuthService;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;

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
