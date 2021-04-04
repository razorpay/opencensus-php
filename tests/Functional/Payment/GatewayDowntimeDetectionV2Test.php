<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Support\Facades\Redis;

use App;
use Mockery;
use Carbon\Carbon;
use Razorpay\IFSC\Bank;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Gateway\Downtime\DowntimeDetection;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DowntimeTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

/**
 * A few end to end functional test to
 */
class GatewayDowntimeDetectionV2Test extends TestCase
{
    use PaymentTrait;

    use DowntimeTrait;

    protected $redis;

    protected function setUp(): void
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
            ->willReturn('on');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $externalMock = Mockery::mock('alias:RZP\Models\Gateway\Downtime\Constants', ConstantsStub::class);

        $externalMock->shouldReceive('getMaxSingleMerchantContribution')->andReturn(1);
        $externalMock->shouldReceive('getAllJobTypes')->andReturn($this->getAllJobTypes());

        $this->enablePaymentDowntimes();

        $app = App::getFacadeRoot();

        $connector = \Mockery::mock('RZP\Base\Database\Connectors\MySqlConnector', [$app])->makePartial();

        $connector->shouldReceive('getReplicationLagInMilli')
            ->with(\Mockery::type('string'))
            ->andReturnUsing(function ()
            {
                return 0;
            });

        $this->app->instance('db.connector.mysql', $connector);
    }

    protected function tearDown(): void
    {
        $this->flushCache();

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
            'success_rate_card_issuer_hdfc_create' => json_encode(
                [['30', '2' , '0.05'],
                 ['300', '2' , '0.05']]),
            'success_rate_card_issuer_hdfc_resolve' => json_encode(
                [['', '2' , '0.40']]),
            'payment_interval_upi_provider_okhdfcbank_create' => json_encode(
                [['60', '2' , '0.05']]),
            'payment_interval_upi_provider_okhdfcbank_resolve' => json_encode(
                [['60', '2' , '0.40']]),
            'payment_interval_netbanking_bank_hdfc_create' => json_encode(
                [['60', '2' , '0.05']]),
            'payment_interval_netbanking_bank_hdfc_resolve' => json_encode(
                [['60', '2' , '0.40']]),
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

        $sbiActual = array_filter($response['config:{downtime}:detection:configuration_v2'], function($arr) {
            return $arr['key'] === 'success_rate_card_issuer_sbin_create';
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

        $this->startTest();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertNull($gatewayDowntime['end']);

        $this->assertEquals('card', $gatewayDowntime['method']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertNotNull($paymentDowntime);

        $this->ba->adminAuth();

        $this->fixtures->create('terminal:enable_default_hdfc_terminal');

        Carbon::setTestNow(Carbon::now()->addSeconds(100));

        $this->doAuthPayment();

        $this->doAuthPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/cron',
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertNotNull($gatewayDowntime['end']);
    }

    public function testDowntimeDetectionForUpi()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'failedcollect@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($payment);
        $this->doAuthPaymentViaAjaxRoute($payment);

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/cron',
        ];

        $this->ba->cronAuth();

        Carbon::setTestNow(Carbon::now()->addSeconds(70));

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertNull($gatewayDowntime['end']);

        $this->assertEquals('upi', $gatewayDowntime['method']);

        $this->assertEquals('okhdfcbank', $gatewayDowntime['vpa_handle']);

        $this->assertNotNull($paymentDowntime);
    }

    public function testDowntimeDetectionForNetbanking()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $this->getFormViaCreateRoute($payment, 'gateway.gatewayPostForm');
        $this->getFormViaCreateRoute($payment, 'gateway.gatewayPostForm');

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/cron',
        ];

        $this->ba->cronAuth();

        Carbon::setTestNow(Carbon::now()->addSeconds(70));

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertNull($gatewayDowntime['end']);

        $this->assertNull($paymentDowntime['end']);

        $this->doAuthAndCapturePayment($payment);
        $this->doAuthAndCapturePayment($payment);

        Carbon::setTestNow(Carbon::now()->addSeconds(70));

        $this->ba->cronAuth();
        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertNull($gatewayDowntime['end']);

        $this->assertEquals('netbanking', $gatewayDowntime['method']);

        $this->assertEquals('HDFC', $gatewayDowntime['issuer']);

        $this->assertNotNull($paymentDowntime);
    }

    public function getAllJobTypes()
    {
        return [
            [
                'type' => DowntimeDetection::SUCCESS_RATE,
                'method' => Method::CARD,
                'key' => DowntimeDetection::ISSUER,
                'value' => Issuer::HDFC,
            ],
            [
                'type' => DowntimeDetection::PAYMENT_INTERVAL,
                'method' => Method::UPI,
                'key' => DowntimeDetection::PROVIDER,
                'value' => ProviderCode::OKHDFCBANK,
            ],
            [
                'type' => DowntimeDetection::PAYMENT_INTERVAL,
                'method' => Method::NETBANKING,
                'key' => DowntimeDetection::BANK,
                'value' => Bank::HDFC,
            ],
        ];
    }

    public function testDowntimeDetectionStoredInDB()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'failedcollect@okhdfcbank';

        $this->doAuthPaymentViaAjaxRoute($payment);
        $this->doAuthPaymentViaAjaxRoute($payment);

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/detection/cron',
        ];

        $this->ba->cronAuth();

        Carbon::setTestNow(Carbon::now()->addSeconds(70));

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        // TODO: Include SR downtime and determine the end time.
        $this->assertNull($gatewayDowntime['end']);

        $this->assertEquals('upi', $gatewayDowntime['method']);

        $this->assertNotNull($paymentDowntime);
    }
}

class ConstantsStub
{
    const SETTINGS_KEY  = ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2;

    const DOWNTIME_KEY  = 'DOWNTIME_CREATED';

    // In ratio to total payments
    const MAX_SINGLE_MERCHANT_CONTRIBUTION = 0.5;
}
