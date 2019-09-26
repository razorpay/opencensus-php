<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Gateway\File;
use RZP\Models\FileStore\Type;
use RZP\Gateway\Netbanking\Sbi;
use RZP\Models\FileStore\Format;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class NetbankingSbiEmandateTest extends TestCase
{
    use PaymentTrait;
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use EmandateSbiTestTrait;

    protected $payment;

    const ACCOUNT_NUMBER    = '12345678901234';
    const IFSC              = 'SBIN0000001';
    const NAME              = 'Test account';

    public function setUp()
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
        ];

        unset($this->payment[Entity::CARD]);

        $this->setMockGatewayTrue();
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

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
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

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
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
            'status'        => 'Failure',
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

    protected function assertRegistrationDetails($entities)
    {
        $successPayment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);
        $successToken = $successPayment->getGlobalOrLocalTokenEntity();
        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $entities[0]['payment']['id']]);

        $this->assertEquals('captured', $successPayment['status']);
        $this->assertEquals('confirmed', $successToken['recurring_status']);
        $this->assertNotNull($successToken['gateway_token']);
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

    protected function assertDebitDetails($entities)
    {
        $successPayment = $this->getDbEntityById('payment', $entities[0]['payment']['id']);
        $successNetbanking =$this->getDbEntity('netbanking', ['payment_id' => $entities[0]['payment']['id']]);

        $this->assertEquals('captured', $successPayment['status']);
        $this->assertTrue($successNetbanking['received']);
        $this->assertEquals('Success', $successNetbanking['status']);
        $this->assertTrue($successPayment->transaction->isReconciled());

        $failurePayment = $this->getDbEntityById('payment', $entities[1]['payment']['id']);
        $failureNetbanking = $this->getDbEntity('netbanking', ['payment_id' => $entities[1]['payment']['id']]);

        $this->assertEquals('failed', $failurePayment['status']);
        $this->assertEquals('Mandate does not Exist / Expired', $failureNetbanking['error_message']);
        $this->assertTrue($failureNetbanking['received']);
    }
}
