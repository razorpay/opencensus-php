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
        $attributes = [
            'vpa'       => 'Raj1@yesb',
            'amount'    => '100',
        ];

        $request = $this->getPayoutRequest($attributes, 'pay');

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');

        return $response;
    }

    public function testPayoutToVpaFailed()
    {
        $attributes = [
            'vpa'       => 'Raj1@yesb',
            'amount'    => '100',
        ];

        $request = $this->getPayoutRequest($attributes, 'pay');

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'payout')
                {
                    $content['statuscode']  = 'F';
                    $content['respcode'] = 'MT01';
                }
            });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerify()
    {
        $response = $this->testPayoutToVpa();

        $attributes = [
            'merchant_reference' => $response['merchant_reference'],
        ];

        $this->ba->privateAuth();

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerifyForFailedPayout()
    {
        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'merchant_reference' => $gatewayEntity['merchant_reference'],
        ];

        $this->ba->privateAuth();

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertEquals('S', $gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerifyForTimedOutPayout()
    {
        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'merchant_reference' => $gatewayEntity['merchant_reference'],
        ];

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'payout_verify')
                {
                    $content['status_code']  = 'T';
                    $content['timed_out_txn_status'] = 'RCC';
                }
            });

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertEquals('S', $gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('pay', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerifyFailed()
    {
        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'merchant_reference' => $gatewayEntity['merchant_reference'],
        ];

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'payout_verify')
                {
                    $content['statuscode']  = 'F';
                    $content['respcode'] = 'MT01';
                }
            });

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $this->assertEquals('F', $gatewayEntity['status_code']);
    }

    public function testPayoutVpaVerifyForIncorrectPayoutReference()
    {
        $attributes = [
            'merchant_reference' => '1234',
        ];

        $this->ba->privateAuth();

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);
    }

    protected function getPayoutRequest(array $attributes, string $type)
    {
        $raw = json_encode($attributes);

        $request = [
            'url'      => '/payout/vpa/' . $type,
            'method'   => 'post',
            'raw'      => $raw,
            'server'   => [
                'CONTENT_TYPE'  => 'application/json',
            ]
        ];

        return $request;
    }
}
