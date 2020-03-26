<?php

use Carbon\Carbon;
use RZP\Models\PaperMandate;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Partner\PartnerTrait;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

class NachGatewayTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;
    use FileHandlerTrait;

    // 09-02-2020 Sunday 5:30 AM
    const FIXED_NON_WORKING_DAY_TIME = 1581206400;
    // 10-02-2020 Monday 5:30 AM
    const FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME = 1581292800;
    // 10-02-2020 Tuesday 5:30 AM
    const FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME = 1581379200;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NachGatewayTestData.php';

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        (new Terminal)->createNachTerminal();
    }

    public function testGatewayFileDebitBankResponsePending()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment, "3");

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $batch = $this->getEntityById('batch', $batch['id'], true);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('created', $payment['status']);
    }

    public function testNachDebitRefund()
    {
        $this->testGatewayFileDebitBankResponseSuccess();

        $payment = $this->getLastEntity('payment', true);

        $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);

        $this->assertEquals('initiated', $refund['status']);

        $this->assertTrue(empty($refund['bank_account_id']) === false);
    }

    public function testGatewayFileRegister()
    {
        $this->createDummyRegisterToken();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileRegisterOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createDummyRegisterToken();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitForPaymentCreatedOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createDummyRegisterToken();

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebit()
    {
        $this->createRecurringNachPayment();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createRecurringNachPayment();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitBankResponseSuccess()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);
        $this->assertEquals(300000, $batch['amount']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testGatewayFileDebitBankResponseFailure()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment, "0");

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testGatewaySuccessRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);
    }

    public function testGatewayFailureRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Rejected', 'No such account');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('failed', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('No such account', $token['recurring_details']['failure_reason']);
    }

    public function testGatewayFailureRegistrationResponseFileInitialReject()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Initial Reject', 'END DATE BEFORE CURENT BUSINESS DATE NOT ALLOWED');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('failed', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('END DATE BEFORE CURENT BUSINESS DATE NOT ALLOWED', $token['recurring_details']['failure_reason']);
    }

    public function testGatewayInitialRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Initial');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testGatewayInitialRegistrationResponseFilePendingResponse()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Pending');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testGatewayInitialRegistrationResponseFilePendingFromBankResponse()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'Pending for confirmation from Destination Bank');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('created', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
    }

    protected function createRecurringNachPayment()
    {
        $initialPayment = $this->createAcceptedToken();

        $tokenId = $initialPayment[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order', [
            'amount' => 300000,
            'method' => 'nach',
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => 'cust_1000000000cust',
            'currency'    => 'INR',
            'method'      => 'nach',
            'amount'      => 300000,
            'recurring'   => true,
            'token'       => $tokenId,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function createAcceptedToken()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        return $payment;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file, $type = 'register')
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => $type,
                'gateway'  => 'nach_citi',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getBatchFileToUploadForBankDebitResponse($payment, $status = '1')
    {
        $paymentId = $payment['razorpay_payment_id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status .'00HDFC00024971111111111111                      CITI000PIGWNACH00000000013149CTTATAAIAA' . $paymentId . '      10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

    protected function getBatchFileToUploadForBankRegisterResponse($payment, $status = 'Accepted', $failureReason = '')
    {
        $paymentId = $payment['id'];

        $this->fixtures->stripSign($paymentId);

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items'  => [
                    [
                        'Sr.no'                  => '1',
                        'Category Code'          => 'U099',
                        'Category Description'   => 'Others',
                        'Start date'             => '21/11/2019',
                        'End date'               => '21/11/2029',
                        'Client code'            => 'CTRAZORPAY',
                        'Unique reference no'    => $paymentId,
                        'Account No'             => '1111111111111',
                        'Account Holder name'    => 'dead pool',
                        'Account type'           => 'savings',
                        'Bank Name'              => 'HDFC',
                        'Bank MICR / IFSC'       => 'HDFC0001233',
                        'Amount'                 => '10000',
                        'Lot'                    => '1',
                        'Softcopy Received Date' => '06/12/19',
                        'Status'                 => $status,
                        'UMRN'                   => 'UTIB6000000005844847',
                        'Remark'                 => $failureReason,
                    ],
                ],
            ],
        ];

        $name = 'RAZORP_EMANDATE_NACH00000000010000_21112019_test';

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

        $data = $excel->string('xlsx');

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile('Register MIS.xlsx', $handle));

        return $file;
    }

    protected function createDummyRegisterToken()
    {
        $this->createOrder([
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'amount' => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'token_id'   => '100000000token',
                    'max_amount' => 1000000,
                    'auth_type'  => 'physical',
                    E::PAPER_MANDATE => [
                        'amount' => 1000000,
                        'status' => PaperMandate\Status::AUTHENTICATED,
                        'uploaded_file_id' => '1000000000file'
                    ],
                ],
            ],
        ]);

        $response = $this->doAuthPayment([
            "amount"      => 0,
            "currency"    => "INR",
            "method"      => "nach",
            "order_id"    => "order_100000000order",
            "customer_id" => "cust_1000000000cust",
            "recurring"   => true,
            "contact"     => "9483159238",
            "email"       => "r@g.c",
            "auth_type"   => "physical",
        ]);

        return $this->getEntityById('payment', $response['razorpay_payment_id'], true);
    }

    protected function createToken(array $overrideWith = [])
    {
        $payment = array_pull($overrideWith, E::PAYMENT, []);

        $token = $this->fixtures
            ->create(
                E::TOKEN,
                array_merge(
                    [
                        'id'              => '100000000token',
                    ],
                    $overrideWith
                )
            );

        $this->createPayment($payment);

        return $token;
    }

    protected function createPayment(array $overrideWith = [])
    {
        $order = array_pull($overrideWith, E::ORDER, []);

        $this->createOrder($order);

        $payment = $this->fixtures
            ->create(
                E::PAYMENT,
                array_merge(
                    [
                        'id'              => '1000000payment',
                    ],
                    $overrideWith
                )
            );

        return $payment;
    }

    protected function createOrder(array $overrideWith = [])
    {
        $invoice = array_pull($overrideWith, E::INVOICE, []);

        $order = $this->fixtures
            ->create(
                E::ORDER,
                array_merge(
                    [
                        'id'              => '100000000order',
                        'amount'          => 100000,
                    ],
                    $overrideWith
                )
            );

        $this->createInvoiceForOrder($invoice);

        return $order;
    }

    protected function createInvoiceForOrder(array $overrideWith = [])
    {
        $subscriptionRegistration = array_pull($overrideWith, E::SUBSCRIPTION_REGISTRATION, []);

        $subscriptionRegistrationId = UniqueIdEntity::generateUniqueId();

        $order = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => '1000000invoice',
                        'order_id'        => '100000000order',
                        'entity_type'     => 'subscription_registration',
                        'entity_id'       => $subscriptionRegistrationId,
                    ],
                    $overrideWith
                )
            );

        $subscriptionRegistration['id'] = $subscriptionRegistrationId;

        $this->createSubscriptionRegistration($subscriptionRegistration);

        return $order;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
        $paperMandate = array_pull($overrideWith, E::PAPER_MANDATE, []);

        $paperMandateId = UniqueIdEntity::generateUniqueId();

        $subscriptionRegistration = $this->fixtures
            ->create(
                'subscription_registration',
                array_merge(
                    [
                        'method'          => 'nach',
                        'notes'           => [],
                        'entity_type'     => 'paper_mandate',
                        'entity_id'       => $paperMandateId,
                    ],
                    $overrideWith
                )
            );

        $paperMandate['id'] = $paperMandateId;

        $this->createPaperMandate($paperMandate);

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountId = UniqueIdEntity::generateUniqueId();

        $bankAccount = array_pull($overrideWith, E::BANK_ACCOUNT, []);

        $this->fixtures->create(
            E::CUSTOMER,
            ['id' => '1000000000cust']
        );

        $paperMandate = $this->fixtures
            ->create(
                'paper_mandate',
                array_merge(
                    [
                        'bank_account_id'   => $bankAccountId,
                        'amount'            => 1000,
                        'status'            => PaperMandate\Status::CREATED,
                        'debit_type'        => PaperMandate\DebitType::MAXIMUM_AMOUNT,
                        'type'              => PaperMandate\Type::CREATE,
                        'frequency'         => PaperMandate\Frequency::YEARLY,
                        'start_at'          => (new Carbon('+5 day'))->timestamp,
                        'utility_code'      => 'NACH00000000013149',
                        'sponsor_bank_code' => 'RATN0TREASU',
                        'terminal_id'       => '1citinachDTmnl',
                    ],
                    $overrideWith
                )
            );

        $bankAccount['id'] = $bankAccountId;

        $this->createBankAccount($bankAccount);

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'beneficiary_name' => 'dead pool',
                        'ifsc_code'        => 'HDFC0001233',
                        'account_number'   => '1111111111111',
                        'account_type'     => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }
}
