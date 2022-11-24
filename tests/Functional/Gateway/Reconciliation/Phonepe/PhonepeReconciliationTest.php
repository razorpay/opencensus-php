<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\Phonepe;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch\Status;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class PhonepeReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    protected $wallet = Wallet::PHONEPE;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_phonepe_terminal');

        $this->gateway = Payment\Gateway::WALLET_PHONEPE;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);
    }

    public function testPaymentReconciliation()
    {
        $paymentId = $this->createPayment($this->gateway);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], $this->gateway);

        $fileContents = $this->generateFile($this->wallet, ['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile, studly_case($this->wallet));

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['providerReferenceId']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testRefundReconciliation()
    {
        $paymentId = $this->createPayment($this->gateway);

        $this->fixtures->edit('payment', $paymentId,
            [
                'wallet' => $this->wallet,
            ]);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], $this->gateway);

        $this->refundPayment('pay_' . $paymentId);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                $gatewayAction = 'col_payment_'.$this->gateway.'_recon';

                if ($action === $gatewayAction)
                {
                    $refund = $this->getDbLastEntityToArray('refund', 'test');

                    $content[ReconFields::PAYMENT_TYPE]      = 'REFUND';
                    $content[ReconFields::RZP_ID]            = $refund['id'];
                    $content[ReconFields::PHONEPE_ID]        = 'P00000000001122334455';
                    $content[ReconFields::AMOUNT]            = -500;
                }
            });

        $fileContents = $this->generateFile($this->wallet, ['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile, studly_case($this->wallet));

        $response = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $response['total_count']);
        $this->assertEquals(1, $response['success_count']);
        $this->assertEquals(0, $response['failure_count']);

        $refund = $this->getLastEntity('refund', true);

        $transactionId = $refund['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PROCESSED, $response['status']);

        $this->assertEquals('N0000012345', $refund['reference1']);
    }

    public function testReconTaxCalculation()
    {
        $paymentId = $this->createPayment($this->gateway);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], $this->gateway);

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $gatewayAction = 'col_payment_'.$this->gateway.'_recon';

                if ($action === $gatewayAction)
                {
                    $content[ReconFields::FEE] = -1.0001;
                    $content[ReconFields::SGST] = -0.0001;
                    $content[ReconFields::CGST] = -0.01;
                    $content[ReconFields::IGST] = -0.1333;
                }
            });

        $fileContents = $this->generateFile($this->wallet, ['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile, studly_case($this->wallet));

        $gatewayEntity = $this->getDbLastEntity('mozart');

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['providerReferenceId']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertEquals(114, $transactionEntity['gateway_fee']);
        $this->assertEquals(14, $transactionEntity['gateway_service_tax']);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testPhonepeAmountMismatch()
    {
        $paymentId = $this->createPayment($this->gateway);

        $payment = $this->getDbLastEntity('payment');

        $this->createMozartEntity($paymentId, $payment['amount'], $this->gateway);

        $this->mockReconContentFunction(
            function(& $content, $action = '')
            {
                $gatewayAction = 'col_payment_'.$this->gateway.'_recon';

                if ($action === $gatewayAction)
                {
                    $content[ReconFields::AMOUNT] = 1;
                }
            });

        $fileContents = $this->generateFile($this->wallet, ['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile( $uploadedFile, studly_case($this->wallet));

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    protected function createMozartEntity($paymentId, $amount, $gateway)
    {
        $mozartAttributes = [
            'payment_id' => $paymentId,
            'gateway'    => $gateway,
            'amount'     => $amount,
            'raw'        => json_encode(['payment_id' => $paymentId,'providerReferenceId' => 'P00000000001122334455']),
            'action'     => 'authorize',
        ];

        $mozart = $this->fixtures->create('mozart', $mozartAttributes);

        return $mozart;
    }

    protected function generateFile($bank, $input)
    {
        $gateway = 'wallet_' . $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createUploadedFile($file)
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
}
