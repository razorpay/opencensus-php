<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate;

use Carbon\Carbon;
use Mail;
use Excel;

use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\FileStore\Type;
use RZP\Gateway\Netbanking\Sbi;
use RZP\Models\FileStore\Format;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Customer\Token\RecurringStatus;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Netbanking\Sbi\Emandate\DebitFileHeadings;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Gateway\Netbanking\Sbi\Emandate\RegisterFileHeadings;

class NetbankingSbiEmandateTest extends TestCase
{
    use PaymentTrait;
    use FileHandlerTrait;
    use DbEntityFetchTrait;

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

        $registerFailureFile = $this->getRegisterFailureCsv($registerPayments);
        $batch = $this->uploadBatchFile($registerFailureFile, 'register');
        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

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

    protected function getRegisterSuccessExcel($entities)
    {
        $items = [];

        foreach ($entities as $index => $entity)
        {
            if ($entity['status'] === 'SUCCESS')
            {
                $amount = number_format($entity['payment']['amount'] / 100, '2', '.', '');

                $items[] = [
                    RegisterFileHeadings::SR_NO                => strval($index + 1),
                    RegisterFileHeadings::EMANDATE_TYPE        => 'random',
                    RegisterFileHeadings::UMRN                 => $entity['umrn'],
                    RegisterFileHeadings::MERCHANT_ID          => 'test ID',
                    RegisterFileHeadings::CUSTOMER_REF_NO      => $entity['payment']['id'],
                    RegisterFileHeadings::SCHEME_NAME          => 'test Scheme',
                    RegisterFileHeadings::SUB_SCHEME           => 'test subScheme',
                    RegisterFileHeadings::DEBIT_CUSTOMER_NAME  => 'Test Account',
                    RegisterFileHeadings::DEBIT_ACCOUNT_NUMBER => self::ACCOUNT_NUMBER,
                    RegisterFileHeadings::DEBIT_ACCOUNT_TYPE   => 'random',
                    RegisterFileHeadings::DEBIT_IFSC           => 'SBIN0000001',
                    RegisterFileHeadings::DEBIT_BANK_NAME      => 'SBI',
                    RegisterFileHeadings::AMOUNT               => $amount,
                    RegisterFileHeadings::AMOUNT_TYPE          => 'Max',
                    RegisterFileHeadings::CUSTOMER_ID          => '123456',
                    RegisterFileHeadings::PERIOD               => 'random',
                    RegisterFileHeadings::PAYMENT_TYPE         => 'ADHO',
                    RegisterFileHeadings::FREQUENCY            => 'ADHO',
                    RegisterFileHeadings::START_DATE           => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    RegisterFileHeadings::END_DATE             => Carbon::now(Timezone::IST)->addYears(10)->format('d/m/Y'),
                    RegisterFileHeadings::MOBILE               => '0000000000',
                    RegisterFileHeadings::EMAIL                => 'test@gmail.com',
                    RegisterFileHeadings::OTHER_REF_NO         => 'test',
                    RegisterFileHeadings::PAN_NUMBER           => '1234',
                    RegisterFileHeadings::AUTO_DEBIT_DATE      => '',
                    RegisterFileHeadings::AUTHENTICATION_MODE  => '',
                    RegisterFileHeadings::DATE_PROCESSED       => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    RegisterFileHeadings::STATUS               => $entity['status'],
                    RegisterFileHeadings::NO_OF_DAYS_PENDING   => '',
                    RegisterFileHeadings::REJECT_REASON        => $entity['return_reason'] ?? 'random reason',
                ];
            }
        }

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A6',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('Sbi Register Recon Emandate', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        return (new TestingFile('Sbi-Register-Recon-Emandate.xlsx', $handle));
    }

