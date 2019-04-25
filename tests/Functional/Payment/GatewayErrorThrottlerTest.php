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
        $this->flushRedis();

        parent::tearDown();
    }

    // ------------------------- Tests -----------------------------------------

    public function testGatewayFailure()
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

        $this->setRedisKey(ConfigKey::DOWNTIME_THROTTLE, $settings);
    }

    protected function getDefaultRedisSettings()
    {
        return [
            'skip' => 0,
            'lrv'  => 1,
            'lrd'  => 60,
            'mbs'  => 1,
        ];
    }

    protected function setRedisKey(string $key, array $parameters)
    {
        if (empty($parameters) === false)
        {
            $this->redis->hmset($key, ...seq_array($parameters));
        }
        else
        {
            $this->redis->del($key);
        }
    }

    protected function flushRedis()
    {
        $this->redis->flushall();
    }
}
