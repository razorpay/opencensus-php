<?php

namespace RZP\Tests\Functional\Fetch;

use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants;

class FetchTest extends TestCase
{
    use HeimdallTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/FetchTestData.php';

        parent::setUp();

        $this->fetchTestSetup();
    }

    public function testFetchRulesForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->createPayment();

        $this->startTest();
    }

    public function testErrorFetchRulesForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRulesForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->createPayment(['email' => $this->getTestData('request.content.email')]);

        $this->startTest();
    }

    public function testErrorFetchRulesForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEsRepositoryForProxyAuth()
    {
        $this->ba->proxyAuth();

        $payment = $this->createPayment();

        $this->mockEsSearchFunction([
            'hits' => [
                'hits' => [
                    [
                        '_id' => $payment->getId(),
                    ]
                ],
            ],
        ]);

        $this->startTest();
    }

    public function testFetchWithSignedIdForPrivateAuth()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $this->createPayment(['order_id' => $order->getId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testErrorFetchWithMaxCountForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchWithExpandsTrueForPrivateAuth()
    {
        $this->ba->privateAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->createPayment(['card_id' => $card->getId()]);

        $this->startTest();
    }

    public function testFindWithExpandForPrivateAuth()
    {
        $this->ba->privateAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->createPayment(['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testErrorFindWithExpandForPrivateAuth()
    {
        $this->ba->privateAuth();

        $payment = $this->createPayment();

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    /*
     *  Helper function
     */
    protected function mockEsSearchFunction($searchResponse)
    {
        $esMock = $this->createEsMock(['search']);

        $esMock->expects($this->once())
            ->method('search')
            ->willReturn($searchResponse);
    }

    protected function getTestData($key, $index = null)
    {
        $index = $index ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        return array_get($this->testData[$index], $key);
    }

    private function createPayment($override = [])
    {
        return $this->fixtures->create('payment', $override);
    }

    private function fetchTestSetup()
    {
        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }
}
