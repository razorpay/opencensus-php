<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\KotakDebitEmi;

use App;
use RZP\Models\Batch\Status;
use RZP\Services\Mock\CardPaymentService;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class KotakDebitEmiReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment  = null;
    protected $terminal = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/KotakDebitEmiReconTestData.php';

        parent::setUp();

        $this->gateway = 'kotak_debit_emi';

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:kotak_debit_emi');

        $this->ba->publicAuth();

    }

    public function testPaymentRecon()
    {
        $data[0] = $this->testData['testKotakDebitEmiSuccessRecon'];

        $payment_success = $this->createDependentEntities($data[0]['FINAL_AMOUNT'] * 100);

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('fetchPaymentIdFromVerificationFields')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $content) use ($payment_success) {
                return $payment_success['id'];
            });

        $file = $this->writeToExcelFile($data, 'CG0000000000025_PURCHASE_11112040', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'CG0000000000025_PURCHASE_11112040.xlsx');

        $this->reconcile($uploadedFile, 'KotakDebitEmi');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'KotakDebitEmi',
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
            'gateway'          => 'kotak_debit_emi',
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
