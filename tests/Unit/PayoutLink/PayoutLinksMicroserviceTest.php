<?php

namespace RZP\Tests\Unit\PayoutLink;

use Mockery;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutLink\Service;

class PayoutLinkMicroserviceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function setUpMocksAndFeature(string $methodName) : array
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive($methodName)->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        $this->fixtures->create('feature',
            [
                'name'      => Constants::X_PAYOUT_LINKS_MS,
                'entity_id' => '12345678901234'
            ]);

        return [
            'auth' => $auth,
            'mock' => $plMock,
            'service' => new Service()
        ];
    }

    public function testGetSettings()
    {
        $result = $this->setUpMocksAndFeature('getSettings');

        $this->ba->adminAuth();

        $result['service']->getSettings('10000000000000');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getSettings');
    }

    public function testUpdateSettings()
    {
        $result = $this->setUpMocksAndFeature('updateSettings');

        $this->ba->adminAuth();

        $result['service']->updateSettings([], '10000000000000');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('updateSettings');
    }

    public function testCancel()
    {
        $result = $this->setUpMocksAndFeature('cancel');

        $this->ba->privateAuth();

        $result['service']->cancel('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('cancel');
    }

    public function testFetch()
    {
        $result = $this->setUpMocksAndFeature('fetch');

        $this->ba->privateAuth();

        $result['service']->fetchMerchantSpecific('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetch');
    }

    public function testFetchMultiple()
    {
        $result = $this->setUpMocksAndFeature('fetchMultiple');

        $this->ba->privateAuth();

        $result['service']->fetchMultipleMerchantSpecific([]);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('fetchMultiple');
    }

    public function testInitiate()
    {
        $result = $this->setUpMocksAndFeature('initiate');

        $result['service']->initiate('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('initiate');
    }

    public function testGenerateAndSendCustomerOtp()
    {
        $result = $this->setUpMocksAndFeature('generateAndSendCustomerOtp');

        $result['service']->generateAndSendCustomerOtp('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('generateAndSendCustomerOtp');
    }

    public function testGetFundAccountsOfContact()
    {
        $result = $this->setUpMocksAndFeature('getFundAccountsOfContact');

        $result['service']->getFundAccountsOfContact('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getFundAccountsOfContact');
    }

    public function testGetHostedPageDatat()
    {
        $result = $this->setUpMocksAndFeature('getHostedPageData');

        $result['service']->viewHostedPage('');

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('getHostedPageData');
    }

    public function testVerifyCustomerOtp()
    {
        $result = $this->setUpMocksAndFeature('verifyCustomerOtp');

        $result['service']->verifyCustomerOtp('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('verifyCustomerOtp');
    }

    public function testResendNotification()
    {
        $result = $this->setUpMocksAndFeature('resendNotification');

        $result['service']->resendNotification('', []);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('resendNotification');
    }

    public function testOnBoardingStatus()
    {
        $result = $this->setUpMocksAndFeature('onBoardingStatus');

        $result['service']->onBoardingStatus();

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('onBoardingStatus');
    }

    public function testSummary()
    {
        $result = $this->setUpMocksAndFeature('summary');

        $result['service']->summary([]);

        // assert that the microservice method was called when feature was enabled
        $result['mock']->shouldHaveReceived('summary');
    }
}
