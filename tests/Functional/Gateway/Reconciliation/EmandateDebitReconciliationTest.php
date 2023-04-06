<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Carbon\Carbon;

use RZP\Constants\Entity;
use Illuminate\Http\UploadedFile;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class EmandateDebitReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    const ACCOUNT_NUMBER    = '9876543210';
    const IFSC              = 'UTIB0002766';
    const NAME              = 'Test account';
    const ACCOUNT_TYPE      = 'savings';

    protected $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate('10000000000000');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $this->setMockGatewayTrue();

        $this->ba->adminAuth();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testAxisNbEmandateDebitRecon()
    {
        $this->bank = 'UTIB';

        $this->gateway = 'netbanking_axis';

        $this->createInitialPayment($this->bank);

        $debitPaymentIds = [];

        $debitPaymentIds[] = $this->createDebitPayment($this->bank, 2500);

        $debitPaymentIds[] = $this->createDebitPayment($this->bank, 3500);

        $content = [
            'type' => 'emandate_debit',
            'targets' => ['axis']
        ];

        $this->generateDebitFile($content);

        $this->mockReconContentFunction(
            function (&$content, $action = null) use ($debitPaymentIds)
            {
                if ($action === 'row_data' and $content[0] === $debitPaymentIds[1])
                {
                    $content[10] = 'REJECTED';
                    $content[11] = 'No Funds Available';
                }
            },
            null,
            ['type' => 'emandate_debit']
        );

        $fileContents = $this->generateReconFile(['type' => 'emandate_debit']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'EmandateAxis');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'          => 'reconciliation',
                'sub_type'      => 'emandate_debit',
                'gateway'       => 'EmandateAxis',
                'status'        => 'processed',
                'success_count' => 2,
            ],
            $batch
        );

        $this->assertAxisEntities($debitPaymentIds);
    }

    public function testAxisNbEmandateDebitReconPostFiveDays()
    {

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'config'     => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 13,
                    "refund_speed": "normal"
                }
            }']);

        $this->bank = 'UTIB';

        $this->gateway = 'netbanking_axis';

        $this->createInitialPayment($this->bank);

        $debitPaymentId = $this->createDebitPayment($this->bank, 2500);

        $content = [
            'type' => 'emandate_debit',
            'targets' => ['axis']
        ];

        $this->generateDebitFile($content);

        $fileContents = $this->generateReconFile(['type' => 'emandate_debit']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->fixtures->edit(
            'payment',
            $debitPaymentId,
            ['created_at' => Carbon::today(Timezone::IST)->subDays(7)->getTimestamp()]
        );

        $this->reconcile($uploadedFile, 'EmandateAxis');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'          => 'reconciliation',
                'sub_type'      => 'emandate_debit',
                'gateway'       => 'EmandateAxis',
                'status'        => 'processed',
                'success_count' => 1,
            ],
            $batch
        );

        $debitPayment = $this->getDbEntityById('payment', $debitPaymentId)->toArray();

        // Assert debit payment entity
        $this->assertEquals('authorized', $debitPayment['status']);

        // Asserts that the transaction is reconciled
        $transaction = $this->getDbEntity('transaction', ['entity_id' => $debitPaymentId])
            ->toArray();

        $this->assertEquals($debitPayment['amount'], $transaction['amount']);

        // Assert netbanking entity updates
        $gatewayPayment = $this->getDbEntity('netbanking', ['payment_id' => $debitPaymentId])
            ->toArray();

        $this->assertEquals('Success', $gatewayPayment['status']);

        $transaction = $this->getDbEntityById('transaction', $debitPayment['transaction_id'])->toArray();

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testAxisNbEmandateDebitReconDuplicateUpload()
    {
        $this->bank = 'UTIB';

        $this->gateway = 'netbanking_axis';

        $this->createAxisInitialPaymentViaFixtures();

        $successDebitPayment = $this->createAxisDebitPaymentViaFixtures(3500);

        $fileContents = $this->generateReconFile(['type' => 'emandate_debit']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'EmandateAxis');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'          => 'reconciliation',
                'sub_type'      => 'emandate_debit',
                'gateway'       => 'EmandateAxis',
                'status'        => 'processed',
                'success_count' => 1,
            ],
            $batch
        );

        // Generate and upload the same file twice
        $fileContents = $this->generateReconFile(['type' => 'emandate_debit']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'EmandateAxis');

        $transactions = $this->getDbEntities('transaction', ['entity_id' => $successDebitPayment['id']]);

        // Assert that only one transaction is created
        // and thus the payment is not authorized twice if the same file is
        // uploaded twice
        $this->assertCount(1, $transactions);

        $this->assertArraySelectiveEquals(
            [
                'type'          => 'reconciliation',
                'sub_type'      => 'emandate_debit',
                'gateway'       => 'EmandateAxis',
                'status'        => 'processed',
                'success_count' => 1,
            ],
            $batch
        );
    }

    protected function createAxisInitialPaymentViaFixtures()
    {
        $this->bank = 'UTIB';

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0])->toArray();

        $token = $this->fixtures->create('token:emandate_confirmed', ['bank' => $this->bank])->toArray();

        $regPayment = $this->fixtures->create(
            'payment:emandate_registration_captured',
            [
                'bank'        => $this->bank,
                'order_id'    => $order['id'],
                'token_id'    => $token['id'],
                'gateway'     => 'netbanking_axis',
                'terminal_id' => 'NAxRecurringTl',
            ]
        )->toArray();

        $transaction = $this->fixtures->create(
            'transaction:emandate_registration',
            [
                'entity_id' => $regPayment['id']
            ]
        )->toArray();

        return $this->fixtures->edit('payment', $regPayment['id'], ['transaction_id' => $transaction['id']])->toArray();
    }

    protected function createAxisDebitPaymentViaFixtures($amount)
    {
        $this->bank = 'UTIB';

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $amount])->toArray();

        $token = $this->getDbLastEntity('token')->toArray();

        $payment = $this->fixtures->create(
            'payment:emandate_debit',
            [
                'amount'         => $amount,
                'bank'           => $this->bank,
                'order_id'       => $order['id'],
                'token_id'       => $token['id'],
                'gateway'        => 'netbanking_axis',
                'terminal_id'    => 'NAxRecurringTl',
                'recurring_type' => 'auto',
                'created_at'     => Carbon::today(Timezone::IST)->addHours(8)->getTimestamp()
            ]
        )->toArray();

        $this->createNetbankingEntity($payment);

        return $payment;
    }

    protected function createNetbankingEntity($payment)
    {
        return $this->fixtures->create(
            'netbanking:emandate_debit',
            [
                'payment_id' => $payment['id'],
                'amount'     => $payment['amount'],
                'bank'       => $this->bank,
            ]
        )->toArray();
    }

    protected function createInitialPayment($bank)
    {
        $payment = $this->getPayment($bank);

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);

        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        return $this->doAuthPayment($payment);
    }

    protected function createDebitPayment($bank, $amount)
    {
        $token = $this->getLastEntity(Entity::TOKEN, true);
        $order = $this->fixtures->create('order:emandate_order', ['amount' => $amount]);

        $payment = $this->getPayment($bank);

        $payment['amount']   = $amount;
        $payment['token']    = $token['id'];
        $payment['order_id'] = $order->getPublicId();

        $paymentId = $this->doS2SRecurringPayment($payment)['razorpay_payment_id'];

        $this->fixtures->stripSign($paymentId);

        // Setting created at to 8 am. Payments for debit are picked from 9 to 9 cycle
        $this->fixtures->edit(
                               'payment',
                               $paymentId,
                               ['created_at' => Carbon::today(Timezone::IST)->addHours(8)->getTimestamp()]
                             );

        return $paymentId;
    }

    protected function generateDebitFile($content = [])
    {
        $this->ba->cronAuth();

        $request = [
            'url'     => '/gateway/files',
            'content' => $content,
            'method'  => 'POST'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getPayment($bank)
    {
        $payment = $this->getEmandateNetbankingRecurringPaymentArray($bank);

        $payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
            'account_type'      => self::ACCOUNT_TYPE,
        ];

        unset($payment[Entity::CARD]);

        return $payment;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function assertAxisEntities(array $debitPaymentIds)
    {
        $this->assertSuccessDebitPayment($debitPaymentIds[0]);
        $this->assertFailureDebitPayment($debitPaymentIds[1]);
    }

    protected function assertSuccessDebitPayment($debitPaymentId)
    {
        $debitPayment = $this->getDbEntityById('payment', $debitPaymentId)->toArray();

        // Assert debit payment entity
        $this->assertEquals('captured', $debitPayment['status']);

        // Asserts that the transaction is reconciled
        $transaction = $this->getDbEntity('transaction', ['entity_id' => $debitPaymentId])
            ->toArray();

        $this->assertEquals($debitPayment['amount'], $transaction['amount']);

        // Assert netbanking entity updates
        $gatewayPayment = $this->getDbEntity('netbanking', ['payment_id' => $debitPaymentId])
            ->toArray();

        $this->assertEquals('Success', $gatewayPayment['status']);

        $transaction = $this->getDbEntityById('transaction', $debitPayment['transaction_id'])->toArray();

        $this->assertNotNull($transaction['reconciled_at']);
    }

    protected function assertFailureDebitPayment($debitPaymentId)
    {
        $debitPayment = $this->getDbEntityById('payment', $debitPaymentId)->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'              => 'failed',
                'amount'              => 3500,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE',
                'error_description'   => 'Your payment could not be completed due to insufficient account balance. Try again with another account.'
            ],
            $debitPayment
        );

        // Assert debit payment entity
        $this->assertEquals('failed', $debitPayment['status']);

        // Asserts that the transaction is reconciled
        $transaction = $this->getDbEntity('transaction', ['entity_id' => $debitPaymentId]);

        $this->assertNull($transaction);

        $gatewayPayment = $this->getDbEntity('netbanking', ['payment_id' => $debitPaymentId])
            ->toArray();

        $this->assertEquals('REJECTED', $gatewayPayment['status']);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        $result = $this->submitPaymentCallbackRedirect($data);
        return $result;
    }
}
