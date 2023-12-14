<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\IciciDebitEmi;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Batch\Status;
use RZP\Services\Mock\CardPaymentService;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class IciciDebitEmiReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment  = null;
    protected $terminal = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/IciciDebitEmiReconTestData.php';

        parent::setUp();

        $this->gateway = 'icici_debit_emi';

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_hitachi_terminal');

        $this->ba->publicAuth();

    }

    public function testPaymentRecon()
    {
        $data[0] = $this->testData['testIciciDebitEmiSuccessRecon'];

        $payment_success = $this->createDependentEntities($data[0]['Sale Amt'] * 100);

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('fetchPaymentIdFromEmiGatewayReferenceIds')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $content) use ($payment_success) {
                return $payment_success['id'];
            });

        $file = $this->writeToExcelFile($data, 'CG0000000000025_PURCHASE_11112040', 'files/filestore', 'PROCESSED_RAZORPAY');

        $uploadedFile = $this->createUploadedFile($file, 'CG0000000000025_PURCHASE_11112040.xlsx');



        $this->reconcile($uploadedFile, 'IciciDebitEmi');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'IciciDebitEmi',
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

    public function testRefundRecon()
    {

        $data[0] = $this->testData['testIciciDebitEmiRefundSuccessRecon'];

        $payment_success = $this->createDependentEntities($data[0]['Amount'] * 100,'captured');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $refund = $this->createRefundEntities($payment_success,$createdAt);

        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('fetchPaymentIdFromEmiGatewayReferenceIds')
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $content) use ($payment_success) {
                return $payment_success['id'];
            });

        $file = $this->writeToExcelFile($data, 'CG0000000000025_PURCHASE_11112040', 'files/filestore','CAN_RAZORPAY');

        $uploadedFile = $this->createUploadedFile($file, 'CG0000000000025_PURCHASE_11112040.xlsx');

        $this->reconcile($uploadedFile, 'IciciDebitEmi');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'IciciDebitEmi',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->refundSuccessAsserts($refund);

    }

    protected function createDependentEntities($amount, $status = 'authorized')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'status'           => $status,
            'gateway'          => 'hitachi',
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

    protected function createRefundEntities($payment , $createdAt)
    {
        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment['id'],
                'merchant_id' => '10000000000000',
                'amount'      => $payment['amount'],
                'base_amount' => $payment['amount'],
                'gateway'     => 'hitachi',
            ])->toArray();

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id' => $refund['id'],
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->edit(
            'refund',
            $refund['id'],
            [
                'created_at' => $createdAt,
                'transaction_id' => $transaction->getId()
            ]);


        return $refund;
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

    protected function refundSuccessAsserts(array $refund)
    {
        $refund = $this->getDbEntity('refund', ['id' => $refund['id']])->toArray();

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $refund['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

}
