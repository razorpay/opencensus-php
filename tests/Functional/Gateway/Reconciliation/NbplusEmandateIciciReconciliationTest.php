<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Services\NbPlus;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Order\Entity as Order;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusService\NbplusPaymentServiceEmandateTest;

class NbplusEmandateIciciReconciliationTest extends NbplusPaymentServiceEmandateTest
{
    use ReconTrait;

    public function testDirectDebitFlowSuccess()
    {
        $orderInput = [
            Order::AMOUNT          => 50000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->payment[Payment::ORDER_ID] = $order[Order::ID];
        $this->payment[Payment::AMOUNT] = $order[Order::AMOUNT];

        $this->doAuthPayment($this->payment);

        $paymentEntity1 = $this->getDbLastPayment();

        $this->assertEquals('captured', $paymentEntity1['status']);
        $this->assertEquals('3', $paymentEntity1['cps_route']);
        $this->assertEquals('emandate', $paymentEntity1['method']);

        $tokenEntity1 = $this->getDbLastEntity('token');

        $this->assertEquals('confirmed', $tokenEntity1['recurring_status']);
        $this->assertEquals($paymentEntity1['token_id'], $tokenEntity1['id']);

        $order = $this->createOrder($orderInput);
        $this->payment[Payment::ORDER_ID] = $order[Order::ID];
        $this->payment[Payment::AMOUNT] = $order[Order::AMOUNT];
        $this->payment[Payment::TOKEN] = 'token_' . $paymentEntity1[Payment::TOKEN_ID];

        $this->doS2SRecurringPayment($this->payment);

        $paymentEntity2 = $this->getDbLastPayment();

        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals('3', $paymentEntity2['cps_route']);
        $this->assertEquals('emandate', $paymentEntity2['method']);

        $mockData = require(__DIR__ . '/NbplusNetbankingReconciliationTestData.php');

        $row1 = $row2 = $mockData['testIciciSuccessRecon'];

        $row1['ITC'] = strtoupper($paymentEntity1['id']);
        $row1['PRN'] = $paymentEntity1['id'];

        $row2['ITC'] = strtoupper($paymentEntity2['id']);
        $row2['PRN'] = $paymentEntity2['id'];

        $reconFile = $this->generateReconFile([$row1, $row2]);

        $fileName = 'razorpayreports_test_'.Carbon::today()->format("m-d-Y").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_ICICI);

        $batchEntity = $this->getDbLastEntity('batch');

        $this->assertEquals('processed', $batchEntity['status']);
        $this->assertEquals('2', $batchEntity['success_count']);
        $this->assertEquals('0', $batchEntity['failure_count']);
    }

    public function testForceAuthForIciciEmandatePayment()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlus\Action::CALLBACK)
            {
                $content = [
                    NbPlus\Response::RESPONSE  => null,
                    NbPlus\Response::ERROR => [
                        NbPlus\Error::CODE  => 'GATEWAY',
                        NbPlus\Error::CAUSE => [
                            NbPlus\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $orderInput = [
            Order::AMOUNT          => 50000,
            Order::METHOD          => Method::EMANDATE,
            Order::PAYMENT_CAPTURE => true,
        ];

        $order = $this->createOrder($orderInput);

        $this->payment[Payment::ORDER_ID] = $order[Order::ID];
        $this->payment[Payment::AMOUNT] = $order[Order::AMOUNT];

        $this->makeRequestAndCatchException(function() {
            $this->doAuthPayment($this->payment);
        }, GatewayErrorException::class);

        $paymentEntity1 = $this->getDbLastPayment();

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $paymentEntity1[Payment::CPS_ROUTE]);
        $this->assertEquals(Status::FAILED, $paymentEntity1[Payment::STATUS]);

        $mockData = require(__DIR__ . '/NbplusNetbankingReconciliationTestData.php');

        $row = $mockData['testIciciSuccessRecon'];

        $row['ITC'] = strtoupper($paymentEntity1['id']);
        $row['PRN'] = $paymentEntity1['id'];

        $reconFile = $this->generateReconFile([$row]);

        $fileName = 'razorpayreports_test_'.Carbon::today()->format("m-d-Y").'.txt';

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'], $fileName, "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_ICICI);

        $batchEntity = $this->getDbLastEntity('batch');

        $this->assertEquals('processed', $batchEntity['status']);
        $this->assertEquals('1', $batchEntity['success_count']);
        $this->assertEquals('0', $batchEntity['failure_count']);
    }

    protected function generateReconFile($content): array
    {
        $data = '';

        foreach ($content as $row)
        {
            $rowData = implode(',', $row);
            $data .= nl2br($rowData."\n");
        }

        return $this->createFile($data);
    }

    protected function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile($url, $fileName, $mime, null, true);
    }
}
