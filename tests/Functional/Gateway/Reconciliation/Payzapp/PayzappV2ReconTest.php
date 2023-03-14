<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Payzapp;

use Mockery;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Batch\Status;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PayzappV2ReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use FileHandlerTrait;
    use DbEntityFetchTrait;

    const WALLET = 'payzapp';

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->terminal = $this->fixtures->create('terminal:shared_payzapp_terminal');

        $this->gateway = Payment\Gateway::WALLET_PAYZAPP;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, self::WALLET);

        $this->app['rzp.mode'] = Mode::TEST;

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->onlyMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return 'nbplusps';
                })
            );

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Wallet', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->markTestSkipped('disabling payzapp temporarily');
    }

    public function testPaymentReconciliation()
    {
        foreach(range(0,2) as $i)
        {
            $this->doAuthAndCapturePayment($this->payment);
            $payments[$i] = $this->getDbLastPayment();
            $this->assertEquals('captured', $payments[$i]['status']);
        }

        $file = $this->generateReconFile($payments);

        $uploadedFile = $this->createUploadedFile($file, 'Payzapp_Wallet.xlsx');

        $input = [
            'key'              => '/Payzapp_Wallet.xlsx',
            'gateway'          => Recon::PAYZAPPV2,
            'attachment-count' => 1,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        $this->ba->h2hAuth();
        $this->makeRequestAndGetContent($request);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $this->assertEquals(Status::PROCESSED, $batch['status']);

        foreach(range(0,2) as $i)
        {
            $payment = $this->getDbEntityById('payment', $payments[$i]['id']);
            $this->assertTrue($payment->transaction->isReconciled());
            $this->assertEquals(450, $payment->transaction->getGatewayServiceTax());
        }
    }

    public function testUPIPaymentReconciliation()
    {
        foreach(range(0,2) as $i)
        {
            $this->doAuthAndCapturePayment($this->payment);
            $payments[$i] = $this->getDbLastPayment();
            $this->assertEquals('captured', $payments[$i]['status']);
        }

        $file = $this->generateUPIReconFile($payments);

        $uploadedFile = $this->createUploadedFile($file, 'Payzapp_UPI.xlsx');

        $this->reconcile($uploadedFile, Recon::PAYZAPPV2);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $this->assertEquals(Status::PROCESSED, $batch['status']);

        foreach(range(0,2) as $i)
        {
            $payment = $this->getDbEntityById('payment', $payments[$i]['id']);
            $this->assertTrue($payment->transaction->isReconciled());
            $this->assertEquals(200, $payment->transaction->getGatewayServiceTax());
        }
    }

    public function testReconAmountValidationFailed()
    {
        $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getDbLastPayment();
        $this->assertEquals('captured', $payment['status']);

        $payment['amount'] = 10000;

        $file = $this->generateReconFile([$payment]);

        $uploadedFile = $this->createUploadedFile($file, 'Payzapp_Wallet.xlsx');

        $this->reconcile($uploadedFile, Recon::PAYZAPPV2);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);
        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);

        $payment = $this->getDbLastPayment();

        // Transaction is not reconciled
        $this->assertFalse($payment->transaction->isReconciled());
    }

    public function testRefundReconciliation()
    {
        foreach(range(0,2) as $i)
        {
            $payments[$i] = $this->doAuthAndCapturePayment($this->payment);
            $this->assertEquals('captured', $payments[$i]['status']);
            $this->refundPayment($payments[$i]['id']);
            $payments[$i] = $this->getDbLastPayment();
            $refunds[$i] = $this->getDbLastRefund();
        }

        $file = $this->generateReconFile([$payments[0], $payments[1], $payments[2], $refunds[0], $refunds[1], $refunds[2]]);

        $uploadedFile = $this->createUploadedFile($file, 'Payzapp_Wallet.xlsx');

        $this->reconcile($uploadedFile, Recon::PAYZAPPV2);

        $response = $this->getLastEntity('batch', true);
        $this->assertEquals(Status::PROCESSED, $response['status']);

        $this->assertEquals(6, $response['total_count']);
        $this->assertEquals(6, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        foreach(range(0,2) as $i)
        {
            $refund = $this->getDbEntityById('refund', $refunds[$i]['id']);
            $this->assertTrue($refund->transaction->isReconciled());
        }
    }

    public function testUPIRefundReconciliation()
    {
        foreach(range(0,2) as $i)
        {
            $payments[$i] = $this->doAuthAndCapturePayment($this->payment);
            $this->assertEquals('captured', $payments[$i]['status']);
            $this->refundPayment($payments[$i]['id']);
            $payments[$i] = $this->getDbLastPayment();
            $refunds[$i] = $this->getDbLastRefund();
        }

        $file = $this->generateUPIReconFile([$payments[0], $payments[1], $payments[2], $refunds[0], $refunds[1], $refunds[2]]);

        $uploadedFile = $this->createUploadedFile($file, 'Payzapp_UPI.xlsx');

        $this->reconcile($uploadedFile, Recon::PAYZAPPV2);

        $response = $this->getLastEntity('batch', true);
        $this->assertEquals(Status::PROCESSED, $response['status']);

        $this->assertEquals(6, $response['total_count']);
        $this->assertEquals(6, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        foreach(range(0,2) as $i)
        {
            $refund = $this->getDbEntityById('refund', $refunds[$i]['id']);
            $this->assertTrue($refund->transaction->isReconciled());
        }
    }

    private function createUploadedFile($file, $filename): UploadedFile
    {
        $this->assertFileExists($file);

        return new UploadedFile(
            $file,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    protected function generateReconFile($content)
    {
        $reconData = [];

        foreach ($content as $entity)
        {
            $txnid  = 'txn_24603f89-5f24-4962-a2a5-8ea34fe' . random_alpha_string(5);
            $amount = $entity['amount']/100;

            if ($entity->getEntity() === Entity::PAYMENT)
            {
                $type        = 'BAT';
                $id          = Payment\Entity::stripDefaultSign($entity[Payment\Entity::ID]);
                $refundID    = '';
                $refundTxnID = '';
            }
            else if ($entity->getEntity() === Entity::REFUND)
            {
                $type        = 'CVD';
                $id          = Payment\Refund\Entity::stripDefaultSign($entity[Payment\Refund\Entity::PAYMENT_ID]);
                $refundID    = Payment\Refund\Entity::stripDefaultSign($entity[Payment\Refund\Entity::ID]);
                $refundTxnID = 'txn_24603f89-5f24-4962-a2a5-8ea34fe' . random_alpha_string(5);
            }

            $reconRow[] = [
                'MERCHANT CODE' => 'V82820',
                'TERMINAL NUMBER' => '75019911',
                'REC FMT' => $type,
                'BAT NBR' => '0',
                'CARD TYPE' => 'Payzapp Wallet',
                'CARD NUMBER' => 'Payzapp Wallet',
                'TRANS DATE' => Carbon::now()->format('m/d/Y'),
                'SETTLE DATE' => Carbon::now()->addDay()->format('m/d/Y'),
                'APPROV CODE' => $txnid,
                'INTNL AMT' => '0',
                'DOMESTIC AMT' => $amount,
                'TRAN_ID' => $txnid,
                'UPVALUE' => '',
                'MERCHANT_TRACKID' => $id,
                'MSF' => 10,
                'SERV TAX' => .5,
                'SB Cess' => 0,
                'KK Cess' => 0,
                'CGST AMT' => 2,
                'SGST AMT' => 2,
                'IGST AMT' => 0,
                'UTGST AMT' => 0,
                'Net Amount' => $amount - 0.5,
                'DEBITCREDIT_TYPE' => 'DD',
                'UDF1' => $refundTxnID,
                'UDF2' => $refundID,
                'UDF3' => $txnid,
                'UDF4' => $id,
                'UDF5' => '',
                'SEQUENCE NUMBER' => '',
                'ARN NO' => $refundTxnID,
                'INVOICE_NUMBER' => '',
                'GSTN_TRANSACTION_ID' => '',
            ];

            $reconData = $reconData + $reconRow;
        }

        return $this->writeToExcelFile($reconData, 'Payzapp_Wallet', 'files/filestore');
    }

    protected function generateUPIReconFile($content)
    {
        $reconData = [];

        foreach ($content as $entity)
        {
            $txnid = 'txn_24603f89-5f24-4962-a2a5-8ea34fe' . random_alpha_string(5);
            $amount = $entity['amount']/100;

            if ($entity->getEntity() === Entity::PAYMENT)
            {
                $type = 'PAY';
                $id   = Payment\Entity::stripDefaultSign($entity['id']);
            }
            else if ($entity->getEntity() === Entity::REFUND)
            {
                $type = 'CREDIT';
                $id   = Payment\Refund\Entity::stripDefaultSign($entity['id']);
            }

            $reconRow[] = [
                'External MID' => 'XXXXXX',
                'External TID' => '11111111',
                'UPI Merchant ID' => 'HDFCXXXXXXXXXXXX',
                'Merchant Name' => 'NATIONAL TESTING AGENCY',
                'Merchant VPA' => 'XXXX.XXXX@hdfcbank',
                'Payer VPA' => 'XXXX.XXXX@hdfcbank',
                'UPI Trxn ID' => 'HDFA2447B75A5934CD0A87D7A3DFB52DD7F',
                'Order ID' => $id,
                'Txn ref no. (RRN)' => $txnid,
                'Transaction Req Date' => '03-JUN-2022 17:03:25',
                'Settlement Date' => '04-JUN-2022 09:41:15',
                'Currency' => 'INR',
                'Transaction Amount' => $amount,
                'MSF Amount' => 10,
                'CGST AMT' => 0,
                'SGST AMT' => 0,
                'IGST AMT' => 2,
                'UTGST AMT' => 0,
                'Net Amount' => $amount - 0.5,
                'GST Invoice No' => 'DD',
                'Trans Type' => $type,
                'Pay Type' => 'P2P',
                'CR / DR' => 'DR',
                'Additional Field 1' => '',
                'Additional Field 2' => '',
                'Additional Field 3' => '',
                'Additional Field 4' => '',
                'Additional Field 5' => '',
            ];

            $reconData = $reconData + $reconRow;
        }

        return $this->writeToExcelFile($reconData, 'Payzapp_UPI', 'files/filestore');
    }

    protected function runPaymentCallbackFlowWalletPayzapp($response, &$callback = null)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        $response = $this->mockCallbackFromGateway($url, $method, $values);

        $data = $this->getPaymentJsonFromCallback($response->getContent());

        $response->setContent($data);

        return $response;
    }
}
