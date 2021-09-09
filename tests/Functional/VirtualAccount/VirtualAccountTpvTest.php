<?php

namespace RZP\Tests\Functional\VirtualAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountTpvTest extends TestCase
{
    use TestsWebhookEvents;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualAccountTpvTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testCreateVirtualBankAccountWithTpv()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualVpaWithTpv()
    {
        self::markTestSkipped();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($expectedResponse, function()
        {
            $this->createVirtualAccount($this->testData['createVAWithAllowedPayer'], false, null,
                                        null, true, 'virtualVpa');
        });
    }

    public function testWebhookVirtualAccountCreatedWithAllowedPayers()
    {
        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('virtual_account.created', $expectedEvent);

        $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);
    }

    public function testWebhookVirtualAccountClosedWithAllowedPayers()
    {
        $virtualAccount = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $expectedEvent = $this->testData[__FUNCTION__]['event'];

        $this->expectWebhookEventWithContents('virtual_account.closed', $expectedEvent);

        $this->closeVirtualAccount($virtualAccount['id']);
    }

    public function testFetchVirtualAccountWithAllowedPayer()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testCreateVirtualAccountWithInvalidAllowedPayerType()
    {
        $this->createAndTestVirtualAccountWithTPV(__FUNCTION__);
    }

    public function testCreateVirtualAccountWithMissingAllowedPayerDetails()
    {
        $this->createAndTestVirtualAccountWithTPV(__FUNCTION__);
    }

    protected function createAndTestVirtualAccountWithTPV($testFunction)
    {
        $data = $this->testData[$testFunction];

        $allowedPayerDetails = $data['request'];

        unset($data['request']);

        $this->runRequestResponseFlow($data, function() use ($allowedPayerDetails)
        {
            $this->createVirtualAccount($allowedPayerDetails);
        });
    }

    public function testAddIciciVpaReceiverToVaWithoutTpv()
    {
        $response         = $this->createVirtualAccount();
        $virtualAccountId = $response['id'];

        $response = $this->addReceiverToVirtualAccount($virtualAccountId, 'vpa');

        $expectedResponse = $this->testData[__FUNCTION__];
        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testAddTpvToExistingVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $this->addTpvToVirtualAccount($response['id'], __FUNCTION__);
    }

    public function testAddTpvToExistingVirtualAccountWithoutIfsc()
    {
        $response = $this->createVirtualAccount();

        $this->addTpvToVirtualAccount($response['id'],__FUNCTION__);
    }

    public function testAddTpvToExistingVirtualAccountWithoutBankAccountNumber()
    {
        $response = $this->createVirtualAccount();

        $this->addTpvToVirtualAccount($response['id'],__FUNCTION__);
    }

    public function testAddTpvToExistingVirtualAccountWithInvalidIfsc()
    {
        $response = $this->createVirtualAccount();

        $this->addTpvToVirtualAccount($response['id'],__FUNCTION__);
    }

    public function testAddTpvToExistingVirtualAccountWithTpv()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $this->addTpvToVirtualAccount($response['id'], __FUNCTION__);
    }

    public function testDuplicateAddTpvToExistingVirtualAccountWithTpv()
    {
        self::markTestSkipped();

        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $this->addTpvToVirtualAccount($response['id'], __FUNCTION__);
    }

    public function testAddTpvToClosedVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $this->closeVirtualAccount($response['id']);

        $this->addTpvToVirtualAccount($response['id'], __FUNCTION__);
    }

    public function addTpvToVirtualAccount($virtualAccountId, $function)
    {
        $this->ba->privateAuth();

        $testData = $this->testData[$function];

        $testData['request']['url'] = '/virtual_accounts/' . $virtualAccountId . '/allowed_payers';
        $this->startTest($testData);
    }

    public function deleteTpvForVirtualAccount($virtualAccountId, $tpvId)
    {
        $this->ba->privateAuth();

        $request = [
            'method' => 'DELETE',
            'url'    => '/virtual_accounts/' . $virtualAccountId . '/allowed_payers/' . $tpvId,
        ];

        return $this->makeRequestAndGetRawContent($request);
    }

    public function testDeleteTpvForVirtualAccount()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $tpvId = $response['allowed_payers'][0]['id'];

        $deleteResponse = $this->deleteTpvForVirtualAccount($response['id'], $tpvId);

        $this->assertEquals('204', $deleteResponse->getStatusCode());

        $virtualAccount = $this->fetchVirtualAccount($response['id']);

        $this->assertCount(1, $virtualAccount['allowed_payers']);
    }

    public function testDeleteTpvForClosedVirtualAccount()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $this->closeVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($expectedResponse, function() use ($response)
        {
            $tpvId = $response['allowed_payers'][0]['id'];

            $this->deleteTpvForVirtualAccount($response['id'], $tpvId);
        });
    }

    public function testDeleteTpvForVirtualAccountWithInvalidAllowedPayerId()
    {
        $response = $this->createVirtualAccount($this->testData['createVAWithAllowedPayer']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($expectedResponse, function() use ($response)
        {
            $tpvId = 'ba_HuceCC9CHQWics';

            $this->deleteTpvForVirtualAccount($response['id'], $tpvId);
        });
    }
}
