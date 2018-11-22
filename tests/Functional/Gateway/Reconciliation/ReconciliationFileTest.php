<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Refund;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Exception\GatewayRequestException;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Gateway\Card\Fss\Entity as CardFssEntity;
use RZP\Tests\Functional\Gateway\Reconciliation\TestTraits;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;
use RZP\Reconciliator\HDFC\SubReconciliator\RefundReconciliate as HdfcRefundRecon;
use RZP\Reconciliator\HDFC\SubReconciliator\PaymentReconciliate as HDFCPaymentRecon;
use RZP\Reconciliator\Axis\SubReconciliator\PaymentReconciliate as AxisPaymentRecon;
use RZP\Reconciliator\Atom\SubReconciliator\PaymentReconciliate as AtomPaymentRecon;
use RZP\Reconciliator\FirstData\SubReconciliator\PaymentReconciliate as FDPaymentRecon;
use RZP\Reconciliator\Hitachi\SubReconciliator\RefundReconciliate as HitachiRefundRecon;
use RZP\Reconciliator\BillDesk\SubReconciliator\RefundReconciliate as BilldeskRefundRecon;
use RZP\Reconciliator\Hitachi\SubReconciliator\PaymentReconciliate as HitachiPaymentRecon;
use RZP\Reconciliator\Freecharge\SubReconciliator\PaymentReconciliate as FreechargePaymentRecon;
use RZP\Reconciliator\VirtualAccYesBank\SubReconciliator\PaymentReconciliate as VirtualAccYesBank;

class ReconciliationFileTest extends TestCase
{
    use BatchTestTrait;
    use VirtualAccountTrait;
    use TestTraits\EbsReconTestTrait;

    protected $payment;
    protected $recurringPayment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/ReconciliationFileTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->recurringPayment = $this->getDefaultRecurringPaymentArray();

