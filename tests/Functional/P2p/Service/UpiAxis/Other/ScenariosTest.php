<?php
namespace RZP\Tests\P2p\Service\UpiAxis\Other;

use RZP\Models\P2p\Base\Entity;
use RZP\Models\P2p\Beneficiary;
use RZP\Tests\P2p\Service\Base;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class ScenariosTest extends TestCase
{
    use TransactionTrait;

    public function testCustomerReRegistration()
    {
        $baHelper = $this->getBankAccountHelper();

        $request = $baHelper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $content = $this->handleSdkRequest($request);

        $bankAccounts = $baHelper->retrieve($request['callback'], $content);

        $this->assertCollection($bankAccounts, 2, [
            [
                'masked_account_number' => 'xxxxxxxxxxxx000001',
            ],
            [
                'masked_account_number' => 'xxxxxxxxxxxx000002',
            ]
        ]);

        $dbBa = $this->getDbBankAccounts([
            Entity::DEVICE_ID       => $this->fixtures->device->getId(),
            Entity::HANDLE          => $this->fixtures->handle->getCode(),
        ]);

        $this->assertSame('UniqueWithCode000001', $dbBa[0][Entity::GATEWAY_DATA]['id']);
        $this->assertSame('UniqueWithCode000001', $dbBa[0][Entity::GATEWAY_DATA]['bankAccountUniqueId']);

        $this->assertSame('UniqueWithCode000002', $dbBa[1][Entity::GATEWAY_DATA]['id']);
        $this->assertSame('UniqueWithCode000002', $dbBa[1][Entity::GATEWAY_DATA]['bankAccountUniqueId']);

        $deviceHelper = $this->getDeviceHelper();

        $request = $deviceHelper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $response = $deviceHelper->getToken($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
            ]
        ]);

        // Since bank account was deleted when we retrieved the new accounts
        $this->assertNull($response['vpa']['bank_account']);

        $vpaHelper = $this->getVpaHelper();

        $assignRequest = $vpaHelper->assignBankAccount($response['vpa']['id'], $dbBa[0]->getPublicId());

        $content = $this->handleSdkRequest($assignRequest);

        $response = $vpaHelper->assignBankAccountCallback($assignRequest['callback'], $content);

        $this->assertSame($dbBa[0]->getPublicId(), $response['bank_account']['id']);

        $response = $deviceHelper->getToken($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
            ]
        ]);

        // Verifying the assign bank account API
        $this->assertSame($dbBa[0]->getPublicId(), $response['vpa']['bank_account']['id']);

        // Now we will deregister
        $response = $deviceHelper->deregisterDevice();

        $this->assertTrue($response['success']);

        $this->withFailureResponse($deviceHelper, function($error)
        {
            $this->assertArraySubset([
                'action' => 'initiateVerification',
            ], $error);
        });

        $request = $deviceHelper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $deviceHelper->expectFailureInResponse(false);

        $initiate = $deviceHelper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $response = $deviceHelper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => $this->fixtures->device->getContact(),
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

        // Since auth token is changed after verification
        $this->fixtures->device->refresh();

        $this->assertSame($this->fixtures->vpa->getPublicId(), $response['vpa']['id']);

        $request = $baHelper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $content = $this->handleSdkRequest($request);

        $bankAccounts = $baHelper->retrieve($request['callback'], $content);

        $this->assertCollection($bankAccounts, 2, [
            [
                'masked_account_number' => 'xxxxxxxxxxxx000001',
            ],
            [
                'masked_account_number' => 'xxxxxxxxxxxx000002',
            ]
        ]);

    }

    public function testBeneficiariesAndVpa()
    {
        $bfHelper = $this->getBeneficiaryHelper();

        $vpaHelper = $this->getVpaHelper();

        $bf = $this->fixtures->createBeneficiary([]);

        $bf2 = $this->fixtures->createBeneficiary([]);

        $bfList = $bfHelper->fetch();

        $this->assertCollection($bfList, 2, [
            [
                Beneficiary\Entity::ID => 'vpa_' . $bf->getEntityId(),
            ],
            [
                Beneficiary\Entity::ID => 'vpa_' . $bf2->getEntityId(),
            ]
        ]);

        $oldVpa = $vpaHelper->fetchAllVpa();

        $deviceHelper = $this->getDeviceHelper();

        $response = $deviceHelper->deregisterDevice();

        $this->assertTrue($response['success']);

        $this->withFailureResponse($deviceHelper, function($error)
        {
            $this->assertArraySubset([
                'action' => 'initiateVerification',
            ], $error);
        });

        $deviceHelper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $deviceHelper->expectFailureInResponse(false);

        $initiate = $deviceHelper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $deviceHelper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => $this->fixtures->device->getContact(),
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

        // Since auth token is changed after verification
        $this->fixtures->device->refresh();

        $bfList = $bfHelper->fetch();

        $this->assertCollection($bfList, 0);

        $this->assertNull($this->getDbLastEntity('p2p_beneficiary'));

        $vpaList = $vpaHelper->fetchAllVpa();

        $this->assertCollection($vpaList, $oldVpa['count'], [
            [
                Entity::ID => $oldVpa['items'][0]['id']
            ]
        ]);
    }

    public function testRestoreVpa()
    {
        $vpaHelper = $this->getVpaHelper();

        $request = $vpaHelper->intiateCreateVpa();

        $content = $this->handleSdkRequest($request);

        $request = $vpaHelper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $response = $vpaHelper->createVpa($request['callback'], $content);

        $vpa = $this->getDbLastVpa();

        $this->assertArraySubset([
            'username'          => 'random',
            'bank_account_id'   => $this->fixtures->bank_account->getId(),
            'default'           => null,
        ], $vpa->toArray());

        $response = $vpaHelper->setDefault($vpa->getPublicId());

        $this->assertTrue($response['default']);
        $this->assertTrue($vpa->refresh()->isDefault());
        $this->assertFalse($this->fixtures->vpa->refresh()->isDefault());

        $response = $vpaHelper->deleteVpa($this->fixtures->vpa->getPublicId());
        $this->assertTrue($response['success']);
        $this->assertTrue($this->fixtures->vpa->refresh()->trashed());

        $newBankAcc = $this->fixtures->createBankAccount([
            'gateway_data'  => [
                'referenceId'   => 'HiIam',
                'id'            => 'HiAgain'
            ],
        ]);

        $request = $vpaHelper->intiateCreateVpa([
            'username'          => $this->fixtures->vpa->getUsername(),
            'bank_account_id'   => $newBankAcc->getPublicId(),
        ]);

        $content = $this->handleSdkRequest($request);

        $request = $vpaHelper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $response = $vpaHelper->createVpa($request['callback'], $content);

        $this->assertSame($newBankAcc->getPublicId(), $response['bank_account']['id']);
    }

    public function testRelinkBankAccount()
    {
        $deviceHelper = $this->getDeviceHelper();

        $vpaHelper = $this->getVpaHelper();

        $baHelper  = $this->getBankAccountHelper();

        $vpaId = $this->fixtures->vpa->getPublicId();

        $newBa = $this->fixtures->createBankAccount([
            'gateway_data' => [
                'referenceId' => $vpaId,
            ]
        ]);

        $baId  = $newBa->getPublicId();

        $request = $vpaHelper->assignBankAccount($vpaId, $baId);

        $content = $this->handleSdkRequest($request);

        $response = $vpaHelper->assignBankAccountCallback($request['callback'], $content);

        $this->assertSame($baId, $response['bank_account']['id']);

        $this->setDeviceTokenExpiryValidation(true);

        $this->withFailureResponse($baHelper, function ($error)
        {
            $this->assertArraySubset([
                'action' => 'initiateGetToken',
            ], $error);
        });

        // Will Throw exception
        $baHelper->initiateFetchBalance($baId);

        $request = $deviceHelper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $deviceHelper->getToken($request['callback'],
            [
                Fields::SDK => [
                    Fields::STATUS                  => 'SUCCESS',
                    Fields::IS_DEVICE_BOUND         => 'true',
                    Fields::IS_DEVICE_ACTIVATED     => 'true',
                    Fields::DEVICE_FINGERPRINT      => '61F275C82A0AECC4788FA',
                ],
            ]);

        $baHelper->expectFailureInResponse(false);

        $response = $baHelper->initiateFetchBalance($baId);

        $this->assertSame($vpaId, $response['request']['content']['accountReferenceId']);
    }
}
