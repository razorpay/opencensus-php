<?php

namespace RZP\Tests\Functional\Gateway\Enach\Rbl;

use Mail;
use Excel;
use Closure;
use Mockery;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;
use RZP\Error\PublicErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Mail\Gateway\EMandate\Base as Email;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class EnachRblGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EnachRblGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'utib0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('UTIB', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['signed_xml']);
    }

    public function testAcknowledgementSuccessfulReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $replacePair = [
            '{$date}' => Carbon::now()->toIso8601String(),
            '{$paymentId}' => $payment->getId(),
            '{$status}' => 'true',
            '{$mandateId}' => 'UTIB6000000005844847',
            '{$firstCol}' => Carbon::now()->addDay()->format('Y-m-d'),
            '{$finalCol}' => Carbon::now()->addDay()->addYears(5)->format('Y-m-d'),
            '{$currency}' => 'INR',
            '{$maxAmount}' => '0',
            '{$accountNumber}' => $token->getAccountNumber(),
            '{$ifsc}' => $token->getIfsc(),
        ];

        $reconFileStub = file_get_contents(__DIR__ . '/acknowledge.stub');

        $reconFileContent = strtr($reconFileStub, $replacePair);

        $handle = tmpfile();
        fwrite($handle, $reconFileContent);
        fseek($handle, 0);
        $file = (new TestingFile('MMS-CREATE-RATN-RATNA0001-06032018-ESIGN6000001-INP-ACK.xml', $handle));

        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => [
                'type' => 'emandate',
                'sub_type' => 'acknowledge',
                'gateway' => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $batch = $this->makeRequestAndGetContent($request);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertEquals('true', $enach['acknowledge_status']);
        $this->assertNotNull($enach['umrn']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testAcknowledgementFailedReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $replacePair = [
            '{$date}' => Carbon::now()->toIso8601String(),
            '{$paymentId}' => $payment->getId(),
            '{$status}' => 'false',
            '{$mandateId}' => 'UTIB6000000005844847',
            '{$firstCol}' => Carbon::now()->addDay()->format('Y-m-d'),
            '{$finalCol}' => Carbon::now()->addDay()->addYears(5)->format('Y-m-d'),
            '{$currency}' => 'INR',
            '{$maxAmount}' => '0',
            '{$accountNumber}' => $token->getAccountNumber(),
            '{$ifsc}' => $token->getIfsc(),
        ];

        $reconFileStub = file_get_contents(__DIR__ . '/acknowledge.stub');

        $reconFileContent = strtr($reconFileStub, $replacePair);

        $handle = tmpfile();
        fwrite($handle, $reconFileContent);
        fseek($handle, 0);
        $file = (new TestingFile('MMS-CREATE-RATN-RATNA0001-06032018-ESIGN6000001-INP-ACK.xml', $handle));

        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => [
                'type' => 'emandate',
                'sub_type' => 'acknowledge',
                'gateway' => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $batch = $this->makeRequestAndGetContent($request);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertEquals('false', $enach['acknowledge_status']);
        $this->assertNotNull($enach['umrn']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('rejected', $token['recurring_status']);
    }

    public function testRegisterSuccessReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $this->createWebhook(['events' => ['token.confirmed' => '1']]);

        $testData = $this->testData['tokenWebhookData'];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertEquals('token.confirmed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $gatewayEntity = $this->getLastEntity('enach', true);

        $this->fixtures->edit(
            'enach',
            $gatewayEntity['id'],
            [
                'umrn' => 'UTIB6000000005844847',
                'acknowledge_status' => 'true',
            ]);

        $sheets = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items' => [
                    [
                        'random' => '1'
                    ]
                ]
            ],
            'sheet2' => [
                'config' => [
                    'start_cell' => 'A2',
                ],
                'items' => [
                    [
                        'SRNO'            => '1',
                        'MANDATE_DATE'    => Carbon::today()->format('m/d/Y'),
                        'MANDATE_ID'      => 'NEW',
                        'UMRN'            => 'UTIB6000000005844847',
                        'CUST_REFNO'      => '',
                        'SCH_REFNO'       => '',
                        'REF_1'           => $payment->getId(),
                        'CUST_NAME'       => 'User name',
                        'BANK'            => '',
                        'BRANCH'          => '',
                        'BANK_CODE'       => 'UTIB0000123',
                        'AC_TYPE'         => 'SAVINGS',
                        'ACNO'            => '914010009305862',
                        'UPDATE_DATE'     => Carbon::now()->addDays(2)->format('m/d/Y'),
                        'AMOUNT'          => '99999',
                        'FREQUENCY'       => 'ADHO',
                        'COLLECTION_TYPE' => 'UPTO MAXIMUM',
                        'START_DATE'      => Carbon::now()->format('m/d/Y'),
                        'END_DATE'        => Carbon::now()->addYears(10)->format('m/d/Y'),
                        'TEL_NO'          => '',
                        'MOBILE_NO'       => '9999999999',
                        'MAIL_ID'         => '',
                        'UPLOAD_BATCH'    => 'ESIGN000001',
                        'UPLOAD_DATE'     => Carbon::now()->format('m/d/Y'),
                        'RESPONSE_DATE'   => Carbon::now()->addDays(2)->format('m/d/Y'),
                        'UTILITY_CODE'    => 'NACH00000000012323',
                        'UTILITY_NAME'    => 'RAZORPAY',
                        'NODAL_ACNO'      => 'RATN3234334',
                        'STATUS'          => 'Active',
                        'CODE_DESC'       => '',
                        'RETURN_CODE'     => '',
                    ]
                ]
            ]
        ];

        $data = $this->getExcelString('Response Report-Response Report', $sheets);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Response Report-Response Report.xlsx', $handle));

        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => [
                'type' => 'emandate',
                'sub_type' => 'register',
                'gateway' => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $batch = $this->makeRequestAndGetContent($request);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $enach = $this->getDbLastEntityToArray('enach');

        $this->assertNotNull($enach['umrn']);
        $this->assertEquals('Active', $enach['registration_status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('captured', $payment['status']);
    }

    protected function createEmandatePayment($amount = 0, $recurringType = 'initial')
    {
        $order = $this->fixtures->create('order:emandate_order', [
            'status' => 'attempted',
            'amount' => 0]);

        $token = $this->fixtures->create('customer:emandate_token', [
            'aadhaar_number' => '390051307206',
            'auth_type' => 'aadhaar']);

        $payment = [
            'auth_type'         => 'aadhaar',
            'terminal_id'       => '1000EnachRblTl',
            'order_id'          => $order->getId(),
            'amount'            => $order->getAmount(),
            'amount_authorized' => $order->getAmount(),
            'gateway'           => 'enach_rbl',
            'bank'              => 'UTIB',
            'recurring'         => '1',
            'customer_id'       => $token->getCustomerId(),
            'token_id'          => $token->getId(),
            'recurring_type'    => $recurringType,
        ];

        $payment = $this->fixtures->create('payment:emandate_authorized', $payment);

        return [$payment, $token, $order];
    }

    public function testDebitFileGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 1000]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN => 'UTIB6000000005844847',
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment = $this->getEmandatePaymentArray('UTIB', null, 1000);
        $payment['token'] = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        $response = $this->doS2SRecurringPayment($payment);

        $this->ba->adminAuth();

        Mail::fake();

        $content = $this->startTest();
        $content = $content['items'][0];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'rbl_enach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertStringMatchesFormat('rbl-enach/outgoing/TXN_INP/ACH-DR-RATN-RATNA0001-%d-000001-INP_test', $file['name']);
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(Email::class, function ($mail) use ($file)
        {
            $key = Gateway::ENACH_RBL . '_debit';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);

            $this->assertNotEmpty($mail->attachments);

            return (($mail->hasFrom('emandate@razorpay.com')) and
                    ($mail->hasTo('rbl.emandate@razorpay.com')));
        });
    }

    public function testDebitFileReconciliation()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 5000]);

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::GATEWAY_TOKEN => 'UTIB6000000005844847',
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment = $this->getEmandatePaymentArray('UTIB', null, $order->getAmount());
        $payment['token'] = $tokenId;
        $payment['order_id'] = $order->getPublicId();

        unset($payment['auth_type']);

        $response = $this->doS2SRecurringPayment($payment);

        $content = [
            'sheet1' => [
                'config' => [
                    'start_cell' => 'A1',
                ],
                'items' => [
                    [
                        'SRNO'            => '1',
                        'ECS_DATE'        => Carbon::today()->format('m/d/Y'),
                        'SETTLEMENT_DATE' => Carbon::today()->format('m/d/Y'),
                        'CUST_REFNO'      => '',
                        'SCH_REFNO'       => '',
                        'CUSTOMER_NAME'   => 'User name',
                        'AMOUNT'          => $payment['amount'] / 100,
                        'REFNO'           => substr($response['razorpay_payment_id'], 4),
                        'UMRN'            => 'UTIB6000000005844847',
                        'CLG_STATUS'      => 'SUCCESS',
                    ],
                ]
            ]
        ];

        $data = $this->getExcelString('Debit MIS', $content);

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);
        $file = (new TestingFile('Debit MIS.xlsx', $handle));

        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => [
                'type' => 'emandate',
                'sub_type' => 'debit',
                'gateway' => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $batch = $this->makeRequestAndGetContent($request);

        $batch = $this->getDbEntityById('batch', $batch['id']);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals('captured', $payment['status']);
    }

    protected function getExcelString($name, $sheets)
    {
        $excel = Excel::create(
            $name,
            function ($excel) use ($sheets)
            {
                foreach ($sheets as $sheetName => $data)
                {
                    $excel->sheet(
                        $sheetName,
                        function ($sheet) use ($data)
                        {
                            $sheet->fromArray($data['items'], null, $data['config']['start_cell'], true);
                        }
                    );
                }
            }
        );

        return $excel->string('xlsx');
    }

    protected function runPaymentCallbackFlowEnachRbl($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                    $url, $method, $content);
        }
        else
        {
            ;
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }
}
