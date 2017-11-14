<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

use RZP\Reconciliator\FirstData\PaymentReconciliate as FDPaymentRecon;
use RZP\Reconciliator\HDFC\PaymentReconciliate as HDFCPaymentRecon;
use RZP\Reconciliator\Axis\PaymentReconciliate as AxisPaymentRecon;

class ReconciliationFileTest extends TestCase
{
    use FileHandlerTrait;
    use PaymentTrait;

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
        $gatewayPayment1 = $this->getLastEntity('first_data', true);

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment1);

        // Non Recurring captured payment
        $payment2 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment2 = $this->getLastEntity('first_data', true);

        $this->assertNull($payment2['reference1']);

        $entries[] = $this->overrideFirstDataPayment($gatewayPayment2);

        $file = $this->writeToExcelFile($entries, 'first_data');
        $this->runForFiles([$file], 'FirstData');

        $updatedPayment1 = $this->getEntityById('payment' ,$payment1['id'], true);

        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][FDPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);

        $updatedPayment2 = $this->getEntityById('payment' ,$payment2['id'], true);

        $this->assertEquals($entries[1][FDPaymentRecon::COLUMN_ARN], $updatedPayment2['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment2['reference2'], $updatedPayment2['reference2']);
    }

    public function testHdfcFssReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getLastEntity('hdfc', true);

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getEntityById('payment' ,$payment1['id'], true);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN[0]], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE[0]], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);
    }

    public function testHdfcCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getLastEntity('cybersource', true);

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideHdfcPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'cybersource');
        $this->runForFiles([$file], 'HDFC');

        $updatedPayment1 = $this->getEntityById('payment' ,$payment1['id'], true);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN[0]], "'" . $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_AUTH_CODE[0]], "'" . $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);
    }

    public function testAxisMigsReconPaymentFile()
    {
        $this->fixtures->create('terminal:shared_migs_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(true, false);
        $gatewayPayment1 = $this->getLastEntity('axis_migs', true);

        $this->assertNull($payment1['reference1']);
        $this->assertNull($payment1['reference2']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'migs');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getEntityById('payment' ,$payment1['id'], true);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_AUTH_CODE], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);
    }

    public function testAxisCyberSourceReconPaymentFile()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $payment1 = $this->getNewPaymentEntity(false, true);
        $gatewayPayment1 = $this->getLastEntity('cybersource', true);

        $this->assertNull($payment1['reference1']);

        $entries[] = $this->overrideAxisPayment($gatewayPayment1,[],'cybersource');

        $file = $this->writeToExcelFile($entries, 'axis', 'files/settlement','Sale');
        $this->runForFiles([$file], 'Axis');

        $updatedPayment1 = $this->getEntityById('payment' ,$payment1['id'], true);

        $this->assertEquals($entries[0][AxisPaymentRecon::COLUMN_ARN], $updatedPayment1['reference1']);
        // Recon should not overwrite reference2 if it was saved before
        $this->assertEquals($payment1['reference2'], $updatedPayment1['reference2']);
        $this->assertTrue($updatedPayment1['gateway_captured']);
    }

    public function testHdfcFssReconRefundFile()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->merchant->addFeatures('charge_at_will');

        // Recurring authorised payment
        $refund1 = $this->getNewRefundEntity(true, false);
        $gatewayPayment1 = $this->getLastEntity('hdfc', true);

        $this->assertNull($refund1['arn']);

        $entries[] = $this->overrideHdfcRefund($gatewayPayment1);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC');

        $updatedRefund1 = $this->getEntityById('refund', $refund1['id'], true);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN[0]], "'" . $updatedRefund1['arn']);

        // Test for for update ARN
        $entries[0][HDFCPaymentRecon::COLUMN_ARN[0]] .= str_random(2);

        $file = $this->writeToExcelFile($entries, 'fss');
        $this->runForFiles([$file], 'HDFC', ['refund_arn']);

        $updatedRefund1 = $this->getEntityById('refund', $refund1['id'], true);

        $this->assertEquals($entries[0][HDFCPaymentRecon::COLUMN_ARN[0]], "'" . $updatedRefund1['arn']);

    }

    /*
     * Helpers
     */

    private function getNewPaymentEntity(bool $recurring = false, bool $captured = false)
    {
        $payment = $recurring ? $this->recurringPayment : $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        if ($recurring === true)
        {
            unset($payment['card']);
            $payment['token'] = $paymentEntity['token_id'];

            $this->doS2SRecurringPayment($payment);

            $paymentEntity = $this->getLastPayment(true);
        }

        if ($captured === true)
        {
            $this->capturePayment($paymentEntity['id'], $paymentEntity['amount']);

            $paymentEntity = $this->getLastPayment(true);
        }

        return $paymentEntity;
    }

    private function getNewRefundEntity($captured = false)
    {
        $payment = $this->getNewPaymentEntity(false, $captured);

        $this->refundPayment($payment['id']);

        return $this->getLastEntity('refund', true);
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

        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID[0]] = $payment['payment_id'];
        $facade[HDFCPaymentRecon::COLUMN_AUTH_CODE[0]]  = "'" . random_integer(6);
        $facade[HDFCPaymentRecon::COLUMN_ARN[0]]        = "'" . str_random(24);

        if ($gateway === 'cybersource')
        {
            $facade[HDFCPaymentRecon::COLUMN_TERMINAL_NUMBER[0]] = "'89050258";
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

    private function overrideHdfcRefund(array $payment, array $forceOverride = [], $gateway = 'fss')
    {
        $facade = $this->overrideHdfcPayment($payment, $forceOverride, $gateway);

        $facade['rec_fmt'] = 'CVD';
        $facade[HDFCPaymentRecon::COLUMN_PAYMENT_ID[0]] = $payment['refund_id'];

        return $facade;
    }

    protected function runForFiles(array $files, string $gateway, array $forceUpdate = [])
    {
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
            'file.xlsx',
            $mime,
            filesize($url),
            null,
            true);
    }
}
