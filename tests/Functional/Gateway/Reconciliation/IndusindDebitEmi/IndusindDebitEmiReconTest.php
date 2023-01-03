<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\IndusindDebitEmi;

use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class IndusindDebitEmiReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment  = null;
    protected $terminal = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/IndusindDebitEmiReconTestData.php';

        parent::setUp();

        $this->gateway = 'indusind_debit_emi';

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:indusind_debit_emi');

        $this->ba->publicAuth();
    }

    public function testPaymentRecon()
    {
        $data[0] = $this->testData['testIndusindDebitEmiSuccessRecon'];

        $payment_success = $this->createDependentEntities($data[0]['Loan Amount'] * 100);

        $data[0]['EMI ID'] = $payment_success['id'];

        $file = $this->writeToExcelFile($data, 'Indusind_Emi_File', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'Indusind_Emi_File.xlsx');

        $this->reconcile($uploadedFile, 'IndusindDebitEmi');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'IndusindDebitEmi',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->paymentSuccessAsserts($payment_success);
    }

    protected function createDependentEntities($amount, $status = 'authorized')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'status'           => $status,
            'gateway'          => 'indusind_debit_emi',
            'terminal_id'      => $this->terminal['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        return $this->fixtures->create(
            'payment',
            $paymentArray
        )->toArray();
    }

    protected function paymentSuccessAsserts(array $payment)
    {
        $payment = $this->getDbEntity('payment', ['id' => $payment['id']])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status' => 'authorized',
            ],
            $payment
        );

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

}
