<?php

namespace RZP\Tests\Functional\Payment;

use Razorpay\Trace\Facades\Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * A few end to end functional test to assert rate limiting is working fine.
 */
class GatewayErrorThrottlerTest extends TestCase
{
    use PaymentTrait;

    protected $redis;

    protected function setUp(): void
    {
        $this->markTestSkipped('not being used now.');

        parent::setUp();

        $this->redis = Redis::connection()->client();

        $this->setRedisSettings();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    protected function tearDown(): void
    {
        $this->flushCache();

        parent::tearDown();
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

    public function testGatewayFailureDowntimeEdit()
    {
        $this->testGatewayFailureDowntimeCreate();

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $data = $this->getErrorTestData();

        $this->runRequestResponseFlow($data, function () {
            $this->doAuthPayment();
        });

        $downtime2 = $this->getLastEntity('gateway_downtime', true);
        $this->assertEquals($downtime1['id'], $downtime2['id']);
    }

    public function testGatewayFailureDowntimeDuration()
    {
        $this->setRedisSettings([
            'duration' => 300,
        ]);

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
        $this->assertEquals(300, $downtime['end'] - $downtime['begin']);
    }

    // ------------------------------ Helpers ----------------------------------

    protected function getErrorTestData()
    {
        return [
            'response'  => [
                'content'     => [
                    'error' => [
                        'code'          => PublicErrorCode::GATEWAY_ERROR,
                        'description'   => PublicErrorDescription::GATEWAY_ERROR,
                    ],
                ],
                'status_code' => 502,
            ],
            'exception' => [
                'class'                 => 'RZP\Exception\GatewayErrorException',
                'internal_error_code'   => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
            ],
        ];
    }

    protected function setRedisSettings(array $settings = [])
    {
        $defaultRedisSettings = $this->getDefaultRedisSettings();

        $settings = array_merge($defaultRedisSettings, $settings);

        $this->redis->hmset(ConfigKey::DOWNTIME_THROTTLE, ...seq_array($settings));
    }

    protected function getDefaultRedisSettings()
    {
        // Low limits to make it easy to get throttled
        return [
            // Enable gateway error throttle
            'skip' => 0,
            // Super low leak rate
            'lrv'  => 1,
            'lrd'  => 60,
            // Practically non-existent burst
            'mbs'  => 1,
        ];
    }
}
