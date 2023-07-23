<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Exception\LogicException;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Models\Merchant\Account;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
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

    private $vpaTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

    }

    public function testCreateBharatQrCode()
    {
        $response = $this->createQrCode();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
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

    public function testQRContentForSubmerchant()
    {
        $submerchantId = '10000000000000';

        $partnerId = '10000000000009';

        $this->createPartnerAndLinkSubmerchant($submerchantId, $partnerId);

        $this->fixtures->merchant->addFeatures(['subm_qr_image_content'], $partnerId);

        $qrCode = $this->createQrCode(['tax_invoice' => $this->testData['tax_invoice'], 'type' => 'upi_qr'], 'test', $submerchantId);

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

    private function runEntityAssertionsForDedicatedTerminalQr($response, $terminal, $mode = 'test')
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, $mode);

        $this->assertEquals($qrCodeEntity['id'], $response['id']);

        $vpa = null;
        switch ($terminal->getGateway())
        {
            case Gateway::UPI_ICICI:
            {
                $vpa = $terminal->getGatewayMerchantId2();

                switch ($qrCodeEntity['usage'])
                {
                    case "single_use":
                    {
                        $this->assertStringContainsString('icicirefID', $qrCodeEntity['qr_string']);

                        break;
                    }
                    case "multiple_use":
                    {
                        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                }
                break;
            }

            default:
            {
                $vpa = $terminal->getVpa();

                switch ($qrCodeEntity['usage'])
                {
                    case "single_use":
                    {
                        $this->assertStringContainsString('icicirefID', $qrCodeEntity['qr_string']);

                        break;
                    }
                    case "multiple_use":
                    {
                        $tr = substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                }
                break;
            }
        }

        $this->assertStringContainsString($vpa, $qrCodeEntity['qr_string']);

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

    public function testProcessIciciQrPaymentInternal()
    {
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

        $this->assertArrayHasKey('refunds', $response);

        $this->assertEquals($response['payment']['id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals($response['payment']['amount'], $response['refunds'][0]['amount']);
    }

    public function testProcessIciciQrPaymentInternalTerminalNotFound()
    {
        $requestInternal = $this->testData['testProcessIciciQrPaymentInternal'];

        $requestInternal['content']['merchantId'] = '1234567';

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED);

        $this->expectExceptionMessage('Terminal should not be null here');

        $this->makeUpiIciciPaymentInternal($requestInternal);
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

    public function testQrPaymentWithOrderIdMandatoryEnabled()
    {

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

    public function testCreateUseQrCodeWithEzetapRequestSource()
    {
        $qrCode = $this->createQrCode(['usage'=>'single_use', 'type'=>'upi_qr'], 'live', 'LiveAccountMer', headers: ['X-Razorpay-Request-Source' => 'ezetap']);

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals($qrCode['request_source'], 'ezetap');
    }

    public function testQRCreatedWebhookWithEzetapRequestSource()
    {
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
        $this->assertEquals('offline', $payment['notes']['receiver_type']);
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

        $this->assertEquals('', $card['name']);
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
        $this->setMockRazorxTreatment([RazorxTreatment::QR_CODE_BANK_TRANSFER => RazorxTreatment::RAZORX_VARIANT_ON]);
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
        $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);
        $qrCode = $this->getLastEntity('qr_code', true);

        $this->assertEquals('qr_' . $vpa['entity_id'], $qrCode['id']);
        $this->assertStringContainsString($vpa['username'], $qrCode['qr_string']);
    }

    public function testSettingsVpaAdditionToQrCode()
    {
        $this->markTestSkipped('Not using settings for VPA anymore');

        $response = $this->createQrCode();

        $vpa    = $this->getLastEntity('vpa', true);

        $this->assertNull($vpa['entity_id']);
        $this->runEntityAssertions($response);
    }

    public function testVpaVerification()
    {
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

    public function testQrCodeWithPartnerNameFlagEnabled()
    {
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
    public function testProcessIciciQrPaymentToFetchPayerName()
    {
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

            self::assertArrayHasKey('qr_customer', $mailData);

            return true;
        });
    }

    public function testProcessIciciQrPaymentToFetchNotPayerName()
    {
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

    public function testCreateDynamicQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $qrCode = $this->createQrCode(['usage'          => 'single_use',
                                       'type'           => 'upi_qr',
                                       'fixed_amount'   => true,
                                       'payment_amount' => 10000
                                      ],
                                      'live',
                                      'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

    }

    public function testCreateStaticQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($response, $terminal, 'live');
    }

    public function testCreateStaticQrWithDedicatedTerminalSharpGateway()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            'test',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($response, $terminal, 'test');
    }

    protected function enableRazorXTreatmentForQrDedicatedTerminal()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DEDICATED_TERMINAL_QR_CODE => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    protected function enableRazorXTreatmentForQrOnDemandClose()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    protected function enableRazorXTreatmentForCCOnUPI()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::ALLOW_CC_ON_UPI_PRICING))
                {
                    return 'on';
                }
                return 'control';
            });
    }

    public function testQrCodePricingForCreditCard(): void
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

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPayerAccountType'];

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

        $this->assertEquals(2360, $payment->getFee());
        $this->assertEquals(360, $payment->getTax());

        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(2000, $feeBreakup[0]['amount']); // 2.0% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(360, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 2000
    }

    public function testQrCodePricingForCreditCardWithoutSplitz(): void
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

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPayerAccountType'];

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
    public function testQrCodePricingForSavings(): void
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

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testQrCodePricingForSavings'];

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
    public function testProcessPaymentForDynamicQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->createQrCode(['usage'          => 'multiple_use',
                             'type'           => 'upi_qr',
                             'fixed_amount'   => true,
                             'payment_amount' => 4000
                            ],
                            'live',
                            'LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['merchantId'] = $terminal->getGatewayMerchantId();
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeEntity['reference'];

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'live');
        $payment = $this->getLastEntity('payment', true, 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    protected function enableRazorXTreatmentForClosedQrAutoCapture()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::QR_PAYMENT_AUTO_CAPTURE_FOR_CLOSED_QR => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    public function testDelayedCallbackOnSingleUseQrCode()
    {
        $this->enableRazorXTreatmentForClosedQrAutoCapture();

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000,
             'name'  => 'Mitasha']
        );

        $qrCodeId = $qrCode['id'];
        $qrCode   = $this->closeQrCode($qrCodeId);
        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);
        $request                              = $this->testData['testProcessIciciQrPayment'];
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('single_use', $qrCode['usage']);

        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('captured', $payment['status']);

        $request['content']['BankRRN'] = '015306767324';
        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('single_use', $qrCode['usage']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testDelayedCallbackOnMultipleUseQrCode()
    {
        $this->enableRazorXTreatmentForClosedQrAutoCapture();

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000,
             'name'  => 'Mitasha']
        );

        $qrCodeId = $qrCode['id'];
        $qrCode   = $this->closeQrCode($qrCodeId);
        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('multiple_use', $qrCode['usage']);

        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('captured', $payment['status']);

        $currentTime = str_replace([':', '-', ' '], '', Carbon::now(Timezone::IST)->toDateTimeString());

        $request['content']['TxnCompletionDate'] = $currentTime;
        $request['content']['BankRRN']           = '015306767324';

        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('multiple_use', $qrCode['usage']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testSingleUseQrCodeWithFixedAmount()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'name'           => 'Mitasha'
            ],
            'live',
            'LiveAccountMer'
        );

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');
    }

    public function testSingleUseQrCodeWithoutFixedAmount()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => false, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );
    }

    public function testMultipleUseQrCodeWithoutCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
                'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');
    }

    public function testMultipleUseQrCodeWithCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $closeBy = str_replace([':', '-', ' '], '', Carbon::now(Timezone::IST)->toDateTimeString());
        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha']
        );
    }

    public function testCloseQrCodeForSingleUse()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->assertEquals(Status::ACTIVE, $qrCode['status']);
        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
    }

    public function testCloseQrCodeForMultipleUse()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
                'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->assertEquals(Status::ACTIVE, $qrCode['status']);

        $this->expectException('RZP\Exception\BadRequestException');
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_CLOSE_STATIC_QR_CODE_FAILURE);

        $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');
    }

    public function testCreateQrCodeWithRequestSourceHeader()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $headers = [
            'X-Razorpay-Request-Source'  => 'payMobApp'
        ];

        $response = $this->createQrCode($input, 'test', '10000000000000', $headers);

        $expectedResponse = $this->testData['testCreateUpiQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals($headers['X-Razorpay-Request-Source'], $qrCode['request_source']);
    }

    public function testCreateQrCodeWithIncorrectRequestSourceHeader()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Not a valid source: abc');

        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $headers = [
            'X-Razorpay-Request-Source'  => 'abc'
        ];

        $this->createQrCode($input, 'test', '10000000000000', $headers);
    }

    public function testProcessIciciQrPaymentWithPayerAccountType()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'CREDIT|0123456';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

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
        $this->assertEquals('credit_card', $payment['reference2']);
    }

    public function testProcessIciciQrPaymentWithPayerAccountTypeNonCredit()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'PPIWALLET';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

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
        $this->assertEquals('ppiwallet', $payment['reference2']);
    }

    public function testProcessIciciQrPaymentWithInvalidPayerAccountType()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'INVALIDTYPE';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

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
        $this->assertNull($payment['reference2']);
    }

    public function testCloseQrCodeWithOnDemandFeatureFlagDisabled()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('This feature is not available for your account. Contact support to get it enabled');

        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $this->closeQrCode($response['id']);
    }

    public function testCloseQrCodeWithOnDemandFeatureFlagEnabled()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->fixtures->merchant->addFeatures(['close_qr_on_demand']);

        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    public function testCloseQrCodeWithQRNotCreatedUsingICICITerminal()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('This feature is not available for your account. Contact support to get it enabled');

        $response = $this->createQrCode();

        $response['qr_string'] = '05240130rzr.qrmoremegast00437171@abcabc27390240121RZPL2070"';

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $this->closeQrCode($response['id']);
    }

    public function testCloseSingleUseQrCodeWithCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $this->fixtures->on('live')->merchant->addFeatures(['close_qr_on_demand'],'LiveAccountMer');

        $terminal = $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');
        $this->fixtures->on('live')->terminal->edit($terminal['id'], ['gateway_merchant_id2' => 'razorpay@icici']);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy ,'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals(Status::ACTIVE, $qrCodeEntity['status']);

        $closeResponse = $this->closeQrCode($qrCodeEntity['id'],'live','LiveAccountMer');

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
    }

    public function testCreateStaticQrWithoutTerminal(): void
    {
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi', [
            'merchant_id'         => Account::SHARED_ACCOUNT,
            'gateway_merchant_id' => 'shared_bharat_qr',
        ]);

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');
    }

    public function testDedicatedTerminalSplitzExpWithVariantOn()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

    }

    public function testDedicatedTerminalSplitzExpWithVariantOff()
    {
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                        "key" => "result",
                        "value" => "off"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');


        $vpa    = $this->getLastEntity('vpa', true,'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertStringContainsString($vpa['username'], $qrCodeEntity['qr_string']);
        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);
    }

    public function testDedicatedTerminalSplitzExpWithNullVariant()
    {
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($output);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');


        $vpa    = $this->getLastEntity('vpa', true,'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertStringContainsString($vpa['username'], $qrCodeEntity['qr_string']);
        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);
    }

    public function testSelectSecondTerminalForQrCreation()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $this->fixtures->create('terminal:live_dedicated_upi_yesbank_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name'  => 'Mitasha'], 'live', 'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->assertEquals($qrCodeEntity['id'], $qrCode['id']);
    }

    public function testCreateQrWithOnDemandFeatureFlagEnabledAndCloseQrOnDemandForYesBank()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->merchant->addFeatures(['close_qr_on_demand']);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_QR_CODE_ON_DEMAND_CLOSE_FOR_YES_BANK);

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );

    }

    public function testBharatQRWithNoDedicatedTerminal()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectExceptionMessage('VPA is required for generating QR');
        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'bharat_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );

    }
}
