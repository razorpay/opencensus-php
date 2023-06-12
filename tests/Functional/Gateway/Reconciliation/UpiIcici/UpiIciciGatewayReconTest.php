<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiIcici;

use Carbon\Carbon;
use RZP\Exception;
use Illuminate\Http\UploadedFile;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Models\Payment\PaymentMeta\MismatchAmountReason;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Reconciliator\UpiIcici\SubReconciliator\PaymentReconciliate;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiIciciGatewayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;
    use VirtualAccountTrait;
    use NonVirtualAccountQrCodeTrait;

    /**
     * @var array
     */
    private $payment;

    private $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiIciciReconTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->gateway = Payment\Gateway::UPI_ICICI;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);
    }

    public function testReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->assertEquals('processed', $refund['status']);

            $this->assertEquals($refund['reference1'], '733817298334');

            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            // We hardcode 04-12-2017 05:09 PM in the upi icici reconciliator class
            $this->assertEquals(1512387540, $transaction['gateway_settled_at']);

            $upi = $this->getDbLastEntity('upi');

            $this->assertEquals($upi['npci_reference_id'], '733817298334');
        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testReconRefundAmountValidationFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting random column value
                    $content['Refund Amount'] = 13476475;
                }
            },
            $this->gateway,
            [
                'type' => 'refund'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $refunds = $this->getEntities('refund', [], true);

        // We do not reconcile refunds that fail the amount assertion step
        foreach ($refunds['items'] as $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNull($transaction['reconciled_at']);

            // We hardcode 04-12-2017 05:09 PM in the upi icici reconciliator class
            $this->assertEquals(1512387540, $transaction['gateway_settled_at']);
        }
    }

    public function testReconRefundNotFoundInDb()
    {
        $this->markTestSkipped();

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        list($refunds, $payments) = $this->makeUpiIciciRefundsSince($createdAt);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                if ($action === 'col_icici_recon')
                {
                    // Setting new unique id as column value
                    $content[4] = UniqueIdEntity::generateUniqueId();
                }
            },
            $this->gateway,
            [
                'type' => 'refund'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'refund']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->updatePaymentStatusToCaptured($payments);

        //
        // The refund id's sent across in the recon file will not be found in the DB.
        // This will force refund recon to create new refunds to reconcile.
        //
        $this->reconcile($uploadedFile, 'UpiIcici');

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $index => $refund)
        {
            $transactionId = $refund['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            //
            // First 3 refunds were not reconciled as they were not found in the DB.
            // Recon created 3 new refunds and reconciled them instead.
            //
            $assertFunction = ($index < 3) ? 'assertNotNull' : 'assertNull';

            $this->$assertFunction($transaction['reconciled_at']);

            // TODO: Handle assertion for persisted gateway settled at value
        }
    }

    // Below are the payment recon test cases
    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $upi = $this->getDbLastEntity('upi');

            $this->assertEquals($upi['npci_reference_id'], '734122607521');

            $this->assertNotNull($upi->getReconciledAt());

            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);

        }

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testPaymentIdAbsentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['banktranid'] = "";
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testUpiIciciForceAuthorizePayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'authorized_at'         =>  null,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($upi['npci_reference_id'], '734122607521');

        $this->assertNotNull($upi->getReconciledAt());

        $this->assertNotNull($payment['reference16']);

        $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);
    }

    public function testUpiIciciRrnMismatchPayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status' => 'failed',
                'authorized_at' => null,
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity, '123456789');

        $file = $this->writeToExcelFile($entries, 'mis_report', 'files/settlement', 'Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertNotNull($upi['npci_reference_id']);

        $this->assertEquals($upi['npci_reference_id'], '000123456789');

        $this->assertNotNull($payment['reference16']);

        $this->assertNotNull($upi->getReconciledAt());

        $this->assertEquals($upi['npci_reference_id'], $payment['reference16']);
    }

    public function testUpiIciciUnexpectedBqrPaymentCreateViaRecon()
    {
        $reconRow = $this->testData['upiIcici'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures('bharat_qr');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->t2 = $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $va = $this->createVirtualAccount(['receiver_types'  => 'qr_code']);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertSame($va['receivers'][0]['reference'], $qrCode->getReference());

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference();
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastEntity('payment');

        $this->assertArraySubset([
            'method'                    => 'upi',
            'status'                    => 'captured',
            'receiver_id'               => $qrCode->getId(),
            'receiver_type'             => 'qr_code',
            'auto_captured'             => true,
            'gateway'                   => 'upi_icici',
            'gateway_captured'          => true,
            'vpa'                       => $reconRow['payerVA'],
            'reference16'               => $reconRow['bankTranID'],
        ], $payment->toArray(), true);

        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);

        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'npci_reference_id'     => $reconRow['bankTranID'],
            'merchant_reference'    => $qrCode->getId(),
        ], $upi->toArray());
    }

    public function testQrPaymentRecon()
    {
        $reconRow = $this->testData['upiIcici'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = $reconRow['bankTranID'];
        $request['content']['merchantTranId'] = $qrCode->getReference() . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');
        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);
        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'npci_reference_id'     => $reconRow['bankTranID'],
            'merchant_reference'    => $qrCode->getId() . 'qrv2',
        ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testQrPaymentReconWithDecimalAmount()
    {
        //while converting decimal values to paise, the value gets changed happens
        //only with few numbers like (265.84, 2159.2, 1175.84)
        //to validate that using this decimal value '1175.84'

        $reconRow = $this->testData['upiIcici'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = $reconRow['bankTranID'];
        $request['content']['merchantTranId'] = $qrCode->getReference() . 'qrv2';
        $request['content']['PayerAmount'] = '1175.84';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';
        $reconRow['amount']             = '1175.84';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');
        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);
        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'npci_reference_id'     => $reconRow['bankTranID'],
            'merchant_reference'    => $qrCode->getId() . 'qrv2',
        ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testQrPaymentReconWithRemarks()
    {
        $reconRow = $this->testData['upiIcici'];

        $reconRow['Remark'] = 'somerandomremarks';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = $reconRow['bankTranID'];
        $request['content']['merchantTranId'] = $qrCode->getReference() . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');
        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);
        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
                                     'payment_id'            => $payment->getId(),
                                     'npci_reference_id'     => $reconRow['bankTranID'],
                                     'merchant_reference'    => $qrCode->getId() . 'qrv2',
                                 ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $this->assertEquals($qrPayment['notes'],'somerandomremarks');
    }

    public function testQrPaymentReconWithExistingRemarks()
    {
        $reconRow = $this->testData['upiIcici'];

        $reconRow['Remark'] = 'somerandomremarks';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = $reconRow['bankTranID'];
        $request['content']['merchantTranId'] = $qrCode->getReference() . 'qrv2';
        $request['content']['Remark'] = 'notsomerandomremark';
        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');
        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);
        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
                                     'payment_id'            => $payment->getId(),
                                     'npci_reference_id'     => $reconRow['bankTranID'],
                                     'merchant_reference'    => $qrCode->getId() . 'qrv2',
                                 ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $this->assertEquals($qrPayment['notes'],'notsomerandomremark');
    }

    public function testCreateQrPaymentViaRecon()
    {
        $reconRow = $this->testData['upiIcici'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';
        $entries[] = $reconRow;
        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastEntity('payment');
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $this->assertEquals($payment['id'], $qrPayment['payment_id']);

        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);

        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');
        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'npci_reference_id'     => $reconRow['bankTranID'],
            'merchant_reference'    => $qrCode->getId() . 'qrv2',
        ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCreateQrPaymentViaReconWithRemarks()
    {
        $reconRow = $this->testData['upiIcici'];

        $reconRow['Remark'] = 'somerandomremarks';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->createQrCode(['type' => 'upi_qr']);
        $qrCode = $this->getDbLastEntity('qr_code');

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'RAZORPAY BHARAT QR';
        $entries[] = $reconRow;
        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastEntity('payment');
        $qrPayment = $this->getDbLastEntity('qr_payment');
        $this->assertEquals($payment['id'], $qrPayment['payment_id']);
        $this->assertEquals($qrPayment['notes'],'somerandomremarks');

        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);

        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');
        $this->assertArraySubset([
            'payment_id'            => $payment->getId(),
            'npci_reference_id'     => $reconRow['bankTranID'],
            'merchant_reference'    => $qrCode->getId() . 'qrv2',
        ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testQrPaymentReconForDedicatedTerminalPayment()
    {
        $reconRow = $this->testData['upiIcici'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->addFeatures(['qr_codes']);
        $this->t2 = $this->fixtures->create('terminal:dedicated_upi_icici_terminal', ['merchant_id' => '10000000000000']);

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

        $this->createQrCode(['usage'          => 'single_use',
                             'type'           => 'upi_qr',
                             'fixed_amount'   => true,
                             'payment_amount' => 10000
                            ]);

        $qrCode = $this->getDbLastEntity('qr_code');

        $request = $this->testData['testProcessIciciQrPayment'];
        $request['content']['BankRRN'] = $reconRow['bankTranID'];
        $request['content']['merchantTranId'] = $qrCode->getReference() . 'qrv2';
        $request['content']['merchantId'] = $this->t2->getGatewayMerchantId();

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);

        $reconRow['merchantID']         = $this->t2->getGatewayMerchantId();
        $reconRow['merchantTranID']     = $qrCode->getReference() . 'qrv2';
        $reconRow['subMerchantName']    = 'DEMO MERCHANT';

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');
        $uploadedFile = $this->createUploadedFile($file);
        $this->reconcile($uploadedFile, 'UpiIcici');
        $transactionId = $payment['transaction_id'];
        $transaction = $this->getDbEntityById('transaction', $transactionId);
        $this->assertNotNull($transaction['reconciled_at']);

        // Assert UPI entity
        $upi = $this->getDbLastEntity('upi');

        $this->assertArraySubset([
                                     'payment_id'            => $payment->getId(),
                                     'npci_reference_id'     => $reconRow['bankTranID'],
                                     'merchant_reference'    => $qrCode->getId() . 'qrv2',
                                 ], $upi->toArray());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    protected function enableRazorXTreatmentForQrDedicatedTerminal()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::DEDICATED_TERMINAL_QR_CODE))
                   {
                       return 'on';
                   }
                   return 'control';
               });
    }

    protected function overrideUpiIciciPayment(array $upiEntity, $gatewayPaymentId = null)
    {
        $facade                   = $this->testData['upiIcici'];

        $facade['amount']         = $upiEntity['amount'] / 100;

        $facade['bankTranID']     = $gatewayPaymentId ?? $upiEntity['npci_reference_id'];

        $facade['merchantTranID'] = $upiEntity['payment_id'];

        return $facade;
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['status'] = "failed";
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testReconAmountValidationFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';
        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['amount'] = 1840913;
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // This one will not get reconciled, this is the easier way
        // we can create a recon file with multiple credits
        $payments['000000000003'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000003', 1)[0];

        // First lets create 2 payments in the system
        $payments['000000000002'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000002', 1)[0];
        $payments['000000000001'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        // Order of payments is important as the last one created will reconcile first
        $this->reconcileWithMock(
            function(& $content) use ($payments)
            {
                $actualRrn = array_search($content['merchantTranID'], $payments);

                // Correct the Gateway Merchant Id in file
                $content['merchantid'] = $this->sharedTerminal->getGatewayMerchantId();

                // Correct the RRN in the recon row
                $content['bankTranID'] = $actualRrn;

                // For the third row, change the payment id so it will become multiple rrn case
                if ($actualRrn === '000000000003')
                {
                    $content['merchantTranID']  = $payments['000000000001'];
                    $content['amount']          = '500.01';
                }
            });

        // This is the payment which must have been created
        $payment = $this->getDbLastPayment();

        // Now we can make sure that this is the new payment created
        $this->assertFalse(in_array($payment->getId(), $payments, true));

        $this->assertArraySubset([
            Payment\Entity::MERCHANT_ID => Account::DEMO_ACCOUNT,
            Payment\Entity::AMOUNT      => 50001,
            Payment\Entity::VPA         => '9619218329@ybl',
            Payment\Entity::STATUS      => Payment\Status::AUTHORIZED,
        ], $payment->toArray(), true);

        $this->assertSame('000000000003', $payment->getReference16());

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            'payment_id'                => $payment->getId(),
            'type'                      => 'pay',
            'action'                    => 'authorize',
            'gateway_merchant_id'       => $this->sharedTerminal->gateway_merchant_id,
            'npci_reference_id'         => '000000000003',
            'gateway_payment_id'        => '000000000003',
            'received'                  => true,
            'status_code'               => 'SUCCESS',
            'vpa'                       => '9619218329@ybl',
            'acquirer'                  => 'icici',
            'provider'                  => 'ybl',
        ], $upi->toArray(), true);

        $this->assertNotEmpty($payment->transaction->getReconciledAt());

        $reconciled1 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000001']]);
        $this->assertNotEmpty($reconciled1->getReconciledAt());

        $reconciled2 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000002']]);
        $this->assertNotEmpty($reconciled2->getReconciledAt());

        // The payment with RRN
        $reconciled3 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000003']]);
        $this->assertNull($reconciled3->getReconciledAt());
    }

    public function testDifferentRrn()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // We will create only one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        // Order of payments is important as the last one created will reconcile first
        $this->reconcileWithMock(
            function(& $content)
            {
                // Change the RRN from what is saved in database
                $content['bankTranID'] = '000000000002';
            });

        $payment = $this->getDbLastPayment();

        // there must only be one payment
        $this->assertSame($paymentId, $payment->getId());
        // RRN must be changed to the updated one
        $this->assertSame('000000000002', $payment->getReference16());

        $this->assertNotEmpty($payment->transaction->getReconciledAt());

        $upi = $this->getDbLastUpi();
        $this->assertNotEmpty($upi->getReconciledAt());

        // Before marking the payment reconciled, we need to update the correct RRN to entity
        $this->assertSame('000000000002', $upi->getNpciReferenceId());
    }

    public function testAllowedAmountMismatch()
    {
        $createdAt  = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();
        $diff       = 0;
        $amount     = $this->payment['amount'];

        $override = [
            'status'                => 'failed',
            'authorized_at'         => null,
            'created_at'            => $createdAt,
            'internal_error_code'   => 'SERVER_ERROR_AMOUNT_TAMPERED',
            'error_code'            => 'SERVER_ERROR',
            'error_description'     => 'We are facing some trouble completing your request at the moment. Please try again shortly.'
        ];
        // We will create two payment IDs where no payments will be allowed now.
        $payments[$this->doUpiIciciPayment($override)] = [amount_format_IN($amount - $diff - 1),  0];
        $payments[$this->doUpiIciciPayment($override)] = [amount_format_IN($amount + $diff + 1),  0];

        $dsTerminal = $this->fixtures->create('terminal:direct_settlement_upi_icici_terminal');
        $dsOverride = array_merge([
            'terminal_id'   => $dsTerminal->getId(),
        ], $override);

        // For DS merchants not even single paisa is allowed for amount mismatch
        $payments[$this->doUpiIciciPayment($dsOverride)] = [amount_format_IN($amount - 2),       0];

        // No we will change the amount in recon
        $this->reconcileWithMock(
            function(& $content) use ($payments)
            {
                $content['amount'] = $payments[$content['merchantTranID']][0];
            });

        $batch = $this->getDbLastEntity('batch');

        $this->assertArraySubset([
            Batch\Entity::TOTAL_COUNT       => 3,
            Batch\Entity::PROCESSED_COUNT   => 3,
            Batch\Entity::SUCCESS_COUNT     => 0,
            Batch\Entity::FAILURE_COUNT     => 3,
            Batch\Entity::FAILURE_REASON    => '{"AMOUNT_MISMATCH":3}',
        ], $batch->toArray(), true);

        foreach ($payments as $paymentId => $values)
        {
            $payment = $this->getDbEntityById('payment', $paymentId);

            if ($values[1] === 1)
            {
                $this->assertArraySubset([
                    'status'                => 'authorized',
                    'amount_authorized'     => 50000,
                ], $payment->toArray(), true);

                $transaction = $this->getDbEntity('transaction', ['entity_id' => $paymentId]);
                $this->assertNotEmpty($transaction->getReconciledAt());

                $paymentMeta = $payment->paymentMeta;

                $this->assertSame($paymentId, $paymentMeta->getPaymentId());

                $baseAmount = $payment->getBaseAmount();
                $gatewayAmount = $paymentMeta->getGatewayAmount();
                $expectedAmount = abs($baseAmount - $gatewayAmount);
                $mismatchAmountReason = $paymentMeta->getMismatchAmountReason();

                $this->assertEquals($expectedAmount, $paymentMeta->getMismatchAmount());

                if ($baseAmount > $gatewayAmount)
                {
                    $this->assertSame($mismatchAmountReason, MismatchAmountReason::CREDIT_DEFICIT);
                }
                else
                {
                    $this->assertSame($mismatchAmountReason, MismatchAmountReason::CREDIT_SURPLUS);
                }
            }
            else
            {
                $this->assertArraySubset($override, $payment->toArray(), true);
            }
        }
    }

    public function testInvalidUpiIciciUpdateReconData()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1)[0];

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $paymentId;

        unset($content['upi']['npci_reference_id']);
        unset($content['reconciled_at']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'    => '/reconciliate/data',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testUpiIciciUpdateAlreadyReconciled()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1)[0];

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $paymentId;

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $this->assertFalse($response['success']);

        $this->assertEquals('ALREADY_RECONCILED', $response['error']['code']);
    }

    public function testUpiIciciUpdatePostReconData()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1)[0];

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $paymentId;

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $currentTransactionEntity = $this->getDbLastEntity('transaction');

        // The reconciled_at is not persisted yet
        $this->assertEmpty($currentTransactionEntity['reconciled_at']);

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $upiEntity = $this->getDbLastEntity('upi');

        // Assert empty reconciledAt in gateway entity
        $this->assertEmpty($upiEntity['reconciled_at']);

        $this->assertEmpty($upiEntity['gateway_settled_at']);

        $this->assertEquals($content['upi']['npci_reference_id'], $upiEntity['npci_reference_id']);

        $this->assertEquals($content['upi']['gateway_payment_id'], $upiEntity['gateway_payment_id']);

        $this->assertNotEmpty($upiEntity['vpa']);

        $updatedTransactionEntity = $this->getDbLastEntity('transaction');

        $this->assertEquals($content['reconciled_at'],$updatedTransactionEntity['reconciled_at']);

        $this->assertNotEmpty($updatedTransactionEntity['reconciled_at']);

        $this->assertEmpty($updatedTransactionEntity['gateway_settled_at']);

        $this->assertTrue($response['success']);
    }

    private function assertFailedPaymentRecon()
    {
        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getLastEntity('payment', true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);
    }

    private function updatePaymentStatusToCaptured(array $payments)
    {
        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment,
                [
                    'status'               => 'captured',
                    'amount_refunded'      => 0,
                    'base_amount_refunded' => 0,
                    'refund_status'        => null
                ]);
        }
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "application/octet-stream";

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    private function makeUpiIciciPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();

            $upiEntity = $this->getDbLastEntity('upi');

            $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => $rrn, 'gateway' => 'upi_icici','vpa' => 'test@icici', 'bank' => 'icici', 'provider' => 'icici']);
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function makeUpiIciciRefundsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();
        }

        $refunds = [];

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);

            $refund = $this->fixtures->create(
                        'refund',
                        [
                            'payment_id'  => $payment,
                            'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                            'amount'      => $this->payment['amount'],
                            'base_amount' => $this->payment['amount'],
                            'gateway'     => 'upi_icici',
                        ]);

            $transaction = $this->fixtures->create(
                                'transaction',
                                [
                                    'entity_id' => $refund->getId(),
                                    'merchant_id' => '10000000000000'
                                ]);

            $this->fixtures->edit(
                'refund',
                $refund->getId(),
                [
                    'created_at' => $createdAt,
                    'transaction_id' => $transaction->getId()
                ]);

            $this->fixtures->create(
                'upi',
                [
                    'payment_id' => $payment,
                    'refund_id'  => PublicEntity::stripDefaultSign($refund['id']),
                    'action'     => Payment\Action::REFUND
                ]);

            $refunds[] = $refund['id'];
        }

        return [$refunds, $payments];
    }

    private function doUpiIciciPayment(array $override = [])
    {
        $status = $override['status'] ?? 'captured';

        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => $status,
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
        ];

        $attributes = array_merge($attributes, $override);

        $payment = $this->fixtures->create('payment', $attributes);

        if ($status !== 'failed')
        {
            $transaction = $this->fixtures->create('transaction', [
                'entity_id' => $payment->getId(),
                'merchant_id' => '10000000000000'
            ]);

            $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);
        }

        $this->fixtures->create('upi', [
            'payment_id'    => $payment->getId(),
            'gateway'       => $this->gateway,
            'amount'        => $payment->getAmount(),
        ]);

        return $payment->getId();
    }

    public function testUpiIciciUpiTransferPaymentCreateViaRecon()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->t2 = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $va = $this->createVirtualAccount([], false, null, null, true, 'vaVpaIcici');
        $vpa = $this->getDbEntityById('vpa', $va['receivers'][0]['id']);

        $reconRow = $this->testData['upiIcici'];

        $reconRow['merchantID']     = '403343';
        $reconRow['merchantTranID'] = explode('.', $vpa->getUsername())[1];

        $entries[] = $reconRow;

        $file = $this->writeToExcelFile($entries, 'mis_report', 'files/settlement', 'Recon MIS');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastEntity('payment');

        $this->assertArraySubset([
                                     'method'           => 'upi',
                                     'status'           => 'captured',
                                     'receiver_id'      => $vpa->getId(),
                                     'receiver_type'    => 'vpa',
                                     'auto_captured'    => true,
                                     'gateway'          => 'upi_icici',
                                     'gateway_captured' => true,
                                     'vpa'              => $reconRow['payerVA'],
                                     'reference16'      => $reconRow['bankTranID'],
                                 ], $payment->toArray(), true);

        $transactionId = $payment['transaction_id'];
        $transaction   = $this->getDbEntityById('transaction', $transactionId);

        $this->assertNotNull($transaction['reconciled_at']);
    }

    protected function enableRazorXTreatmentForRazorXVpaIcici()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === 'virtual_vpa_icici')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    private function reconcileWithMock(callable $closure = null)
    {
        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null) use ($closure)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    if (is_callable($closure) === true)
                    {
                        $closure($content);
                    }
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');
    }

    private function makeUpdatePostReconRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
