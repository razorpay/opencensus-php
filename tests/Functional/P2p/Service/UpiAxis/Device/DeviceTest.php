<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Device;

use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class DeviceTest extends TestCase
{
    use TransactionTrait;
    use EventsTrait;
    use TestsWebhookEvents;

    public function testInitiateVerification()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);
    }

    public function testVerification()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testVerificationEvents()
    {
        $this->expectWebhookEvent(
            'customer.verification.completed',
            function(array $event)
            {
                $this->assertArraySubset([
                    'customer_id'   => 'cust_ArzpLocalCust1',
                    'contact'       => '919742417121',
                    'entity'        => 'device',
                ], $event['payload']);

                $this->assertStringStartsWith('device_', $event['payload']['id']);
                $this->assertArrayNotHasKey('auth_token', $event['payload']);
            }
        );

        $this->mockRaven();

        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

        $this->assertRavenRequest(function($input)
        {
            $this->assertArraySubset([
                'receiver'  => '919742417121',
                'source'    => 'api.test.p2p',
                'template'  => 'sms.p2p.verification_completed',
                'sender'    => 'BajajP',
                'params'    => [
                    'app_name'      => 'Bajaj Finserv MARKETS',
                ],
            ], $input);
        });
    }

    public function testInitiateGetToken()
    {
        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $helper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);
    }

    public function testGetToken()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $helper->getToken($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testVerificationWithBinding()
    {
        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $helper->withSchemaValidated();

        $request = $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'false',
                Fields::IS_DEVICE_ACTIVATED       => 'false',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

         $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);
    }

    public function testDeregister()
    {
        $deviceToken = $this->fixtures->deviceToken(self::DEVICE_1);
        $bankAccount = $this->fixtures->bankAccount(self::DEVICE_1);
        $vpa         = $this->fixtures->vpa(self::DEVICE_1);
        $transaction = $this->createPayTransaction();
        $beneficiary = $this->fixtures->createBeneficiary([]);

        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $this->mockActionContentFunction([
            Device\Action::DEREGISTER => function(& $content)
            {
                $this->assertArrayHasKey('payload', $content);
            }]);

        $helper->deregisterDevice();

        $this->assertTrue($deviceToken->refresh()->trashed());
        $this->assertFalse($bankAccount->refresh()->trashed());
        $this->assertNull($vpa->refresh()->getBankAccountId());
        $this->assertTrue($transaction->refresh()->isCreated());
        $this->assertNull($this->getDbLastEntity('p2p_beneficiary'));
    }
}
