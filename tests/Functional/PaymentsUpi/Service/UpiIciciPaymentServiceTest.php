<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use Illuminate\Http\UploadedFile;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;


class UpiIciciPaymentServiceTest extends UpiPaymentServiceTest
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = 'upi_icici';
    }

    public function testNonRearchPaymentSuccessWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upiEntity['npci_reference_id'],
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway
        ], $payment);
    }
    public function testPaymentFailureWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $upiEntity = $this->getDbLastUpi();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => 0,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE          => Flow::COLLECT,
            UpiEntity::ACTION        => 'authorize',
            UpiEntity::GATEWAY       => $this->gateway,
            UpiEntity::STATUS_CODE   => 'U30'
        ], $upiEntity->toArray());
    }

    public function testPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway,
            Entity::CPS_ROUTE       => 4,
        ], $payment);
    }

    public function testPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);
    }

    public function testTpvPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_tpv_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
        ];

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $terminal = $this->terminal;

        $merchant = $this->fixtures->merchant;

        // assert terminal is tpv
        $this->assertEquals(true, $terminal->isTpvAllowed());

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::TERMINAL_ID     => $terminal->getId(), // assert terminal id
            Entity::GATEWAY         => $this->gateway,
            Entity::CPS_ROUTE       => 4,
        ], $payment);
    }

    public function testTpvPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_tpv_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
        ];
        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $terminal = $this->terminal;

        $merchant = $this->fixtures->merchant;

        // assert terminal is tpv
        $this->assertEquals(true, $terminal->isTpvAllowed());

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);
    }

    public function testPaymentReconciliation()
    {
        $this->gateway = 'upi_icici';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $this->assertNotNull($payment['reference16']);
        }

        $this->assertBatchStatus(BatchStatus::PROCESSED);
    }

    public function testPaymentIdAbsentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['banktranid'] = '';
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testUpiIciciForceAuthorizePayment()
    {
        $this->gateway = 'upi_icici';

        $this->testData = [
            'upiIcici' => [
                'accountNumber'   => '000205025290',
                'merchantID'      => '116798',
                'merchantName'    => 'RAZORPAY',
                'subMerchantID'   => '116798',
                'subMerchantName' => 'Razorpay SUB',
                'merchantTranID'  => 'EqLhm2zHgYQ1Mx',
                'bankTranID'      => '734122607521',
                'date'            => '03/07/2020',
                'time'            => '08:27 PM',
                'amount'          => 500,
                'payerVA'         => '9619218329@ybl',
                'status'          => 'SUCCESS',
                'Commission'      => '0',
                'Net amount'      => '0',
                'Service tax'     => '0',
            ],
        ];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'authorized_at'         => null,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createIciciUploadedFile($file);

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    public function testUpiIciciRrnMismatchPayment()
    {
        $this->testData = [
            'upiIcici' => [
                'accountNumber'   => '000205025290',
                'merchantID'      => '116798',
                'merchantName'    => 'RAZORPAY',
                'subMerchantID'   => '116798',
                'subMerchantName' => 'Razorpay SUB',
                'merchantTranID'  => 'EqLhm2zHgYQ1Mx',
                'bankTranID'      => '734122607521',
                'date'            => '03/07/2020',
                'time'            => '08:27 PM',
                'amount'          => 500,
                'payerVA'         => '9619218329@ybl',
                'status'          => 'SUCCESS',
                'Commission'      => '0',
                'Net amount'      => '0',
                'Service tax'     => '0',
            ],
        ];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status' => 'failed',
                'authorized_at' => null,
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity, '123456789');

        $file = $this->writeToExcelFile($entries, 'mis_report', 'files/settlement', 'Recon MIS');

        $uploadedFile = $this->createIciciUploadedFile($file);

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    public function testMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $this->terminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // This one will not get reconciled, this is the easier way
        // we can create a recon file with multiple credits
        $payments['000000000003'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000003', 1)[0];

        // First lets create 2 payments in the system
        $payments['000000000002'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000002', 1)[0];
        $payments['000000000001'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        // Mark reconciled_at of entity fetch response for multiple rrn scenario
        $this->mockServerContentFunction(function (&$content) use ($payments)
        {
            if ($content['entity']['payment_id'] === $payments['000000000001'])
            {
                $content['entity']['reconciled_at'] =  Carbon::now(Timezone::IST)->getTimestamp();
                $content['entity']['customer_reference'] = '000000000001';
            }
            else if ($content['entity']['payment_id'] === $payments['000000000002'])
            {
                $content['entity']['reconciled_at'] =  Carbon::now(Timezone::IST)->getTimestamp();
                $content['entity']['customer_reference'] = '000000000002';
            }
            else if ($content['entity']['payment_id'] === $payments['000000000003'])
            {
                $content['entity']['customer_reference'] = '000000000003';
            }
        });

        // Order of payments is important as the last one created will reconcile first
        $uploadedFile = $this->reconcileWithMock(
            function(&$content) use ($payments)
            {
                $actualRrn = array_search($content['merchantTranID'], $payments);

                // Correct the Gateway Merchant Id in file
                $content['merchantid'] = $this->terminal->getGatewayMerchantId();

                // Correct the RRN in the recon row
                $content['bankTranID'] = $actualRrn;

                // For the third row, change the payment id so it will become multiple rrn case
                if ($actualRrn === '000000000003')
                {
                    $content['merchantTranID']  = $payments['000000000001'];
                    $content['amount']          = '500.01';
                }
            });

        $this->unlinkUpiEnity('000000000003');
        $this->unlinkUpiEnity('000000000002');
        $this->unlinkUpiEnity('000000000001');

        $this->reconcile($uploadedFile, 'UpiIcici');

        // This is the payment which must have been created
        $payment = $this->getDbLastPayment();

        // Now we can make sure that this is the new payment created
        $this->assertFalse(in_array($payment->getId(), $payments, true));

        $this->assertArraySubset([
            Entity::MERCHANT_ID => Account::DEMO_ACCOUNT,
            Entity::AMOUNT      => 50001,
            Entity::VPA         => '9619218329@ybl',
            Entity::STATUS      => Status::AUTHORIZED,
        ], $payment->toArray(), true);

        $this->assertSame('000000000003', $payment->getReference16());

        $this->assertNotEmpty($payment->transaction->getReconciledAt());

        $reconciled1 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000001']]);
        $this->assertNotEmpty($reconciled1->getReconciledAt());

        $reconciled2 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000002']]);
        $this->assertNotEmpty($reconciled2->getReconciledAt());

        // The payment with RRN
        $reconciled3 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000003']]);
        $this->assertNull($reconciled3->getReconciledAt());
    }

    protected function unlinkUpiEnity($rrn = '')
    {
        $upiEntity = $this->getDbEntity('upi', [
            'npci_reference_id' => $rrn,
        ]);

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );
    }

    public function testDifferentRrn()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // We will create only one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        $this->mockServerContentFunction(function (&$content) use ($paymentId)
        {
            if ($content['entity']['payment_id'] === $paymentId)
            {
                $content['entity']['customer_reference'] = '000000000001';
            }
        });

        // Order of payments is important as the last one created will reconcile first
        $uploadedFile = $this->reconcileWithMock(
            function(&$content)
            {
                // Change the RRN from what is saved in database
                $content['bankTranID'] = '000000000002';
            });

        $this->unlinkUpiEnity('000000000001');

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastPayment();

        // there must only be one payment
        $this->assertSame($paymentId, $payment->getId());
        // RRN must be changed to the updated one
        $this->assertSame('000000000002', $payment->getReference16());

        $this->assertNotEmpty($payment->transaction->getReconciledAt());
    }

    private function reconcileWithMock(callable $closure = null)
    {
        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(&$content, $action = null) use ($closure)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    if (is_callable($closure) === true)
                    {
                        $closure($content);
                    }
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        return $uploadedFile;
    }

    private function makeUpiIciciPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();

            $upiEntity = $this->getDbLastEntity('upi');

            $this->fixtures->edit(
                'upi',
                $upiEntity['id'],
                [
                    'npci_reference_id' => $rrn,
                    'gateway'           => 'upi_icici',
                    'vpa'               => 'test@icici',
                    'bank'              => 'icici',
                    'provider'          => 'icici'
                ]
            );
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiIciciPayment(array $override = [])
    {
        $status = $override['status'] ?? 'captured';

        $attributes = [
            'terminal_id'       => $this->terminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => $status,
            'gateway'           => $this->gateway,
            'cps_route'         => 4,
            'authorized_at'     => time(),
        ];

        $attributes = array_merge($attributes, $override);

        $payment = $this->fixtures->create('payment', $attributes);

        if ($status !== 'failed')
        {
            $transaction = $this->fixtures->create('transaction', [
                'entity_id' => $payment->getId(),
                'merchant_id' => '10000000000000'
            ]);

            $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);
        }

        $this->fixtures->create('upi', [
            'payment_id'    => $payment->getId(),
            'gateway'       => $this->gateway,
            'amount'        => $payment->getAmount(),
        ]);

        return $payment->getId();
    }

    private function createIciciUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/octet-stream';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function overrideUpiIciciPayment(array $upiEntity, $gatewayPaymentId = null)
    {
        $facade                   = $this->testData['upiIcici'];

        $facade['amount']         = $upiEntity['amount'] / 100;

        $facade['bankTranID']     = $gatewayPaymentId ?? $upiEntity['npci_reference_id'];

        $facade['merchantTranID'] = $upiEntity['payment_id'];

        return $facade;
    }

    private function assertFailedPaymentRecon()
    {
        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getLastEntity('payment', true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }
}
