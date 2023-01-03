<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Models\P2p\Vpa\Entity;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;
use RZP\Exception\BadRequestValidationFailureException;

class VpaTest extends TestCase
{
    use TransactionTrait;

    public function testFetchHandles()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $handles = $helper->fetchHandles();

        $this->assertCollection($handles, 5);
    }

    public function testInitiateCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->intiateCreateVpa();
    }

    public function testInitiateCreateVpaWithPhonenumber()
    {
        $helper = $this->getVpaHelper();

        $response = $helper->intiateCreateVpa([
            'username' => substr($this->fixtures->device->getContact(), -10),
        ]);

        $this->assertSame('9988771111@razoraxis', $response['request']['content']['customerVpa']);
    }

    public function testCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $request = $helper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $helper->createVpa($request['callback'], $content);
    }

    public function testCreateVpaSuggestion()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa([
            'username' => null
        ]);

        /**
         * Asserting that the vpa suffix is there in the suggested vpa
         */
        $this->assertStringContainsString('suf', $request['request']['content']['customerVpa']);
    }

    public function testCreateVpaWithUppercaseUsername()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa([
            'username' => 'RandomCaps'
        ]);

        $this->assertSame('randomcaps@razoraxis', $request['request']['content']['customerVpa']);

        $content = $this->handleSdkRequest($request);

        $request = $helper->createVpa($request['callback'], $content);

        $this->assertSame('randomcaps@razoraxis', $request['request']['content']['customerVpa']);

        $content = $this->handleSdkRequest($request);

        $vpa = $helper->createVpa($request['callback'], $content);

        $this->assertSame('randomcaps@razoraxis', $vpa['address']);
    }

    public function testFetchVpa()
    {
        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->fetchVpa($vpaId);
    }

    public function testFetchAllVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->fetchAllVpa();
    }

    public function testFetchAllVpaWithDeleted()
    {
        $helper = $this->getVpaHelper();

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $helper->withSchemaValidated();

        $response = $helper->fetchAllVpa();

        $this->assertSame(2, $response['count']);

        $this->assertArrayHasKey('deleted_at', $response['items'][0]);

        $helper->deleteVpa($vpa->getPublicId());

        $response = $helper->fetchAllVpa();

        $this->assertSame(1, $response['count']);

        $response = $helper->fetchAllVpa(['deleted' => 1]);

        $vpas = $response['items'];

        $this->assertSame(2, $response['count']);

        $this->assertArrayHasKey('deleted_at', $vpas[0]);
        $this->assertArrayHasKey('deleted_at', $vpas[1]);

        $this->assertNull($vpas[0]['bank_account']);
        $this->assertNotNull($vpas[1]['bank_account']);
    }

    public function testAssignBankAccountForDeletedVpa()
    {
        $helper = $this->getVpaHelper();

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $helper->deleteVpa($vpa->getPublicId());

        $bankAccount = $this->fixtures->createBankAccount([
            'gateway_data' => [
                'referenceId' => 'SomeReferenceId'
            ]
        ]);

        $request = $helper->assignBankAccount($vpa->getPublicId(), $bankAccount->getPublicId());

        $content = $this->handleSdkRequest($request);

        $vpa = $helper->assignBankAccountCallback($request['callback'], $content);

        $this->assertNull($vpa['deleted_at']);
    }

    public function testInitiateVpaAvailability()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->initiateCheckVpaAvailable();
    }

    public function testCheckVpaAvailability()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->initiateCheckVpaAvailable();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $response = $helper->checkAvailability($request['callback'], $content);
    }

    public function testCheckVpaAvailabilityWithSuggestions()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->initiateCheckVpaAvailable();

        $suggestions = [
            'sample1@razoraxis',
            'sample2@razoraxis',
            'sample3@razoraxis'
        ];

        $this->mockSdkContentFunction(function(& $content) use ($suggestions)
        {
            $content['available'] = false;

            $content['vpaSuggestions'] = $suggestions;
        });

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $response = $helper->checkAvailability($request['callback'], $content);

        $this->assertArrayHasKey(Entity::SUGGESTIONS, $response);
        $this->assertSame($response[Entity::SUGGESTIONS], array_map(
            function($item)
            {
                return explode('@', $item)[0];
            }, $suggestions));
    }

    public function testAssignBankAccount()
    {
        $bankAccount = $this->fixtures->createBankAccount([
            'gateway_data' => [
                'referenceId' => 'SomeReferenceId'
            ]
        ]);

        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $request = $helper->assignBankAccount($vpaId, $bankAccount->getPublicId());

        $content = $this->handleSdkRequest($request);

        $helper->assignBankAccountCallback($request['callback'], $content);

        $this->assertSame($bankAccount->getId(), $this->fixtures->vpa->reload()->getBankAccountId());

        //For axis we expire device token after assigning bank account to refresh their SDK content
        $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1);
        $expireAt    = $deviceToken->getGatewayData()['expire_at'];

        $this->assertTrue($deviceToken->shouldRefresh());
        $this->assertGreaterThanOrEqual($this->testCurrentTime->getTimestamp(), $expireAt);

    }

    public function testDeleteVpa()
    {
        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $transaction = $this->createCollectIncomingTransaction([
            'payer_id' => $vpa->getId(),
        ]);

        $this->assertArraySubset([
            'status'        => 'requested',
            'payer_id'      => $vpa->getId(),
        ], $transaction->toArray());

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpa->getPublicId());

        $this->assertTrue($vpa->refresh()->trashed());

        // Pending collect transaction should be deleted
        $this->assertTrue($transaction->refresh()->trashed());
    }

    public function testSetDefault()
    {
        $default = $this->fixtures->vpa;

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $this->assertTrue($default->isDefault());

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $response = $helper->setDefault($vpa->getPublicId());

        $this->assertTrue($response['default']);

        $this->assertTrue($vpa->refresh()->isDefault());

        $this->assertFalse($default->refresh()->isDefault());
    }

    public function testInitiateCreateVpaForAlreadyTakenVpa()
    {
        $this->testCreateVpa();

        $vpa = $this->fixtures->getDbLastVpa();

        $helper = $this->getVpaHelper();

        $this->withFailureResponse($helper, function ($error, $response) {
            $this->assertSame('BAD_REQUEST_ERROR', $error['code']);
            $this->assertSame('Duplicate VPA address, try a different username', $error['description']);
        });

        $helper->intiateCreateVpa([
            'username' => $vpa->getUsername(),
        ]);
    }

    public function testInitiateVpaAvailabilityForAlreadyTakenVpa()
    {
        $this->testCreateVpa();

        $vpa = $this->fixtures->getDbLastVpa();

        $helper = $this->getVpaHelper();

        $helper->initiateCheckVpaAvailable([
            'username' => $vpa->getUsername(),
        ]);
    }

    public function testFetchVpaAndBankAccountByDeviceId()
    {
        $helper = $this->getDeviceHelper()->setMerchantOnAuth(true);

        $device = $this->fixtures->device(self::DEVICE_1);

        $response = $helper->fetchAll(['contact' => $device->getContact()]);

        $deviceId = $response['items'][0]['id'];

        $helper = $this->getVpaHelper()->setMerchantOnAuth(true);

        $response = $helper->fetchAllVpa(['device_id'  => $deviceId]);

        $this->assertEquals($response['items'][0]['entity'],'vpa');
    }

    public function testFetchVpaAndBankAccountWithoutDeviceId()
    {
        $helper = $this->getDeviceHelper()->setMerchantOnAuth(true);

        $device = $this->fixtures->device(self::DEVICE_1);

        $response = $helper->fetchAll(['contact' => $device->getContact(),]);

        $deviceId = $response['items'][0]['id'];

        $helper = $this->getVpaHelper()->setMerchantOnAuth(true);

        $this->expectException(BadRequestValidationFailureException::class);

        $response = $helper->fetchAllVpa([]);
    }
}
