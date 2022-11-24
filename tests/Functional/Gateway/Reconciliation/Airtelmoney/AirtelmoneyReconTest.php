<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Airtelmoney;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Services\Mock\Scrooge;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class AirtelmoneyReconTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    protected $method = Payment\Method::WALLET;

    protected $wallet = Wallet::AIRTELMONEY;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_airtelmoney_terminal');

        $this->gateway = Payment\Gateway::WALLET_AIRTELMONEY;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testPaymentReconciliation()
    {
        $payments = $this->makeAirtelmoneyPaymentSince();

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::AIRTEL);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $wallets = $this->getEntities('wallet', [], true);

        foreach ($wallets['items'] as $id => $wallet)
        {
            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
            $this->assertNotNull($transaction['reconciled_type']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testAirtelMoneyForceAuthorizePayment()
    {
        $payments = $this->makeAirtelmoneyPaymentSince();

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $forceAuthPayments = [];

        // set the payment status as 'failed'
        foreach ($payments as $id => $payment)
        {
            $this->fixtures->payment->edit($payment,
                [
                    'status' => 'failed',
                    'error_code' => 'BAD_REQUEST_ERROR',
                ]);

            $updatedPayment = $this->getEntityById('payment', $payment, true);

            $this->assertEquals('failed', $updatedPayment['status']);

            $forceAuthPayments[] = $updatedPayment['id'];
        }

        // Pass the failed payments in force auth field
        $this->reconcile($uploadedFile, Recon::AIRTEL, $forceAuthPayments);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $wallets = $this->getEntities('wallet', [], true);

        foreach ($wallets['items'] as $id => $wallet)
        {
            $payment = $this->getEntityById('payment', $payments[$id], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $this->assertEquals('authorized', $payment['status']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
            $this->assertNotNull($transaction['reconciled_type']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testReconAmountValidationFailed()
    {
        $payment = $this->makeAirtelmoneyPaymentSince(1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_airtel_recon')
                {
                    // Setting amount to 100 will cause payment amount validation to fail
                    $content['ORIGINAL_INPUT_AMT']      = '100';
                    $content['NET_AMOUNT_PAYABLE_CR_']  = '100';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::AIRTEL);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testReconPaymentIdAbsent()
    {
        $payment = $this->makeAirtelmoneyPaymentSince(1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_airtel_recon')
                {
                    $content['PARTNER_TXN_ID'] = '';
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::AIRTEL);

        $response = $this->getLastEntity('batch', true);

        // Assert that the payment was not reconciled
        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(0, $response['success_count']);
        $this->assertEquals(1, $response['failure_count']);

        $payment = $this->getEntityById('payment', $payment, true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $response['status']);
    }

    public function testRefundReconciliation()
    {
        $payment = $this->makeAirtelmoneyPaymentSince(1)[0];

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_payment_airtel_recon')
                {
                    $gatewayRefund = $this->getDbLastEntityToArray('wallet', 'test');

                    $content['REF_TXN_NO_ORG']          = $content['TRANSACTION_ID'];
                    $content['TRANSACTION_STATUS']      = 'Refund';
                    $content['NET_AMOUNT_PAYABLE_DR_']  = $gatewayRefund['amount'];
                    $content['NET_AMOUNT_PAYABLE_CR_']  = '';
                    $content['TRANSACTION_ID']          = $gatewayRefund['gateway_payment_id'];
                }
            });

        $gatewayRefund = $this->getDbLastEntityToArray('wallet');

        $this->mockScroogeResponse($gatewayRefund['gateway_payment_id'], $refund['id'], $refund['payment_id']);

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Recon::AIRTEL);

        $response = $this->getDbLastEntity('batch');

        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $refund = $this->getDbLastRefund();

        $this->assertNotNull($refund->transaction['reconciled_at']);

        $this->assertEquals(Status::PROCESSED, $response['status']);
    }

    protected function makeAirtelmoneyPaymentSince($count = 3)
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, $count);

        foreach ($payment as $paymentId)
        {
            $this->fixtures->edit('payment', $paymentId,
                                  [
                                      'wallet' => $this->wallet,
                                  ]);

            $this->fixtures->create($this->method,
                                    [
                                        'payment_id' => $paymentId,
                                        'action' => 'authorize',
                                        'wallet' => $this->gateway,
                                        'gateway_payment_id' => uniqid(),
                                    ]);
        }

        return $payment;
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'text/csv';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
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
