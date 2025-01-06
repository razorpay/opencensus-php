<?php

namespace Functional\QrCode;

use Mockery;
use Carbon\Carbon;
use RZP\Error\PublicErrorDescription;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;
use RZP\Services\SplitzService;
use Queue;

use RZP\Exception\LogicException;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Models\Merchant\Account;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Jobs\QrStatusCheck;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\Payment\Gateway;
use RZP\Services\Mock\Reminders;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;
use RZP\Tests\Traits\TestsWebhookEvents;


class NonVirtualAccountQrCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use TestsWebhookEvents;
    use PartnerTrait;

    private $vpaTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr', 'omni_enabled']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr', 'omni_enabled'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->bqrTerminal = $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->config['gateway.mock_upi_mozart'] = true;
    }

    public function testCreateBharatQrCode()
    {
        $response = $this->createQrCode(['request_source' => 'ezetap']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testDownloadBharatQr()
    {
        $response = $response = $this->createQrCode(['request_source' => 'ezetap']);

        $qrCodeId = $response['id'];

        $request = [
            'method'  => 'GET',
            'url'     => '/t/qrcode/' . $qrCodeId,
        ];

        $this->ba->directAuth();

        $response = $this->sendRequest($request);

        $this->assertContentTypeForResponse('image/png', $response);
    }

    public function testCreateQrCodeInvalidCustomer()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('The id provided does not exist');

        $this->createQrCode(['customer_id' => 'cust_110000customer','request_source' => 'ezetap']);
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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

        $data = $this->testData['processOrNotifyBankTransfer'];
        $payerBankAccountNumber =  $data['content']['payer_account'];

        $this->assertEquals($qrPayment['payer_bank_account_id'], $bankAccount['id']);
        $this->assertEquals($payerBankAccountNumber, $bankAccount['account_number']);

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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    public function testProcessBqrBankTransferDuplicate()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->enableRazorXTreatmentForQrBankTransfer();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['qr_image_content']);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrBankAccount = $this->getDbLastEntity('bank_account');

        $this->assertEquals('qr_' . $qrBankAccount['entity_id'], $qrCode['id']);
        $this->assertEquals('qr_code', $qrBankAccount['type']);

        $accountNumberPos = strpos($qrCode['image_content'], '0827');
        $accountNumber    = substr($qrCode['image_content'], $accountNumberPos + 15, 16);
        $ifsc             = substr($qrCode['image_content'], $accountNumberPos + 4, 11);

        $this->assertEquals($accountNumber, $qrBankAccount['account_number']);
        $this->assertEquals($ifsc, $qrBankAccount['ifsc_code']);

        $this->processOrNotifyBankTransfer($accountNumber, $ifsc, '1234utr');

        $qrPaymentRequest = $this->getDbLastEntityToArray('qr_payment_request');
        $this->assertEquals($qrPaymentRequest['is_created'], 1);
        $this->assertEquals($qrPaymentRequest['expected'], 1);
        $this->assertEquals($qrPaymentRequest['failure_reason'],'');

        $this->processOrNotifyBankTransfer($accountNumber, $ifsc, '1234utr');

        $qrPaymentRequest = $this->getDbLastEntityToArray('qr_payment_request');
        $this->assertEquals($qrPaymentRequest['is_created'], 0);
        $this->assertNull($qrPaymentRequest['expected']);
        $this->assertEquals($qrPaymentRequest['failure_reason'], "QR_PAYMENT_DUPLICATE_NOTIFICATION");
    }

    public function testProcessBqrIciciBankTransferAndRefund()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    public function testCreateUpiQrCodeWithoutTransactionName()
    {
        $this->fixtures->merchant->addFeatures(['qr_custom_txn_name']);

        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $response = $this->createQrCode($input);

        $expectedResponse = $this->testData['testCreateUpiQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertStringNotContainsString('&tn=', $qrCodeEntity['qr_string']);
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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
            'usage'     => 'single_use',
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

    public function testQRContentForSubmerchant()
    {
        $submerchantId = '10000000000000';

        $partnerId = '10000000000009';

        $this->createPartnerAndLinkSubmerchant($submerchantId, $partnerId);

        $this->fixtures->merchant->addFeatures(['subm_qr_image_content'], $partnerId);

        $qrCode = $this->createQrCode(['tax_invoice' => $this->testData['tax_invoice'], 'type' => 'upi_qr','request_source' => 'ezetap'], 'test', $submerchantId);

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
        $response = $this->createQrCode(['request_source' => 'ezetap','usage' => 'single_use']);

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    public function testProcessIciciQrPayment()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testFetchUnprocessedPaymentByQrCodeId()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $qrCode = $this->createQrCode();
        $qrCodeId = $qrCode['id'];
        $this->assertNotNull($qrCodeId);

        $searchResponse = $this->fetchPaymentByQrCodeIdOnCheckout($qrCodeId);

        $this->assertEquals('unprocessed', $searchResponse['status']);
    }

    public function testFetchCapturedPaymentByQrCodeId()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $qrCode = $this->createQrCode();
        $qrCodeId = $qrCode['id'];
        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $searchResponse = $this->fetchPaymentByQrCodeIdOnCheckout('qr_' . $qrCodeId);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($searchResponse['razorpay_payment_id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $searchResponse['status']);

        $this->assertEquals($qrCodeId, $payment['receiver_id']);
    }

    public function testCreateBharatQrCodeWithEntityOrigin()
    {
        $this->markTestSkipped("Entity Origin for merchant auth is deprecated and will not be stores");
        $response = $this->createQrCode();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);

        $entityOriginEntity = $this->getLastEntity('entity_origin', true);

        $this->fixtures->stripSign($response['id']);
        $this->assertEquals($entityOriginEntity['entity_id'], $response['id']);
    }

    public function testProcessIciciQrPaymentForQrNotFound()
    {
        self::markTestSkipped();
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

    public function testQrCodeDemo()
    {
        $this->markTestSkipped('landing page feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $this->ba->directAuth();

        $this->app['rzp.mode'] = 'test';

        $testData = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($testData);

        $this->assertEquals($testData['content']['name'],$response['name']);

        $this->assertEquals($testData['content']['usage'],$response['usage']);

        $this->assertEquals($testData['content']['type'],$response['type']);

        $this->assertNotNull($response['id']);
    }

    /**
     * @group payment_links
     */
    public function testQrCodeCreateForPaymentLinks()
    {
        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
        ]);

        [$input, $response] = $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals($response['payment_amount'], $order->getAmount());
        $this->assertStringContainsString('icicirefID', $response[Entity::QR_STRING]);

        $this->runEntityAssertionsForPaymentLinksQR($qrCode, $input, $order);

    }

    /**
     * @group payment_links
     */
    public function testQrCodeCreateForPaymentLinksUsingExistingQR()
    {
        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
        ]);

        /* Call 1: initial call where fresh QR is always created */
        [$inputCall1, $responseCall1] = $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode1 = $this->getDbLastEntity('qr_code');


        /* Call 2: As there would be enough buffer for the user to pay before expiry, we reuse the QR */
        $tsCall2 = Carbon::now()->getTimestamp();
        [$inputCall2, $responseCall2] = $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode2 = $this->getDbLastEntity('qr_code');
        $buffer = $qrCode2->getAttribute(Entity::CLOSE_BY) - Carbon::now()->getTimestamp();

        $this->assertEquals($responseCall2['id'], 'qr_' . $qrCode1->getId());
        $this->assertEquals($qrCode2->getId(), $qrCode1->getId());
        $this->runEntityAssertionsForPaymentLinksQR($qrCode2, $inputCall2, $order);
        // time checks
        $this->assertGreaterThanOrEqual($qrCode2->getCreatedAt(), $tsCall2);
        $this->assertGreaterThanOrEqual(10*100, $buffer);


        /* Call 3: When there's no enough buffer, create a new QR */
        $this->fixtures->qr_code->edit($qrCode1->getId(), ['close_by' => Carbon::now()->getTimestamp() + 2*100 ]); // change expiry.

        $tsCall3 = Carbon::now()->getTimestamp();
        [$inputCall3, $responseCall3] = $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode3 = $this->getDbLastEntity('qr_code');
        $buffer = $qrCode3->getAttribute(Entity::CLOSE_BY) - Carbon::now()->getTimestamp();

        $this->assertNotEquals($responseCall3['id'], 'qr_' . $qrCode1->getId());
        $this->assertNotEquals($qrCode3->getId(), $qrCode1->getId());
        $this->runEntityAssertionsForPaymentLinksQR($qrCode3, $inputCall3, $order);
        // time checks
        $this->assertGreaterThanOrEqual($tsCall3, $qrCode3->getCreatedAt());
        $this->assertGreaterThanOrEqual(10*100, $buffer);

    }

    /**
     * @group payment_links
     */
    public function testPaymentOnAutoCapturePaymentLinksQr()
    {

        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        // load entities
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode = $this->getDbLastEntity('qr_code');
        $order->reload();

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertTrue($qrPayment['expected']);
        $this->assertEquals(Status::CLOSED, $qrCode['status']);
        $this->assertEquals(CloseReason::PAID, $qrCode['close_reason']);
        $this->assertEquals(Order\Status::PAID, $order->getStatus());
    }

    /**
     * @group payment_links
     */
    public function testPaymentOnManualCapturePaymentLinksQr()
    {
        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => false // set auto capture false
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        // load entities
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode = $this->getDbLastEntity('qr_code');
        $order->reload();

        // basic assertions.
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertTrue($qrPayment['expected']);
        $this->assertEquals(Status::CLOSED, $qrCode['status']);
        $this->assertEquals(CloseReason::PAID, $qrCode['close_reason']);

        // status checks - payment should not be captured already.
        $this->assertEquals(Order\Status::ATTEMPTED, $order->getStatus());
        $this->assertEquals('authorized', $payment['status']);
    }

    /**
     * @group payment_links
     */
    public function testUnexpectedPaymentAmountMismatchOnPLQr()
    {

        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order, ['PayerAmount' => 40]);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals('FallbackQrCode', $payment['receiver_id']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $this->assertFalse($qrPayment['expected']);
        $this->assertEquals(\RZP\Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH, $qrPayment['unexpected_reason']);
    }

    /**
     * @group payment_links
     */
    public function testUnexpectedPaymentPaidOrderOnPLQr()
    {

        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        // mark the order paid before payment
        $this->fixtures->order->edit($order->getId(), ['status' => 'paid']);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $order->reload();

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals('FallbackQrCode', $payment['receiver_id']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertFalse($qrPayment['expected']);
        $this->assertEquals(\RZP\Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID, $qrPayment['unexpected_reason']);

    }

    /**
     * @group payment_links
     */
    public function testPaymentOnClosedQrOnPLQr()
    {
        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $qrCode = $this->closeQrCode('qr_' . $qrCode['id']);
        $this->assertEquals('closed', $qrCode['status']);

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        //Payment made before Closing Time so, we will accept this Callback
        $refund = $this->getDbLastEntity('refund');
        $this->assertNull($refund,'Refund Entity should be null');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(1000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCode['id'], 'qr_' . $qrPayment['qr_code_id']);

        $this->assertEquals(true, $qrPayment['expected']);
    }

    /**
     * @group payment_links
     */
    public function testDuplicatePaymentOnPLQr()
    {
        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '1000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        $oldQrPaymentRequest = $this->getDbLastEntity('qr_payment_request');
        $this->assertEquals($oldQrPaymentRequest['expected'], true);

        // Call 2
        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        $newQrPaymentRequest = $this->getDbLastEntity('qr_payment_request');

        $this->assertEquals($newQrPaymentRequest['transaction_reference'], $oldQrPaymentRequest['transaction_reference']);

        $this->assertNull($newQrPaymentRequest['expected']);
        $this->assertEquals($newQrPaymentRequest['failure_reason'], 'QR_PAYMENT_DUPLICATE_NOTIFICATION');
    }



    /**
     * @group payment_links
     */
    public function testPaymentLinksQrCodePricing(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 2.00%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 100, // 100 base points i.e. 1.00%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $order = $this->fixtures->order->create([
            'product_type' => 'payment_link_v2',
            'amount' => '10000',
            'currency' => 'INR',
            'payment_capture' => true
        ]);

        $this->createQrCodeForPaymentLinksOrder($order);

        $qrCode = $this->fetchAndAssertQrEntity();

        $this->hitUpiIciciCallbackForQRv2($qrCode, $order);

        // load entities
        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );

        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals($order->getAmount(), $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure UPI Fees is Charged and not QR's i.e. 2.00%
        $this->assertEquals(236, $payment->getFee());
        $this->assertEquals(36, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(200, $feeBreakup[0]['amount']); // 2.00% of 10000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(36, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 200
    }

    public function testQrCodeCreateForCheckoutWithOrder()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $order = $this->fixtures->create('order');

        $response = $this->createQrCodeForCheckout($order);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals($order['id'], $qrCode['entity_id']);
        $this->assertEquals('order', $qrCode['entity_type']);
        $this->runEntityAssertions($response);

        // for same order, QR code should be same
        $response2 = $this->createQrCodeForCheckout($order);
        $this->assertEquals($response['id'], $response2['id']);
    }

    public function testQrCodeCreateForCheckoutWithoutOrder()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $response = $this->createQrCodeForCheckout(null, 1000);

        $this->runEntityAssertions($response);
    }

    public function testPaymentOnQrCodeWithOrder()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $order = $this->fixtures->create('order');

        $qrCode = $this->createQrCodeForCheckout($order);

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order->getAmountDue() / 100;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode = $this->getDbLastEntity('qr_code');
        $order->reload();

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertTrue($qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);

        $this->assertEquals(Status::CLOSED, $qrCode['status']);
        $this->assertEquals(CloseReason::PAID, $qrCode['close_reason']);
        $this->assertEquals(Order\Status::PAID, $order->getStatus());
    }

    public function testFetchQrCodeOnCheckout()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $order = $this->fixtures->create('order');

        $this->createQrCodeForCheckout($order);

        $response = $this->fetchQrCode();

        $this->assertEquals(0, $response['count']);
    }

    public function testPaymentOnQrCodeWithoutOrder()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $qrCode = $this->createQrCodeForCheckout(null, 4510);

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 45.10;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4510, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertTrue($qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);

        $this->assertEquals(Status::CLOSED, $qrCode['status']);
        $this->assertEquals(CloseReason::PAID, $qrCode['close_reason']);
    }

    public function testCheckoutQrPaymentOnPaidOrder()
    {
        $this->markTestSkipped('checkout feature has been removed,
        the routes created for this feature are no longer being used.
        Skipping this test case as this tests that particular routes');

        $order = $this->fixtures->create('order');

        $qrCode = $this->createQrCodeForCheckout($order);

        $this->fixtures->order->edit($order->getId(), ['status' => 'paid']);

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order->getAmount() / 100;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
        $this->assertEquals('FallbackQrCode', $payment['receiver_id']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertFalse($qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessIciciQrPaymentWithInvalidHandle()
    {
        self::markTestSkipped();
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerVA'] = 'vinaysurya@randominvalidvpahandle';

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

    public function testProcessIciciQrPaymentInternal()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $response = $this->makeUpiIciciPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessIciciQrPaymentInternalWithPayerAccountType()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $response = $this->makeUpiIciciPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals('credit_card', $payment['reference2']);
    }

    public function testProcessIciciQrPaymentInternalWithInvalidPayerAccountType()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $response = $this->makeUpiIciciPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertNull($payment['reference2']);
    }

    public function testProcessIciciQrPaymentInternalDuplicate()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];
        $requestInternal = $this->testData['testProcessIciciQrPaymentInternal'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $requestInternal['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $requestInternal['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);
        $response = $this->makeUpiIciciPaymentInternal($requestInternal);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessIciciQrPaymentInternalQrNotFound()
    {

        $requestInternal = $this->testData['testProcessIciciQrPaymentInternal'];

        $requestInternal['content']['merchantTranId'] = 'qwertyuiop1234qrv2';

        $response = $this->makeUpiIciciPaymentInternal($requestInternal);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('refunded', $response['payment']['status']);

    }

    public function testProcessIciciQrPaymentInternalTerminalNotFound()
    {
        $requestInternal = $this->testData['testProcessIciciQrPaymentInternal'];

        $requestInternal['content']['merchantId'] = '1234567';

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED);

        $this->expectExceptionMessage('Qr payment processing failed');

        $this->makeUpiIciciPaymentInternal($requestInternal);
    }

    public function testQrPaymentWithDisabledUpiMethod()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);

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

    public function testQrPaymentWithOrderIdMandatoryEnabled()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);

        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn                                  = '000011100101';
        $request['content']['BankRRN']        = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->fixtures->merchant->addFeatures( ['order_id_mandatory'],'LiveAccountMer');

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntityToArray('qr_payment', 'live');
        $payment   = $this->getDbLastEntityToArray('payment', 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals( $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals(UnexpectedPaymentReason::QR_CODE_MISSING_ORDER_ID, $qrPayment['unexpected_reason']);
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

    public function testCreateBharatQrCodeWithUpiDisabled()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage('No identifiers found for the merchant');

        $this->fixtures->merchant->disableMethod('LiveAccountMer', 'upi');

        $this->createQrCode(['usage'=>'single_use', 'type'=>'bharat_qr'], 'live', 'LiveAccountMer');
    }

    protected function processIciciQrPaymentWithDifferentAmountUtil($amount)
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    public function testProcessIciciQrPaymentOnClosedQrCode()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

        //Payment made before Closing Time so, we will accept this Callback
        $refund = $this->getDbLastEntity('refund');
        $this->assertNull($refund,'Refund Entity should be null');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);

        $this->assertEquals(true, $qrPayment['expected']);

        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessBankTransferOnClosedQrCode()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
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

    public function testQrCodePricing(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 1.50%
        $this->assertEquals(1770, $payment->getFee());
        $this->assertEquals(270, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']); // 1.50% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 1500
    }

    public function testRZPPosQrCodePricing(): void
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        // add qr_code pricing plan
        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];
        // add pos qr_code pricing plan
        $posQRPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantPosUPIPricingPlan1',
            'payment_method'      => 'upi',
            'channel'             => 'in_person',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'offline',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);
        $this->fixtures->create('pricing', $posQRPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 200000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100102';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 2000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );

        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(200000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 1.50%
        $this->assertEquals(3540, $payment->getFee());
        $this->assertEquals(540, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(3000, $feeBreakup[0]['amount']); // 1.50% of 200000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(540, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 3000

        // use ezetap auth to add request source as ezetap
        $this->ba->ezetapInternalAuth('test', Account::TEST_ACCOUNT);

        // create QR code with ezetap auth
        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT, headers: ['X-Razorpay-Request-Source' => 'ezetap']
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->assertEquals($qrCode['request_source'], 'ezetap');

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );

        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals("in_person", $payment->getSourceChannel());
        // Ensure Default POS UPI Fees is Charged i.e. 0
        $this->assertEquals(0, $payment->getFee());
        $this->assertEquals(0, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(0, $feeBreakup[0]['amount']); // 1.50% of 0
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(0, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 0
    }

    public function testProcessDuplicateIciciQrPayment()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
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

    public function testCreateUseQrCodeWithEzetapRequestSource()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer', headers: ['X-Razorpay-Request-Source' => 'ezetap']);

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals($qrCode['request_source'], 'ezetap');
    }

    public function testQRCreatedWebhookWithEzetapRequestSource()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
        $this->expectWebhookEvent(
            'qr_code.created',
            function (array $event)
            {
                $this->assertSame('ezetap', $event['payload']['qr_code']['entity']['request_source'] );
            }
        );

        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer', headers: ['X-Razorpay-Request-Source' => 'ezetap']);

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals($qrCode['request_source'], 'ezetap');
    }

    public function testPaymentEntityInQrCodeWithEzetapRequestSource()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        // add pos qr_code pricing plan
        $posQRPricingPlan = [
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'TestMerchantPosUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'offline',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'channel'             => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);

        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer', headers: ['X-Razorpay-Request-Source' => 'ezetap']);

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100102';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $payment   = $this->getLastEntity('payment', true, 'live');
        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($qrCode['request_source'], 'ezetap');
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals('in_person', $payment['reference13']);
    }


    public function testCardQrPaymentProcess()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

        $this->assertEquals('', $card['name']);
    }

    public function testQrCodeTestPayments()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    protected function enableRazorXTreatmentForQrBankTransfer()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::QR_CODE_BANK_TRANSFER => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    private function runUpiQrV2Assertion($response, string $usageType)
    {
        $this->assertStringContainsString('ver=01', $response['image_content']);
        $this->assertStringContainsString('qrMedium=04', $response['image_content']);

        if ($usageType === UsageType::MULTIPLE_USE)
        {
            $this->assertStringContainsString('mode=19', $response['image_content']);
        }
        else
        {
            $this->assertStringContainsString('mode=22', $response['image_content']);
        }
    }

    public function testDynamicVpaAdditionToQrCode()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);
        $qrCode = $this->getLastEntity('qr_code', true);

        $this->assertEquals('qr_' . $vpa['entity_id'], $qrCode['id']);
        $this->assertStringContainsString($vpa['username'], $qrCode['qr_string']);
    }

    public function testVpaVerification()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    public function testQrCodeWithPartnerNameFlagEnabled()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->fixtures->merchant->addFeatures(['qr_image_partner_name']);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

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

    public function testFetchQrCodeByPaymentId()
    {
        $qrCode =$this->createQrCode(['type'  => 'upi_qr']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $expectedResponse = $this->testData['testFetchQrCodeByPaymentId'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getDbLastEntityToArray('qr_payment');

        $this->assertArraySelectiveEquals($expectedResponse,
                                          $this->fetchQrCode(null, ['payment_id' => 'pay_' . $qrPayment['payment_id']]));
    }

    protected function createPartnerAndLinkSubmerchant(string $submerchantId, string $partnerId = '10000000000009')
    {
        $this->fixtures->merchant->create(['id' => $partnerId]);

        $this->fixtures->merchant->edit($partnerId, ['partner_type' => 'fully_managed']);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'fully_managed']);

        $appId = $app->getId();

        // Link new submerchants to the partner account
        $accessMap = $this->getAccessMapArray('application', $appId, $submerchantId, $partnerId);

        $this->fixtures->create('merchant_access_map',$accessMap);
    }

    public function testQrCodeCreditedEventWithTransactionIsolation()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->createPartnerAndSubmerchantMapping();

        $this->mockSplitzTreatmentBulkRequest([["variant" => ["name" => "enable"]]]);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $expectedEventData = $this->testData[__FUNCTION__];

        $this->expectWebhookEventWithContext(
            'qr_code.credited',
            ['entity_type' => 'qr_code', 'event_type' => 'partnership'],
            function (array $event) use ($expectedEventData)
            {
                $this->assertArraySelectiveEquals($expectedEventData, $event);
                $this->assertArrayNotHasKey('context', $event);
            }
        );

        $this->makeUpiIciciPayment($request);
    }

    protected function getAccessMapArray($entityType, $entityId, $merchantId, $entityOwnerId)
    {
        return [
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'merchant_id'     => $merchantId,
            'entity_owner_id' => $entityOwnerId,
        ];
    }

    public function testCreateQrCodeWithInvalidUtf8Chars()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Only plain text characters are allowed');

        //Here , \xf8 is an invalid utf8 character
        $response = $this->createQrCode(['name' => "vinay\xf8surya"]);
    }

    public function testFetchQrCodePayments()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode();

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $this->processPaymentForQr($qrCodeId);

        $expectedResponse = $this->testData['testFetchPaymentsForQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment());
    }

    public function testFetchPaymentsForQrCode()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]
        );

        $qrCode = $this->getDbLastEntity('qr_code');
        $qrCodeId = $qrCode['id'];

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['merchantId'] = $terminal->getGatewayMerchantId();
        $request['content']['merchantTranId'] = $qrCode['reference'].'qrv2';

        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getDbLastEntity('qr_payment');

        $expectedResponse = $this->testData['testFetchPaymentsForQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment('qr_' . $qrCodeId));
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
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $response = $this->fetchQrCode(null, ['cust_email' => 'test@razorpay.com']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testProcessIciciQrPaymentToFetchPayerName()
    {

        Mail::fake();

        $this->fixtures->merchant->addFeatures(['send_name_in_email_for_qr']);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer','type' => 'upi_qr']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $reflection = new \ReflectionClass($mail);
            $property = $reflection->getProperty('data');
            $property->setAccessible(true);
            $mailData = $property->getValue($mail);

            self::assertArrayHasKey('qr_customer', $mailData);

            return true;
        });
    }

    public function testProcessIciciQrPaymentToFetchNotPayerName()
    {
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        Mail::fake();

        $this->fixtures->merchant->addFeatures(['send_name_in_email_for_qr']);

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        Mail::assertQueued(AuthorizedMail::class, function ($mail)
        {
            $reflection = new \ReflectionClass($mail);
            $property = $reflection->getProperty('data');
            $property->setAccessible(true);
            $mailData = $property->getValue($mail);

            self::assertArrayNotHasKey('qr_customer', $mailData);

            return true;
        });
    }

    public function testQrPaymentOnIntentSubType()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
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
        $upi_metadata = $this->getLastEntity('upi_metadata', true, 'live');

        $this->assertEquals('intent', $upi_metadata['flow']);
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

    //This is to test a case where amount while converting to rupees gets rounded off,
    // which will result in payment refund due to amount mismatch
    public function testProcessQrPaymentWithPaiseInAmount()
    {
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminal->getId(), ['merchant_id' => 'LiveAccountMer']);
        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr', 'payment_amount'=>27071, 'fixed_amount'=> true], 'live', 'LiveAccountMer');

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = '270.71';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment   = $this->getLastEntity('payment', true, 'live');
        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals('closed', $qrCode['status']);
        $this->assertEquals('paid', $qrCode['close_reason']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(27071, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);

        $this->assertStringContainsString('am=270.71', $qrCode['qr_string']);
    }

    public function testFetchPaymentsForQrCodeFromDB()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]
        );

        $qrCode = $this->getLastEntity('qr_code', true);
        $qrCodeId = str_after($qrCode['id'], 'qr_');

        $request = $this->testData['testProcessIciciQrPayment'];
        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantId'] = $terminal->getGatewayMerchantId();
        $request['content']['merchantTranId'] = $qrCode['reference'] . 'qrv2';

        $this->makeUpiIciciPayment($request);
        $expectedResponse = $this->testData['testFetchPaymentsForQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $this->fetchQrPayment('qr_' . $qrCodeId));
    }

    public function testProcessPaymentOnStaticQrWithoutTransactionReference(): void
    {
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_gateway_unrecognised_payment_process') => 'on',

            ]
        );

        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeId = $qrCodeEntity['id'];
        $this->fixtures->stripSign($qrCodeId);

        $this->fixtures->on('test')->create('qr_code_config',
                                            [
                                                'merchant_id'  => $terminal['merchant_id'],
                                                'config_key'   => 'static_qr',
                                                'config_value' => '{"' . $terminal['id'] . '" : "' . $qrCodeId . '"}',
                                            ]);

        $request                              = $this->testData['testProcessIciciQrPayment'];
        $rrn                                  = '000011100101';
        $request['content']['BankRRN']        = $rrn;
        $request['content']['merchantId']     = $terminal->getGatewayMerchantId();
        $request['content']['merchantTranId'] = strtoupper(random_alphanum_string(22));

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment   = $this->getLastEntity('payment', true);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($qrCodeEntity['id'], 'qr_' . $qrPayment['qr_code_id']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function testProcessPaymentOnStaticQrWithoutTransactionReferenceWithRazorxDisabled(): void
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeId = $qrCodeEntity['id'];
        $this->fixtures->stripSign($qrCodeId);

        $this->fixtures->on('test')->create('qr_code_config',
                                            [
                                                'merchant_id'  => $terminal['merchant_id'],
                                                'config_key'   => 'static_qr',
                                                'config_value' => '{"' . $terminal['id'] . '" : "' . $qrCodeId . '"}',
                                            ]);

        $request                              = $this->testData['testProcessIciciQrPayment'];
        $rrn                                  = '000011100101';
        $request['content']['BankRRN']        = $rrn;
        $request['content']['merchantId']     = $terminal->getGatewayMerchantId();
        $request['content']['merchantTranId'] = strtoupper(random_alphanum_string(22));

        $this->makeUpiIciciPayment($request, false);

    }

    public function testReminderCallbackOnClosedQrCode()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'single_use',
            'fixed_amount' => true,
            'payment_amount' => 4000
        ];

        $input['close_by'] = Carbon::now()->getTimestamp() + 1000;

        $qrCode = $this->createQrCode($input);



        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = '000011100101';
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrEntity = $this->getDbLastEntity('qr_code');
        $this->assertEquals('closed', $qrEntity['status']);
        $this->assertEquals('paid', $qrEntity['close_reason']);


        $testData = $this->testData['testReminderCallback'];
        $callback_url = $testData['base_url'] . $qrCode['id'];
        $request = [
            'method'  => 'POST',

            'url'     => $callback_url
        ];

        $this->ba->reminderAppAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertTrue($response['success']);

        $qrCodeEntity= $this->getDbLastEntity('qr_code');

        $this->assertEquals('paid', $qrCodeEntity['close_reason']);
        $this->assertEquals($testData['expected_status'], $qrCodeEntity->getStatus());
    }


}
