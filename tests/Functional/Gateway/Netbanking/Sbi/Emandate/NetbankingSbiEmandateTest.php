<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Refund;
use RZP\Models\FileStore\Type;
use RZP\Gateway\Netbanking\Sbi;
use RZP\Models\FileStore\Format;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Exception\GatewayTimeoutException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

class NetbankingSbiEmandateTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use EmandateSbiTestTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;

    protected $payment;

    const ACCOUNT_NUMBER    = '12345678901234';
    const IFSC              = 'SBIN0000001';
    const NAME              = 'Test account';
    const ACCOUNT_TYPE      = 'savings';

    protected function setUp(): void
    {
        $this->gateway = Payment\Gateway::NETBANKING_SBI;

        $this->testDataFilePath = __DIR__.'/NetbankingSbiEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_sbi_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('SBIN');

        $this->payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
            'account_type'      => self::ACCOUNT_TYPE,
        ];

        unset($this->payment[Entity::CARD]);

        $this->setMockGatewayTrue();

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testEmandateInitialPayment()
    {
        $payment = $this->createRegistrationPayment();

        $this->assertArraySelectiveEquals(
            [
                Payment\Entity::AMOUNT => 0,
                Payment\Entity::STATUS => Payment\Status::AUTHORIZED,
            ],
            $payment
        );

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $expiredAt = Carbon::createFromTimestamp($payment['created_at'])->addYears(10)->getTimestamp();

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
                TokenEntity::EXPIRED_AT       => $expiredAt,
            ],
            $token
        );

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertNotNull($netbanking[NetbankingEntity::BANK_PAYMENT_ID]);

        $this->assertTrue($netbanking[NetbankingEntity::RECEIVED]);

        $this->assertEquals(Sbi\Status::SUCCESS, $netbanking[NetbankingEntity::STATUS]);
    }

    public function testEmandateInitialPaymentLateAuth()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                throw new GatewayTimeoutException('Gateway timed out');
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->authorizedFailedPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertArraySelectiveEquals(
            [
                Payment\Entity::AMOUNT => 0,
                Payment\Entity::STATUS => Payment\Status::AUTHORIZED,
            ],
            $payment
        );

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $expiredAt = Carbon::createFromTimestamp($payment['created_at'])->addYears(10)->getTimestamp();

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
                TokenEntity::EXPIRED_AT       => $expiredAt,
            ],
            $token
        );

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertNotNull($netbanking[NetbankingEntity::BANK_PAYMENT_ID]);

        $this->assertTrue($netbanking[NetbankingEntity::RECEIVED]);

        $this->assertEquals(Sbi\Status::SUCCESS, $netbanking[NetbankingEntity::STATUS]);

    }

    public function testEmandateInitialPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                $content[Sbi\ResponseFields::MANDATE_SBI_STATUS]      = Sbi\Status::FAILURE;
                $content[Sbi\ResponseFields::MANDATE_SBI_REF]         = '';
                $content[Sbi\ResponseFields::MANDATE_SBI_DESCRIPTION] = 'failed';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $token = $this->getDbLastEntityToArray(Entity::TOKEN);

        $this->assertNull($token[TokenEntity::RECURRING_STATUS]);
        $this->assertNull($token[TokenEntity::GATEWAY_TOKEN]);

        $netbanking = $this->getDbLastEntityToArray(Entity::NETBANKING);
         $this->assertEquals(Sbi\Status::FAILURE, $netbanking[NetbankingEntity::STATUS]);
    }

    public function testPaymentIdMismatch()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                $content[Sbi\ResponseFields::MANDATE_PAYMENT_ID] = 'ABCD1234567890';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $paymentEntity[Payment\Entity::STATUS]);
    }

    public function testPaymentVerify()
    {
        $payment = $this->createRegistrationPayment();

        $verify = $this->verifyPayment($payment['public_id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testRegisterRecon()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerPayments[] = [
            'payment'       => $this->createRegistrationPayment(),
            'status'        => 'FAILURE',
            'umrn'          => '',
            'return_reason' => 'Invalid Account',
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $batch = $this->uploadBatchFile($registerSuccessFile, 'register');
        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $batch = $this->getEntityById('batch', $batch['id'], true);
        $this->assertEquals('processed', $batch['status']);

        $registerFailureFile = $this->getRegisterFailureCsv($registerPayments);
        $batch = $this->uploadBatchFile($registerFailureFile, 'register');
        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $batch = $this->getEntityById('batch', $batch['id'], true);
        $this->assertEquals('processed', $batch['status']);

        $this->assertRegistrationDetails($registerPayments);
    }

    public function testRegisterReconInvalidAccNo()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111',
            'accNo'   => '12345678900000'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);

        $batch = $this->uploadBatchFile($registerSuccessFile, 'register');

        $this->assertEquals('emandate', $batch['type']);

        $this->assertEquals('created', $batch['status']);

        $batch = $this->getEntityById('batch', $batch['id'], true);

        $this->assertEquals('partially_processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $registerPayments[0]['payment']['id']);

        $token = $payment->getGlobalOrLocalTokenEntity();

        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $registerPayments[0]['payment']['id']]);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('initiated', $token['recurring_status']);

        $this->assertNull($token['gateway_token']);

        $this->assertNull($successNetbanking['si_status']);

        $this->assertNull($successNetbanking['si_token']);
    }

    public function testRegisterReconZeroPaddedAccNo()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111',
            'accNo'   => '0012345678901234'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);

        $batch = $this->uploadBatchFile($registerSuccessFile, 'register');

        $this->assertEquals('emandate', $batch['type']);

        $this->assertEquals('created', $batch['status']);

        $successPayment = $this->getDbEntityById('payment', $registerPayments[0]['payment']['id']);

        $successToken = $successPayment->getGlobalOrLocalTokenEntity();

        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $registerPayments[0]['payment']['id']]);

        $this->assertEquals('captured', $successPayment['status']);

        $this->assertEquals('confirmed', $successToken['recurring_status']);

        $this->assertNotNull($successToken['gateway_token']);

        $this->assertEquals('confirmed', $successNetbanking['si_status']);

        $this->assertNotNull($successNetbanking['si_token']);

        $this->assertTrue($successNetbanking['received']);

        $this->assertTrue($successPayment->transaction->isReconciled());

        $batch = $this->getEntityById('batch', $batch['id'], true);

        $this->assertEquals('processed', $batch['status']);
    }

    public function testRegisterReconForLateAuth()
    {
        $this->testEmandateInitialPaymentFailure();

        $registerPayments[] = [
            'payment' => $this->getDbLastEntity('payment'),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);

        $batch = $this->uploadBatchFile($registerSuccessFile, 'register');
        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $batch = $this->getEntityById('batch', $batch['id'], true);
        $this->assertEquals('processed', $batch['status']);

        $successPayment = $this->getDbEntityById('payment', $registerPayments[0]['payment']['id']);
        $successToken = $successPayment->getGlobalOrLocalTokenEntity();
        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $registerPayments[0]['payment']['id']]);

        $this->assertEquals('captured', $successPayment['status']);
        $this->assertEquals('confirmed', $successToken['recurring_status']);
        $this->assertNotNull($successToken['gateway_token']);
        $this->assertEquals('confirmed', $successNetbanking['si_status']);
        $this->assertNotNull($successNetbanking['si_token']);
        $this->assertTrue($successNetbanking['received']);
        $this->assertTrue($successPayment->transaction->isReconciled());
        $this->assertEquals($successPayment['terminal_id'], $successToken['terminal_id']);
    }

    public function testEmandateDebit()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $this->uploadBatchFile($registerSuccessFile, 'register');

        $token = $this->getLastEntity('token', true);

        $debitPayment = $this->createSecondReccuringPayment($token);

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit('payment', $debitPayment['id'], ['created_at' => $createdAt]);

        $content = $this->generateDebitGatewayFile();

        $this->assertEquals(1, count($content['items']));
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => Type::SBI_EMANDATE_DEBIT,
            'entity_type' => Entity::GATEWAY_FILE,
            'entity_id'   => $content['id'],
            'extension'   => Format::TXT,
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testDebitFileRecon()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $this->uploadBatchFile($registerSuccessFile, 'register');

        $token = $this->getLastEntity('token', true);

        $debitPayments[] = [
            'payment' => $this->createSecondReccuringPayment($token),
            'status'  => 'Success',
        ];

        $debitPayments[] = [
            'payment'       => $this->createSecondReccuringPayment($token),
            'status'        => 'REJECTED',
            'return_reason' => 'Mandate does not Exist / Expired',
        ];

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        foreach ($debitPayments as $entry)
        {
            $this->fixtures->edit('payment', $entry['payment']['id'], ['created_at' => $createdAt]);
        }

        $this->generateDebitGatewayFile();

        $batch = $this->uploadDebitBatchFile($debitPayments);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $this->assertDebitDetails($debitPayments);
    }

    public function testDebitFileReconFailedPayments()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $this->uploadBatchFile($registerSuccessFile, 'register');

        $token = $this->getLastEntity('token', true);

        $payment1 = $this->createSecondReccuringPayment($token);

        $debitPayments[] = [
            'payment' => $payment1,
            'status'  => 'Success',
        ];

        $payment2 = $this->createSecondReccuringPayment($token);

        $debitPayments[] = [
            'payment'       => $payment2,
            'status'        => 'REJECTED',
            'return_reason' => 'ACCT HAS HOLD. INSUFFICIENT FREE BAL FOR TXN',
        ];

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        foreach ($debitPayments as $entry)
        {
            $this->fixtures->edit('payment', $entry['payment']['id'], ['created_at' => $createdAt]);
        }

        $this->generateDebitGatewayFile();

        $createdAt = Carbon::today(Timezone::IST)->subDays(31)->getTimestamp();

        $this->fixtures->edit('payment', $payment1['id'], ['created_at' => $createdAt]);
        $this->fixtures->edit('payment', $payment2['id'], ['created_at' => $createdAt - 10]);

        $this->timeoutOldPayment();

        $pay1 = $this->getDbEntityById('payment', $payment1['id']);
        $pay2 = $this->getDbEntityById('payment', $payment2['id']);

        $this->assertEquals('failed', $pay1['status']);
        $this->assertEquals('failed', $pay2['status']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_TIMED_OUT', $pay1['internal_error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_TIMED_OUT', $pay2['internal_error_code']);

        $batch = $this->uploadDebitBatchFile($debitPayments);

        $pay1 = $this->getDbEntityById('payment', $payment1['id']);
        $pay2 = $this->getDbEntityById('payment', $payment2['id']);

        $this->assertEquals(null, $pay1['internal_error_code']);
        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE', $pay2['internal_error_code']);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $this->assertDebitDetails($debitPayments, 'captured', 'ACCT HAS HOLD. INSUFFICIENT FREE BAL FOR TXN');
    }

    // Use case where sbi appends additional 0s to the account number
    public function testDebitFileReconWithModifiedAccNo()
    {
        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $this->uploadBatchFile($registerSuccessFile, 'register');

        $token = $this->getLastEntity('token', true);

        $debitPayments[] = [
            'payment' => $this->createSecondReccuringPayment($token),
            'status'  => 'Success',
            'AccNo'   => '0012345678901234',
        ];

        // setting created at to 8am. Payments are picked from 9 to 9 cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        foreach ($debitPayments as $entry)
        {
            $this->fixtures->edit('payment', $entry['payment']['id'], ['created_at' => $createdAt]);
        }

        $this->generateDebitGatewayFile();

        $batch = $this->uploadDebitBatchFile($debitPayments);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('processed', $batch['status']);

        $successPayment = $this->getDbEntityById('payment', $debitPayments[0]['payment']['id']);
        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $debitPayments[0]['payment']['id']]);

        $this->assertEquals('captured', $successPayment['status']);
        $this->assertTrue($successNetbanking['received']);
        $this->assertEquals('Success', $successNetbanking['status']);
        $this->assertTrue($successPayment->transaction->isReconciled());
    }

    public function testEmandateRefund()
    {
        $this->testDebitFileRecon();

        $payment = $this->getEntities('payment', ['status' => 'captured', 'amount' => 3000, 'count' => 1], true);

        $response = $this->refundPayment($payment['items'][0]['id'],200,
            ["bank_account" =>["ifsc_code" => "SBIN0000001", "account_number" => "12345678901234", "beneficiary_name" =>"test"]]);

        $refund  = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);

        $this->assertEquals($payment['items'][0]['id'], $refund['payment_id']);

        $fundTransferAttempt  = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fundTransferAttempt['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['items'][0]['id'], 4), $fundTransferAttempt['narration']);

        $this->assertEquals('yesbank', $fundTransferAttempt['channel']);

        $bankAccount = $this->getLastEntity('bank_account', true);

        $this->assertEquals('SBIN0000001', $bankAccount['ifsc_code']);

        $this->assertEquals('test', $bankAccount['beneficiary_name']);

        $this->assertEquals('12345678901234', $bankAccount['account_number']);

        $this->assertEquals('refund', $bankAccount['type']);

        $channel = Channel::YESBANK;

        $this->initiateTransfer(
            $channel,
            Attempt\Purpose::REFUND,
            Attempt\Type::REFUND);

        $this->reconcileOnlineSettlements($channel, false);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertNotNull($attempt['utr']);

        $this->assertEquals(Attempt\Status::PROCESSED, $attempt[Attempt\Entity::STATUS]);

        // Process entities

        $this->reconcileEntitiesForChannel($channel);

        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(1, $refund['attempts']);

        $this->assertNotNull($attempt['utr']);
    }

    protected function assertRegistrationDetails($entities)
    {
        $successPayment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);
        $successToken = $successPayment->getGlobalOrLocalTokenEntity();
        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $entities[0]['payment']['id']]);

        $expiredAt = Carbon::createFromTimestamp($successPayment['created_at'])->addYears(10)->getTimestamp();

        $this->assertEquals('captured', $successPayment['status']);
        $this->assertEquals('confirmed', $successToken['recurring_status']);
        $this->assertNotNull($successToken['gateway_token']);
        $this->assertEquals($successToken['expired_at'], $expiredAt);
        $this->assertEquals('confirmed', $successNetbanking['si_status']);
        $this->assertNotNull($successNetbanking['si_token']);
        $this->assertTrue($successNetbanking['received']);
        $this->assertTrue($successPayment->transaction->isReconciled());

        $failurePayment = $this->getDbEntityById('payment', $entities[1]['payment']['id']);
        $rejectToken = $failurePayment->getGlobalOrLocalTokenEntity();
        $failureNetbanking = $this->getDbEntity('netbanking', ['payment_id' => $entities[1]['payment']['id']]);
        $refundOfFailedRegister = $failurePayment->refunds->first();

        $this->assertEquals('refunded', $failurePayment['status']);
        $this->assertEquals('rejected', $rejectToken['recurring_status']);
        $this->assertNull($rejectToken['gateway_token']);
        $this->assertEquals('GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED', $rejectToken['recurring_failure_reason']);
        $this->assertEquals('rejected', $failureNetbanking['si_status']);
        $this->assertEquals('GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED', $failureNetbanking['si_message']);
        $this->assertTrue($failureNetbanking['received']);
        $this->assertTrue($refundOfFailedRegister->transaction->isReconciled());
    }

    protected function assertDebitDetails($entities, $status = 'captured', $errorMsg = 'Mandate does not Exist / Expired')
    {
        $successPayment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);
        $successNetbanking = $this->getDbEntity('netbanking', ['payment_id' => $entities[0]['payment']['id']]);

        $this->assertEquals($status, $successPayment['status']);
        $this->assertTrue($successNetbanking['received']);
        $this->assertEquals('Success', $successNetbanking['status']);
        $this->assertTrue($successPayment->transaction->isReconciled());

        $failurePayment = $this->getDbEntityById('payment', $entities[1]['payment']['id']);
        $failureNetbanking = $this->getDbEntity('netbanking', ['payment_id' => $entities[1]['payment']['id']]);

        $this->assertEquals('failed', $failurePayment['status']);
        $this->assertEquals($errorMsg, $failureNetbanking['error_message']);
        $this->assertTrue($failureNetbanking['received']);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        if (strpos($response->getContent(), 'RZPAY_EMDT') != null)
        {
            list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
            $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
            return $this->submitPaymentCallbackRequest($data);
        }

        return $this->runPaymentCallbackFlowForNbplusGateway($response, $gateway, $callback);
    }
}
