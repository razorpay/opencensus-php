<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\JioMoney;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Gateway\Wallet\Jiomoney\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class JioMoneyReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    private $wallet = Wallet::JIOMONEY;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_jiomoney_terminal');

        $this->gateway = 'wallet_jiomoney';

        $this->fixtures->merchant->enableWallet('10000000000000', 'jiomoney');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        foreach ($payments as $paymentId)
        {
            $payment = $this->getEntityById('payment', $paymentId, true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testRefundReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $this->makePaymentsSince($createdAt, 1);

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS][ResponseFields::TXN_STATUS] = 'error';
            }
            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_jiomoney_recon')
                {
                    $wallet = $this->getDbLastEntityToArray('wallet', 'test');

                    $content['Transaction Type']    = 'Refund';
                    $content['Payment Type']        = 'Refund';
                    $content['Gross Amount']        = $this->formatAmount((-1) * $wallet['amount']);
                    $content['Net Amount']          = $this->formatAmount((-1) * $wallet['amount']);
                    $content['Merchant Ref ID']     = $wallet['refund_id'];
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $refund = $this->getDbLastEntityToArray('refund', 'test');

        $this->assertEquals(true, $refund['gateway_refunded']);

        $transactionId = $refund['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testJioMoneyFailedPaymentReconciliation()
    {
        $this->createFailedPayment();

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $paymentEntity['status']);

        $gatewayEntity = $this->getLastEntity('wallet_jiomoney', true);

        $this->assertNotNull($gatewayEntity['gateway_payment_id']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testVerifyAndRefundForAuthorizeFailedPayment()
    {
        $this->createFailedPayment();

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment = $this->capturePayment($paymentEntity['id'], $paymentEntity['amount']);

        $this->assertEquals('captured', $payment['status']);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'checkpaymentstatus')
            {
                $content[ResponseFields::RESPONSE][ResponseFields::GETREQUESTSTATUS][ResponseFields::TXN_STATUS] = 'error';
            }
            return $content;
        });

        $this->refundPayment($payment['id']);

        $refundEntity = $this->getLastEntity('refund', true);

        $wallet = $this->getLastEntity('wallet', true);

        $this->assertNotNull($wallet['refund_id']);
        $this->assertEquals('SUCCESS', $wallet['response_code']);
        $this->assertEquals('processed', $refundEntity['status']);
    }

    public function testReconAmountMismatch()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_jiomoney_recon')
                {
                    $content['Gross Amount'] = 2;
                    $content['Net Amount'] = 2;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    public function testReconPaymentIdAbsent()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'col_jiomoney_recon')
                {
                    $content['Merchant Ref ID'] = 0;
                }
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, Base::JIOMONEY);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertNull($payment['gateway_captured']);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    protected function createPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => $this->method,
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'wallet'            => $this->wallet,
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create(
                'transaction',
                ['entity_id' => $payment->getId(),
                'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create($this->method,
            [   'payment_id' => $payment->getId(),
                'action' => 'authorize',
                'wallet' => $this->gateway,
                'received' => true,
                'gateway_payment_id' => strval(random_int(11111111111, 99999999999)),
                'status_code' => '000',
            ]
        );

        return $payment->getId();
    }

    protected function createFailedPayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $this->makePaymentsSince($createdAt, 1);

        $paymentEntity = $this->getLastEntity('payment', true);

        $gatewayEntity = $this->getLastEntity('wallet_jiomoney', true);

        $this->fixtures->edit('payment', $paymentEntity['id'], ['status' => 'failed']);

        $this->fixtures->edit('wallet', $gatewayEntity['id'], ['status_code' => '501']);
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

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
