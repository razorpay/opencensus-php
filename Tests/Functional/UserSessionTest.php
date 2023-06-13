<?php

namespace Functional;

use App\Merchant\Constants;
use App\MerchantDetails\Service;
use App\Providers\GenericUser;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserSessionTest extends IlluminateTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app = \App::getFacadeRoot();

        $this->cache = $this->app['cache'];
        // clear the cache for an individual test case
        $this->cache->flush();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Tests getSessionData when user is not logged in
     */
    public function testGetSessionDataWhenUserIsNotLoggedIn()
    {
        $response = (new User\Service())->getSessionData($this->getQueryParams());

        self::assertCount(2, $response);
        self::assertNull($response[0]);
        self::assertNull($response[1]);
    }

    /**
     * Tests getSessionData with only containing attribute query as input
     */
    public function testGetSessionDataWithoutSourceRequest()
    {
        $userData = $this->setUpMockData([], false);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams());

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertArrayNotHasKey(User\Service::OAUTH_ACTION, $sessionData);
    }

    /**
     * Tests getSessionData with input containing query and source attributes for activated merchant
     */
    public function testGetSessionDataForActivatedMerchant()
    {
        $data = [
            'activation_status' => 'activated',
            'merchant'          => [
                'suspended_at'  => null
            ]
        ];
        $userData = $this->setUpMockData($data, true);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams(true));

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertEquals(Constants::OAUTH_ACTION_RENDER, $sessionData[User\Service::OAUTH_ACTION]);
    }

    /**
     * Tests getSessionData with input containing query and source attributes for merchant in under_review status
     */
    public function testGetSessionDataForMerchantUnderReview()
    {
        $data = [
            'activation_status' => 'under_review',
            'merchant'          => [
                'suspended_at'  => null
            ]
        ];
        $userData = $this->setUpMockData($data, true);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams(true));

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertEquals(Constants::OAUTH_ACTION_RENDER, $sessionData[User\Service::OAUTH_ACTION]);
    }

    /**
     * Tests getSessionData with input containing query and source attributes for merchant in needs_clarification status
     */
    public function testGetSessionDataForMerchantInNeedsClarification()
    {
        $data = [
            'activation_status' => 'needs_clarification',
            'merchant'          => [
                'suspended_at'  => null
            ]
        ];
        $userData = $this->setUpMockData($data, true);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams(true));

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertEquals(Constants::OAUTH_ACTION_RENDER, $sessionData[User\Service::OAUTH_ACTION]);
    }

    /**
     * Tests getSessionData with input containing query and source attributes for suspended merchant
     */
    public function testGetSessionDataForSuspendedMerchant()
    {
        $data = [
            'activation_status' => null,
            'merchant'          => [
                'suspended_at'  => 1686167616
            ]
        ];
        $userData = $this->setUpMockData($data, true);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams(true));

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertEquals(Constants::OAUTH_ACTION_REDIRECT, $sessionData[User\Service::OAUTH_ACTION]);
    }

    /**
     * Tests getSessionData with input containing query and source attributes for merchant in rejected status
     */
    public function testGetSessionDataForRejectedMerchant()
    {
        $data = [
            'activation_status' => 'rejected',
            'merchant'          => [
                'suspended_at'  => null
            ]
        ];
        $userData = $this->setUpMockData($data, true);
        $merchantData = $userData['merchants']->toArray();

        $response = (new User\Service())->getSessionData($this->getQueryParams(true));

        self::assertCount(2, $response);
        self::assertNull($response[0]);

        $sessionData = $response[1];
        $this->checkAndVerifyResponse($sessionData, $userData, $merchantData[0]);
        self::assertEquals(Constants::OAUTH_ACTION_REDIRECT, $sessionData[User\Service::OAUTH_ACTION]);
    }

    private function getQueryParams(bool $source = false) : array
    {
        $params = [
            'query'  => 'client_id=KuTbMlS3qbAaM5&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=code&amp;scope=read_only&amp;state=current_state',
        ];

        if ($source)
        {
            $params['source'] = 'oauth';
        }
        return $params;
    }

    private function checkAndVerifyResponse($response, $userData, $merchantData)
    {
        self::assertArrayHasKey('token', $response);
        self::assertEquals($userData['email'], $response['email']);
        self::assertEquals($userData['name'], $response['name']);
        self::assertEquals($merchantData->id, $response['merchant_id']);
        self::assertEquals($merchantData->role, $response['role']);
        self::assertEquals($merchantData->name, $response['merchant_name']);
        self::assertNull($response['logo']);
        self::assertEquals($userData['id'], $response['user_id']);
    }

    private function fetchMockedData() : array
    {
        $merchantData = [
            'id' => 1,
            'role' => 'owner',
            'name' => 'John Doe',
            'banking_role' => 'owner',
            'logo_url' => null
        ];

        return [
            'id'     => 1,
            'name'   => 'John Doe',
            'email' => 'test@gmail.com',
            'merchants' => new Collection([
                (object) $merchantData
            ])
        ];
    }

    private function setUpMockData($data, $mockHttpRequest = true)
    {
        if ($mockHttpRequest)
        {
            $mockInstance = \Mockery::mock("overload:" . Service::class);
            $mockInstance->shouldReceive('fetchDetails')->times()->andReturn($data);
        }

        $userData = $this->fetchMockedData();

        $user = new GenericUser($userData);

        $this->actingAs($user);
        Auth::login($user);

        return $userData;
    }
}
