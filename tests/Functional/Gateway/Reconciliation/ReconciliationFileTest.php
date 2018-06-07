<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Gateway\Blade\Mock\CardNumber;

use RZP\Reconciliator\HDFC\RefundReconciliate as HdfcRefundRecon;
use RZP\Reconciliator\HDFC\PaymentReconciliate as HDFCPaymentRecon;
use RZP\Reconciliator\Axis\PaymentReconciliate as AxisPaymentRecon;
use RZP\Reconciliator\Atom\PaymentReconciliate as AtomPaymentRecon;
use RZP\Reconciliator\FirstData\PaymentReconciliate as FDPaymentRecon;
use RZP\Reconciliator\Hitachi\RefundReconciliate as HitachiRefundRecon;
use RZP\Reconciliator\BillDesk\RefundReconciliate as BilldeskRefundRecon;
use RZP\Reconciliator\Hitachi\PaymentReconciliate as HitachiPaymentRecon;

use RZP\Reconciliator\VirtualAccYesBank\PaymentReconciliate as VirtualAccYesBank;

class ReconciliationFileTest extends TestCase
{
    use BatchTestTrait;
    use VirtualAccountTrait;
    use DbEntityFetchTrait;

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
        $this->assertBatchStatus();
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

        $this->assertBatchStatus();
    }

    public function testHdfcFssCaptureFailureReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'capture')
            {
                throw new Exception\GatewayRequestException('Timed out');
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

        $this->assertBatchStatus();
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

        $this->assertBatchStatus();
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

        $this->assertBatchStatus();
    }

    public function testVirtualAccYesBankReconFile()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $account = $this->createVirtualAccount();

        $payment = $this->payVirtualAccount($account['id']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(null, $transaction['reconciled_at']);

        $entries[] = $this->overrideVirtualAccYesBankPayment($account, $payment);

        $file = $this->writeToExcelFile($entries, 'virtualAccYesBank', 'files/settlement','Sheet1');

        $this->runForFiles([$file], 'VirtualAccYesBank');

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals($entries[0]['rmtr_account_ifsc'], $bankTransfer['payer_ifsc']);
        $this->assertEquals($entries[0]['rmtr_account_ifsc'], $bankAccount['ifsc']);

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

        $this->assertBatchStatus();
    }

    public function testHdfcFssReconRefundFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $refund1 = $this->getNewRefundEntity(true, false);
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

        $this->assertBatchStatus();
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

        $this->assertEquals($batch['total_count'], 3);
        $this->assertEquals($batch['success_count'], 2);
        $this->assertEquals($batch['failure_count'], 1);

        // One failure, status will be partially_processed
        $this->assertEquals($batch['status'], Status::PARTIALLY_PROCESSED);
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

        $this->assertBatchStatus();
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

        return $this->getDbLastRefund('refund')->toArrayAdmin();
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

        return $facade;
    }

    private function overrideHdfcOnusRefund(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($payment, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HdfcRefundRecon::COLUMN_ARN]       = "'(Onus transaction)";
        $facade[HdfcRefundRecon::COLUMN_REFUND_ID] = $payment['refund_id'];

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
        $facade[AtomPaymentRecon::COLUMN_SERVICE_TAX]         = (float) $facade[AtomPaymentRecon::COLUMN_AMOUNT] * 0.002;
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

        return array_merge($facade, $forceOverride);
    }

    private function overrideHitachiRefund(array $payment, array $forceOverride = [])
    {
        $facade = $this->overrideHitachiPayment($payment, $forceOverride);

        $facade['message_type'] = '0220';
        $facade[HitachiRefundRecon::COLUMN_REFUND_ID] = $payment['refund_id'];

        return $facade;
    }

    protected function runForFiles(array $files, string $gateway, array $forceUpdate = [])
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

        $this->runRequestResponseFlow($testData);
    }

    private function setFileToRequest($filename, $callee)
    {
        $this->testData[$callee]['request']['files']['attachment-1'] = $this->createUploadedFile($filename);
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
    }

    public function testHitachiReconRefundFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $this->payment['card']['number'] = CardNumber::VALID_ENROLL_NUMBER;

        $refund1 = $this->getNewRefundEntity(true, false);

        $gatewayPayment1 = $this->getDbLastEntityToArray('hitachi');

        $this->assertNull($refund1['arn']);

        $entries[] = $this->overrideHitachiRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'hitachi');
        $this->runForFiles([$file], 'Hitachi');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HitachiRefundRecon::COLUMN_ARN], $updatedRefund1['arn']);
    }

    public function testHdfcFssOnusTransactionRecon()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        $refund1 = $this->getNewRefundEntity(true, false);
        $gatewayPayment1 = $this->getDbLastEntityToArray('hdfc');

        $this->assertNull($refund1['arn']);

        $entries[] = $this->overrideHdfcOnusRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund1 = $this->getDbEntityById('refund', $refund1['id'])->toArrayAdmin();

        $this->assertEquals($entries[0][HdfcRefundRecon::COLUMN_SEQUENCE_NUMBER], "'" . $updatedRefund1['arn']);

        $this->assertBatchStatus();
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

        // Asserting status of batch as 'Processed'.
        $this->assertBatchStatus();
    }
}
