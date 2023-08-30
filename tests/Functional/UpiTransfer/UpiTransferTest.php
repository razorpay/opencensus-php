<?php

namespace RZP\Tests\Functional\UpiTransfer;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class UpiTransferTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;

    protected $virtualAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiTransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->vpa = $this->createVirtualAccount();
    }

    protected function createVirtualAccount($mode = 'test', $merchantId = '10000000000000', $vpaDescriptor = null, $additionalFields = [])
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = array_merge($this->testData[__FUNCTION__], $additionalFields);

        if ($vpaDescriptor !== null)
        {
            $request['content']['receivers']['vpa']['descriptor'] = $vpaDescriptor;
        }
        $response = $this->makeRequestAndGetContent($request);

        $this->virtualAccountId = $response['id'];

        $vpa = $response['receivers'][0];

        return $vpa;
    }

    protected function processUpiTransfer($function = __FUNCTION__, $valid = true, string $gateway = Gateway::UPI_ICICI, $requestContent = [])
    {
        $request = $this->testData[$function];

        $request['content'] = array_merge($request['content'], $requestContent);

        $mockServer = $this->mockServer($gateway);

        switch ($gateway)
        {
            case Gateway::UPI_ICICI:
            {
                if (ends_with($request['url'], 'internal'))
                {
                    $this->ba->appAuth();

                    $request['raw'] = json_encode($request['content']);

                    $response = $this->makeRequestAndGetContent($request);
                }
                else
                {
                    $this->ba->directAuth();

                    $request['raw'] = $mockServer->getAsyncCallbackContentForBharatQr($request['content']);

                    $response = $this->makeRequestAndGetContent($request);

                    $this->assertEquals($valid, $response['valid']);
                }

                break;
            }
        }

        return $response;
    }

    public function testProcessIciciUpiTransferRefund()
    {
        $this->processUpiTransfer();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($this->vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

        // Being used in scrooge checks
        $this->gateway = $payment['gateway'];

        $this->refundPayment(
            $payment['id'],
            4000,
            [
                'is_fta' => true,
                'fta_data' => [
                    'vpa' => [
                        'address' => $payment['vpa']
                    ],
                ],
            ]
        );

        $refund = $this->getDbLastEntity('refund');

        $this->assertEquals(1, $refund['is_scrooge']);
        $this->assertEquals('processed', $refund['status']);

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals($refund->getId(), $fta['source_id']);
        $this->assertEquals('refund', $fta['source_type']);
        $this->assertNotNull($fta['vpa_id']);
    }

    public function testProcessUpiTransferAndFetch()
    {
        $this->processUpiTransfer();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($this->vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

        // Being used in scrooge checks
        $this->gateway = $payment['gateway'];

        $response = $this->fetchPaymentForUpiTransfer($payment['id']);

        $this->assertEquals($payment['id'], $response['payment_id']);
        $this->assertEquals($upiTransfer['id'], $response['id']);
        $this->assertEquals(10000, $response['amount']);
    }

    protected function fetchPaymentForUpiTransfer($paymentId)
    {
        $this->ba->privateAuth();

        $request = array(
            'method'    => 'GET',
            'url'       => '/payments/'.$paymentId.'/upi_transfer'
        );

        $payment = $this->makeRequestAndGetContent($request);

        return $payment;
    }

    public function testFetchPaymentForBankReference()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer('processUpiTransfer', true, Gateway::UPI_ICICI);

        $response = $this->fetchVirtualAccountPayments(null, '015306767323');

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testProcessIciciUpiTransferPayment()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentWithPayerAccountType()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals('credit_card', $payment['reference2']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferWithPayerAccountTypeNonCredit()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals('bank_account', $payment['reference2']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentWithInvalidPayerAccountType()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertNull($payment['reference2']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentInternal()
    {
        $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $response = $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);

        $this->assertArrayHasKey('refunds', $response);
        $this->assertIsArray($response['refunds']);
        $this->assertEmpty($response['refunds']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentIgnoreCaseInternal()
    {
        $this->processUpiTransfer();

        $actualPayment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $actualPayment['method']);
        $this->assertEquals('captured', $actualPayment['status']);
        $this->assertEquals(10000, $actualPayment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $actualPayment['gateway']);
        $this->assertEquals('vpa', $actualPayment['receiver_type']);

        $response = $this->processUpiTransfer('processUpiTransferIgnoreCaseInternal', true, Gateway::UPI_ICICI);

        $this->assertEquals($response['payment']['id'], 'pay_' . $actualPayment['id']);
        $this->assertEquals('captured', $response['payment']['status']);

        $this->assertArrayHasKey('refunds', $response);
        $this->assertIsArray($response['refunds']);
        $this->assertEmpty($response['refunds']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentInternalDuplicate()
    {
        $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI);
        $response = $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals(10000, $response['payment']['amount']);
        $this->assertEquals('payment', $response['payment']['entity']);
    }

    public function testProcessIciciUpiTransferPaymentInternalRefund()
    {
        $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->closeVirtualAccount($this->virtualAccountId);

        $response = $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('refunded', $response['payment']['status']);
        $this->assertEquals(10000, $response['payment']['amount']);
        $this->assertEquals('payment', $response['payment']['entity']);

        $this->assertArrayHasKey('refunds', $response);
        $this->assertEquals($response['payment']['id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals($response['payment']['amount'], $response['refunds'][0]['amount']);
    }

    public function testProcessIciciUpiTransferPaymentInternalTerminalNotFound()
    {
        $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_UPI_TRANSFER_PROCESSING_FAILED);

        $this->expectExceptionMessage('No terminal found for upi transfer');

        $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI, ['merchantId' => '199602']);
    }

    public function testUpiTransferRefundFailPaymentSuccessInternal()
    {
        $this->fixtures->merchant->editBalance(0);

        $this->fixtures->merchant->editCredits('29000','10000000000000');

        $this->fixtures->pricing->editDefaultPlan(
            [
                'fee_bearer'    => 'customer',
                'percent_rate'  => '0',
                'fixed_rate'    => '1000000',
            ]
        );

        $response = $this->processUpiTransfer('processUpiTransferInternal', true, Gateway::UPI_ICICI);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('authorized', $response['payment']['status']);
        $this->assertEquals(10000, $response['payment']['amount']);
        $this->assertEquals('payment', $response['payment']['entity']);
    }

    public function testProcessIciciUpiTransferPaymentWithTPVFeatureEnabled()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->fixtures->merchant->addFeatures(['tpv']);

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferPaymentWithTr()
    {
        $vpa = $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals($vpa['address'], $upiTransfer['payee_vpa']);
        $this->assertEquals('xyz1234567', $upiTransfer['transaction_reference']);

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
    }

    public function testProcessIciciUpiTransferPaymentIgnoreCase()
    {
        $this->processUpiTransferIgnoreCase();

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEqualsIgnoringCase($this->vpa['address'], $upiTransfer['payee_vpa'], '');

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');
        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
    }

    protected function processUpiTransferIgnoreCase($function = __FUNCTION__, $valid = true)
    {
        $this->ba->privateAuth();

        $request = $this->testData[$function];

        $data = $request['content'];

        $mockServer = $this->mockServer(Gateway::UPI_ICICI);

        $request['raw'] = $mockServer->getAsyncCallbackContentForBharatQr($request['content']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['valid'], $valid);

        return $response;
    }

    public function testProcessIciciUpiTransferUnexpectedPayment()
    {
        $this->processUpiTransfer('testProcessIciciUpiTransferPayment', true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getDbLastEntity('upi');

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals($upiTransfer['expected'], false);
        $this->assertEquals('VIRTUAL_ACCOUNT_NOT_FOUND', $upiTransfer['unexpected_reason']);

        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            'VIRTUAL_ACCOUNT_NOT_FOUND',
            [
                'intended_virtual_account_id'   => null,
                'actual_virtual_account_id'     => 'va_ShrdVirtualAcc',
                'merchant_id'                   => null,
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferWithVpaPricing()
    {
        $pricingPlanId = $this->fixtures->create('pricing:upi_transfer_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $this->processUpiTransfer();

        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 1%
        $this->assertEquals($transaction['amount'] * 1 / 100, $transaction['fee'] - $transaction['tax']);
    }

    /**
     * This test is to verify the case when merchant doesn't have either UPI or vpa pricing enabled.
     * In that case, default/fallback pricing has to be picked up for payment creation.
     */
    public function testProcessIciciUpiTransferWithDefaultPricing()
    {
        $pricingPlanId = $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $this->processUpiTransfer();

        $transaction = $this->getLastEntity('transaction', true);
        // Pricing 2%
        $this->assertEquals($transaction['amount'] * 2 / 100, $transaction['fee'] - $transaction['tax']);
    }

    public function testCreateVPAForPLAppWithOrderAndPay()
    {
        $this->testCreateVPAForPLAppWithOrder();

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $payment     = $this->getDbLastEntity('payment');

        $order     = $this->getDbLastEntity('order');

        $va = $this->getDbLastEntity('virtual_account');

        $this->assertEquals('captured', $payment->getStatus());

        $this->assertEquals('paid', $order->getStatus());

        $this->assertEquals('paid', $va->getStatus());
    }

    public function testCreateVPAForPLAppWithOrder()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order', 'payment_capture' => true, 'amount' => 3500]);

        $this->fixtures->merchant->removeFeatures(['virtual_accounts']);

        $this->ba->paymentLinksAuth();

        $this->startTest();

        $va = $this->getDbLastEntity('virtual_account');

        $this->assertEquals($va->getSourceType(), 'payment_links_v2');
    }

    public function testCreateVPAForPLAppWithOrderForIncorrectDescriptor()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order', 'payment_capture' => true, 'amount' => 3500]);

        $this->fixtures->merchant->removeFeatures(['virtual_accounts']);

        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    public function testCreateVPAForPLAppWithOrderForNoDescriptor()
    {
        $order = $this->fixtures->create('order', ['id' => '100000000order', 'payment_capture' => true, 'amount' => 3500]);

        $this->fixtures->merchant->removeFeatures(['virtual_accounts']);

        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    public function testCreateVPAForPLAppWithPaidOrder()
    {
        $order = $this->fixtures->create('order',
            [
            'id'                => '100000000order',
            'payment_capture'   => true,
            'amount'            => 3500,
            'status'            => 'paid'
            ]
        );

        $this->fixtures->merchant->removeFeatures(['virtual_accounts']);

        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    public function testCreateVPAForPLAppWithRandomOrder()
    {
        $this->fixtures->create('merchant',
            [
                'id'                => 'randommerchant',
            ]
        );

        $order = $this->fixtures->create('order',
            [
                'id'                => '100000000order',
                'merchant_id'       => 'randommerchant',
                'payment_capture'   => true,
                'amount'            => 3500,
                'status'            => 'paid'
            ]
        );

        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    public function testProcessIciciUpiTransferToClosedVa()
    {
        $this->closeVirtualAccount($this->virtualAccountId);

        $response = $this->processUpiTransfer();
        $this->assertNull($response['message']);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $this->assertEquals(false, $upiTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_CLOSED', $upiTransfer['unexpected_reason']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals('refunded', $payment['status']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            'VIRTUAL_ACCOUNT_CLOSED',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessIciciUpiTransferToVaWithPastCloseBy()
    {
        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->createVirtualAccount(
            'test',
            10000000000000,
            'anothervpa',
            ['close_by' => $currentTimestamp + (20 * 60)]
        );

        // When close_by time has passed but the next cron execution time is still due.
        $this->fixtures->edit('virtual_account', $this->virtualAccountId, ['close_by' => $currentTimestamp - 60]);

        $response = $this->processUpiTransfer('testProcessICICIUpiTransferToVaWithPastCloseBy');
        $this->assertNull($response['message']);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $this->assertEquals(false, $upiTransfer['expected']);
        $this->assertEquals('VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED', $upiTransfer['unexpected_reason']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEquals('refunded', $payment['status']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            'VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $upiTransfer['virtual_account_id'],
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer['id'],
                'payment_id'                    => $payment['id'],
            ]
        );
    }

    public function testProcessIciciUpiTransferWithPaymentLessThanFee()
    {
        $this->createMerchantAndAssignVirtualVpa('20000000000000', 'rzr.payto00000vpvpaicici@icici');

        $pricingPlanId = $this->fixtures->create('pricing:upi_transfer_pricing_plan', [
            'percent_rate' => '0',
            'fixed_rate' => '5000',
        ]);

        $this->fixtures->merchant->edit('20000000000000', ['pricing_plan_id' => $pricingPlanId]);

        $response = $this->processUpiTransfer('testProcessIciciUpiTransferPayment', true, Gateway::UPI_ICICI);

        $this->assertNull($response['message']);

        $this->runUpiTransferRequestAssertions(
            Gateway::UPI_ICICI,
            true,
            'The fees calculated for payment is greater than the payment amount. Please provide a higher amount',
            [
                'actual_virtual_account_id'   => 'va_ShrdVirtualAcc',
                'merchant_id'                 => '20000000000000',
            ]
        );
    }

    public function testProcessIciciUpiTransferForCustomerFeeBearer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel('10000000000000');

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => 'customer']);

        $this->createVirtualAccount('test', '10000000000000', 'vpVpaIcici');

        $response = $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');

        $payment = $this->getDbLastEntity('payment');
        $this->assertNotNull($payment['fee']);

        $this->assertNull($response['message']);

        $this->runUpiTransferRequestAssertions(
            Gateway::UPI_ICICI,
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );
    }

    public function testProcessFailedIciciUpiTransferPayment()
    {
        $this->processUpiTransfer(__FUNCTION__, false);

        $upiTransfer = $this->getLastEntity('upi_transfer', true);
        $payment     = $this->getLastEntity('payment', true);
        $upi         = $this->getLastEntity('upi', true);

        $this->assertNull($payment);
        $this->assertNull($upiTransfer);
        $this->assertNull($upi);
    }

    public function testProcessDuplicateIciciUpiTransferPayment()
    {
        $this->processUpiTransfer();

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getDbLastEntity('upi');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEqualsIgnoringCase($this->vpa['address'], $upiTransfer['payee_vpa'], '');

        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');
        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

        $this->runUpiTransferRequestAssertions(
            Gateway::UPI_ICICI,
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );

        $this->processUpiTransfer();

        $this->runUpiTransferRequestAssertions(
            Gateway::UPI_ICICI,
            false,
            'UPI_TRANSFER_PAYMENT_DUPLICATE_NOTIFICATION',
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
            ]
        );
    }

    public function testProcessDuplicateIciciTransferWithAmountAndPayeeVpa()
    {

        $this->processUpiTransfer();

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getDbLastEntity('upi');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('vpa', $payment['receiver_type']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);
        $this->assertEqualsIgnoringCase($this->vpa['address'], $upiTransfer['payee_vpa'], '');

        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');
        $this->assertEquals($upiTransfer['expected'], true);

        $this->assertEquals(null, $upiTransfer['unexpected_reason']);

        $request = $this->testData['processUpiTransfer'];
        $request['amount'] = '200.0';

        $this->processUpiTransfer(__FUNCTION__,true);

        $vpaAddress = 'rzr.payto00055vpvpaicici@icici';

        $merchantId = '10000000000000';

        $virtualAccount = $this->fixtures->create(
            'virtual_account',
            [
                'merchant_id' => $merchantId,
                'status'      => 'active',
            ]
        );

        $vpa = $this->fixtures->create(
            'vpa',
            [
                'merchant_id' => $merchantId,
                'entity_id'   => $virtualAccount->getId(),
                'entity_type' => 'virtual_account',
                'username'    => explode('@', $vpaAddress)[0],
                'handle'      => explode('@', $vpaAddress)[1],
            ]
        );

        $this->fixtures->edit('virtual_account', $virtualAccount->getId(),
            [
                'vpa_id' => $vpa->getId()
            ]
        );

        $this->processUpiTransfer();
    }

    protected function runUpiTransferRequestAssertions(string $gateway, bool $isCreated, $errorMessage = null, $expectedValues = [])
    {
        $upiTransferRequest = $this->getDbLastEntity('upi_transfer_request');

        $this->assertEquals($gateway, $upiTransferRequest['gateway']);
        $this->assertNotNull($upiTransferRequest['request_payload']);
        $this->assertEquals($isCreated, $upiTransferRequest['is_created']);
        $this->assertEquals($errorMessage, $upiTransferRequest['error_message']);

        if (empty($expectedValues) === true)
        {
            return;
        }

        $this->ba->adminAuth();
        $testData = $this->testData['adminFetchUpiTransferRequest'];
        $testData['request']['url'] .= $upiTransferRequest->getPublicId();
        $upiTransferRequest = $this->startTest($testData);

        $this->assertArraySelectiveEquals($expectedValues, $upiTransferRequest);
    }

    protected function createMerchantAndAssignVirtualVpa(string $merchantId, string $vpaAddress)
    {
        $this->fixtures->merchant->createAccount($merchantId);
        $this->fixtures->merchant->addFeatures(['virtual_accounts'], $merchantId);
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $virtualAccount = $this->fixtures->create(
            'virtual_account',
            [
                'merchant_id' => $merchantId,
                'status'      => 'active',
            ]
        );

        $vpa = $this->fixtures->create(
            'vpa',
            [
                'merchant_id' => $merchantId,
                'entity_id'   => $virtualAccount->getId(),
                'entity_type' => 'virtual_account',
                'username'    => explode('@', $vpaAddress)[0],
                'handle'      => explode('@', $vpaAddress)[1],
            ]
        );

        $this->fixtures->edit('virtual_account', $virtualAccount->getId(),
            [
                'vpa_id' => $vpa->getId()
            ]
        );
    }


    public function testUpiTransferRefundFailPaymentSuccess()
    {
        $this->fixtures->merchant->editBalance(0);

        $this->fixtures->merchant->editCredits('29000','10000000000000');

        $this->fixtures->pricing->editDefaultPlan(
            [
                'fee_bearer'    => 'customer',
                'percent_rate'  => '0',
                'fixed_rate'    => '1000000',
            ]
        );

        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransferRequestArray = $this->getDbLastEntityToArray('upi_transfer_request');

        $this->assertEquals('REFUND_OR_CAPTURE_PAYMENT_FAILED',$upiTransferRequestArray['error_message']);

        $this->assertTrue($upiTransferRequestArray['is_created']);
    }

    public function testWebhookUpiPaymentWithoutTr()
    {
        $expectedEvent = [];

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event) use ($expectedEvent)
            {
                $upiTransferArray = $event['payload']['upi_transfer']['entity'];

                $this->assertArrayNotHasKey('tr', $upiTransferArray);
            }
        );

        $this->processUpiTransfer();
    }

    public function testWebhookUpiPaymentWithTr()
    {
        $expectedEvent = [];

        $this->expectWebhookEvent(
            'virtual_account.credited',
            function (array $event) use ($expectedEvent)
            {
                $upiTransferArray = $event['payload']['upi_transfer']['entity'];

                $this->assertArrayHasKey('tr', $upiTransferArray);

                $this->assertEquals($upiTransferArray['tr'],'randomutr');
            }
        );

        $this->processUpiTransfer('testWebhookUpiPaymentWithTr');
    }

    public function testUpiTransferWithLongVpaAddress()
    {
        $this->processUpiTransfer(__FUNCTION__, true, Gateway::UPI_ICICI);

        $upiTransfer = $this->getDbLastEntity('upi_transfer');
        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getLastEntity('upi', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(10000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);

        $this->assertEquals($upiTransfer['payment_id'], $payment['id']);

        $this->assertNotNull($upi['payment_id']);
        $this->assertTrue(isset($upi['type']));
        $this->assertEquals($upi['type'], 'pay');

        $this->assertEquals($upiTransfer['expected'], true);
        $this->assertEquals(null, $upiTransfer['unexpected_reason']);
        $this->assertNull($upiTransfer['transaction_reference']);

        $this->runUpiTransferRequestAssertions(
            'upi_icici',
            true,
            null,
            [
                'intended_virtual_account_id'   => $this->virtualAccountId,
                'actual_virtual_account_id'     => $this->virtualAccountId,
                'merchant_id'                   => '10000000000000',
                'upi_transfer_id'               => $upiTransfer->getPublicId(),
                'payment_id'                    => $payment->getPublicId(),
            ]
        );

    }
}
