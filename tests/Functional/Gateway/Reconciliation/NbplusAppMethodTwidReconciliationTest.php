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
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceAppsTest;

class NbplusAppMethodTwidReconciliationTest extends NbPlusPaymentServiceAppsTest
{
    use ReconTrait;
    use FileHandlerTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusAppMethodReconciliationTestData.php';

        parent::setUp();

        $this->provider = 'twid';

        $this->payment = $this->getDefaultAppPayment($this->provider);
    }

    public function testTwidSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data[] = $this->testData[__FUNCTION__];

        $data[0]['Merchant Transaction Id'] = $payment['id'];

        $file = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($file['local_file_path'], 'twid_recon_file.csv');

        $this->reconcile($uploadedFile, Base::TWID);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['acquirer_data']['amount'], $payment[Payment::AMOUNT] / 100);
        $this->assertEquals($paymentEntity['acquirer_data']['transaction_id'], 1234);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testTwidCombinedReconSuccess()
    {
        $this->doAuthCaptureAndRefundPayment($this->payment);

        $refund = $this->getDbLastEntity('refund');

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->assertEquals(1, $refund[Refund\Entity::IS_SCROOGE]);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], 'refunded');

        $data[0] = $this->testData['testTwidSuccessRecon'];
        $data[1] = $this->testData['testTwidSuccessRecon'];

        $data[0]['Merchant Transaction Id'] = $payment['id'];
        $data[1]['Merchant Transaction Id'] = $payment['id'];
        $data[1]['Bill Value']              = '-' . $data[1]['Bill Value'];
        $data[1]['Status']                  = 'Refund';
        $data[1]['Twid Refund Id']          = 'RFR-49138';
        $data[1]['Merchant Refund Id']      = $refund['id'];
        $data[1]['Refund Date']             = '2021-06-29 17:54:20';

        $file = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($file['local_file_path'], 'twid_recon_file.csv');

        $this->mockScroogeResponse($refund['id'], $payment['id']);

        $this->reconcile($uploadedFile, Base::TWID);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['acquirer_data']['amount'], $payment[Payment::AMOUNT] / 100);
        $this->assertEquals($paymentEntity['acquirer_data']['transaction_id'], 1234);

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

    public function testTwidReconWithLateAuthorize()
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

        $data[] = $this->testData['testTwidSuccessRecon'];

        $data[0]['Merchant Transaction Id'] = $payment['id'];

        $file = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($file['local_file_path'], 'twid_recon_file.csv');

        $this->reconcile($uploadedFile, Base::TWID);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $paymentEntity = $this->getDbEntityById('payment', $payment['public_id']);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $this->assertEquals($paymentEntity['acquirer_data']['amount'], $payment[Payment::AMOUNT] / 100);
        $this->assertEquals($paymentEntity['acquirer_data']['transaction_id'], 1234);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testTwidForceAuth()
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

        $data[] = $this->testData['testTwidSuccessRecon'];

        $data[0]['Merchant Transaction Id'] = $payment['id'];

        $file = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($file['local_file_path'], 'twid_recon_file.csv');

        $this->reconcile($uploadedFile, Base::TWID, [$payment['public_id']]);

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
        $fileData = 'S.No.,"Transaction ID","Merchant Transaction ID",Date,Brand,"Bill Value",Commission,"GST on Commission","Total Commission","Total Payable",Status,"Twid Refund Id","Merchant Refund Id","Refund Date"';

        foreach ($data as $val)
        {
            $fileData = $fileData . "\n" .implode(',', $val);
        }

        $fileData = $fileData . "\n" . ",,,,,,,,,,";
        $fileData = $fileData . "\n" . "Total,,,,,5,,,0.088,4.912,";

        return $this->createFile($fileData);
    }

    public function createUploadedFile(string $url, $fileName = 'file.csv', $mime = null): UploadedFile
    {
        $mime = 'text/csv';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
}
