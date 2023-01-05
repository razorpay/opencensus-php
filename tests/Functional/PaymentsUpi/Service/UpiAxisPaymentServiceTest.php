<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;

class UpiAxisPaymentServiceTest extends UpiPaymentServiceTest
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_axis';

        $this->testData['upiAxis'] = [
            'RRN'                  => '822012050352',
            'TXNID'                => 'AXIS00090439839',
            'ORDER_ID'             => 'AiuZGLBpFIMuT3',
            'AMOUNT'               => '500.00',
            'MOBILE_NO'            => '',
            'BANKNAME'             => '',
            'MASKEDACCOUNTNUMBER'  => '',
            'IFSC'                 => '',
            'VPA'                  => 'vishnu@icici',
            'ACCOUNT_CUST_NAME'    => 'SANDIP SURESH NIKAM',
            'RESPCODE'             => '00',
            'RESPONSE'             => 'Success',
            'TRANSACTION_DATE'     => '08-AUG-18 12:29',
            'CREDITVPA'            => 'razaorpay@axis',
            'REMARKS'              => 'A',
        ];

        $this->testData['upi_axis_payment_format_v2'] = [
            'RRN'                  => '822012050352',
            'TXNID'                => 'AXIS00090439839',
            'ORDERID'              => 'AiuZGLBpFIMuT3',
            'AMOUNT'               => '500.00',
            'MOBILE_NO'            => '',
            'BANKNAME'             => '',
            'MASKEDACCOUNTNUMBER'  => '',
            'IFSC'                 => '',
            'VPA'                  => 'vishnu@icici',
            'ACCOUNT_CUST_NAME'    => 'SANDIP SURESH NIKAM',
            'RESPCODE'             => '00',
            'RESPONSE'             => 'Success',
            'TXN_DATE'             => '08-AUG-18 12:29',
            'CREDITVPA'            => 'razaorpay@axis',
            'REMARKS'              => 'A',
            'SURCHARGE'            => '',
            'TAX'                  => '',
            'DEBIT_AMOUNT'         => '500.00',
            'MDR_TAX'              => '',
            'MERCHANT_ID'          => 'AIRTELPROD0010999999',
            'UNQ_CUST_ID'          => '',
        ];
    }

    public function testPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_axis';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['upi_txn_id'] = 'IBL3aa942ae75214480b73704d09b3c1f69';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];
        $upiEntity['amount'] = $payment['amount'];
        $upiEntity['npci_reference_id'] = '227121351902';

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway,
            Entity::CPS_ROUTE       => 4,
            Entity::REFERENCE1      => 'IBL3aa942ae75214480b73704d09b3c1f69',
        ], $payment);
    }

    public function testPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_axis';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];
        $upiEntity['amount'] = $payment['amount'];

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

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent(
            $upiEntity,
            $payment,
            'U30',
            'DEBIT HAS FAILED');

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

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

        $this->doAjaxPaymentWithUps('terminal:shared_upi_axis_tpv_terminal', 'upi_axis');

        $this->gateway = 'upi_axis';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
            'amount'                => $payment['amount'],
        ];

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

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

        $this->doAjaxPaymentWithUps('terminal:shared_upi_axis_tpv_terminal', 'upi_axis');

        $this->gateway = 'upi_axis';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
            'amount'                => $payment['amount'],
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

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent(
            $upiEntity,
            $payment,
            'U30',
            'DEBIT HAS FAILED');

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

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

    public function testPaymentSuccessWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE           => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_axis')->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upi['npci_reference_id'],
            Entity::VPA             => $upi['vpa'],
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway,
            Entity::REFERENCE1      => 'AXIS00090439839',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::STATUS_CODE          => '00',
            UpiEntity::MERCHANT_REFERENCE   => $payment['id']
        ], $upi);
    }

    public function testPaymentFailureWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_axis_terminal', 'upi_axis');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE           => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_axis')
                        ->getAsyncCallbackContent($upi, $payment, 'U30', 'Debit Failed');

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $this->assertEquals(
            [
                'callBackstatusCode'        => 'U30',
                'callBackstatusDescription' => 'Debit Failed',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::REFERENCE16         => null,
            Entity::CPS_ROUTE           => 0,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::STATUS_CODE          => 'U30',
            UpiEntity::MERCHANT_REFERENCE   => $payment['id']
        ], $upi);
    }

    public function testUnexpectedPaymentWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_axis';

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_axis_pre_process_v1', 'upi_axis');
        });

        $id = str_random(12);

        $content = $this->unexpectedPaymentContent($id);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_axis');

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertEquals(
            [
                'callBackstatusCode'        => '00',
                'callBackstatusDescription' => 'Success',
                'callBacktxnId'             => 'AXIS00090439839'
            ], $response
        );

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']         => $paymentEntity['gateway'],
            $id                                    => $authorizeUpiEntity['merchant_reference']
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUpiAxisPaymentReconcilliation()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity, 'upi_axis_payment_format_v2');

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);
    }

    public function testUpiAxisUnexpectedPaymentFile()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }
        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $paymentId = $upiEntity['payment_id'];

        $upiEntity['payment_id'] = 'BB31121900923519425756';

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $this->mockServerContentFunction(function(&$content, $action) use ($paymentId)
        {
            if ($action === 'entity_fetch')
            {
                $content['entity']['payment_id'] = $paymentId;
            }
        });

        $this->createFileAndReconcile('Razorpay Software Pvt Ltd.xlsx', $entries);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($entries[0]['RRN'], $payment['reference16']);
        }
    }

    public function testUpiAxisForceAuthorizeFailedPayment()
    {
        $upiEntity = $this->getNewAxisUpiEntity('10000000000000', 'upi_axis');

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals(4, $payment['cps_route']);

        $entries[] = $this->overrideNewUpiAxisPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'Razorpay Software Pvt Ltd');

        $uploadedFile = $this->createUploadedFile($file, 'Razorpay Software Pvt Ltd.xlsx');

        // set the payment status to 'failed' and try to reconcile it with force authorise
        $this->fixtures->edit('payment', $upiEntity['payment_id'], ['status' => 'failed']);

        $this->reconcile($uploadedFile, 'UpiAxis', ['pay_' . $upiEntity['payment_id']]);

        $this->assertBatchStatus('processed');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $updatedPayment = $this->getDbEntityById('payment', $upiEntity['payment_id']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals(4, $updatedPayment['cps_route']);

        $this->assertEquals($entries[0]['VPA'], $updatedPayment['vpa']);

        $this->assertEquals('authorized', $updatedPayment['status']);
    }

    public function testUpiAxisDirectSettlementPaymentFileForceCreate()
    {
        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->gateway = 'upi_axis';

        $this->terminal->fill([
            'gateway_merchant_id' => 'shared_merchant',
        ])->saveOrFail();

        $terminal = $this->fixtures->create('terminal:direct_settlement_upi_axis_terminal');

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = [
            'payment_id'                => 'SomeUnexpectedOrderId',
            'npci_reference_id'         => '000100010001',
        ];

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === 'entity_fetch')
            {
                $content = [];
            }
        });

        $entries[] = $this->overrideUpiAxisPayment($upiEntity);

        $entries[0]['amount']                       = '600.00';

        // Adds additional columns as needed for unexpected payment creation
        $entries[0]['unexpected_payment_ref_id']    = '000100010001';
        $entries[0]['upi_merchant_id']              = 'TSTMERCHI';
        $entries[0]['upi_merchant_channel_id']      = 'TSTMERCHIAPP';

        $this->createFileAndReconcile('Razorpay Software Private Limited.xlsx', $entries);

        $payment = $this->getDbLastEntity('payment');
        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertArraySubset([
            'status'            => 'captured',
            'reference16'       => '000100010001',
            'cps_route'         => 0,
            'amount'            => $transaction['amount'],
            'transaction_id'    => substr($transaction['id'], 4),
        ], $payment->toArray());
    }

    protected function getNewAxisUpiEntity($merchantId, $gateway)
    {
        $this->testPaymentSuccess();

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals(4, $payment['cps_route']);

        $this->gateway = 'upi_axis';

        $upiEntity['npci_reference_id'] = $payment['reference16'];

        $upiEntity['payment_id'] = $payment['id'];

        return $upiEntity;
    }

    protected function overrideUpiAxisPayment(array $upiEntity)
    {
        $facade = $this->testData['upiAxis'];

        $facade['ORDER_ID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function overrideNewUpiAxisPayment(array $upiEntity)
    {
        $facade = $this->testData['upi_axis_payment_format_v2'];

        $facade['ORDERID'] = $upiEntity['payment_id'];

        $facade['RRN'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function createFileAndReconcile($fileName = '', $entries = [])
    {
        $file = $this->writeToExcelFile($entries, $fileName);

        $uploadedFile = $this->createUploadedFile($file, $fileName);

        $this->reconcile($uploadedFile, 'UpiAxis');

        $this->assertBatchStatus('processed');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    /****************************** helpers  ******************************/

    protected function unexpectedPaymentContent(string $id)
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $upi = [
            'amount'    => '60000',
            'vpa'       => 'unexpected@axisbank'
        ];

        $payment = [
            'id'        => $id,
        ];

        return $this->mockServer('upi_axis')->getAsyncCallbackContent($upi, $payment);
    }

    public function testRefundSuccess()
    {
        $this->testPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment->reload();

        $this->assertEquals('captured', $payment->getStatus());

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content['code'] = '111';
            }
        }, $this->gateway);

        // Attempt a partial refund
        $this->refundPayment($payment->getPublicId(), 100);

        $payment->reload();

        $this->assertEquals('captured', $payment->getStatus());

        $this->assertEquals(100, $payment->getAmountRefunded());

        $this->refundPayment($payment->getPublicId(), 100);

        $payment->reload();

        $this->assertEquals(200, $payment->getAmountRefunded());
    }

    public function testFullRefundSuccess()
    {
        $this->testPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $payment->reload();

        $this->assertEquals('captured', $payment->getStatus());

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content['code'] = '111';
            }
        }, $this->gateway);

        // Attempt a partial refund
        $this->refundPayment($payment->getPublicId(), $payment->getAmount());

        $payment->reload();

        $this->assertEquals('refunded', $payment->getStatus());

        $this->assertEquals($payment->getAmount(), $payment->getAmountRefunded());
    }

    public function testRefundFailure()
    {
        $payment = $this->testPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $this->mockServerGatewayContentFunction(function (& $content, $action = null)
        {
            $content['code'] = 'A79';
        });

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
    }

    public function testVerifyRefund()
    {
        $payment = $this->testPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        // Add a capture as well, just for completeness sake
        $this->capturePayment($payment->getPublicId(), $payment->getAmount());

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            $content['code'] = 'A79';
        }, $this->gateway);

        $this->refundPayment($payment->getPublicId());

        $refund = $this->getLastEntity('refund', true);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals('created', $response['status']);

        $this->resetMockServer();

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }
}
