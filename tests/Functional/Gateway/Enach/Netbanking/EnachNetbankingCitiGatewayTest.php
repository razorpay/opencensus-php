<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;
Use Carbon\Carbon;

use RZP\Models\Payment\Refund;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class EnachNetbankingCitiGatewayTest extends EnachNetbankingNpciGatewayTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedCitiTerminal = $this->sharedTerminal;

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');
    }

    // enach refund migration to scrooge
    public function testDebitFileReconciliationRefund()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '1',
            'error_code' => '00',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        // $response = $this->refundPayment('pay_' . $payment['id']);
        $response = $this->refundPayment('pay_' . $payment['id'], null, ['is_fta' => true]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals('pay_' . $payment['id'], $refund['payment_id']);

        // $this->assertEquals('initiated', $refund['status']);
        $this->assertEquals('created', $refund['status']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . $payment['id'], $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('UTIB0000123', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('1111111111111', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);
    }

    public function testDebitFileReconciliationRefundBankTransfer()
    {
        $this->testDebitFileReconciliationRefund();

        $channel = Channel::YESBANK;

        $content = $this->initiateTransfer(
            $channel,
            Attempt\Purpose::REFUND,
            Attempt\Type::REFUND);

        $data = $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNotNull($attempt['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);

        // Process entities
        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);

        $refund = $this->getLastEntity('refund', true);

        // $this->assertEquals(Refund\Status::PROCESSED, $refund['status']);
        $this->assertEquals(Refund\Status::CREATED, $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
        $this->assertNotNull($attempt['utr']);
    }

    public function testDebitFileReconciliation()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '1',
            'error_code' => '00',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status' => '1',
            ],
            $enach
        );
    }

    public function testDebitFileRejectResponse()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '0',
            'error_code' => '09',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals('GATEWAY_ERROR_DEBIT_FAILED', $payment['internal_error_code']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertEquals('0', $enach['status']);
    }

    public function testDebitFilePendingResponse()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '3',
            'error_code' => '00',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(29));

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('created', $payment['status']);
    }

    public function testDebitFileReconciliationAfter30Days()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '1',
            'error_code' => '00',
            'error_desc' => '',
        ];

        Carbon::setTestNow(Carbon::now()->addDays(31));

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('emandate', $payment['method']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);
    }

    protected function makeBatchDebitPayment($payment, $status)
    {
        $this->fixtures->create(
            'enach',
            [
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'amount'     => $payment['amount'],
            ]
        );

        $file = $this->getBatchDebitFile($payment, $status);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $fileContents = file($file);

        $debitResponseRow = $fileContents[1];

        $flag = substr($debitResponseRow, 153, 1);

        $errCode = substr($debitResponseRow, 154, 2);

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $file);

        $batchEntity = $this->fixtures->create('batch',
            [
                'id'          => '00000000000001',
                'type'        => 'nach',
                'sub_type'    => 'debit',
                'gateway'     => 'nach_citi',
                'total_count' => '1',
            ]);

        $paymentId = $payment['id'];

        $entries = [
            "data" => [
                Headings::ACH_TRANSACTION_CODE             =>  '67',
                Headings::CONTROL_9S                       =>  '         ',
                Headings::DESTINATION_ACCOUNT_TYPE         =>  '10',
                Headings::LEDGER_FOLIO_NUMBER              =>  '   ',
                Headings::CONTROL_15S                      =>  '               ',
                Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  'ABIJITO GUHA                            ',
                Headings::CONTROL_9SS                      =>  '17012020 ',
                Headings::CONTROL_7S                       =>  '       ',
                Headings::USER_NAME                        =>  'RAZORPAY SOFTWARE PV',
                Headings::CONTROL_13S                      =>  '             ',
                Headings::AMOUNT                           =>  '0000000300000',
                Headings::ACH_ITEM_SEQ_NO                  =>  '4764222450',
                Headings::CHECKSUM                         =>  '4081750481',
                Headings::FLAG                             =>  $flag,
                Headings::REASON_CODE                      =>  $errCode,
                Headings::DESTINATION_BANK_IFSC            =>  'HDFC0002497',
                Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  '1111111111111                      ',
                Headings::SPONSOR_BANK_IFSC                =>  'CITI000PIGW',
                Headings::USER_NUMBER                      =>  'NACH00000000013149',
                Headings::TRANSACTION_REFERENCE            =>  'CTTATAAIAA' . $paymentId . '      ',
                Headings::PRODUCT_TYPE                     =>  '10 ',
                Headings::BENEFICIARY_AADHAR_NUMBER        =>  '000000000000000',
                Headings::UMRN                             =>  'HDFC0000000010936518',
                Headings::FILLER                           =>  '       ',
            ],
            'type'        => 'nach',
            'sub_type'    => 'debit',
            'gateway'     => 'nach_citi',
        ];

        $this->runWithData($entries, $batchEntity['id']);

        return $batch;
    }

    public function testFailureDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testPartialDebitFileGeneration()
    {
        $this->markTestSkipped('not applicable');
    }

    public function testCancelEmandateTokenCiti()
    {
        $this->makeDebitPayment();

        $payment = $this->getDbLastEntity('payment');

        $fileStatuses = [
            'status'     => '1',
            'error_code' => '00',
            'error_desc' => '',
        ];

        $this->makeBatchDebitPayment($payment, $fileStatuses);

        $payment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('captured', $payment['status']);

        $transaction = $payment->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $enach = $this->getDbEntities('enach', ['payment_id' => $payment['id']])->first()->toArray();

        $this->assertArraySelectiveEquals(['status' => '1'], $enach);

        $response = $this->deleteCustomerToken('token_' . $payment['token_id'], 'cust_' . $payment['customer_id']);

        $this->assertEquals(true, $response['deleted']);

        $this->ba->adminAuth();

        $this->startTest();
    }
}
