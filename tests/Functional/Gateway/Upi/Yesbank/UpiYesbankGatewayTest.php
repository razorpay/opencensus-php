<?php

namespace RZP\Tests\Functional\Gateway\Upi\Yesbank;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Gateway\Upi\UpiPaymentTrait;

class UpiYesbankGatewayTest extends TestCase
{
    use PaymentTrait;
    use UpiPaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiYesbankGatewayTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = Gateway::UPI_YESBANK;

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayoutRouteWithAccess()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '123456'],
            'merchant'  => ['category' => '1520'],
            'fund_transfer_attempt' => [],
            'gateway_input' => [
                'vpa'       => 'komal@yesb',
                'amount'    => '100',
                'ref_id'    => time() .  str_random(4)
            ]
        ];

        $request = $this->getPayoutRequest($attributes, 'pay');

        $this->ba->privateAuth('random_key');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTestResponse($response, 'testPayoutRouteWithAccess');
    }

    public function testPayoutToVpa()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '123456'],
            'merchant'  => ['category' => '1520'],
            'fund_transfer_attempt' => ['ref_id' => '12345'],
            'gateway_input' => [
            'vpa'       => 'komal@yesb',
            'amount'    => '100',
            'ref_id'    => time() .  str_random(4)
            ]
        ];

        $request = $this->getPayoutRequest($attributes, 'pay');

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);
        $this->assertNotNull($response['bank_reference_number']);

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($gatewayEntity['vpa']);
        $this->assertNotNull($gatewayEntity['received']);
        $this->assertNotNull($gatewayEntity['merchant_reference']);
        $this->assertNotNull($gatewayEntity['gateway_payment_id']);
        $this->assertNotNull($gatewayEntity['status_code']);
        $this->assertNotNull($gatewayEntity['npci_txn_id']);
        $this->assertNotNull($gatewayEntity['npci_reference_id']);
        $this->assertEquals('PAY', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');

        return $response;
    }

    public function testPayoutToVpaFailed()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '123456'],
            'merchant'  => ['category' => '1520'],
            'fund_transfer_attempt' => ['ref_id' => '12345'],
            'fund_transfer_attempt' => [],
            'gateway_input' => [
                'vpa'       => 'raj1@yesb',
                'amount'    => '100',
                'ref_id'    => time() .  str_random(4)
            ]
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
        $this->assertEquals('PAY', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerify()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $response = $this->testPayoutToVpa();

        $upi = $this->getDbLastEntity('upi');

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '12445'],
            'gateway_input' => [
                'ref_id'    => $upi['merchant_reference'],
            ]
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
        $this->assertEquals('PAY', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerifyForFailedPayout()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '12445'],
            'gateway_input' => [
                'ref_id'    => $gatewayEntity['merchant_reference'],
            ]
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
        $this->assertEquals('PAY', $gatewayEntity['type']);
        $this->assertEquals($gatewayEntity['action'], 'payout');
    }

    public function testPayoutVpaVerifyForTimedOutPayout()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '12445'],
            'gateway_input' => [
                'ref_id'    => $gatewayEntity['merchant_reference'],
            ]
        ];

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'payout_verify')
                {
                    $content['statuscode']  = 'T';
                    $content['timed_out_txn_status'] = 'RCC';
                }
            });

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $gatewayEntity = $this->getLastEntity('upi', true);
    }

    public function testPayoutVpaVerifyFailed()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $this->testPayoutToVpaFailed();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('F', $gatewayEntity['status_code']);

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '12445'],
            'gateway_input' => [
                'ref_id'    => $gatewayEntity['merchant_reference'],
            ]
        ];

        $this->ba->privateAuth();

        $this->mockServerContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'payout_verify')
                {
                    $content['statuscode']  = 'FAILED';
                    $content['respcode'] = 'MT01';
                }
            });

        $request = $this->getPayoutRequest($attributes, 'verify');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $this->assertNotNull($response['api_error_code']);

        $this->assertEquals('F', $gatewayEntity['status_code']);
    }

    public function testDuplicatePayoutRequest()
    {
        $this->markTestSkipped('Currently skipping the test, till FTA and gateway code integration is merged');

        $attributes = [
            'terminal'  => ['gateway_merchant_id' => '123456'],
            'merchant'  => ['category' => '1520'],
            'fund_transfer_attempt' => ['ref_id' => '12345'],
            'gateway_input' => [
                'vpa'       => 'komal@yesb',
                'amount'    => '100',
                'ref_id'    => 'testReference'
            ]
        ];

        $request = $this->getPayoutRequest($attributes, 'pay');

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertFalse($response['success']);

        $this->assertEquals('RZP_DUPLICATE_PAYOUT', $response['response_code']);
    }

    public function testUnexpectedPaymentCallback()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $content = $this->mockServer()->getUnexpectedCallback();

        $response = $this->makeRequestAndGetContent($content);

        $paymentEntity = $this->getLastEntity('payment', true);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($upiEntity['merchant_reference']);

        $this->assertSame('107611570997', $paymentEntity['reference16']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $upiEntity['action'],
            'pay'                                  => $upiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $upiEntity['payment_id'],
            $transactionEntity['id']               => 'txn_' . $paymentEntity['transaction_id'],
            $transactionEntity['entity_id']        => $paymentEntity['id'],
            $transactionEntity['type']             => 'payment',
            $transactionEntity['amount']           => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
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