    protected function getRegisterFailureCsv($entities)
    {
        $data = [];

        foreach ($entities as $entity)
        {
            if ($entity['status'] === 'FAILURE')
            {
                $data[] = [
                    RegisterFileHeadings::TRANSACTION_DATE        => Carbon::now(Timezone::IST)->format('d/m/Y H:i:s'),
                    RegisterFileHeadings::CUSTOMER_NAME           => 'test',
                    RegisterFileHeadings::CUSTOMER_REF_NO         => $entity['payment']['id'],
                    RegisterFileHeadings::CUSTOMER_ACCOUNT_NUMBER => self::ACCOUNT_NUMBER,
                    RegisterFileHeadings::AMOUNT                  => '1.00',
                    RegisterFileHeadings::MAX_AMOUNT              => '99999.00',
                    RegisterFileHeadings::STATUS                  => $entity['status'],
                    RegisterFileHeadings::STATUS_DESCRIPTION      => $entity['return_reason'],
                    RegisterFileHeadings::START_DATE_REJECT_FILE  => '',
                    RegisterFileHeadings::END_DATE_REJECT_FILE    => '',
                    RegisterFileHeadings::FREQUENCY               => '',
                    RegisterFileHeadings::UMRN_REJECT_RILE        => $entity['umrn'],
                    RegisterFileHeadings::SBI_REFERENCE_NO        => $entity['umrn'],
                    RegisterFileHeadings::MODE_OF_VERIFICATION    => 'DB',
                    RegisterFileHeadings::AMOUNT_TYPE_REJECT_FILE => 'M',
                ];
            }
        }

        $txt = $this->generateTextWithHeadings($data, ',', false, array_keys(current($data)));

        $handle = tmpfile();

        fputs($handle, $txt);

        fseek($handle, 0);

        return (new TestingFile('Sbi-Register-Recon-Emandate.txt', $handle));
    }

    protected function uploadDebitBatchFile($entities)
    {
        $items = [];

        foreach ($entities as $index => $entity)
        {
            if($entity['payment']['recurring_type'] === 'auto')
            {
                $amount = number_format($entity['payment']['amount'] / 100, '2', '.', '');

                $items[] = [
                    DebitFileHeadings::SERIAL_NUMBER            => $index + 1,
                    DebitFileHeadings::EMANDATE_TYPE            => 'random',
                    DebitFileHeadings::UMRN                     => '1234',
                    DebitFileHeadings::SCHEME_NAME              => 'test Scheme',
                    DebitFileHeadings::SUB_SCHEME_NAME          => 'test subScheme',
                    DebitFileHeadings::MANDATE_HOLDER_NAME_RESP => 'Test Account',
                    DebitFileHeadings::DEBIT_ACC_NO             => '12345678901234',
                    DebitFileHeadings::DEBIT_BANK_IFSC          => 'SBIN0000001',
                    DebitFileHeadings::DEBIT_DATE_RESP          => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    DebitFileHeadings::AMOUNT                   => $amount,
                    DebitFileHeadings::JOURNAL_NUMBER           => '',
                    DebitFileHeadings::PROCESSING_DATE          => Carbon::now(Timezone::IST)->format('d/m/Y'),
                    DebitFileHeadings::CUSTOMER_REF_NO          => $entity['payment']['id'],
                    DebitFileHeadings::DEBIT_STATUS             => $entity['status'],
                    DebitFileHeadings::CREDIT_STATUS            => '',
                    DebitFileHeadings::REASON                   => $entity['return_reason'] ?? 'random reason'
                ];
            }
        }

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A6',
                ],
                'items' => $items
            ]
        ];

        $data = $this->getExcelString('Sbi Debit Recon Emandate', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Sbi-Debit-Recon-Emandate.xlsx', $handle));

        return $this->uploadBatchFile($file, 'debit');
    }

    protected function createRegistrationPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id'])->toArray();
    }

    protected function createSecondReccuringPayment($token)
    {
        $paymentRequestArray = $this->payment;

        $paymentRequestArray[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);

        $paymentRequestArray['amount'] = 3000;

        $paymentRequestArray['order_id'] = $order->getPublicId();

        $response = $this->doS2SRecurringPayment($paymentRequestArray);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id']);
    }

    protected function uploadBatchFile($file, $type)
    {
        $url = '/admin/batches';

        $this->ba->adminAuth();

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'emandate',
                'sub_type' => $type,
                'gateway'  => 'sbi',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getExcelString($name, $sheets)
    {
        $excel = Excel::create(
            $name,
            function($excel) use ($sheets) {
                foreach ($sheets as $sheetName => $data)
                {
                    $excel->sheet(
                        $sheetName,
                        function($sheet) use ($data) {
                            $sheet->fromArray($data['items'], null, $data['config']['start_cell'], true);
                        }
                    );
                }
            }
        );

        return $excel->string('xlsx');
    }

    protected function generateDebitGatewayFile()
    {
        $this->ba->adminAuth();

        $testData = $this->testData['testEmandateDebit'];

        return $this->runRequestResponseFlow($testData);
    }
}
