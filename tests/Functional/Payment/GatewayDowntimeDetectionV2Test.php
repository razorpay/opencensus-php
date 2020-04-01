<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Support\Facades\Redis;

use App;
use Mockery;
use Carbon\Carbon;
use RZP\Error;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

/**
 * A few end to end functional test to
 */
class GatewayDowntimeDetectionV2Test extends TestCase
{
    use PaymentTrait;

    protected $redis;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayDowntimeDetectionV2TestData.php';

        parent::setUp();

        $this->redis = Redis::connection()->client();

        $this->setRedisSettings();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->mockCardVault();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('On');
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

        $this->redis->hmset(ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2, ...seq_array($settings));
    }

    protected function getDefaultRedisSettings()
    {
        return [
            'success_rate_issuer_hdfc_create' => json_encode(
                [['30', '2' , '0.05'],
                 ['300', '2' , '0.05']]),
            'success_rate_issuer_hdfc_resolve' => json_encode(
                [['2' , '0.40']]),
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
    public function testPutGatewayDowntimeRedisConf()
    {
        $this->ba->adminAuth();

        $response = $this->startTest();

        $sbiExpected = $this->testData['redisConfDowntimeResponse'];

        $sbiActual = array_filter($response['config:downtime:detection:configuration_v2'], function($arr) {
            return $arr['key'] === 'success_rate_issuer_sbin_create';
        });

        $this->assertEquals(current($sbiActual), $sbiExpected);
    }

    public function testGetGatewayDowntimeRedisConf()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGatewayFailureDowntimeCreate()
    {
        $data = $this->getErrorTestData();

        $this->gatewayDown = true;

        // Sharp throws a gateway fatal error
        // 2 errors should create a downtime
        $this->runRequestResponseFlow($data, function () {
            $this->doAuthPayment();
        });

        // This one just trace exception because duplicate downtime already exist.
        $this->runRequestResponseFlow($data, function () {
            $this->doAuthPayment();
        });

        $this->ba->cronAuth();

        $externalMock = Mockery::mock('alias:RZP\Models\Gateway\Downtime\Constants', ConstantsStub::class);

        $externalMock->shouldReceive('getMaxSingleMerchantContribution')->andReturn(1);

        $this->startTest();

        $this->assertNotNull($this->redis->get('DOWNTIME_CREATED_success_rate_issuer_HDFC'));

        $this->ba->adminAuth();

        $this->fixtures->create('terminal:enable_default_hdfc_terminal');

        Carbon::setTestNow(Carbon::now()->addSeconds(100));

        $this->doAuthPayment();

        $this->doAuthPayment();

        $request = [
            'method'  => 'GET',
            'url'     => '/gateway/downtimes/detection/cron?type=success_rate&key=issuer&value=HDFC',
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $this->assertNull($this->redis->get('DOWNTIME_CREATED_success_rate_issuer_HDFC'));
    }
}

class ConstantsStub
{
    const SETTINGS_KEY  = ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2;

    const DOWNTIME_KEY  = 'DOWNTIME_CREATED';

    // In ratio to total payments
    const MAX_SINGLE_MERCHANT_CONTRIBUTION = 0.5;
}