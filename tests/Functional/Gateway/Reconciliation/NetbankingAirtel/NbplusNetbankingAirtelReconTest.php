<?php

namespace Functional\Gateway\Reconciliation\NetbankingAirtel;

use Illuminate\Http\UploadedFile;

use Razorpay\IFSC\Bank;
use RZP\Models\Batch\Status;
use RZP\Services\Mock\Scrooge;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingAirtelReconTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingAirtelReconTestData.php';

        parent::setUp();

        $this->bank = Bank::AIRP;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_airtel_terminal');
    }

    public function testPaymentReconciliation()
    {
        $this->doAuthAndCapturePayment($this->payment);
        $payment1 = $this->getDbLastPayment();
        $row1 = $this->testData['airtel'];
        $row1['PARTNER_TXN_ID'] = $payment1->getId();
        $row1['TRANSACTION_STATUS'] = 'Sale';

        $this->doAuthPayment($this->payment);
        $payment2 = $this->getDbLastPayment();
        $row2 = $this->testData['airtel'];
        $row2['PARTNER_TXN_ID'] = $payment2->getId();
        $row2['TRANSACTION_STATUS'] = 'Sale';

        $this->doAuthCaptureAndRefundPayment($this->payment);
        $refund = $this->getDbLastRefund();
        $payment3 = $this->getDbLastPayment();
        $row3 = $this->testData['airtel'];
        $row3['PARTNER_TXN_ID'] = $payment3->getId();

        $fileContents = $this->generateFile([array_keys($this->testData['airtel']), $row1, $row2, $row3]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'AirtelReconTest.csv');

        $this->ba->h2hAuth();

        $this->mockScroogeResponse($row3['TRANSACTION_ID'], $refund['id'], $refund['payment_id']);

        $this->reconcile($uploadedFile, Base::AIRTEL);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $this->assertTrue($payment1->transaction->isReconciled());
        $this->assertTrue($payment2->transaction->isReconciled());
        $this->assertTrue($refund->transaction->isReconciled());

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    private function createUploadedFile($file, $fileName): UploadedFile
    {
        $this->assertFileExists($file);

        $mimeType = 'text/csv';

        return new UploadedFile(
            $file,
            $fileName,
            $mimeType,
            null,
            true
        );
    }

    protected function generateFile(array $data): array
    {
        $fileData = '';
        foreach ($data as $row)
        {
            $fileData .= implode(',', $row);
            $fileData .= "\n";
        }

        return $this->createFile($fileData);
    }

    public function mockScroogeResponse($bankRef, $refundId, $paymentId)
    {
        $scroogeResponse = [
            'body' => [
                'data' => [
                    $bankRef => [
                        'payment_id' => PublicEntity::stripDefaultSign($paymentId),
                        'refund_id'  => PublicEntity::stripDefaultSign($refundId)
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
}
