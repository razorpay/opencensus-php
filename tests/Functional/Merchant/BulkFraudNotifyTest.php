<?php


namespace Functional\Merchant;

use Mail;
use Mockery;
use RZP\Models;
use RZP\Gateway;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Batch\CreatePaymentFraud;
use RZP\Services\FreshdeskTicketClient;
use RZP\Models\Card\IIN\Import\XLSFileHandler;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Fraud\BulkNotification\File;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;

class BulkFraudNotifyTest extends TestCase
{
    use RequestResponseFlowTrait;
    use SalesforceTrait;

    use FreshdeskTrait;

    use WorkflowTrait;

    protected $datalakeMock;

    protected $harvesterMock;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BulkFraudNotifyTestData.php';

        parent::setUp();

        $this->setUpFreshdeskClientMock();

        $this->setUpSalesforceMock();

        $this->setUpDatalakeMock();

        $this->setupHarvesterMock();
    }

    protected function setUpDatalakeMock(): void
    {
        $this->datalakeMock = Mockery::mock('RZP\Services\Mock\DataLakePresto')->makePartial();

        $this->datalakeMock->shouldAllowMockingProtectedMethods();

        $this->app['datalake.presto'] = $this->datalakeMock;
    }

    protected function setupHarvesterMock(): void
    {
        $this->harvesterMock = Mockery::mock('RZP\Services\Mock\HarvesterClient')->makePartial();

        $this->harvesterMock->shouldAllowMockingProtectedMethods();

        $this->app['eventManager'] = $this->harvesterMock;
    }

    protected function validateContent($actualContent, $expectedContent): bool
    {
        foreach ($expectedContent as $key => $value)
        {
            if (isset($actualContent[$key]) === false)
            {
                return false;
            }

            if ($expectedContent[$key] !== $actualContent[$key])
            {
                return false;
            }
        }

        return true;
    }

    protected function mockPrestoService($expectedContent, $response, $times = 1): void
    {
        $this->datalakeMock->shouldReceive('getDataFromDataLake')
                        ->times($times)
                        ->with(Mockery::on(function($request) use ($expectedContent) {
                            return $request == $expectedContent;
                        }))
                        ->andReturnUsing(function() use ($response) {
                            return $response;
                        });

    }

    protected function mockHarvesterService($expectedContent, $response, $times = 1): void
    {
        $expectedContent = [
            'query' => $expectedContent
        ];

        $this->harvesterMock->shouldReceive('getDataFromPinot')
            ->times($times)
            ->with(Mockery::on(function($request) use ($expectedContent) {
                return $request == $expectedContent;
            }))
            ->andReturnUsing(function() use ($response) {
                return $response;
            });
    }

    protected function assertGetAttributesVisa($response)
    {
        $this->assertEquals([
            'code' => "B",
            'reason' => "Account or credentials takeover",
        ], last($response['types']));

        $this->assertEquals(0, sizeof($response['sub_types']));
    }

    protected function assertGetAttributesMastercard($response)
    {
        $this->assertEquals([
            'code' => "51",
            'reason' => "Bust-out Collusive Merchant",
        ],  last($response['types']));

        $this->assertEquals([
            'code' => "U",
            'reason' => "Unknown",
        ],  last($response['sub_types']));

    }

    public function testGetFraudAttributes()
    {
       $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('get_fraud_attributes');

        $payment = $this->fixtures->create('payment');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['payment_id'] = $payment->getPublicId();

        $this->assertGetAttributesVisa($this->startTest($testData));

        $card = $this->fixtures->create('card', [
            'network'   =>  'MasterCard',
        ]);

        $paymentMastercard = $this->fixtures->create('payment', [
            'card_id'   =>  $card->getId(),
        ]);

        $testData['request']['content']['payment_id'] = $paymentMastercard->getId();

        $this->assertGetAttributesMastercard($this->startTest($testData));
    }

    public function testSavePaymentFraud()
    {
        $this->ba->adminAuth();

        $snsPayloadArray = [];

        $this->mockLumberjackSns(1, $snsPayloadArray);

        $this->addPermissionToBaAdmin('save_payment_fraud');

        $merchant = $this->fixtures->create('merchant', [
            'email' =>  'testing101@gmail.com'
        ]);

        $payment = $this->fixtures->create('payment', [
            'merchant_id'   =>  $merchant->getId(),
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['payment_id'] = $payment->getPublicId();

        $this->mockFreshdesk(1);

        $this->startTest($testData);

        $fraud = $this->assertFraudEntityExists($payment->getId());

        $this->assertEquals($fraud->amount, $testData['request']['content']['amount']*100);
        $this->assertEquals($fraud->base_amount, $testData['request']['content']['amount']*100);
        $this->assertEquals($fraud->currency, $testData['request']['content']['currency']);

        //for pushing fraud event to lumberjack
        $expectedSNSPayload = $this->getExpectedSNSPayload($fraud, $payment);

        $this->assertArraySelectiveEquals($expectedSNSPayload, $snsPayloadArray[0]);
    }

    protected function mockLumberjackSns($count, &$snsPayloadArray = [])
    {
        $sns = \Mockery::mock('RZP\Services\Aws\Sns');

        $this->app['config']->set('applications.lumberjack.mock', false);

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->times($count)
            ->with(\Mockery::on(function(string $input) use (& $snsPayloadArray) {
                $jsonDecodedInput = json_decode($input, true);

                array_push($snsPayloadArray, $jsonDecodedInput);

                return true;

            }),    \Mockery::type('string'));
    }

    protected function getExpectedSNSPayload($fraud, $payment)
    {
        $merchant = $payment->merchant;
        return [
            'mode'   => 'test',
            'events' => [
                [
                    'event_type'    => 'payment-fraud-events',
                    'event_version' => 'v1',
                    'event_group'   => 'payment_fraud',
                    'event'         => 'payment_fraud.created',
                    'properties'    => [
                        'payment_fraud' => [
                            'id'                      => $fraud->id,
                            'payment_id'              => $fraud->payment_id,
                            'base_amount'             => $fraud->base_amount,
                            'reported_to_razorpay_at' => $fraud->reported_to_razorpay_at ?? $fraud->created_at,
                            'reported_to_issuer_at'   => (int) ($fraud->reported_to_issuer_at ?? $fraud->reported_to_razorpay_at ?? $fraud->created_at),
                        ],
                        'payment'       => [
                            'id'          => $payment->getPublicId(),
                            'amount'      => 1000000,
                            'base_amount' => 1000000,
                            'currency'    => 'INR',
                            'method'      => 'card',
                            'issuer'      => null,
                            'type'        => 'PG',
                            'gateway'     => 'hdfc',
                            'created_at'   => $payment->getCreatedAt(),
                        ],
                        'merchant'      => [
                            'id'        => $merchant->getId(),
                            'name'      => $merchant->getBillingLabel(),
                            'mcc'       => $merchant->getCategory(),
                            'category'  => $merchant->getCategory2(),
                        ],
                        'error_code'    => 'SUCCESS',
                    ],
                ],
            ]
        ];
    }

    public function testSavePaymentFraudValidationError()
    {
        $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('save_payment_fraud');

        $payment = $this->fixtures->create('payment');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['payment_id'] = $payment->getId();

        $this->startTest($testData);
    }

    public function testNotifyWithChargebackPocEmail()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        (new Models\Merchant\Email\Service())->createEmails($payment->getMerchantId(), ['type' => 'chargeback', 'email' => 'a@rzp.com,b@rzp.com']);

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithPaymentId(bool $addPermission = true)
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, $addPermission);
    }

    public function testNotifyWithPaymentIdSkipMerchantNotification()
    {
        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => '',
                'send_mail' => 'N',
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), null, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0,true);
    }

    public function testNotifyWithHitachiPrrn()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Hitachi\Entity $hitachiEntity */
        $hitachiEntity = $this->fixtures->create('hitachi', ['payment_id' => $payment->getId(), 'pRRN' => 123]);

        $arn = $hitachiEntity->getRrn();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'card',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithPaysecureRrn()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Paysecure\Entity $paysecureEntity */
        $paysecureEntity = $this->fixtures->create('paysecure', ['rrn' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $paysecureEntity->getRrn();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'card',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithNpciReferenceId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Upi\Base\Entity $upiEntity */
        $upiEntity = $this->fixtures->create('upi', ['npci_reference_id' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $upiEntity->getNpciReferenceId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'upi',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithGatewayPaymentId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Upi\Base\Entity $upiEntity */
        $upiEntity = $this->fixtures->create('upi', ['gateway_payment_id' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $upiEntity->getGatewayPaymentId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'upi',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithBankUtr()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Models\BankTransfer\Entity $bankTransferEntity */
        $bankTransferEntity = $this->fixtures->create('bank_transfer', ['utr' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $bankTransferEntity->getUtr();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'bank_transfer',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

     public function testNotifyWithNetbankingGatewayPaymentId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Netbanking\Base\Entity $netbankingEntity */
        $netbankingEntity = $this->fixtures->create('netbanking', [
            'bank' => 'hdfc',
            'bank_payment_id' => 1111,
            'caps_payment_id' => '123',
            'payment_id' => $payment->getId()
        ]);

        $arn = $netbankingEntity->getBankPaymentId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'netbanking',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

     public function testNotifyNotAbleToResolvePaymentIdCase()
    {
        $arn = 'random_arn';

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'netbanking',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, null, null, 'Could not resolve payment_id']
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0, true);
    }

    public function testNotifyFutureDateCase()
    {
        $arn = 'random_arn';

        $fileData = [
            [
                'reported_to_razorpay_at' => '18/12/3000',
                'payment_method' => 'netbanking',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, null, null, 'Future Date should not be given: 3000-12-18']
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0, true);
    }

    public function testVisaFraudReportBatchCreated()
    {
        $this->prepareAndTestBatchCreatedVisaMastercard();
    }

    public function testMastercardFraudReportBatchCreated()
    {
        $this->prepareAndTestBatchCreatedVisaMastercard('mastercard');
    }

    public function testCreateFraudBatchMastercard()
    {
        $payment = $this->setupForCreateFraudBatchMastercard();

        $snsPayloadArray = [];

        $this->mockLumberjackSns(1, $snsPayloadArray);

        $response = $this->startTest();

        $fraud = $this->assertFraudEntityExists($response['items'][0]['Payment ID'], 'MasterCard');

        $this->assertEquals($response['items'][0]['Fraud ID'], $fraud->id);

        $this->assertEquals(1635552000, $fraud->reported_to_issuer_at);

        $expectedSNSPayload = $this->getExpectedSNSPayload($fraud, $payment);

        $this->assertArraySelectiveEquals($expectedSNSPayload, $snsPayloadArray[0]);

        $this->assertEquals(1644624000, $fraud->reported_to_razorpay_at);
    }

    public function testCreateFraudBatchSkipSendMail()
    {
        $this->setupForCreateFraudBatchMastercard();

        $response = $this->startTest();

        $fraud = $this->assertFraudEntityExists($response['items'][0]['Payment ID'], 'MasterCard');

        $this->assertEquals(1652400000, $fraud->reported_to_issuer_at);

        $this->assertEquals(1652400000, $fraud->reported_to_razorpay_at);

        $notificationDisableMidSet = $this->app['redis']->smembers('bulk_fraud_notification_disable_mid_set_100000Razorpay');

        $this->assertEquals([
            '10000000000000',
        ], $notificationDisableMidSet);
    }

    public function testCreateFraudBatchVisa()
    {
        $paymentId = '10000000000002';

        $payment = $this->fixtures->create('payment', ['id' => $paymentId]);

        $this->mockHarvesterService("select payments_reference1, payments_id, payments_merchant_id from pinot.payments_auth_fact where payments_reference1 in ('74110751299033415520957','74110751299033415520957')",
                                [
                                    [
                                        'payments_reference1' => '74110751299033415520957',
                                        'payments_id' => $paymentId,
                                        'payments_merchant_id' => '10000000000000'
                                    ]
                                ]);

        $this->ba->batchAppAuth();

        $snsPayloadArray = [];

        $this->mockLumberjackSns(1, $snsPayloadArray);

        $response = $this->startTest();

        $fraud = $this->assertFraudEntityExists($response['items'][0]['Payment ID']);

        $fraudId = $fraud->id;

        $this->assertEquals($response['items'][0]['Fraud ID'], $fraudId);

        $this->assertEquals($response['items'][1]['Fraud ID'], $fraudId);

        $expectedSNSPayload = $this->getExpectedSNSPayload($fraud, $payment);

        $this->assertArraySelectiveEquals($expectedSNSPayload, $snsPayloadArray[0]);

        $this->assertEquals(1644624000, $fraud->reported_to_razorpay_at);

        $this->assertEquals(1644624000, $fraud->reported_to_issuer_at);

    }

    public function testCreateFraudBatchVisaDatalakeQueryFails()
    {
        $this->mockHarvesterService("select payments_reference1, payments_id, payments_merchant_id from pinot.payments_auth_fact where payments_reference1 in ('74110751299033415520957')", []);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testNotifyIgnoreOnSecondCall()
    {
        $this->testNotifyWithPaymentId();
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);

        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $message = "Merchant was already notified 8 times. Can not notify more than 8 times in 24 hours. Please try again later.";

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), null, $message]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0, false);
    }

    //this function will assert that only one fraud exist for given payment Id and will return that fraud
    protected function assertFraudEntityExists($paymentId, $reportedBy = 'Visa')
    {
        $fraud = \DB::connection('test')->table('payment_fraud')
                    ->where('payment_id', $paymentId);

        $this->assertEquals(1, $fraud->count());

        $this->assertEquals($paymentId, $fraud->first()->payment_id);
        $this->assertEquals($reportedBy, $fraud->first()->reported_by);

        return $fraud->first();
    }

    public function testNotifySingleForSamePaymentIdAndReportedBy()
    {
        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '0',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null],
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);

        $this->assertFraudEntityExists($payment->getId());
    }

    public function testNotifySingleForOneMerchant()
    {
        /** @var Models\Payment\Entity $payment */
        $payment1 = $this->fixtures->create('payment');

        /** @var Models\Payment\Entity $payment */
        $payment2 = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment1->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment2->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment1->getPublicId(), $payment1->getMerchantId(), 123, null],
            [null, $payment2->getPublicId(), $payment2->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);

        $this->assertFraudEntityExists($payment1->getId());

        $this->assertFraudEntityExists($payment2->getId());
    }

    public function testNotifySingleForOneMerchantMobileSignup()
    {
        /** @var Models\Payment\Entity $payment */
        $payment1 = $this->fixtures->create('payment');

        /** @var Models\Payment\Entity $payment */
        $payment2 = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment1->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment2->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment1->getPublicId(), $payment1->getMerchantId(), 123, null],
            [null, $payment2->getPublicId(), $payment2->getMerchantId(), 123, null]
        ];

        $expectedContent = [
            'group_id'        => 82000656452,
            'tags'            => ['bulk_fraud_email'],
            'priority'        => 1,
            'phone'           => '+919991119991',
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'Fraud alerts',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                'cf_merchant_id'                => '10000000000000',
                'cf_merchant_activation_status' => 'undefined',
            ],
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id'        => 123,
                                                        'priority'  => 1,
                                                        'fr_due_by' => 'today'
                                                    ]);

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true, true);
    }

    public function testNotifyForMultipleMerchant()
    {
        /** @var Models\Payment\Entity $payment */
        $payment1 = $this->fixtures->create('payment');

        $merchant = $this->fixtures->create('merchant');

        /** @var Models\Payment\Entity $payment */
        $payment2 = $this->fixtures->create('payment', ['merchant_id' => $merchant->getId()]);

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment1->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment2->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment1->getPublicId(), $payment1->getMerchantId(), 123, null],
            [null, $payment2->getPublicId(), $payment2->getMerchantId(), 123, null]
        ];

        $this->mockSalesforceRequest($payment1->getMerchantId(),'abc@gmail.com');
        $this->mockSalesforceRequest($payment2->getMerchantId(),'abc@gmail.com');

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 2, true);
    }

    public function testNotifyPostBatchWithNotificationsDisabled()
    {
        //usecase: if any of the rows in the input batch file are marked as "N" for send notification,
        // then no notifcation should go for the whole file[any merchant whose payment is marked is fraud
        // for that batch_id
        $this->setupForNotifyPostBatch();

        $this->app['redis']->sadd('bulk_fraud_notification_disable_mid_set_100000Razorpay', '10000000000000');

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
            [],
            [],
        0);

        $this->startTest();
    }

    public function testNotifyPostBatch()
    {
        $this->setupForNotifyPostBatch();

        $expectedContent = [
            'status'          => 6,
            'type'            => 'Service request',
            'email'           => "test@razorpay.com",
            'priority'        => 3,
            'tags'            => ['bulk_fraud_email'],
            'group_id'        => 82000656452,
            'email_config_id' => 82000098661,
            'custom_fields'   => [
                'cf_ticket_queue' => 'Merchant',
                'cf_merchant_id'  => '10000000000000',
                'cf_category'     => 'Risk Report_Merchant',
                'cf_subcategory'  => 'Fraud alerts',
                'cf_product'      => 'Payment Gateway',
            ],
            'subject'         =>    'Razorpay: Cross-Border Fraud transactions alert from the Card Schemes/Networks | 10000000000000 | test name',
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
            $expectedContent,
            [
                'id'        => 123,
                'priority'  => 1,
                'fr_due_by' => 'today',
                'spam' => false,
                'fr_escalated' => false,
                'group_id' => 82000147768,
                'requester_id' => 82009627521,
                'company_id' => 82000331265,
                'subject' => 'Razorpay: Cross-Border Fraud transactions alert from the Card Schemes/Networks | 10000000000000 | test name'
            ]);

        $this->startTest();

        Mail::assertSent(CreatePaymentFraud::class);
    }

    protected function setupForNotifyPostBatch(): void
    {
        Mail::fake();

        $this->ba->batchAppAuth();

        $payment = $this->fixtures->create('payment');

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'test name',
        ]);

        $this->fixtures->create('payment_fraud', [
            'payment_id' => $payment->getId(),
            'batch_id'   => '100000Razorpay',
        ]);
    }

    protected function setupForCreateFraudBatchMastercard()
    {
        $paymentId = '10000000000002';

        $payment = $this->fixtures->create('payment', ['id' => $paymentId]);

        $this->mockHarvesterService("select payments_reference1, payments_id, payments_merchant_id from pinot.payments_auth_fact where payments_reference1 in ('02705601344033737573894')", []);

        $this->mockHarvesterService("select authorization_rrn, authorization_payment_id, payments_merchant_id from pinot.payments_auth_fact where authorization_rrn in ('003373757389')",
            [
                    [
                        'authorization_rrn'        => '003373757389',
                        'authorization_payment_id' => $paymentId,
                        'payments_merchant_id'     => '10000000000000'
                    ]
            ]);

        $this->ba->batchAppAuth();

        return $payment;
    }


    private function assertBatchInputForMastercard($csvRows)
    {
        $this->assertContains('send_mail', $csvRows[0]);

        $this->assertContains('02705601344033737573894', $csvRows[1]);

        $this->assertContains('2975', $csvRows[1]);

        $this->assertContains('003373757389', $csvRows[1]);

        $this->assertContains('USD', $csvRows[1]);

        $this->assertContains('MasterCard', $csvRows[1]);

        $this->assertEquals('', $csvRows[1][11]); //asserting for send_mail which is at 11 offset

        $this->assertContains('reported_to_issuer_at', $csvRows[0]);

        $this->assertEquals('1635897600', $csvRows[1][6]);  //asserting for reported_to_issuer at [11-03-2021 in format MM/dd/yy]

        $this->assertEquals('44694', $csvRows[2][6]);


        $this->assertContains('reported_to_razorpay_at', $csvRows[0]);

        $this->assertEquals('1644624000', $csvRows[1][12]);  //asserting for reported_to_razorpay_at

        $this->assertEquals('44694', $csvRows[2][12]);
    }

    private function assertBatchInputForVisa($csvRows)
    {
        $this->assertContains('reported_to_razorpay_at', $csvRows[0]);

        $this->assertContains('reported_to_issuer_at', $csvRows[0]);

        $this->assertContains('send_mail', $csvRows[0]);

        $this->assertContains('1635379200', $csvRows[1]); // corresponds to 28-Oct-21 [fraud post date] in input file

        $this->assertContains('74110751299033415520957', $csvRows[1]);

        $this->assertContains('2694', $csvRows[1]);

        $this->assertContains('USD', $csvRows[1]);

        $this->assertContains('Visa', $csvRows[1]);

        $this->assertContains('1635379200', $csvRows[2]); // corresponds to 28-Oct-21 [fraud post date] in input file

        $this->assertContains('ARN not found for the following row', $csvRows[2]);

        $this->assertEquals('N', $csvRows[1][11]); //asserting for send_mail which is at 11 offset

        $this->assertEquals('Y', $csvRows[2][11]); //asserting for send_mail which is at 11 offset

        $this->assertEquals('44694', $csvRows[1][12]);  //asserting for reported_to_razorpay_at which is at 12 offset

        $this->assertEquals('', $csvRows[2][12]); //asserting for reported_to_razorpay_at which is at 12 offset

        $this->assertEquals('', $csvRows[3][12]);
    }

    private function prepareAndTestBatchCreatedVisaMastercard(string $fileSource = 'visa')
    {
        $fileData = $this->testData[$fileSource . '_file_data'];

        $testData = $this->testData['commonTestData'];

        $testData['request']['url'] = '/fraud/bulk/' . $fileSource;

        $testData['request']['files']['file'] = $this->getBulkFraudNotifyUploadedXLSXFileFromFileData($fileData);

        $this->ba->adminAuth();

        $this->addAdminPermission();

        $response = $this->startTest($testData);

        $this->assertNotNull($response);

        $batchId = last(explode('/', $response['link']));

        $filePath = storage_path('files/filestore/batch/upload') . '/' . $batchId . '.csv';

        $csvRows = (new XLSFileHandler)->getCsvData($filePath)['data'];

        $functionName = camel_case('assertBatchInputFor' . $fileSource);

        $this->$functionName($csvRows);
    }

    private function prepareAndDoTest(array $fileData, array $expectedOutputFileRows, int $expectFdCallCount, bool $addPermission, bool $mobileSignupTest = false)
    {
        $testData = &$this->testData['commonTestData'];

        $testData['request']['files']['file'] = $this->getBulkFraudNotifyUploadedXLSXFileFromFileData($fileData);

        if ($mobileSignupTest === false)
        {
            $this->mockFreshdesk($expectFdCallCount);
        }

        $this->ba->adminAuth();

        if ($addPermission === true)
        {
            $this->addAdminPermission();
        }

        if ($mobileSignupTest === true)
        {
            $this->fixtures->edit('merchant', '10000000000000', [
                'signup_via_email' => 0,
            ]);

            $this->fixtures->create('merchant_detail', [
                'merchant_id'    => '10000000000000',
                'contact_mobile' => '9991119991',
            ]);

            $this->fixtures->create('merchant_email', [
                'type'  => 'chargeback',
                'email' => null,
            ]);
        }

        $response = $this->startTest($testData);

        $entityId = $response['entity_id'];

        $fileStoreEntities = (new Models\FileStore\Repository())->fetch([
            'type'      => 'bulk_fraud_notification',
            'entity_id' => $entityId
        ]);

        $this->assertCount(2, $fileStoreEntities);

        /** @var Models\FileStore\Entity $outputFile */
        $outputFile = $fileStoreEntities->firstWhere('name', '=', $entityId . '_output');

        $uploadFile = new UploadedFile($outputFile->getFullFilePath(), $outputFile->getName() . '.' . $outputFile->getExtension());

        $fileData = (new File())->getFileData($uploadFile);

        $this->assertArraySelectiveEquals($expectedOutputFileRows, $fileData);
    }

    private function addAdminPermission()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::BULK_FRAUD_NOTIFY]);

        $role->permissions()->attach($perm->getId());
    }

    private function getBulkFraudNotifyUploadedXLSXFileFromFileData($fileData): UploadedFile
    {
        $inputExcelFile = (new File())->createExcelFile(
            $fileData,
            'bulk_dispute_test_input',
            'files/bulk_fraud_notify/test'
        );

        return $this->createUploadedFile($inputExcelFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createUploadedFile(string $filePath, string $mimeType = null, int $fileSize = -1): UploadedFile
    {
        $this->assertFileExists($filePath);

        $mimeType = $mimeType ?: 'image/png';

        $fileSize = ($fileSize === -1) ? filesize($filePath) : $fileSize;

        return new UploadedFile($filePath, $filePath, $mimeType, null, true);
    }

    private function mockFreshdesk(int $expectFdCallCount): void
    {
        $freshdeskClientMock = $this->getMockBuilder(FreshdeskTicketClient::class)
                                    ->setConstructorArgs([$this->app])
                                    ->onlyMethods(['sendOutboundEmail'])
                                    ->getMock();

        $freshdeskClientMock
            ->expects($this->exactly($expectFdCallCount))
            ->method('sendOutboundEmail')
            ->willReturn(['id' => 123]);

        $this->app->instance('freshdesk_client', $freshdeskClientMock);
    }
}
