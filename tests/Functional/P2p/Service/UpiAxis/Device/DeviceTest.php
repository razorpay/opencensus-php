<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Device;

use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\Functional\Helpers\MocksMetricTrait;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Models\P2p\Base\Metrics\GatewayActionMetric;
use RZP\Models\P2p\Base\Metrics\RequestLatencyMetric;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class DeviceTest extends TestCase
{
    use EventsTrait;
    use MocksMetricTrait;
    use TransactionTrait;
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
            /**
             * Asserting the notification , sender and app_name will be fetched from
             * p2p client, changing it to generic values, making the test
             * agnostic of merchant
             */
            $this->assertArraySubset([
                'receiver'  => '919742417121',
                'source'    => 'api.test.p2p',
                'template'  => 'sms.p2p.verification_completed',
                'sender'    => 'SENDER',
                'params'    => [
                    'app_name'       => 'APPLICATION NAME',
                    'sms_signature'  => 'SMS SIGNATURE',
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

        // Adding this here to test , we fetching MCC from gatewayData
        $client = $this->fixtures->client;

        $gatewayData = $client->getGatewayData()->put('mcc', '1720')->toArray();

        $client->setGatewayData($gatewayData)->save();

        $initiate = $helper->initiateGetToken([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $this->assertArraySubset([
                Fields::MCC    => '1720',
                Fields::SIM_ID => '0',
        ], $initiate['request']['content']);

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
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

        $this->assertSame('BIND_DEVICE', $request['request']['action']);

        $request = $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'false',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => [],
            ]
        ]);

        $this->assertSame('ACTIVATE_DEVICE_BINDING', $request['request']['action']);
        $udfString = $request['request']['content']['udfParameters'];
        $this->assertArrayHasKey('rsh', json_decode($udfString, true));

        $helper->withSchemaValidated(false);

        $this->withFailureResponse($helper,
            function($error)
            {
                $this->assertArraySubset([
                    'code'          => 'GATEWAY_ERROR',
                    'description'   => 'Action could not be completed at bank',
                ], $error);
            },
            502);

        $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => '[]',
            ]
        ]);

        $helper = $this->getDeviceHelper();

        $this->withFailureResponse($helper,
            function($error)
            {
                $this->assertArraySubset([
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'Access forbidden for requested resource',
                ], $error);
            },
            403);

        $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417122',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => $udfString,
            ]
        ]);

        $helper = $this->getDeviceHelper();

        $helper->withSchemaValidated();

        $response = $helper->verification($request['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'SUCCESS',
                Fields::IS_DEVICE_BOUND           => 'true',
                Fields::IS_DEVICE_ACTIVATED       => 'true',
                Fields::DEVICE_FINGERPRINT        => '61F275C82A0AECC4788FA',
                Fields::CUSTOMER_MOBILE_NUMBER    => '919742417121',
                Fields::VPA_ACCOUNTS              => [],
                Fields::UDF_PARAMETERS            => $udfString,
            ]
        ]);

        $this->assertArraySubset([
            Device\DeviceToken\Entity::STATUS => 'verified',
        ], $response);
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

    public function testDeregisterEvents()
    {
        $this->expectWebhookEvent(
            'customer.deregistration.completed',
            function(array $event)
            {
                $this->assertArraySubset([
                    'customer_id'   => 'cust_ArzpLocalCust1',
                    'contact'       => '919988771111',
                    'entity'        => 'device',
                ], $event['payload']);

                $this->assertStringStartsWith('device_', $event['payload']['id']);
                $this->assertArrayNotHasKey('auth_token', $event['payload']);
            }
        );

        $helper = $this->getDeviceHelper();

        $helper->deregisterDevice();
    }


    public function testGatewayActionMetrics()
    {
        $mock = $this->mockMetricDriver('mock');

        $helper = $this->getDeviceHelper();

        $initiate = $helper->initiateVerification([
            Fields::SDK => [
                Fields::SIM_ID  => '0',
            ]
        ]);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'SMS_SENDING_EXCEPTION. Unable to send SMS',
            ], $error);
        }, 502);

        $helper->verification($initiate['callback'], [
            Fields::SDK => [
                Fields::STATUS                    => 'FAILURE',
                Fields::ERROR_CODE                => 'SMS_SENDING_EXCEPTION',
                Fields::ERROR_DESCRIPTION         => 'Unable to send SMS',
            ]
        ]);

        $this->assertArraySelectiveEquals([
            [
                GatewayActionMetric::DIMENSION_ACTION       => 'initiateVerification',
                GatewayActionMetric::DIMENSION_ENTITY       => 'device',
                GatewayActionMetric::DIMENSION_GATEWAY      => 'p2p_upi_axis',
                GatewayActionMetric::DIMENSION_STATUS       => 'success',
                GatewayActionMetric::DIMENSION_TYPE         => 'next',
                GatewayActionMetric::DIMENSION_OS           => null,
                GatewayActionMetric::DIMENSION_OS_VERSION   => null,
                GatewayActionMetric::DIMENSION_SDK_VERSION  => null,
                GatewayActionMetric::DIMENSION_NETWORK_TYPE => null
            ],
            [
                GatewayActionMetric::DIMENSION_ACTION       => 'verification',
                GatewayActionMetric::DIMENSION_ENTITY       => 'device',
                GatewayActionMetric::DIMENSION_GATEWAY      => 'p2p_upi_axis',
                GatewayActionMetric::DIMENSION_STATUS       => 'failed',
                GatewayActionMetric::DIMENSION_TYPE         => 'processed',
                GatewayActionMetric::DIMENSION_OS           => null,
                GatewayActionMetric::DIMENSION_OS_VERSION   => null,
                GatewayActionMetric::DIMENSION_SDK_VERSION  => null,
                GatewayActionMetric::DIMENSION_NETWORK_TYPE => null
            ],
        ], $mock->metric(GatewayActionMetric::PSP_GATEWAY_ACTION_TOTAL));
    }
}
