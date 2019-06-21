<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Support\Facades\Redis;

use RZP\Error;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

/**
 * A few end to end functional test to
 */
class GatewayDowntimeDetectionTest extends TestCase
{
    use PaymentTrait;

    protected $redis;

    public function setUp()
    {
        parent::setUp();

        $this->redis = Redis::connection()->client();

        $this->setRedisSettings();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function tearDown()
    {
        $this->redis->flushall();

        parent::tearDown();
    }

    protected function setRedisSettings(array $settings = [])
    {
        $defaultRedisSettings = $this->getDefaultRedisSettings();

        $settings = array_merge($defaultRedisSettings, $settings);

        $this->redis->hmset(ConfigKey::DOWNTIME_DETECTION_CONFIGURATION, ...seq_array($settings));
    }

    protected function getDefaultRedisSettings()
    {
        return [
            'sharp' => json_encode(
                [['300', '50' , '2', '600'],
                ['3000', '60' , '2', '6000']]),
        ];
    }

    protected function getErrorTestData()
    {
        return [
            'response'  => [
                'content'     => [
                    'error' => [
                        'code'          => Error\PublicErrorCode::GATEWAY_ERROR,
                        'description'   => Error\PublicErrorDescription::GATEWAY_ERROR,
                    ],
                ],
                'status_code' => 502,
            ],
            'exception' => [
                'class'                 => 'RZP\Exception\GatewayErrorException',
                'internal_error_code'   => Error\ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
            ],
        ];
    }

    // ------------------------- Tests -----------------------------------------
    public function testGatewayFailureDowntimeCreate()
    {
        $data = $this->getErrorTestData();

        $this->gatewayDown = true;

        // Sharp throws a gateway fatal error
        // 2 errors should create a downtime
        $this->runRequestResponseFlow($data, function () {
            $this->doAuthPayment();
        });

        $this->runRequestResponseFlow($data, function () {
            $this->doAuthPayment();
        });

        $downtime = $this->getLastEntity('gateway_downtime', true);
        $this->assertEquals('sharp', $downtime['gateway']);
        $this->assertEquals(600, $downtime['end'] - $downtime['begin']);
    }

    public function testPurge()
    {
        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/keys/purge',
        ];

        $response = $this->makeRequestAndGetContent($request);
    }
}
