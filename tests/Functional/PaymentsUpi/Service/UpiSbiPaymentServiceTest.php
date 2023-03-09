<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Account;

class UpiSbiPaymentServiceTest extends UpiPaymentServiceTest
{

    public function testSbiWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_sbi_terminal', 'upi_sbi');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_sbi_pre_process_v1', 'upi_sbi');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_sbi')->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_sbi');

        $this->assertEquals(
            [
                'status' => 'SUCCESS',
                'pspRefNo' => $payment['id'],
                'message' => 'Request Processed Successfully',
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset(
            [
                Entity::STATUS          => Status::AUTHORIZED,
                Entity::GATEWAY         => 'upi_sbi',
                Entity::TERMINAL_ID     => $this->terminal->getId(),
                Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    public function testSbiWithApiPaymentWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->gateway = 'upi_sbi';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_sbi_pre_process_v1', 'upi_sbi');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_sbi')->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_sbi');

        $this->assertEquals(
            [
                'status' => 'SUCCESS',
                'pspRefNo' => $payment['id'],
                'message' => 'Request Processed Successfully',
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset(
            [
                Entity::STATUS          => Status::AUTHORIZED,
                Entity::GATEWAY         => 'upi_sbi',
                Entity::TERMINAL_ID     => $this->terminal->getId(),
                Entity::CPS_ROUTE       => Entity::API,
            ], $payment
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNotNull($upiEntity);
    }

    public function testUpiSbiUpdatePostReconSuccess()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_sbi';

        $this->makeUpiSbiPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $payment->getId();

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_recon_sqs_update_' . $this->gateway, 'on');
        });

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotEmpty($transactionEntity['reconciled_at']);

        $this->assertTrue($response['success']);
    }

    public function testUpiSbiUpdatePostReconFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_sbi';

        $this->makeUpiSbiPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $content = $this->getDefaultUpiPostReconArray();

        $content['payment_id'] = $payment->getId();

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $this->mockServerRequestFunction(
            function (&$content)
            {
                $content['entity_fetch_failure'] = true;
            }
        );

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_recon_sqs_update_' . $this->gateway, 'on');
        });

        $this->makeRequestAndCatchException
        (
            function() use ($content)
            {
                $this->makeUpdatePostReconRequestAndGetContent($content);
            },
            Exception\BadRequestException::class,
            'received wrong entity from Upi Payment Service'
        );

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNull($transactionEntity['reconciled_at']);

    }

    public function testPaymentSbiReconciliation(){

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_sbi';

        $this->makeUpiSbiPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $this->mockReconContentFunction(function(& $content, $action = null) {
            if ($action === 'sbi_recon')
            {
                $content[0]['Customer Ref No.']          = '227121351902';
            }
        });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUpsUploadedFile(
            $fileContents['local_file_path'],
            $fileContents['local_file_path'],
            'application/octet-stream');

        $this->reconcile($uploadedFile, 'UpiSbi');

        $payment = $this->getDbEntity('payment', ['id' => $payment['id']]);

        $this->assertEquals(true, $payment['gateway_captured']);

        $this->assertEquals($payment['reference16'], '227121351902');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiSbi',
                'status'          => 'processed',
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testSbiForceAuthorizePayment()
    {
        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_sbi';

        $this->makeUpiSbiPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastEntity('payment');

        $this->fixtures->payment->edit($payment->getId(),
            [
                'status'                => 'failed',
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
                'authorized_at'         => NULL,
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $this->assertNull($payment['reference16']);

        $this->mockReconContentFunction( function(& $content, $action = null) {
            if ($action === 'sbi_recon')
            {
                $content[0]['Customer Ref No.']          = '227121351902';
                $content[0]['Payer Virtual Address']     = 'vishnu@icici';
            }
        });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUpsUploadedFile(
            $fileContents['local_file_path'],
            $fileContents['local_file_path'],
            'application/octet-stream');

        $this->reconcile($uploadedFile, 'UpiSbi', ['pay_'. $payment['id']]);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertEquals('227121351902', $updatedPayment['reference16']);

        $this->assertEquals('vishnu@icici', $updatedPayment['vpa']);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    private function makeUpiSbiPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiSbiPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiSbiPayment()
    {
        $attributes = [
            'terminal_id'       => $this->terminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
            'cps_route'         => Entity::UPI_PAYMENT_SERVICE,
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction',
            ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        return $payment->getId();
    }

    public function testSbiUnexpectedPaymentWithApiPreProcess()
    {
        $this->ba->publicAuth();

        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_sbi';

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_sbi_pre_process_v1', 'upi_sbi');
        });

        $content = $this->mockServer('upi_sbi')
            ->getUnexpectedAsyncCallbackContent('success',
                [
                    'payerVPA' => 'unexpected@v2contract'
                ]
            );

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_sbi');

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertEquals(
            [
                'status' => 'SUCCESS',
                'pspRefNo' => $content['payment_id'],
                'message' => 'Request Processed Successfully',
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
            $authorizeUpiEntity['gateway_data']    => "{\"addInfo2\":\"7971807546\"}"
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }
}
