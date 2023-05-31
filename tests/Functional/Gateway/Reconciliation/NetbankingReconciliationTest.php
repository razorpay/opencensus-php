<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Models\FileStore;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Reconciliator\NetbankingEquitas\Constants;

class NetbankingReconciliationTest extends TestCase
{
    use BatchTestTrait;

    const ACCOUNT_NUMBER = '309002069863';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingReconciliationTestData.php';

        parent::setUp();


        $this->gateway = '';
    }

    public function testRblManualReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        $payment = $this->createPayment('netbanking_rbl', ['terminal_id' => $terminal['id']]);

        $netbanking = $this->createNetbanking($payment['id'], 'RATN');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                $content['0']['Debit Account'] = self::ACCOUNT_NUMBER;

                $content['0']['Credit Account'] = '309001141935';
            }
        });

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);

        $this->assertEquals('309001141935', $gatewayEntity['credit_account_number']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testRblWrongFormatReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $payment = $this->createPayment('netbanking_rbl');

        $netbanking = $this->createNetbanking($payment['id'], 'RATN');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                unset($content[0]['Error Description']);
            }
        });

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(1, $batch['total_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(0, $batch['failure_count']);

        $transactionEntity = $this->getDbLastEntityPublic('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    public function testRblFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['amount' => 10000012, 'terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'RATN', null, null);

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('processed', $batch['status']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testRblForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_rbl';

        $this->setMockGatewayTrue();

        $payment = $this->createFailedPayment($this->gateway, ['amount' => 10000012]);

        $this->createNetbanking($payment['id'], 'RATN', null, null);

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertNull($paymentEntity['reference1']);

        $this->reconcile('NetbankingRbl', $uploadedFile,  ['pay_' . $payment['id']]);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('processed', $batch['status']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['reference1'], 99999999);

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 99999999);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testEquitasPaymentReconciliation()
    {
        $this->gateway = 'netbanking_equitas';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_equitas_terminal');

        $payment = $this->createPayment('netbanking_equitas', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ESFB', 'S');

        $fileContents = $this->generateFile('equitas', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingEquitas', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingentity['reference1'], 99999);

    }

    public function testEquitasFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_equitas';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_equitas_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $netbanking = $this->createNetbanking($payment['id'], 'ESFB', 'F');

        $this->fixtures->edit('netbanking', $netbanking->getId(), ['bank_payment_id' => null]);

        $fileContents = $this->generateFile('equitas', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingEquitas', $uploadedFile);

        $transactionEntity = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);

        $netbankingEntity = $this->getDbLastEntityToArray('netbanking');

        $this->assertEquals($netbankingEntity['reference1'], 99999);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');
    }

    public function testEquitasAmountMismatchReconciliation()
    {
        $this->gateway = 'netbanking_equitas';

        $this->setMockGatewayTrue();

        $payment = $this->createPayment($this->gateway);

        $netbanking = $this->createNetbanking($payment['id'], 'ESFB', 'Y');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'equitas_recon')
                {
                    $content[Constants::AMOUNT] = '50.00';
                }
            });

        $fileContents = $this->generateFile('equitas', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingEquitas', $uploadedFile);

        $transactionEntity = $this->getDbLastEntityToArray('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);
    }

    public function testIndusindManualReconciliation()
    {
        $this->gateway = 'netbanking_indusind';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $payment = $this->createPayment('netbanking_indusind', ['terminal_id' => $terminal['id']]);

        $netbanking = $this->createNetbanking($payment['id'], 'INDB', 'Y');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                $content['0']['account_number'] = self::ACCOUNT_NUMBER;
            }
        });

        $fileContents = $this->generateFile('indusind', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIndusind', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testNetbankingIndusindForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_indusind';

        $payment = $this->createFailedPayment($this->gateway);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $updatedPayment['status']);

        $this->createNetbanking($payment['id'], 'INDB', 'Y');

        $fileContents = $this->generateFile('indusind', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIndusind', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals($updatedPayment['acquirer_data']['bank_transaction_id'], 23453);

        $this->assertEquals($updatedPayment['reference1'], 23453);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testPnbReconciliation()
    {
        $this->gateway = 'netbanking_pnb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');

        $payment1 = $this->createPayment('netbanking_pnb', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment1['id'], 'PUNB', '0');

        $this->setMockGatewayTrue();

        $payment2 = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment2['id'], 'PUNB', '1000');

        $fileContents = $this->generateFile('pnb', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingPnb', $uploadedFile);

        $payment1 = $this->getDbEntityById('payment', $payment1['id']);

        $transaction1 = $payment1->transaction;

        $this->assertTrue($transaction1['reconciled_at'] !== null);

        $payment2 = $this->getDbEntityById('payment', $payment2['id']);

        $transaction2 = $payment2->transaction;

        $this->assertEquals($payment2['status'], 'authorized');

        $this->assertTrue($transaction2['reconciled_at'] !== null);

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingEntity['account_number'], "9999999999");

        $this->assertEquals($netbankingEntity['bank_payment_id'], 99999);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testNetbankingPnbForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_pnb';

        $payment = $this->createFailedPayment($this->gateway);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $updatedPayment['status']);

        $this->createNetbanking($payment['id'], 'PUNB', 'Y');

        $fileContents = $this->generateFile('pnb', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingPnb', $uploadedFile, ['pay_' . $payment['id']]);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals($updatedPayment['acquirer_data']['bank_transaction_id'], 99999);

        $this->assertEquals($updatedPayment['reference1'], 99999);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testPnbAmountMismatchReconciliation()
    {
        $this->gateway = 'netbanking_pnb';

        $this->setMockGatewayTrue();

        $payment = $this->createPayment($this->gateway);

        $netbanking = $this->createNetbanking($payment['id'], 'PUNB', '0');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                $content["Amount"] = '50.00';
            });

        $fileContents = $this->generateFile('pnb', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingPnb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testIdfcPaymentReconciliation()
    {
        $this->gateway = 'netbanking_idfc';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_idfc_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'IDFB', 'S');

        $input = [
            'gateway' => $this->gateway,
        ];

        $fileContents = $this->generateFile('idfc', $input);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIdfc', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $netbankingentity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingentity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    public function testIdfcFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_idfc';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_idfc_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $netbanking = $this->createNetbanking($payment['id'], 'IDFB', 'ACT001');

        $this->fixtures->edit('netbanking', $netbanking->getId(), ['bank_payment_id' => null]);

        $fileContents = $this->generateFile('idfc', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIdfc', $uploadedFile);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($netbankingEntity['bank_payment_id']);

        $this->assertEquals($netbankingEntity['status'], 'SUC000');
    }

    public function testObcManualReconciliation()
    {
        $this->gateway = 'netbanking_obc';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $payment = $this->createPayment('netbanking_obc', ['amount' => 150, 'terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ORBC', 'Y');

        $fileContents = $this->generateFile('obc', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingObc', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingEntity['bank_payment_id'], '99999');

        $batch = $this->getLastEntity('batch', true);

        $testData = $this->testData['testObcReconBatch'];

        $this->assertArraySelectiveEquals($testData, $batch);
    }

    public function testObcFailedPaymentReconciliation()
    {
        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $payment = $this->createFailedPayment('netbanking_obc', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ORBC', 'F');

        $fileContents = $this->generateFile('obc', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingObc', $uploadedFile);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 99999);

        $this->assertEquals($paymentEntity['reference1'], 99999);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $batch = $this->getLastEntity('batch', true);

        $testData = $this->testData['testObcReconBatch'];

        $this->assertArraySelectiveEquals($testData, $batch);
    }

    public function testObcAmountMismatchReconciliation()
    {
        $this->gateway = 'netbanking_obc';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_obc_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ORBC', 'Y');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_oriental_recon')
                {
                    // Setting amount to 1 will cause payment amount validation to fail
                    $content[3] = '0000000001.00';
                }
            });

        $fileContents = $this->generateFile('obc', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingObc', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNull($transactionEntity['reconciled_at']);

        $batch = $this->getLastEntity('batch', true);

        $testData = $this->testData['testObcReconBatchPartiallyProcessed'];

        $this->assertArraySelectiveEquals($testData, $batch);
    }

    public function testBobManualReconciliation()
    {
        $this->gateway = 'netbanking_bob';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_bob_terminal');

        $payment = $this->createPayment('netbanking_bob', ['amount' => 40000, 'terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'BARB', 'S');

        $payment = $this->createPayment('netbanking_bob', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'BARB', 'S');

        $fileContents = $this->generateFile('bob', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingBob', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testHdfcPaymentReconciliation()
    {
        $this->gateway = 'netbanking_hdfc';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->createPayment('netbanking_hdfc', ['terminal_id' => $terminal['id']]);

        //bank name is wrong?
        $this->createNetbanking($payment['id'], 'ICIC', 'S');

        $fileContents = $this->generateFile('hdfc', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingHdfc', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testAllahabadPaymentReconciliation()
    {
        $this->gateway = 'netbanking_allahabad';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');

        $payment = $this->createPayment('netbanking_allahabad', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ALLA', 'Y');

        $fileContents = $this->generateFile('allahabad', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingAllahabad', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testCanaraPaymentReconciliation()
    {
        $this->gateway = 'netbanking_canara';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $payment = $this->createPayment('netbanking_canara', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'CNRB');

        $fileContents = $this->generateFile('canara', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCanara', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $this->assertEquals($gatewayEntity['account_number'], '100000');

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testAllahabadFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_allahabad';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_allahabad_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ALLA', 'N');

        $fileContents = $this->generateFile('allahabad', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingAllahabad', $uploadedFile);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testCanaraFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_canara';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'CNRB','F');

        $fileContents = $this->generateFile('canara', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCanara', $uploadedFile);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    public function testCanaraAmountMismatchReconciliation()
    {
        $this->gateway = 'netbanking_canara';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $netbanking = $this->createNetbanking($payment['id'], 'CNRB');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'canara_recon')
                {
                    // Setting amount to 1 will cause payment amount validation to fail
                    $content[5] = '1.00';
                }
            });

        $fileContents = $this->generateFile('canara', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCanara', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNull($transactionEntity['reconciled_at']);

        $batch = $this->getLastEntity('batch', true);

        $testData = $this->testData['testCanaraReconBatchPartiallyProcessed'];

        $this->assertArraySelectiveEquals($testData, $batch);
    }

    public function testIciciFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_icici';

        $this->setMockGatewayTrue();

        $terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ICIC', 'N');

        $fileContents = $this->generateFile('icici', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIcici', $uploadedFile);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 99999);

        $this->assertEquals($paymentEntity['reference1'], 99999);

        $this->assertEquals($paymentEntity['status'], 'authorized');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertNotNull($transactionEntity['reconciled_type']);
    }

    public function testIciciManualReconciliation()
    {
        $this->gateway = 'netbanking_icici';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $payment = $this->createPayment('netbanking_icici', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ICIC', 'Y');

        $fileContents = $this->generateFile('icici', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIcici', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testIciciAmountMismatch()
    {
        $this->gateway = 'netbanking_icici';

        $payment = $this->createPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'ICIC', 'Y');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $content[0]['amount'] = '1.00';
            });

        $fileContents = $this->generateFile('icici', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIcici', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testCbiSuccessRecon()
    {
        $this->gateway = 'netbanking_cbi';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_cbi_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_cbi');

        $fileContents = $this->generateFile('cbi', ['gateway' => 'netbanking_cbi']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCbi', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($gatewayEntity['data']['bank_payment_id']);
        $this->assertNotNull($gatewayEntity['data']['account_number']);
        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCbiFailedRecon()
    {
        $this->gateway = 'netbanking_cbi';

        $payments = [];

        $terminal = $this->fixtures->create('terminal:shared_netbanking_cbi_terminal');

        $payments[] = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payments[0]['id'], $payments[0]['amount'], 'netbanking_cbi', '123');

        $payments[] = $this->createPayment($this->gateway, ['amount' => 2000, 'terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payments[1]['id'], $payments[1]['amount'], 'netbanking_cbi', '234');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_cbi_nb_recon' and $content['amount'] === 20)
                {
                    $content['amount'] = 1;
                }
            });

        $fileContents = $this->generateFile('cbi', ['gateway' => 'netbanking_cbi']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCbi', $uploadedFile);

        $successTransactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payments[0]['id']])->toArray();
        $failureTransactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payments[1]['id']])->toArray();

        $this->assertNotNull($successTransactionEntity['reconciled_at']);

        $this->assertNull($failureTransactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'status' => 'partially_processed',
                'success_count' => 1,
                'failure_count' => 1,
            ],
            $batch
        );
    }

    public function testScbSuccessRecon()
    {
        $this->gateway = 'netbanking_scb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_scb_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_scb');

        $fileContents = $this->generateFile('scb', ['gateway' => 'netbanking_scb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingScb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testScbBankAmountMismatch()
    {
        $this->gateway = 'netbanking_scb';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_scb');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_scb_nb_recon')
                {
                    $content['amount'] = 100;
                }
            });

        $fileContents = $this->generateFile('scb', ['gateway' => 'netbanking_scb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingScb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testScbReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_scb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_scb_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_scb');

        $fileContents = $this->generateFile('sib', ['gateway' => 'netbanking_scb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingScb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testSibSuccessRecon()
    {
        $this->gateway = 'netbanking_sib';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_sib_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_sib');

        $fileContents = $this->generateFile('sib', ['gateway' => 'netbanking_sib']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingSib', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testSibForceAuth()
    {
        $this->gateway = 'netbanking_sib';

        $payment = $this->createFailedPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_sib');

        $fileContents = $this->generateFile('sib', ['gateway' => 'netbanking_sib']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingSib', $uploadedFile, [$payment['public_id']]);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(true, $payment['late_authorized']);
        $this->assertEquals($data['bank_payment_id'], $payment['reference1']);
        $this->assertEquals($data['bank_payment_id'], $payment['acquirer_data']['bank_transaction_id']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testSibBankAmountMismatch()
    {
        $this->gateway = 'netbanking_sib';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_sib');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_sib_nb_recon')
                {
                    $content['payment amount'] = 1;
                }
            });

        $fileContents = $this->generateFile('sib', ['gateway' => 'netbanking_sib']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingSib', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testSibReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_sib';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_sib_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_sib');

        $fileContents = $this->generateFile('sib', ['gateway' => 'netbanking_sib']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingSib', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testYesbSuccessRecon()
    {
        $this->gateway = 'netbanking_yesb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_yesb_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_yesb');

        $fileContents = $this->generateFile('yesb', ['gateway' => 'netbanking_yesb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingYesb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testYesBankAmountMismatch()
    {
        $this->gateway = 'netbanking_yesb';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_yesb');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_yesb_nb_recon')
                {
                    $content['Amount'] = 100;
                }
            });

        $fileContents = $this->generateFile('yesb', ['gateway' => 'netbanking_yesb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingYesb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testYesbReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_yesb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_yesb_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_yesb');

        $fileContents = $this->generateFile('yesb', ['gateway' => 'netbanking_yesb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingYesb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testYesbReconStatusFailedSucessApi()
    {
        $this->gateway = 'netbanking_yesb';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_yesb');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_yesb_nb_recon')
                {
                    $content['Transaction Status'] = 'FAILURE';
                }
            });

        $fileContents = $this->generateFile('yesb', ['gateway' => 'netbanking_yesb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingYesb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testYesbReconForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_yesb';

        $payment = $this->createFailedPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_yesb');

        $fileContents = $this->generateFile('yesb', ['gateway' => 'netbanking_yesb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingYesb', $uploadedFile, [$payment['public_id']]);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(true, $payment['late_authorized']);
        $this->assertEquals($data['bank_payment_id'], $payment['reference1']);
        $this->assertEquals($data['bank_payment_id'], $payment['acquirer_data']['bank_transaction_id']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testKvbSuccessRecon()
    {
        $this->gateway = 'netbanking_kvb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_kvb_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_kvb');

        $fileContents = $this->generateFile('kvb', ['gateway' => 'netbanking_kvb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingKvb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $this->assertNotNull($data['account_number']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testKvbBankAmountMismatch()
    {
        $this->gateway = 'netbanking_kvb';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_kvb');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_kvb_nb_recon')
                {
                    $content['Transaction Amount'] = 100;
                }
            });

        $fileContents = $this->generateFile('kvb', ['gateway' => 'netbanking_kvb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingKvb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testKvbReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_kvb';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_kvb_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_kvb');

        $fileContents = $this->generateFile('kvb', ['gateway' => 'netbanking_kvb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingKvb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $this->assertNotNull($data['account_number']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testIbkSuccessRecon()
    {
        $this->markTestSkipped('migrated to nbplus');

        $this->gateway = 'netbanking_ibk';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_ibk');

        $fileContents = $this->generateFile('ibk', ['gateway' => 'netbanking_ibk']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIbk', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testIbkAmountMismatch()
    {
        $this->markTestSkipped('migrated to nbplus');

        $this->gateway = 'netbanking_ibk';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_ibk');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_ibk_nb_recon')
                {
                    $content['Amount'] = 100;
                }
            });

        $fileContents = $this->generateFile('ibk', ['gateway' => 'netbanking_ibk']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIbk', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testIbkReconcileFailedPayment()
    {
        $this->markTestSkipped('migrated to nbplus');

        $this->gateway = 'netbanking_ibk';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_ibk');

        $fileContents = $this->generateFile('ibk', ['gateway' => 'netbanking_ibk']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIbk', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCubSuccessRecon()
    {
        $this->gateway = 'netbanking_cub';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_cub');

        $fileContents = $this->generateFile('cub', ['gateway' => 'netbanking_cub']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCub', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCubAmountMismatch()
    {
        $this->gateway = 'netbanking_cub';

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_cub');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                if ($action === 'col_payment_cub_nb_recon')
                {
                    $content['Payment Amount'] = 100;
                }
            });

        $fileContents = $this->generateFile('cub', ['gateway' => 'netbanking_cub']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCub', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testCubReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_cub';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_cub');

        $fileContents = $this->generateFile('cub', ['gateway' => 'netbanking_cub']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCub', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCorporationSuccessRecon()
    {
        $this->gateway = 'netbanking_corporation';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'corporation', 'S');

        $fileContents = $this->generateFile('corporation', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $data = $this->reconcile('NetbankingCorporation', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCorporationBankAmountMismatch()
    {
        $this->gateway = 'netbanking_corporation';

        $payment = $this->createPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'corporation', 'S');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $content[0][4] = '1.00';
            });

        $fileContents = $this->generateFile('corporation', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCorporation', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testCorporationReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_corporation';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'corporation', 'F');

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNull($netbankingEntity['date']);

        $fileContents = $this->generateFile('corporation', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCorporation', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($netbankingEntity['date']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCorporationForceAuthPayments()
    {
        $this->gateway = 'netbanking_corporation';

        $payment = $this->createFailedPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'corporation', 'F');

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNull($netbankingEntity['date']);

        $fileContents = $this->generateFile('corporation', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCorporation', $uploadedFile, ['pay_' . $payment['id']]);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals($payment['acquirer_data']['bank_transaction_id'], 99999);

        $this->assertEquals($payment['reference1'], 99999);

        $this->assertEquals('authorized', $payment['status']);

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($netbankingEntity['date']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCorpaymentReconStatusFailedSucessApi()
    {
        $this->gateway = 'netbanking_corporation';

        $payment = $this->createPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'corporation', 'S');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $content[0][5] = 'F';
            });

        $fileContents = $this->generateFile('corporation', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCorporation', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testVijayaSuccessRecon()
    {
        $this->gateway = 'netbanking_vijaya';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_vijaya_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'vijaya', 'Y');

        $fileContents = $this->generateFile('vijaya', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $data = $this->reconcile('NetbankingVijaya', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testVijayaBankAmountMismatch()
    {
        $this->gateway = 'netbanking_vijaya';

        $payment = $this->createPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'vijaya', 'Y');

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $content[0][1] = '1.00';
            });

        $fileContents = $this->generateFile('vijaya', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingVijaya', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    public function testVijayaReconcileFailedPayment()
    {
        $this->gateway = 'netbanking_vijaya';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_vijaya_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'vijaya', 'N');

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNull($netbankingEntity['date']);

        $fileContents = $this->generateFile('vijaya', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingVijaya', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('authorized', $payment['status']);

        $netbankingEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($netbankingEntity['date']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testNetbankingAxisForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_axis';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $payment = $this->createFailedPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $updatedPayment['status']);

        $this->createNetbanking($payment['id'], 'AXIS', 'Y');

        $fileContents = $this->generateFile('axis', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingAxis', $uploadedFile, ['pay_' . $payment['id']]);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 99999);

        $this->assertEquals($paymentEntity['reference1'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testNetbankingAxisSuccessRecon()
    {
        $this->gateway = 'netbanking_axis';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'AXIS', 'Y');

        $fileContents = $this->generateFile('axis', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingAxis', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals(99999, $gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testNetbankingAxisDoubleBidFirstSuccess()
    {
        $this->gateway = 'netbanking_axis';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $payment1 = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment1['id'], 'AXIS', 'Y');

        $fileContents = $this->generateFile('axis', []);

        $data = file($fileContents['local_file_path']);

        $filePath = $this->createAxisDoubleBidFile($data);

        $uploadedFile = $this->createUploadedFile($filePath);
        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals(99999, $gatewayEntity['bank_payment_id']);
        $this->reconcile('NetbankingAxis', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals(99999, $gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment1['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);
    }

    public function testNetbankingAxisDoubleBidSecondSuccess()
    {
        $this->gateway = 'netbanking_axis';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $payment1 = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment1['id'], 'AXIS', 'Y');

        $fileContents = $this->generateFile('axis', []);

        $data = file($fileContents['local_file_path']);

        $filePath = $this->createAxisDoubleBidFile($data, true);

        $uploadedFile = $this->createUploadedFile($filePath);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals(99999, $gatewayEntity['bank_payment_id']);

        $this->reconcile('NetbankingAxis', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals(99999, $gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $payment1['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);
    }

    protected function createAxisDoubleBidFile($data, $swap = false)
    {
        $fileData = [];
        $fileData[] = explode(",", trim($data[0]));
        $fileData[] = explode(",", trim($data[1]));
        $row2 = explode(",", trim($data[1]));
        $row2[0] = 11111;
        $fileData[] = $row2;
        $fileData[] = explode(",", trim($data[2]));

        if($swap === true)
        {
            $temp = $fileData[1];
            $fileData[1] = $fileData[2];
            $fileData[2] = $temp;
        }

        $creator = new FileStore\Creator;
        $type = FileStore\Type::MOCK_RECONCILIATION_FILE;
        $txt = $this->generateText($fileData, ',', false);

        $file = $creator->extension('txt')
            ->content($txt)
            ->name('razorpay mis report')
            ->type($type)
            ->headers(false)
            ->save()
            ->get();

       return $file['local_file_path'];
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    public function testNetbankingFederalSuccessRecon()
    {
        $this->gateway = 'netbanking_federal';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_federal_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'federal');

        $fileContents = $this->generateFile('federal', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingFederal', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testKotakSuccessRecon()
    {
        $this->gateway = 'netbanking_kotak';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

        $payment = $this->createPayment($this->gateway, ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'kotak', 'SUC', 99999, 456789);

        $fileContents = $this->generateFile('kotak', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('Kotak', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testNetbankingKotakForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_kotak';

        $payment = $this->createFailedPayment($this->gateway);

        $this->createNetbanking($payment['id'], 'kotak', 'SUC', 99999, 456789);

        $fileContents = $this->generateFile('kotak', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('Kotak', $uploadedFile, ['pay_' . $payment['id']]);

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertNotNull($gatewayEntity['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testJsbSuccessRecon()
    {
        $this->gateway = 'netbanking_jsb';

        $payment1 = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment1['id'], $payment1['amount'], 'netbanking_jsb');

        $payment2 = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment2['id'], $payment2['amount'], 'netbanking_jsb');

        $fileContents = $this->generateFile('jsb', ['gateway' => 'netbanking_jsb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingJsb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testJsbReconForceAuthorizePayment()
    {
        $this->gateway = 'netbanking_jsb';

        $payment = $this->createFailedPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_jsb');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_jsb_nb_recon')
                {
                    $content['payment_status'] = 'Success';
                }
            });

        $fileContents = $this->generateFile('jsb', ['gateway' => 'netbanking_jsb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingJsb', $uploadedFile, [$payment['public_id']]);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(true, $payment['late_authorized']);
        $this->assertEquals($data['bank_payment_id'], $payment['reference1']);
        $this->assertEquals($data['bank_payment_id'], $payment['acquirer_data']['bank_transaction_id']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testJsbSuccessMixedPaymentsRecon()
    {
        $this->gateway = 'netbanking_jsb';

        $payment1 = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment1['id'], $payment1['amount'], 'netbanking_jsb');

        $payment2 = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment2['id'], $payment2['amount'], 'netbanking_jsb');

        $payment3 = $this->createFailedPayment($this->gateway);

        $this->createMozartEntity($payment3['id'], $payment3['amount'], 'netbanking_jsb');

        $fileContents = $this->generateFile('jsb', ['gateway' => 'netbanking_jsb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingJsb', $uploadedFile);

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['bank_payment_id']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testJsbAmountMismatchReconciliation()
    {
        $this->gateway = 'netbanking_jsb';

        $this->setMockGatewayTrue();

        $payment = $this->createPayment($this->gateway);

        $this->createMozartEntity($payment['id'], $payment['amount'], 'netbanking_jsb');

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_jsb_nb_recon')
                {
                    $content['payment_amount'] = '50';
                }
            });

        $fileContents = $this->generateFile('jsb', ['gateway' => 'netbanking_jsb']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingJsb', $uploadedFile);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);
    }

    protected function reconcile($gateway, $uploadedFile, $forceAuthorizePayments = [])
    {
        $this->ba->h2hAuth();

        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $input[Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                Base::ATTACHMENT_HYPHEN_ONE => $uploadedFile,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/plain";

        $uploadedFile = new UploadedFile(
                            $file,
                            $file,
                            $mimeType,
                            null,
                            true
                        );

        return $uploadedFile;
    }


    protected function createPayment($gateway, $attributes = [])
    {
        $paymentAttributes = [
            'gateway' => $gateway
        ];

        $paymentAttributes = array_merge($paymentAttributes, $attributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        return $payment;
    }

    protected function createFailedPayment($gateway, $attributes = [])
    {
        $paymentAttributes = [
            'gateway' => $gateway
        ];

        $attributes = array_merge($paymentAttributes, $attributes);

        $payment = $this->fixtures->create('payment:netbanking_failed', $attributes);

        return $payment;
    }

    protected function createNetbanking($paymentId, $bank, $status = 'SUC', $bankPaymentId = 99999, $verificationId = null)
    {
        $netbankingAttributes = [
            'payment_id'      => $paymentId,
            'bank'            => $bank,
            'caps_payment_id' => strtoupper($paymentId),
            'bank_payment_id' => $bankPaymentId,
            'status'          => $status,
            'verification_id' => $verificationId,
        ];

        $netbanking = $this->fixtures->create('netbanking', $netbankingAttributes);

        return $netbanking;
    }

    protected function createMozartEntity($paymentId, $amount, $gateway, $mozartId = null)
    {
        $mozartAttributes = [
            'payment_id'      => $paymentId,
            'gateway'         => $gateway,
            'amount'          => $amount,
            'raw'             => json_encode(['payment_id' => $paymentId,'bank_payment_id' => '99999']),
            'action'          => 'authorize',
        ];

        if ($mozartId !== null)
        {
            $mozartAttributes['id'] = $mozartId;
        }

        $mozart = $this->fixtures->create('mozart', $mozartAttributes);

        return $mozart;
    }

    protected function generateFile($bank, $input)
    {
        $gateway = 'netbanking_' . $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockRecon()
    {
        $class = $this->app['gateway']->getReconClass($this->gateway);

        return Mockery::mock($class, [])->makePartial();
    }

    protected function mockReconContentFunction($closure)
    {
        $recon =  $this->mockRecon()
                       ->shouldReceive('content')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->app['gateway']->setRecon($this->gateway, $recon);

        return $recon;
    }

    protected function setMockGatewayTrue()
    {
        $var = 'gateway.mock_'.$this->gateway;

        $this->config[$var] = true;
    }
}
