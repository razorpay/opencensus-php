<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class NonVirtualAccountQrCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    private $vpaTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateBharatQrCode()
    {
        $response = $this->createQrCode();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateQrCodeInvalidCustomer()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $this->createQrCode(['customer_id' => 'cust_110000customer']);
    }

    public function testBadRequestBharatQrCodeWithTaxInvoice()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('The tax invoice field may be sent only when type is upi_qr');

        $input['tax_invoice'] = $this->testData['tax_invoice'];
        $input['type'] = 'bharat_qr';

        $this->createQrCode($input);
    }

    public function testProcessBqrBankTransfer()
    {
        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrBankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('qr_' . $qrBankAccount['entity_id'], $qrCode['id']);
        $this->assertEquals('qr_code', $qrBankAccount['type']);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, $qrBankAccount['account_number']);
        $this->assertEquals($ifsc, $qrBankAccount['ifsc_code']);

        $this->processOrNotifyBankTransfer($accountNumber, $ifsc, '1234utr');

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals($qrPayment['payer_bank_account_id'], $bankAccount['id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);

        $this->processRefund($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
    }

    public function testProcessBqrRblBankTransferAndRefund()
    {
        $qrCode = $this->getQrCodeForBankTransfer(Gateway::BT_RBL, '222333');

        $qrBankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('qr_' . $qrBankAccount['entity_id'], $qrCode['id']);
        $this->assertEquals('qr_code', $qrBankAccount['type']);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, $qrBankAccount['account_number']);
        $this->assertEquals($ifsc, $qrBankAccount['ifsc_code']);

        $this->processOrNotifyRblBankTransfer($accountNumber, 'utr12345');

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals($qrPayment['payer_bank_account_id'], $bankAccount['id']);
        $this->assertEquals('utr12345', $qrPayment['provider_reference_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals(343946, $qrPayment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);

        $this->runQrPaymentRequestAssertions(true, true, 'utr12345', null, null);

        $this->processRefund('pay_' . $payment['id']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(343946, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
    }

    public function testProcessBqrIciciBankTransferAndRefund()
    {
        $qrCode = $this->getQrCodeForBankTransfer(Gateway::BT_ICICI, '111222');

        $qrBankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('qr_' . $qrBankAccount['entity_id'], $qrCode['id']);
        $this->assertEquals('qr_code', $qrBankAccount['type']);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, $qrBankAccount['account_number']);
        $this->assertEquals($ifsc, $qrBankAccount['ifsc_code']);

        $this->processOrNotifyIciciBankTransfer($accountNumber);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $bankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals($qrPayment['payer_bank_account_id'], $bankAccount['id']);
        $this->assertEquals('ICICI123', $qrPayment['provider_reference_id']);
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals(100000, $qrPayment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);

        $this->runQrPaymentRequestAssertions(true, true, 'ICICI123', null, null);

        $this->processRefund('pay_' . $payment['id']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(100000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
    }

    private function getQrCodeForBankTransfer($gateway, $gatewayMerchantId)
    {
        $terminalAttributes = [ 'id' =>'BankTransTermi', 'gateway' => $gateway, 'gateway_merchant_id' => $gatewayMerchantId ];
        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal', $terminalAttributes);
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        return $this->createQrCode();
    }

    public function testProcessBqrBankTransferRefund()
    {
        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode(['fixed_amount' => true, 'payment_amount' => 10000]);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');

        $accountNumber = substr($qrCode['image_content'], $accountNumberPos + 15, 16);

        $ifsc = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->processOrNotifyBankTransfer($accountNumber, $ifsc, '1234utr');

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(0, $qrPayment['expected']);
    }

    protected function processOrNotifyBankTransfer($accountNumber, $ifsc, $utr, $amount = null, $mode = 'test')
    {
        $this->ba->proxyAuth();

        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $accountNumber;

        $request['content']['payee_ifsc'] = $ifsc;

        if (isset($this->testData['content']['payer_ifsc']) === true)
        {
            $request['content']['payer_ifsc'] = $this->testData['content']['payer_ifsc'];
        }

        $utr = $utr ?: strtoupper(random_alphanum_string(22));

        $request['content']['transaction_id'] = $utr;

        if ($mode === 'live')
        {
            $this->ba->yesbankAuth('live');
        }

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($utr, $response['transaction_id']);

        $this->assertEquals(true, $response['valid']);

        return $response;
    }

    protected function processOrNotifyRblBankTransfer($accountNumber, $utr, $amount = null, $mode = 'test')
    {
        $this->ba->directAuth();

        $request = $this->testData[__FUNCTION__];

        $request['request']['content']['Data'][0]['beneficiaryAccountNumber'] = $accountNumber;
        $request['request']['content']['Data'][0]['UTRNumber'] = $utr;

        $this->startTest($request);
    }

    protected function processOrNotifyIciciBankTransfer($accountNumber)
    {
        $this->ba->iciciAuth();

        $request = $this->testData[__FUNCTION__];

        $request['request']['content']['Virtual_Account_Number_Verification_IN'][0]['payee_account'] = $accountNumber;

        $this->startTest($request);
    }

    public function testCreateUpiQrCode()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateUpiQrCodeVerionModeTags()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'single_use'
        ];

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $response = $this->createQrCode($input);

        $this->runUpiQrV2Assertion($response, 'single_use');

        $input['usage'] = 'multiple_use';

        $response = $this->createQrCode($input);

        $this->runUpiQrV2Assertion($response, 'multiple_use');
    }

    public function testCreateUpiQrCodeFixedAmount()
    {
        $input = [
            'type'           => 'upi_qr',
            'usage'          => 'multiple_use',
            'fixed_amount'   => true,
            'payment_amount' => 5000
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
        $this->assertEquals(5000, $response['payment_amount']);
        $this->assertTrue($response['fixed_amount']);

        $this->runEntityAssertions($response);
    }

    public function testBqrGenerationWithBankAccount()
    {
        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode();

        $qrBankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('qr_' . $qrBankAccount['entity_id'], $qrCode['id']);
        $this->assertEquals('qr_code', $qrBankAccount['type']);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber    = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc             = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, $qrBankAccount['account_number']);
        $this->assertEquals($ifsc, $qrBankAccount['ifsc_code']);
    }

    public function testBqrGenerationWithRecoveryBankAccount()
    {
        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode();

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber    = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc             = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, '2223330048827001');
        $this->assertEquals($ifsc, 'YESB0CMSNOC');
    }

    public function testCreateQrCodeShortCloseBy()
    {
        $now = Carbon::now(Timezone::IST);

        $input = [
            'close_by'  => $now->getTimestamp() + 10,
        ];

        $minCloseBy = $now->copy()->addSeconds(120);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time');

        $this->createQrCode($input);
    }

    public function testCreateUpiQrWithInvoiceDetails()
    {
        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode(['tax_invoice' => $this->testData['tax_invoice'], 'type' => 'upi_qr']);

        $this->assertNotNull($qrCode['image_content']);
        $this->assertStringContainsString('gstIn=06AABCU9603R1ZR', $qrCode['image_content']);
        $this->assertStringContainsString('gstBrkUp=GST:40.1|SGST:20.05|CGST:20.05|CESS:2', $qrCode['image_content']);
        $this->assertStringContainsString('invoiceNo=INV001', $qrCode['image_content']);
        $this->assertStringContainsString('invoiceDate=2020-05-20T17:14:58+05:30', $qrCode['image_content']);

        $this->runEntityAssertions($qrCode);
    }

    public function testCreateUpiQrCodeUpiIntentLinkExposure()
    {
        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData['testCreateUpiQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->assertArrayHasKey('image_content', $response);

        $this->runEntityAssertions($response);
    }

    public function testCloseQrCode()
    {
        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    private function runEntityAssertions($response)
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertNotNull($qrCodeEntity['short_url']);
        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('qrmoremegast', $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('@icici', $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['fixed_amount'] === true)
        {
            $amount = $qrCodeEntity['amount'] / 100;

            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }

        if ($response['type'] === Type::BHARAT_QR)
        {
            $this->assertStringContainsString('0518' . substr($response['id'], 3) . 'qrv2', $qrCodeEntity['qr_string']);
        }
    }

    public function testProcessIciciQrPayment()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testQrPaymentWithDisabledUpiMethod()
    {

        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn                                  = '000011100101';
        $request['content']['BankRRN']        = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->fixtures->merchant->disableMethod('LiveAccountMer', 'upi');

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment', 'live');
        $payment   = $this->getDbLastEntityToArray('payment', 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals( $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED, $qrPayment['unexpected_reason']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testCreateQrCodeWithUpiDisabled()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('UPI transactions are not enabled for the merchant');

        $this->fixtures->merchant->disableMethod('LiveAccountMer', 'upi');

        $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');
    }

    protected function processIciciQrPaymentWithDifferentAmountUtil($amount)
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $request['content']['PayerAmount'] = $amount;

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId .'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment');
        $payment   = $this->getDbLastEntityToArray('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($amount * 100, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentWithDifferentAmounts()
    {
        $this->processIciciQrPaymentWithDifferentAmountUtil(100);
        $this->processIciciQrPaymentWithDifferentAmountUtil(200);
    }
    
    public function testProcessIciciQrPaymentForQrNotFound()
    {
        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'H1234567890abcqrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getLastEntity('payment', true, 'live');

        $refund = $this->getDbLastEntity('refund', 'live');
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_QR_NOT_FOUND, $refund['notes']['refund_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentOnClosedQrCode()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_ON_CLOSED_QR_CODE, $refund['notes']['refund_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals(0, $qrPayment['expected']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessBankTransferOnClosedQrCode()
    {
        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $utr = '110101010';
        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->processOrNotifyBankTransfer($accountNumber, $ifsc, $utr);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('bank_transfer', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(5000000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals(0, $qrPayment['expected']);
    }

    public function testProcessQrPaymentAmountMismatch()
    {
        $qrCode = $this->createQrCode(['fixed_amount' => true, 'payment_amount' => 5000]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 10.00;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH, $refund['notes']['refund_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(1000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH, $qrPayment['unexpected_reason']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentWithCustomerFeeBearerModel()
    {
        $this->fixtures->merchant->edit('LiveAccountMer',['fee_bearer' => FeeBearer::CUSTOMER]);

        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment','live');
        $payment = $this->getDbLastEntityToArray('payment','live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::QR_CODE_PAYMENT_FAILED_FEE_OR_TAX_TAMPERED, $qrPayment['unexpected_reason']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessDuplicateIciciQrPayment()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $oldQrPaymentRequest = $this->getDbLastEntity('qr_payment_request');
        $this->assertEquals($oldQrPaymentRequest['expected'], true);

        $this->makeUpiIciciPayment($request);

        $newQrPaymentRequest = $this->getDbLastEntity('qr_payment_request');

        $this->assertEquals($rrn, $newQrPaymentRequest['transaction_reference']);

        $this->assertEquals($newQrPaymentRequest['transaction_reference'], $oldQrPaymentRequest['transaction_reference']);

        $this->assertNull($newQrPaymentRequest['expected']);
        $this->assertEquals($newQrPaymentRequest['failure_reason'], 'QR_PAYMENT_DUPLICATE_NOTIFICATION');
    }

    public function testProcessIciciQrPaymentOnSingleUseQrCode()
    {
        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment   = $this->getLastEntity('payment', true, 'live');
        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals(1, $qrPayment['expected']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testCardQrPaymentProcess()
    {
        $qrCode = $this->createQrCode();

        $this->ba->directAuth();

        $qrCodeId = substr($qrCode['id'], 3);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $content = $this->getMockServer('hitachi')->getBharatQrCallback($qrCodeId . 'qrv2', 'random123');

        $request = [
            'url'       => '/payment/callback/bharatqr/hitachi',
            'raw'       => http_build_query($content),
            'method'    => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $xmlResponse = $response['original'];

        $response = $this->parseResponseXml($xmlResponse);

        $this->assertEquals('OK', $response[0]);

        //Created Qr Entity As Expected
        $qrPayment = $this->getLastEntity('qr_payment', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('card', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(200, $payment['amount']);
        $this->assertEquals('hitachi', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(true, $qrPayment['expected']);
        $this->assertEquals('random123', $qrPayment['provider_reference_id']);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('Random Name', $card['name']);
    }

    public function testQrCodeTestPayments()
    {
        $this->fixtures->create('terminal:shared_sharp_terminal');

        $qrCode = $this->createQrCode();

        $qrCodeId = substr($qrCode['id'], 3);

        $this->ba->privateAuth();

        $content = [
            'reference' => $qrCodeId . 'qrv2',
            'method'    => 'upi',
            'amount'    => '100',
        ];

        $request['content'] = $content;

        $request['method'] = 'post';

        $request['url'] = '/bharatqr/pay/test';

        $this->makeRequestAndGetContent($request);

        //Created Qr Entity As Expected
        $qrPayment = $this->getLastEntity('qr_payment', true);

        // Payment is automatically captured
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(100, $payment['amount']);
        $this->assertEquals('sharp', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals('10000000000000', $payment['merchant_id']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(true, $qrPayment['expected']);
    }

    public function testReminderCallback()
    {
        $input = $this -> getDefaultQrCodeRequestArray();

        $input['close_by'] = Carbon::now()->getTimestamp() + 1000;

        $qrCode = $this->createQrCode($input);

        $qrCodeId = $qrCode['id'];

        $testData = $this->testData[__FUNCTION__];

        $callback_url = $testData['base_url'].$qrCodeId;

        $request = [
            'method'  => 'POST',

            'url'     => $callback_url
        ];

        $this->ba->reminderAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['success']);

        $qrCodeEntity= $this->getDbLastEntity('qr_code');

        $this->assertEquals($testData['expected_status'], $qrCodeEntity->getStatus());
    }

    public function testFetchQrCodePayments()
    {
        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->processPaymentForQr($qrCodeId);

        $expectedResponse = $this->testData['testFetchPaymentsForQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment());
    }

    public function testFetchPaymentsForQrCode()
    {
        $this->markTestSkipped();

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->processPaymentForQr($qrCodeId);

        $expectedResponse = $this->testData['testFetchPaymentsForQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment($qrCode['id']));
    }

    protected function processPaymentForQr($qrCodeId)
    {
        $request = $this->testData['testProcessIciciQrPayment'];

        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);
    }

    public function testFetchQrCodeByCustomerId()
    {
        $this->createQrCode(['customer_id' => 'cust_100000customer', 'type'  => 'upi_qr']);

        $expectedResponse = $this->testData['testFetchQrCodeByCustomerId'];

        $this->assertArraySelectiveEquals($expectedResponse,
                                          $this->fetchQrCode(null, ['customer_id' => 'cust_100000customer']));
    }

    public function testFetchQrCodeById()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer', 'type'  => 'upi_qr']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrCode($qrCode['id']));
    }

    public function testFetchQrCodeByCustomerEmail()
    {
        $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $response = $this->fetchQrCode(null, ['cust_email' => 'test@razorpay.com']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    protected function enableRazorXTreatmentForQrBankTransfer()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::QR_CODE_BANK_TRANSFER))
                   {
                       return 'on';
                   }
                   return 'control';
               });
    }

    protected function enableRazorXTreatmentForQrDynamicVpa()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::QR_CODE_DYNAMIC_VPA))
                   {
                       return 'on';
                   }
                   return 'control';
               });
    }

    private function runUpiQrV2Assertion($response, string $usageType)
    {
        $this->assertStringContainsString('ver=01', $response['image_content']);
        $this->assertStringContainsString('qrMedium=04', $response['image_content']);

        if ($usageType === UsageType::MULTIPLE_USE)
        {
            $this->assertStringContainsString('mode=01', $response['image_content']);
        }
        else
        {
            $this->assertStringContainsString('mode=15', $response['image_content']);
        }
    }

    public function testDynamicVpaAdditionToQrCode()
    {
        $this->enableRazorXTreatmentForQrDynamicVpa();

        $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);
        $qrCode = $this->getLastEntity('qr_code', true);

        $this->assertEquals('qr_' . $vpa['entity_id'], $qrCode['id']);
        $this->assertStringContainsString($vpa['username'], $qrCode['qr_string']);
    }

    public function testSettingsVpaAdditionToQrCode()
    {
        $response = $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);

        $this->assertNull($vpa['entity_id']);
        $this->runEntityAssertions($response);
    }

    public function testVpaVerification()
    {
        $this->enableRazorXTreatmentForQrDynamicVpa();

        $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);
        $address = explode('.', $vpa['username'])[1];

        $input = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>' . $address . '</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVpa('upi_icici', $virtualUpiRoot, $input);
        $response = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '0');
        $this->assertEquals($response['Message'], 'VALID');
        $this->assertEquals($response['CustName'], 'more-megastore-account');
        $this->assertEquals($response['TxnId'], 'YBL457b50e1fa8b452ab996560a0c9bc8be');
    }

    public function testVpaVerificationForQrPrefixedVpa()
    {
        $input = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>qrtestaccount1234567</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVpa('upi_icici', $virtualUpiRoot, $input);
        $response = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '0');
        $this->assertEquals($response['Message'], 'VALID');
        $this->assertEquals($response['CustName'], 'Razorpay QR Payment');
        $this->assertEquals($response['TxnId'], 'YBL457b50e1fa8b452ab996560a0c9bc8be');
    }

    public function testInvalidVpaVerification()
    {
        $input = '<XML><Source>ICICI-EAZYPAY</Source><SubscriberId>upitestaccount123456</SubscriberId><TxnId>YBL457b50e1fa8b452ab996560a0c9bc8be</TxnId></XML>';
        $virtualUpiRoot = explode('.', $this->vpaTerminal['virtual_upi_root'])[0];

        $rawResponse = $this->ecollectValidateVpa('upi_icici', $virtualUpiRoot, $input);
        $response = (array) simplexml_load_string($rawResponse->content());

        $this->assertEquals($response['ActCode'], '1');
        $this->assertEquals($response['Message'], 'INVALID');
    }

    protected function runQrPaymentRequestAssertions(bool $isCreated, $expected, string $transactionReference, $errorMessage = null, $upiId = null)
    {
        $qrPaymentRequest = $this->getDbLastEntity('qr_payment_request');

        $this->assertNotNull($qrPaymentRequest['request_payload']);
        $this->assertNotNull($qrPaymentRequest['bharat_qr_id']);
        $this->assertEquals($isCreated, $qrPaymentRequest['is_created']);
        $this->assertEquals($expected, $qrPaymentRequest['expected']);
        if ($errorMessage !== null)
        {
            $this->assertNotNull($qrPaymentRequest['failure_reason']);
        }
        $this->assertEquals($transactionReference, $qrPaymentRequest['transaction_reference']);
    }

    public function testQrCodeDemo()
    {
        $this->ba->directAuth();

        $this->app['rzp.mode'] = 'test';

        $testData = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($testData);

        $this->assertEquals($testData['content']['name'],$response['name']);

        $this->assertEquals($testData['content']['usage'],$response['usage']);

        $this->assertEquals($testData['content']['type'],$response['type']);

        $this->assertNotNull($response['id']);
    }
}
