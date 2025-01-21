<?php

namespace Functional\Payment;

use Mockery;
use RZP\Models\Payment;
use RZP\Models\Pricing\Fee;
use RZP\Services\UpiPayment;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\DB;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;


use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;


class BankingUpiTest extends TestCase
{
    use NonVirtualAccountQrCodeTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;


    private $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/BankingUpiTestData.php';

        parent::setUp();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['activated' => true, 'live' => true]);

        $this->fixtures->on('test')->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->terminal = $this->fixtures->create(
            'terminal:dedicated_upi_mindgate_terminal',
            [
                'merchant_id' => '10000000000000',
            ]
        );
    }

    public function mockUpsResponse()
    {
        $upsService = $this->getMockBuilder( UpiPayment\Service::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('upi.payments', $upsService);

        $this->app['upi.payments']
              ->method('fetchAuthorizeEntityViaRRN')
              ->willReturn([['payment_id' => 'Ohx4E6GLjW1KDT']]);
    }

    protected function mockSplitzTreatmentBulkRequest($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testFetchUpiPaymentsByRrn()
    {
        $this->ba->proxyAuth();

        $this->fixtures->org->addFeatures(['vas_merchant'], '100000razorpay');

        $this->mockSplitzTreatmentBulkRequest($this->testData['sampleSpltizOutput']);

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250'
        ])['id'];

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444251'
            ]);

        $this->startTest();
    }

    public function testFetchUpiPaymentsByRrnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    // RRN should be ignored if feature flag or experiment is not enabled
    public function testFetchUpiPaymentsByRrnWithoutFeatureFlag()
    {
        $this->ba->proxyAuth();

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250'
        ])['id'];

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250'
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444251'
            ]);

       $this->startTest();
    }

    public function testFetchUpiPaymentsByRrnWithAdditionalFilters()
    {
        $this->ba->proxyAuth();

        $this->fixtures->org->addFeatures(['vas_merchant'], '100000razorpay');

        $this->mockSplitzTreatmentBulkRequest($this->testData['sampleSpltizOutput']);

        $this->mockUpsResponse();

        $this->fixtures->create('upi', [
            'payment_id'    => 'Ohx4E6GLjW1KDQ',
            'npci_reference_id' => '422012444250',
        ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDT',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test@razorpay.com',
                'status' => 'captured',
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDQ',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test2@razorpay.com',
                'status' => 'captured',
            ]);

        $this->fixtures->create('payment',
            [
                'id' =>  'Ohx4E6GLjW1KDR',
                'created_at'   => 1723021115,
                'reference16' => '422012444250',
                'email' => 'test@razorpay.com',
                'status' => 'authorized',
            ]);

        $this->startTest();
    }

    public function testPayerNameForPaymentOnDynamicQRCode() :void
    {
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );

        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'upi_qr',
            ]
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'test');

        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $this->terminal);

        $upi = $this->getLastEntity('upi', true, 'test');

        $this->assertEquals('SANKETH B K', $upi['name']);

        $payment = $this->getDbLastEntity('payment');

        DB::table('keys')->delete();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/pay_' . $payment->id;

        $response = $this->startTest();

        $this->assertNull($response['upi']['payer_name']);

        $this->mockSplitzTreatmentBulkRequest($this->testData['sampleSpltizOutputUpiPayerName']);

        $this->fixtures->create('feature', [
            'name'          => 'display_upi_payer_name',
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $response = $this->startTest();

        $this->assertEquals('SANKETH B K',$response['upi']['payer_name']);
    }

    public function testPayerNameForPaymentOnV1QRCode()
    {
        $this->gateway = 'upi_mindgate';

        $this->fixtures->merchant->setCategory('1111');

        $this->fixtures->merchant->addFeatures(['upiqr_v1_hdfc']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_intent_terminal');

        $org = $this->fixtures->create('org', [
            'display_name'            => 'HDFC CollectNow',
            'business_name'            => 'HDFC Bank',
        ]);

        $this->fixtures->create('feature', [
            'name'          => 'org_custom_upi_logo',
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id' => $org->getId(),
            'hostname' => 'hdfcupicollect.razorpay.com',
        ]);

        $this->fixtures->edit('merchant','10000000000000',[
            'name'=>'Test Name',
            'org_id' => $org->getId(),
        ]);

        $terminal = $this->fixtures->create('terminal', [
            'id'                        => '10000000000112',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            // Sample hex for aes encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'unittest@hdfcbank',
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ]);

        $input = $this->testData['createQRCode'];

        $response = $this->createVirtualAccount($input);

        $this->va = $this->getDbLastEntity('virtual_account');

        $content = $this->getMockServer()->getAsyncCallbackContent(
            [
                'gateway_payment_id'    => $this->va->getId(),
                'payment_id'            => 'STQ'.$this->va->qrCode->getId(),
            ],
            [
                'vpa'                   => 'random@upi',
                'amount'                => $this->va->getAmountExpected(),
            ]);

        $content['pgMerchantId'] = $this->terminal->getGatewayMerchantId();

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $upi = $this->getDbLastEntity(Payment\Method::UPI);

        $this->assertEquals('SANKETH B K', $upi->name);
    }
}
