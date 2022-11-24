<?php

namespace RZP\Tests\Functional\Gateway\File;

use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Services\Mock\Scrooge;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbplusPaymentServiceCardlessEmiTest;

class NbplusCardlessEmiWalnut369ReconciliationTest extends NbplusPaymentServiceCardlessEmiTest
{
    use ReconTrait;
    use FileHandlerTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusCardlessEmiReconciliationTestData.php';

        parent::setUp();

        $this->provider = 'walnut369';

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);
    }

    public function testWalnut369SuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data[] = $this->testData[__FUNCTION__];

        $data[0]['RZP Txn ID'] = $payment['id'];

        $file = $this->writeToExcelFile($data, 'walnut369_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'walnut369_recon_file.xlsx');

        $this->reconcile($uploadedFile, Base::WALNUT369);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testWalnut369CombinedReconSuccessonly()
    {
        $this->markTestSkipped("This flow is moved to netbanking ");
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntity('refund');

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->assertEquals(1, $refund[Refund\Entity::IS_SCROOGE]);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], 'refunded');

        $data[0] = $this->testData['testWalnut369SuccessRecon'];
        $data[1] = $this->testData['testWalnut369SuccessRecon'];

        $data[0]['RZP Txn ID'] = $payment['id'];
        $data[1]['RZP Txn ID'] = $refund['id'];
        $data[1]['Txn Type'] = 'Cancellation';

        $file = $this->writeToExcelFile($data, 'walnut369_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'walnut369_recon_file.xlsx');

        $this->mockScroogeResponse($refund['id'], $payment['id']);

        $this->reconcile($uploadedFile, Base::WALNUT369);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function mockScroogeResponse($refundId, $paymentId)
    {
        $scroogeResponse = [
            'body' => [
                'data' => [
                    ltrim($refundId, '0') => [
                        'payment_id'     => PublicEntity::stripDefaultSign($paymentId),
                        'refund_id'      => PublicEntity::stripDefaultSign($refundId)
                    ],
                ]
            ]
        ];

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRefundsFromPaymentIdAndGatewayId'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefundsFromPaymentIdAndGatewayId')->willReturn($scroogeResponse);

    }

    public function testWalnut369ReconWithLateAuthorize()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::CALLBACK)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            GatewayErrorException::class);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Status::FAILED);

        $data[] = $this->testData['testWalnut369SuccessRecon'];

        $data[0]['RZP Txn ID'] = $payment['id'];

        $file = $this->writeToExcelFile($data, 'walnut369_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'walnut369_recon_file.xlsx');

        $this->reconcile($uploadedFile, Base::WALNUT369);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testWalnut369ForceAuth()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::CALLBACK)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            GatewayErrorException::class);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Status::FAILED);

        $data[] = $this->testData['testWalnut369SuccessRecon'];

        $data[0]['RZP Txn ID'] = $payment['id'];

        $file = $this->writeToExcelFile($data, 'walnut369_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'walnut369_recon_file.xlsx');

        $this->reconcile($uploadedFile, Base::WALNUT369, [$payment['public_id']]);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        return $this->createFile($data);
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
}
