<?php

namespace RZP\Tests\Functional\Gateway\Upi\Yesbank;

use RZP\Constants\Mode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Base\Secure;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiYesbankGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $sharedTerminal;

    /**
     * Payment array
     * @var array
     */
    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/YesbankGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = Gateway::UPI_YESBANK;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayoutToVpa()
    {
        $request = $this->getPayoutRequest();

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_merchant_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutToVpaFailed()
    {
        $request = $this->getPayoutRequest();

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                $content['statuscode']  = 'F';
                $content['error_code'] = 'MT01';
            });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_merchant_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    protected function getPayoutRequest()
    {
        $attributes = [
            'vpa'       => 'Raj1@yesb',
            'amount'    => '100',
        ];

        $raw = json_encode($attributes);

        $request = [
            'url'      => '/payout/vpa/pay',
            'method'   => 'post',
            'raw'      => $raw,
            'server'   => [
                'CONTENT_TYPE'  => 'application/json',
            ]
        ];

        return $request;
    }
}