        $this->mockTokenex();
    }

    public function testFirstDataReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('first_data');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment1);

        // Non Recurring captured payment
        $payment2 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment2 = $this->getDbLastEntityToArray('first_data');

        $this->assertNull($payment2['reference1']);

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment2);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedPayment2 = $this->getDbEntityById('payment' ,$payment2['id']);

        $this->assertEquals($entries[1][FDPaymentRecon::COLUMN_ARN], $updatedPayment2['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment2['reference2'], $updatedPayment2['reference2']);

        // Overriding Entity to test force update
        unset($entries[1]);
        $entries[0][FDPaymentRecon::COLUMN_ARN]         = 'force_updated_arn';
        $entries[0][FDPaymentRecon::COLUMN_AUTH_CODE]   = 'force_updated_auth_code';

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData', ['payment_arn', 'payment_auth_code']);

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);

        // Check the status of processed batch.
        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * Assert the status of batch processed.
     *
     * @param string $status
     */
    protected function assertBatchStatus(string $status = Status::PROCESSED)
    {
        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], $status);
    }

    public function testHdfcFssReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssCaptureFailureReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new GatewayRequestException('Timed out');
            }

            return $content;
        }, 'hdfc');

        $this->makeRequestAndCatchException(
            function ()
            {
                $this->doAuthAndCapturePayment();
            });

        $gatewayPayment = $this->getDbLastEntityToArray('hdfc');

        $entries[] = $this->overrideHdfcPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment = $this->getDbLastPayment();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment['reference2']);
        $this->assertTrue($updatedPayment['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAxisMigsForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $payment = $this->getNewPaymentEntity(true, false);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status' => 'failed',
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
                'verify_bucket' => 0,
                'verified' => null
            ]);

        $gatewayPayment = $this->getDbLastEntityToArray('axis_migs');

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment, [], 'migs');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement', 'Sale');
        $this->runForFiles([$file], 'Axis', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testAxisMigsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('axis_migs');

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'migs');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);
    }

    public function testCardFssReconCombinedFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        $payment_transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($payment_transaction['reconciled_at']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $paymentData = $this->overrideCardFssPayment($gatewayPayment);

        $paymentHeader = array_keys($paymentData);

        $blank_rows = array_combine($paymentHeader, array_fill(0,count($paymentHeader), null));

        // Refund this newly created payment, and populate refund data
        $this->refundPayment('pay_' . $gatewayPayment['payment_id']);

        $refund_transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($refund_transaction['reconciled_at']);

        $gatewayRefund = $this->getLastEntity('card_fss', true);

        $refundData = $this->overrideCardFssRefund($gatewayRefund, $gatewayPayment);

        $refundHeader = array_keys($refundData);

        //
        // add one payment data row, followed by two blank rows
        //
        $entries[] = $paymentData;
        $entries[] = $blank_rows;
        $entries[] = $blank_rows;

        // add refund header and refund data row
        $entries[] = array_combine($paymentHeader, array_pad($refundHeader, count($paymentHeader), null));
        $entries[] = array_combine($paymentHeader, array_pad($refundData, count($paymentHeader), null));

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement');

        $this->runForFiles([$file], 'CardFssHdfc');

        // ======== verify refund reconciliation ========
        $updatedTransaction = $this->getDbEntityById('transaction', $refund_transaction['id']);

        $updatedRefund = $this->getDbEntityById('refund', $refund_transaction['entity_id']);

        // here 'Reference Tran Id' of refund header is mapped to payment header column 'MSF Amount'
        // in the test excel file, so we are using 'MSF Amount' in next line.
        $this->assertEquals($entries[4]['MSF Amount'], $updatedRefund['reference1']);

        $this->assertNotNull($updatedTransaction['reconciled_at']);

        // ======== verify payment reconciliation =======
        $updatedPayment = $this->getDbEntityById('payment', $response['id']);

        $this->assertEquals($entries[0]['RRN'], $updatedPayment['reference1']);
        $this->assertEquals($entries[0]['Auth/Approval Code'], $updatedPayment['reference2']);

        $updatedTransaction = $this->getDbEntityById('transaction', $payment_transaction['id']);

        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $updatedGatewayPayment = $this->getLastEntity('card_fss', true);

        // Here in next line we wanted to check the refund column 'Aggregator Transaction ID', but
        // this is mapped to 'transaction category' column of payment header in test excel file
        $this->assertEquals($entries[4]['transaction category'], $updatedGatewayPayment['tranid']);

        $this->assertBatchStatus();
    }


    public function testCardFssReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $entries[] = $this->overrideCardFssPayment($gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'payment');

        $this->runForFiles([$file], 'CardFssHdfc');

        $updatedPayment = $this->getDbEntityById('payment', $response['id']);

        $this->assertEquals($entries[0]['RRN'], $updatedPayment['reference1']);
        $this->assertEquals($entries[0]['Auth/Approval Code'], $updatedPayment['reference2']);

        $updatedTransaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $updatedGatewayPayment = $this->getLastEntity('card_fss', true);

        $this->assertEquals($entries[0]['payment gateway transaction id'], $updatedGatewayPayment['tranid']);

        $this->assertBatchStatus();
    }

    public function testCardFssReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getLastEntity('card_fss', true);

        $this->refundPayment('pay_' . $gatewayPayment['payment_id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $gatewayRefund = $this->getLastEntity('card_fss', true);

        $entries[] = $this->overrideCardFssRefund($gatewayRefund, $gatewayPayment);

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'refund', 'xls');

        $this->runForFiles([$file], 'CardFssHdfc');

        $updatedTransaction = $this->getLastEntity('transaction', true);

        $updatedRefund = $this->getLastEntity('refund', true);

        $this->assertEquals($entries[0]['Reference Tran Id'], $updatedRefund['arn']);
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCardFssForceAuthorizePayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_fss_terminal', [
            'gateway_acquirer' => 'barb',
        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment1 =$this->doAuthAndGetPayment($payment);

        $this->fixtures->payment->edit($payment1['id'],
            [
                'status' => 'failed',
                'error_code' => 'BAD_REQUEST_ERROR',
            ]);

        $gatewayPayment = $this->getDbLastEntityToArray('card_fss');

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideCardFssPayment($gatewayPayment, [], 'card_fss');

        $file = $this->writeToExcelFile($entries, 'AllTransaction', 'files/settlement', 'payment');

        $this->runForFiles([$file], 'CardFssHdfc', [], ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testVirtualAccYesBankReconFile()
    {
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        // Intentionally changing the IFSC to validate IFSC is not updated from recon file anymore.
        $payment = $this->payVirtualAccount($account['id'], ['payer_ifsc' => 'PYTM0000001']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PROCESSED);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankTransfer['payer_ifsc']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertNotEquals($entries[0]['rmtr_account_ifsc'], $bankAccount['ifsc_code']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);

        // Beneficiary name should be overridden by the one in the file.
        $this->assertEquals($entries[0]['rmtr_full_name'], $bankAccount['beneficiary_name']);
    }

    public function testVirtualAccYesBankReconFileWithWrongValues()
    {
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        $payment = $this->payVirtualAccount($account['id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        // With wrong amount, batch should be marked partially processed and reconciled at should not be present.
        $entries[0]['amount'] = 1000;

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $utr = $entries[0]['transaction_ref_no'];

        // If UTR is not present, batch should be marked partially processed and reconciled at should not be present.
        $entries[0]['transaction_ref_no'] = strtoupper(random_alphanum_string(22));

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        // With correct values of utr, batch should be marked processed with reconciled at timestamp.
        $entries[0]['transaction_ref_no'] = $utr;

        $entries[0]['amount'] = 100;

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $this->assertBatchStatus(Status::PROCESSED);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);

    }

    public function testAxisCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getDbEntityById('payment' ,$payment1['id']);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment1['reference2'], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssReconRefundFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $refund1 = $this->getNewRefundEntity(true);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($refund1['arn']);

        $entries[] = $this->overrideHdfcRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedRefund1['arn']);

        // Test for for update ARN
        $entries[0][HDFCPaymentRecon::COLUMN_ARN] .= str_random(2);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC', ['refund_arn']);

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedRefund1['arn']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcCybersourceReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->gateway = 'cybersource';

        $refund = $this->getNewRefundEntity(true);
        $gatewayRefund = $this->getDbLastEntityToArray('cybersource');

        $this->assertNull($refund[Refund\Entity::ARN]);

        $entries[] = $this->overrideHdfcCybersourceOnusRefund($gatewayRefund);

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund = $this->getDbEntityById('refund', $refund['id'])->toArrayAdmin();

        $this->assertEquals($gatewayRefund['ref'], $updatedRefund[Refund\Entity::ARN]);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAtomReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_atom_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $gatewayPayment = $this->getLastEntity('atom', true);

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $entries[] = $this->overrideAtomPayment($gatewayPayment);

        $file = $this->writeToCsvFile($entries, 'settlementReport');

        $this->runForFiles([$file], 'Atom');

        $updatedTransaction = $this->getLastEntity('transaction', true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);
        $this->assertNotNull($updatedTransaction['gateway_settled_at']);
        $this->assertNotNull($updatedTransaction['gateway_fee']);
        $this->assertNotNull($updatedTransaction['gateway_service_tax']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    //For success case of Bill desk reconciliation
    public function testBillDeskReconRefundFileFailure()
    {
        $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);
        $gatewayRefund = $this->getLastEntity('billdesk', true);

        $refundEntity = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $transaction = $this->getEntityById('transaction', $refundEntity['transaction_id'], true);

        //Reconciled at should be null
        $this->assertNull($transaction['reconciled_at']);

        $entries[] = $this->overrideBilldeskRefund($gatewayRefund);

        $file = $this->writeToCsvFile($entries, 'billdesk_refund');

        $this->runForFiles([$file], 'BillDesk');

        $updatedRefund1 = $this->getEntityById('refund', $gatewayRefund['refund_id'], true);
        $updatedTransaction = $this->getEntityById('transaction', $updatedRefund1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * Test for success and failure count of a processed batch
     */
    public function testAxisMigsBatchProcessTest(bool $addFeature = true)
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        if ($addFeature === true) $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payments
        $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('axis_migs');

        $this->getNewPaymentEntity(true, false);
        $gatewayPayment2 = $this->getDbLastEntityToArray('axis_migs');

        $this->getNewPaymentEntity(true, false);
        $gatewayPayment3 = $this->getDbLastEntityToArray('axis_migs');

        $entries['Sale'][0] = $this->overrideAxisPayment($gatewayPayment1, [], 'migs');
        $entries['Sale'][1] = $this->overrideAxisPayment($gatewayPayment2, [], 'migs');

        // Passing payment id in refund also for a failure case
        $entries['Refund'][2] = $this->overrideAxisPayment($gatewayPayment3, [], 'migs');

        $file = $this->writeToExcelFile($entries, 'axis_razorpayadd', 'files/settlement', ['Sale', 'Refund'], 'xls');

        $this->runForFiles([$file], 'Axis');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);
        $this->assertEquals(3, $batch['processed_count']);

        // One failure, status will be partially_processed
        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    /**
     * Tests the flow of HDFC recon after Axis recon.
     * Aim is to test flow where recon setting sheet name runs first and then
     * and recon having sheet indices.
     * This tests if selectedSheets gets reset after parsing finished.
     */
    public function testLaravelExcelReaderSheetNamesReset()
    {
        // Run Axis migs test
        $this->testAxisMigsBatchProcessTest();

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        // Recurring authorised payment
        $payment3 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment3 = $this->getDbLastEntityToArray('hdfc');

        $entries2[] = $this->overrideHdfcPayment($gatewayPayment3);

        $file = $this->writeToExcelFile($entries2, 'fss', 'files/settlement', ['Sheet 1'], 'xls');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment2 = $this->getDbEntityById('payment', $payment3['id']);

        $this->assertEquals($entries2[0][HDFCPaymentRecon::COLUMN_ARN], "'" . $updatedPayment2['reference1']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * Tests the flow of Axis recon after First Data recon
     * Aim is to test flow where recon setting sheet indices runs first and then
     * and recon having sheet names.
     * This tests if selectedSheetIndices gets reset to empty after parsing finished.
     */
    public function testLaravelExcelReaderSheetNamesResetReverse()
    {
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('first_data');

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'first_data', 'files/settlement', ['Sheet 1'], 'xls');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment1 = $this->getDbEntityById('payment', $payment1['id']);

        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        // Run Axis migs test
        $this->testAxisMigsBatchProcessTest(false);
    }

    public function testFreechargeReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_freecharge_terminal');

        $gatewayPayment1 = $this->getNewWalletEntity('10000000000000', 'freecharge');

        $wallet = $this->getLastEntity('wallet', true);

        $entries = $this->overrideFreechargePayment($wallet);

        $file = $this->writeToCsvFile($entries, 'freecharge');

        $this->runForFiles([$file], 'Freecharge');

        $updatedPayment1 = $this->getEntityById('payment', $wallet['payment_id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /*
     * Helpers
     */

    private function getNewPaymentEntity(bool $recurring = false, bool $captured = false)
    {
        $payment = $recurring ? $this->recurringPayment : $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getDbLastPayment();

        if ($recurring === true)
        {
            $paymentArray = $paymentEntity->toArrayPublic();
            unset($payment['card']);
            $payment['token'] = $paymentArray['token_id'];

            $this->doS2SRecurringPayment($payment);

            $paymentEntity = $this->getDbLastPayment();
        }

        if ($captured === true)
        {
            $paymentArray = $paymentEntity->toArrayPublic();
            $this->capturePayment($paymentArray['id'], $paymentArray['amount']);

            $paymentEntity = $this->getDbLastPayment();
        }

        return $paymentEntity->toArrayAdmin();
    }

    private function getNewRefundEntity($captured = false)
    {
        $payment = $this->getNewPaymentEntity(false, $captured);

        $this->refundPayment($payment['id']);

        return $this->getDbLastRefund()->toArrayAdmin();
    }

    private function overrideFirstDataPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['first_data'];

        $facade[FDPaymentRecon::COLUMN_CAPS_PAYMENT_ID] = $payment['payment_id'];
        $facade[FDPaymentRecon::COLUMN_PAYMENT_AMOUNT]  = intval($payment['amount'] / 100);
        $facade[FDPaymentRecon::COLUMN_AUTH_CODE]       = random_integer(6);
        $facade[FDPaymentRecon::COLUMN_ARN]             = str_random(24);

        return array_merge($facade, $forceOverride);
    }

    private function overrideHdfcPayment(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->testData['facades']['hdfc'];

        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = $payment['payment_id'];
        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE]  = "'" . random_integer(6);
        $facade[HDFCPaymentRecon::COLUMN_ARN]        = "'" . str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[HDFCPaymentRecon::COLUMN_TERMINAL_NUMBER] = "'89050258";
        }

        return $facade;
    }

    private function overrideAxisPayment(array $payment, array $forceOverride = [], $gateway = 'migs')
    {
        $facade = $this->testData['facades']['axis'];

        $facade[AxisPaymentRecon::COLUMN_PAYMENT_ID[0]] = $payment['payment_id'];
        $facade[AxisPaymentRecon::COLUMN_AUTH_CODE]     = random_integer(6);
        $facade[AxisPaymentRecon::COLUMN_ARN]           = str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[AxisPaymentRecon::COLUMN_MID] = 'RAZORPAYCYBS';
            $facade[AxisPaymentRecon::COLUMN_ORDER_ID] = $payment['ref'];
        }

        return $facade;
    }

    private function overrideFreechargePayment(array $payment, array $forceOverride = [], $gateway = 'freecharge')
    {
        $facade = $this->testData['facades']['freecharge'];

        $facade[0][FreechargePaymentRecon::COLUMN_PAYMENT_ID] = 'pay_' . $payment['payment_id'];;

        return $facade;
    }

    private function overrideVirtualAccYesBankPayment($account, $payment)
    {
        $facade = $this->testData['facades']['virtual_yes_bank'];

        $facade[VirtualAccYesBank::COLUMN_UTR]           = $payment['transaction_id'];
        $facade[VirtualAccYesBank::COLUMN_PAYEE_ACCOUNT] = $account['receivers'][0]['account_number'];

        return $facade;
    }

    private function overrideHdfcRefund(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($payment, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID] = $payment['refund_id'];
        $facade[HdfcRefundRecon::COLUMN_GATEWAY_TRANSACTION_ID] = $payment['gateway_transaction_id'];

        return $facade;
    }

    private function overrideCardFssPayment($gatewayPayment)
    {
        $facade = $this->testData['facades']['card_fss_payment'];

        $facade['payment gateway payment transaction id'] = $gatewayPayment[CardFssEntity::GATEWAY_PAYMENT_ID];
        $facade['transaction amount']                     = number_format($gatewayPayment[CardFssEntity::AMOUNT] / 100, 2, '.', '');
        $facade['merchant track id']                      = $gatewayPayment[CardFssEntity::PAYMENT_ID];
        $facade['RRN']                                    = $gatewayPayment[CardFssEntity::REF];
        $facade['Auth/Approval Code']                     = $gatewayPayment[CardFssEntity::AUTH];
        $facade['payment gateway transaction id']         = $gatewayPayment[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['MSF Amount']                             = $facade['transaction amount'] * 0.009 * (-1);
        $facade['GST On MSF']                             = $facade['MSF Amount'] / 5.6;
        $facade['settlement amount']                      = $facade['transaction amount'] - $facade['MSF Amount'] - $facade['GST On MSF'];
        $facade['Action Code']                            = 'Random String';

        return $facade;
    }

    private function overrideCardFssRefund($gatewayRefund, $gatewayPayment)
    {
        $facade = $this->testData['facades']['card_fss_refund'];

        $facade['Aggregator Transaction ID']       = $gatewayRefund[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['Transaction Date']                = Carbon::createFromTimestamp($gatewayPayment[CardFssEntity::CREATED_AT], Timezone::IST)->format('d/m/Y h:i:s');
        $facade['Action Code']                     = 'Credit';
        $facade['transaction_amount']              = number_format($gatewayPayment[CardFssEntity::AMOUNT] / 100, 2, '.', '');
        $facade['Merchant Track Id']               = $gatewayRefund[CardFssEntity::REFUND_ID];
        $facade['Original Transaction Id']         = $gatewayPayment[CardFssEntity::GATEWAY_TRANSACTION_ID];
        $facade['aggregator_request_sent_time']    = $facade['Transaction Date'];
        $facade['merchant_response_sent_time']     = $facade['Transaction Date'];
        $facade['Reference Tran Id']               = $gatewayRefund[CardFssEntity::REF];
        $facade['Transaction Type']                = 'Random';

        return $facade;
    }

    private function overrideHdfcOnusRefund(array $refund, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($refund, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HdfcRefundRecon::COLUMN_ARN]       = "'(Onus transaction)";
        $facade[HdfcRefundRecon::COLUMN_REFUND_ID] = $refund['refund_id'];
        $facade[HdfcRefundRecon::COLUMN_GATEWAY_TRANSACTION_ID] = $refund['gateway_transaction_id'];

        return $facade;
    }

    private function overrideHdfcCybersourceOnusRefund(array $refund, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->testData['facades']['hdfc_cybersource'];

        $facade[HdfcRefundRecon::COLUMN_REFUND_ID]   = $refund['refund_id'];
        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE]  = "'" . random_integer(6);

        return $facade;
    }

    private function overrideBilldeskRefund(array $refund)
    {
        $facade = $this->testData['facades']['billdesk'];

        $facade[BilldeskRefundRecon::COLUMN_REFUND_ID]  = $refund['RefundId'];
        $facade[BilldeskRefundRecon::COLUMN_PAYMENT_ID] = $refund['payment_id'];

        return $facade;
    }

    private function overrideAtomPayment(array $gatewayPayment)
    {
        $facade = $this->testData['facades']['atom'];

        $facade[AtomPaymentRecon::COLUMN_ATOM_TRANSACTION_ID] = $gatewayPayment['gateway_payment_id'];
        $facade[AtomPaymentRecon::COLUMN_PAYMENT_ID]          = $gatewayPayment['payment_id'];
        $facade[AtomPaymentRecon::COLUMN_BANK_REFERENCE_NO]   = $gatewayPayment['bank_payment_id'];
        $facade[AtomPaymentRecon::COLUMN_AMOUNT]              = $gatewayPayment['amount'] / 100;
        $facade[AtomPaymentRecon::COLUMN_TRANSACTION_CHARGES] = (float) $facade[AtomPaymentRecon::COLUMN_AMOUNT] * 1.1;
        $facade['GST (18%)']                                  = (float) $facade[AtomPaymentRecon::COLUMN_AMOUNT] * 0.002;
        $facade['Bank / Card Name']                           = $gatewayPayment['bank_name'];
        $facade['Net Amount to be Paid']                      = $facade['GST (18%)'] + $facade['Txn Charges'];
        $facade['Settlement Date']                            = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)->format('d-M-Y h:i:s');
        $facade['Txn Date']                                   = Carbon::createFromTimestamp($gatewayPayment['created_at'], Timezone::IST)->format('d-M-Y h:i:s');

        return $facade;
    }

    private function overrideHitachiPayment(array $payment, array $forceOverride = [])
    {
        $facade = $this->testData['facades']['hitachi'];
        $facade[HitachiPaymentRecon::COLUMN_PAYMENT_ID]     = $payment['payment_id'];
        $facade[HitachiPaymentRecon::COLUMN_PAYMENT_AMOUNT] = intval($payment['amount'] / 100);
        $facade[HitachiPaymentRecon::COLUMN_AUTH_CODE]      = $payment['pAuthID'];
        $facade[HitachiPaymentRecon::COLUMN_ARN]            = str_random(24);
        $facade[HitachiPaymentRecon::COLUMN_CURRENCY_CODE]  = '356';

        return array_merge($facade, $forceOverride);
    }

    private function overrideHitachiRefund(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideHitachiPayment($payment, $forceOverride);

        $facade['message_type'] = '0220';
        $facade[HitachiRefundRecon::COLUMN_REFUND_ID] = $payment['refund_id'];

        return $facade;
    }

    private function overrideOlamoneyPayment(array $payment)
    {
        $facade = $this->testData['facades']['olamoney'];

        $facade['Unique Bill Id'] = $payment['payment_id'];

        return $facade;
    }

    private function overrideMobikwikPayment(array $payment)
    {
        $facade = $this->testData['facades']['mobikwik'];

        $paymentId = str_replace("pay_", '', $payment['id']);

        $facade['OrderID'] = '"""'. $paymentId;

        return $facade;
    }

    protected function runForFiles(array $files, string $gateway, array $forceUpdate = [],  array $forceAuthorizePayments = [])
    {
        $this->ba->appAuth();

        $testData = $this->testData['reconciliate'];

        $testData['request']['content']['gateway'] = $gateway;
        $testData['request']['content']['attachment-count'] = count($files);

        foreach ($files as $index => $file)
        {
            $testData['request']['files']['attachment-' . ($index + 1)] = $this->createUploadedFile($file);
        }

        if (empty($forceUpdate) === false)
        {
            foreach ($forceUpdate as $forceUpdateColumn)
            {
                $testData['request']['content']['force_update'][] = $forceUpdateColumn;
            }
        }

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $testData['request']['content'][Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        $this->runRequestResponseFlow($testData);
    }

    public function createUploadedFile(string $url): UploadedFile
    {
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            basename($url),
            $mime,
            filesize($url),
            null,
            true);
    }

    private function getNewWalletEntity($merchantId, $wallet)
    {
        $this->fixtures->merchant->enableWallet($merchantId, $wallet);

        $payment = $this->getDefaultWalletPaymentArray($wallet);

        $capturePayment = $this->doAuthAndCapturePayment($payment);

        $gatewayPayment = $this->getDbLastEntityPublic('payment');

        return $gatewayPayment;
    }

    public function testHitachiReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $payment1 = $this->getNewPaymentEntity(false,true);

        $gatewayPayment1 = $this->getLastEntity('hitachi', true);

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideHitachiPayment($gatewayPayment1, ['auth_id' => $payment1['reference2']]);

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $updatedPayment1 = $this->getEntityById('payment', $payment1['id'], true);

        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HitachiPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiBharatQrRecon()
    {
        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create('terminal:bharat_qr_terminal');

        $this->createVirtualAccount([], true, null, true);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->payViaBharatQr($qrCode['id'], 'hitachi');

        $payment = $this->getDbLastEntity('payment');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($transaction['reconciled_at']);

        $entries[] = $this->testData['facades']['hitachi'];

        $entries[0][HitachiPaymentRecon::COLUMN_TERMINAL_NUMBER] = '38R00450';

        $entries[0][HitachiPaymentRecon::COLUMN_PAYMENT_AMOUNT] = $payment['amount']/100;

        $file = $this->writeToExcelFile($entries, 'hitachi');

        $this->runForFiles([$file], 'Hitachi');

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHitachiReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $refund1 = $this->getNewRefundEntity(true);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hitachi');

        $this->assertNull($refund1['arn']);

        $entries[] = $this->overrideHitachiRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'hitachi');
        $this->runForFiles([$file], 'Hitachi');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HitachiRefundRecon::COLUMN_ARN], $updatedRefund1['arn']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcFssOnusTransactionRecon()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $refund = $this->getNewRefundEntity(true);
        $gatewayRefund = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($refund[Refund\Entity::ARN]);

        $entries[] = $this->overrideHdfcOnusRefund($gatewayRefund);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund = $this->getDbEntityById('refund', $refund['id'])->toArrayAdmin();

        $updatedGatewayRefund = $this->getDbLastEntityToArray('hdfc');

        $this->assertEquals($gatewayRefund['ref'], $updatedRefund[Refund\Entity::ARN]);

        $this->assertEquals($updatedRefund[Refund\Entity::ARN], $updatedGatewayRefund['arn_no']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    /**
     * Test for failed reconciliation batch. Retrying will mark it processed.
     */
    public function testFailedReconBatchRetry()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->getNewPaymentEntity(false, true);

        $gatewayPayment1 = $this->getDbLastEntityToArray('first_data');

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment1);

        // Creating batch with failed status.
        $this->fixtures->create('batch:recon_with_failed_status', $entries);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as failed.
        $this->assertBatchStatus(Status::FAILED);

        // Retrying failed batch.
        $this->retryFailedBatch('batch_' . $batch['id']);

        $batch = $this->getDbLastEntityToArray('batch');

        // Asserting status of batch as 'Processed' and counts.
        $this->assertEquals(Status::PROCESSED, $batch['status']);
        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(1, $batch['processed_count']);
    }

    public function testOlamoneyReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_olamoney_terminal');

        $gatewayPayment1 = $this->getNewWalletEntity('10000000000000', 'olamoney');

        $wallet = $this->getLastEntity('wallet', true);

        $entries[] = $this->overrideOlamoneyPayment($wallet);

        $file = $this->writeToCsvFile($entries, 'olamoney');

        $this->runForFiles([$file], 'Olamoney');

        $updatedPayment1 = $this->getEntityById('payment', $wallet['payment_id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testMobikwikReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_mobikwik_terminal');

        $gatewayPayment1 = $this->getNewWalletEntity('10000000000000', 'mobikwik');

        $entries[] = $this->overrideMobikwikPayment($gatewayPayment1);

        $file = $this->writeToCsvFile($entries, 'mobikwik');

        $this->runForFiles([$file], 'Mobikwik');

        $updatedPayment1 = $this->getEntityById('payment', $gatewayPayment1['id'], true);

        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedTransaction = $this->getEntityById('transaction', $updatedPayment1['transaction_id'], true);

        //Reconciled at should not be null
        $this->assertNotNull($updatedTransaction['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFssBobPaymentReconFile()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getNewPaymentEntity(false, true);

        $this->assertNull($payment['reference1']);

        $gatewayPayment1 = $this->getDbLastEntityToArray('card_fss');

        $this->fixtures->edit('card_fss', $gatewayPayment1['id'], ['ref' => null]);

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement'=>' To Settlement' , '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobRecon($gatewayPayment1, $gatewayPayment1['payment_id']);

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $response = $this->runForFiles([$file], 'CardFssBob');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['settled_at']);
        $this->assertNotNull($transactionEntity['gateway_fee']);
        $this->assertNotNull($transactionEntity['gateway_service_tax']);

        //Test that gateway entity value is updated from response
        $updatedGatewayEnity = $this->getDbLastEntityToArray('card_fss');

        $this->assertEquals('30-07-2018', $updatedGatewayEnity['postdate']);

        //Test We update payment reference2 from recon
        $paymentEnity = $this->getDbLastEntity('payment');
        $this->assertNotNull($paymentEnity['reference2']);
        $this->assertNotNull($paymentEnity['reference1']);

        $gatewayFee = Helper::getIntegerFormattedAmount(abs($entries[0]['MSF Amount']));
        $gst = Helper::getIntegerFormattedAmount(abs($entries[0]['GST']));

        // Test that the gateway fee and tax sum is as expected
        $this->assertEquals( $gatewayFee + $gst, $transactionEntity->getGatewayFee());

        $this->assertEquals($gst, $transactionEntity->getGatewayServiceTax());

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testFssBobRefundRecon()
    {
        $this->fixtures->create('terminal:shared_fss_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();

         $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntity('payment');

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getDbLastEntity('refund');

        $gatewayRefund = $this->getDbLastEntityToArray('card_fss');

        $headers[] = ['Merchant Setttlment' => '  '];

        $headers[] = ['From Settlement'=>' To Settlement' , '31-08-2018' => '31-08-2018'];

        $file = $this->writeToCsvFile($headers, 'MerchantSettlementTransactionListing');

        $entries[] = $this->overrideFssBobRecon($gatewayRefund, $refund['id'], 'Refund');

        $file = $this->writeToCsvFile($entries, 'MerchantSettlementTransactionListing', $file);

        $this->runForFiles([$file], 'CardFssBob');

        $refund = $this->getDbLastEntity('refund');

        $this->assertEquals('175309', $refund['reference1']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);
        $this->assertNotNull($transactionEntity['settled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testAmexPaymentRecon()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $payment = $this->getNewPaymentEntity(false, true);

        $gatewayPayment = $this->getDbLastEntityToArray('amex');

        $paymentData = $this->overrideAmexPayment($gatewayPayment);

        // amex recon file contains 20 lines of extra data before the actual payment

        // adding 20 rows with data before the actual row that has to be processed
        for ($row_index = 1; $row_index < 20; $row_index++)
        {
            $entries[] = [];
        }

        $entries[] = array_keys($paymentData);

        $entries[] = $paymentData;

        $file = $this->writeToExcelFile($entries, 'Submission_details10032018_023644' , 'files/settlement',
                                        ['Sheet 1'], 'xls');

        $response = $this->runForFiles([$file], 'Amex');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['settled_at']);

        $this->assertBatchStatus(Status::PROCESSED);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(1, $batch['total_count']);
    }

    public function testAmexPaymentReconFailureCount()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $payment = $this->getNewPaymentEntity(false, true);

        $gatewayPayment = $this->getDbLastEntityToArray('amex');

        $paymentData = $this->overrideAmexPayment($gatewayPayment);

        //changing payment amount to fail the recon

        $paymentData['Charge amount'] = '1.00';

        // amex recon file contains 20 lines of extra data before the actual payment

        // adding 20 rows with data before the actual row that has to be processed
        for ($row_index = 1; $row_index < 20; $row_index++)

        {
            $entries[] = [];
        }

        $entries[] = array_keys($paymentData);

        $entries[] = $paymentData;

        $file = $this->writeToExcelFile($entries, 'Submission_details10032018_023644' , 'files/settlement',
            ['Sheet 1'], 'xls');

        $response = $this->runForFiles([$file], 'Amex');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);

        $this->assertEquals(1, $batch['failure_count']);
    }

    private function overrideFssBobRecon(array $gatewayPayment, string $entityId, $transactionType ='Purchase')
    {
        $facade = $this->testData['facades']['testFssBobRecon'];

        $facade['Transaction Amount'] = number_format($gatewayPayment['amount'] / 100, 2);

        $facade['Settlement Amount'] = $facade['Transaction Amount']/100;

        $facade['Auth/Approval Code'] = $gatewayPayment['auth'];

        $facade['Merchant Track ID'] = "''". $entityId;

        $facade['Transaction Type'] =  $transactionType;

        return $facade;
    }

    private function overrideAmexPayment(array $gatewayPayment)
    {
        $facade = $this->testData['facades']['testAmexPaymentRecon'][0];

        $facade['Charge reference number'] = $gatewayPayment['vpc_ShopTransactionNo'];

        $facade['Reference number'] = $gatewayPayment['vpc_ShopTransactionNo'];

        $facade['Rental agreement number'] = $gatewayPayment['vpc_ShopTransactionNo'];

        $facade['Merchant Account Number'] = 'razorpay amex';

        return $facade;
    }
}
