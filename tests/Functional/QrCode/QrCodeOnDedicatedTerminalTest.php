<?php

namespace Functional\QrCode;

use Queue;
use Carbon\Carbon;


use RZP\Exception\LogicException;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Merchant\Account;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Jobs\QrStatusCheck;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\Payment\Gateway;
use RZP\Services\Mock\Reminders;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;
use RZP\Tests\Traits\TestsWebhookEvents;


class QrCodeOnDedicatedTerminalTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use TestsWebhookEvents;
    use PartnerTrait;

    private $vpaTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    public function testCreateDynamicQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $qrCode = $this->createQrCode(['usage'          => 'single_use',
                                       'type'           => 'upi_qr',
                                       'fixed_amount'   => true,
                                       'payment_amount' => 10000
                                      ],
                                      'live',
                                      'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');

    }

    public function testCreateStaticQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($response, $terminal, 'live');
    }

    public function testCreateStaticQrWithDedicatedTerminalSharpGateway()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            'test',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr($response, $terminal, 'test');
    }

    protected function enableRazorXTreatmentForQrDedicatedTerminal()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DEDICATED_TERMINAL_QR_CODE => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    protected function enableRazorXTreatmentForQrOnDemandClose()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    public function testQrCodePricingForCreditCard(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', 'LiveAccountMer');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            'LiveAccountMer'
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPayerAccountType'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());

        $this->assertEquals(2950, $payment->getFee());
        $this->assertEquals(450, $payment->getTax());

        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(2500, $feeBreakup[0]['amount']); // 2.0% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(450, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 2000
    }

    public function testQrCodePricingForPPIOnUPI(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ppiOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantPPIOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'wallet',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 300, // 300 base points i.e. 3.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $this->fixtures->create('pricing', $ppiOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1',  Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPPIPayerAccountType'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());

        $this->assertEquals(3540, $payment->getFee());
        $this->assertEquals(540, $payment->getTax());

        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(3000, $feeBreakup[0]['amount']); // 3.0% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(540, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 3000
    }

    public function testQrCodePricingForWalletOnUPI(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ppiOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantPPIOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'wallet',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 300, // 300 base points i.e. 3.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $this->fixtures->create('pricing', $ppiOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1',  Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithWalletPayerAccountType'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());

        $this->assertEquals(3540, $payment->getFee());
        $this->assertEquals(540, $payment->getTax());

        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(3000, $feeBreakup[0]['amount']); // 3.0% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(540, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 3000
    }


    public function testQrCodePricingForPPIOnUPIWithoutSplitz(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ppiOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantPPIOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'wallet',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 300, // 300 base points i.e. 3.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "off"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $this->fixtures->create('pricing', $ppiOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1',  Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPPIPayerAccountType'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals(1770, $payment->getFee());
        $this->assertEquals(270, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']); // 1.50% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 1500
    }

    public function testQrCodePricingForCreditCardWithoutSplitz(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', Account::TEST_ACCOUNT);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            Account::TEST_ACCOUNT
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentWithPayerAccountType'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );
        // Payment Assertions
        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 1.50%
        $this->assertEquals(1770, $payment->getFee());
        $this->assertEquals(270, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']); // 1.50% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 1500
    }
    public function testQrCodePricingForSavings(): void
    {
        $upiPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => null,
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $upiPricingPlan);

        $qrPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantQrCodePricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'qr_code',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 150, // 150 base points i.e. 1.50%
            'fixed_rate'          => 0,
        ];

        $this->fixtures->create('pricing', $qrPricingPlan);

        $ccOnUPIPricingPlan = [
            'plan_id'             => 'TestPlan1',
            'plan_name'           => 'TestMerchantCCOnUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'credit',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 200, // 200 base points i.e. 2.00%
            'fixed_rate'          => 0,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_sharp_terminal');

        $this->fixtures->create('pricing', $ccOnUPIPricingPlan);

        $this->fixtures->merchant->editPricingPlanId('TestPlan1', 'LiveAccountMer');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100000],
            'test',
            'LiveAccountMer'
        );

        $qrCodeId = $qrCode['id'];

        $this->assertNotNull($qrCodeId);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testQrCodePricingForSavings'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = 1000;

        $this->makeUpiIciciPayment($request);

        $payment = $this->getDbLastPayment();
        $feeBreakup = $this->getDbEntities(
            'fee_breakup',
            ['transaction_id' => $payment->getTransactionId()]
        );

        $this->assertEquals(Account::TEST_ACCOUNT, $payment->getMerchantId());
        $this->assertEquals(100000, $payment->getAmount());
        $this->assertEquals('captured', $payment->getStatus());
        // Ensure Default UPI Fees is Charged i.e. 1.50%
        $this->assertEquals(1180, $payment->getFee());
        $this->assertEquals(180, $payment->getTax());
        // Fee Breakup Assertions
        $this->assertCount(2, $feeBreakup);
        $this->assertEquals('payment', $feeBreakup[0]['name']);
        $this->assertEquals(1000, $feeBreakup[0]['amount']); // 1.50% of 100000
        $this->assertEquals('tax', $feeBreakup[1]['name']);
        $this->assertEquals(180, $feeBreakup[1]['amount']); // 18% GST on Fee = 18% of 1500
    }
    public function testProcessPaymentForDynamicQrWithDedicatedTerminal()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->createQrCode(['usage'          => 'multiple_use',
                             'type'           => 'upi_qr',
                             'fixed_amount'   => true,
                             'payment_amount' => 4000
                            ],
                            'live',
                            'LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['merchantId'] = $terminal->getGatewayMerchantId();
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeEntity['reference'];

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'live');
        $payment = $this->getLastEntity('payment', true, 'live');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    protected function enableRazorXTreatmentForClosedQrAutoCapture()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::QR_PAYMENT_AUTO_CAPTURE_FOR_CLOSED_QR => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    public function testDelayedCallbackOnSingleUseQrCode()
    {
        $this->enableRazorXTreatmentForClosedQrAutoCapture();

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000,
             'name'  => 'Mitasha']
        );

        $qrCodeId = $qrCode['id'];
        $qrCode   = $this->closeQrCode($qrCodeId);
        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);
        $request                              = $this->testData['testProcessIciciQrPayment'];
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('single_use', $qrCode['usage']);

        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('captured', $payment['status']);

        $request['content']['BankRRN'] = '015306767324';
        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('single_use', $qrCode['usage']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testDelayedCallbackOnMultipleUseQrCode()
    {
        $this->enableRazorXTreatmentForClosedQrAutoCapture();

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000,
             'name'  => 'Mitasha']
        );

        $qrCodeId = $qrCode['id'];
        $qrCode   = $this->closeQrCode($qrCodeId);
        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('multiple_use', $qrCode['usage']);

        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('captured', $payment['status']);

        $currentTime = str_replace([':', '-', ' '], '', Carbon::now(Timezone::IST)->toDateTimeString());

        $request['content']['TxnCompletionDate'] = $currentTime;
        $request['content']['BankRRN']           = '015306767324';

        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('multiple_use', $qrCode['usage']);

        $this->assertEquals(0, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testSingleUseQrCodeWithFixedAmount()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'name'           => 'Mitasha'
            ],
            'live',
            'LiveAccountMer'
        );

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');
    }

    public function testSingleUseQrCodeWithoutFixedAmount()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => false, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );
    }

    public function testMultipleUseQrCodeWithoutCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->runEntityAssertionsForDedicatedTerminalQr($qrCode, $terminal, 'live');
    }

    public function testMultipleUseQrCodeWithCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $closeBy = str_replace([':', '-', ' '], '', Carbon::now(Timezone::IST)->toDateTimeString());
        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha']
        );
    }

    public function testCloseQrCodeForSingleUse()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->assertEquals(Status::ACTIVE, $qrCode['status']);
        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
    }

    public function testCloseQrCodeForMultipleUse()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'multiple_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha'],
            'live',
            'LiveAccountMer'
        );

        $this->assertEquals(Status::ACTIVE, $qrCode['status']);

        $this->expectException('RZP\Exception\BadRequestException');
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_CLOSE_STATIC_QR_CODE_FAILURE);

        $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');
    }

    public function testCreateQrCodeWithRequestSourceHeader()
    {
        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $headers = [
            'X-Razorpay-Request-Source'  => 'payMobApp'
        ];

        $response = $this->createQrCode($input, 'test', '10000000000000', $headers);

        $expectedResponse = $this->testData['testCreateUpiQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $qrCode = $this->getDbLastEntity('qr_code');

        $this->assertEquals($headers['X-Razorpay-Request-Source'], $qrCode['request_source']);
    }

    public function testCreateQrCodeWithIncorrectRequestSourceHeader()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Not a valid source: abc');

        $input = [
            'type'  => 'upi_qr',
            'usage' => 'multiple_use'
        ];

        $headers = [
            'X-Razorpay-Request-Source'  => 'abc'
        ];

        $this->createQrCode($input, 'test', '10000000000000', $headers);
    }

    public function testProcessIciciQrPaymentWithPayerAccountType()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'CREDIT|0123456';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals('credit_card', $payment['reference2']);
    }

    public function testProcessIciciQrPaymentWithPayerAccountTypeNonCredit()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'PPIWALLET';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals('wallet', $payment['reference2']);
    }

    public function testProcessIciciQrPaymentWithInvalidPayerAccountType()
    {
        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'INVALIDTYPE';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAccountType'] = $payerAccountType;

        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertNull($payment['reference2']);
    }

    public function testCloseQrCodeWithOnDemandFeatureFlagDisabled()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('This feature is not available for your account. Contact support to get it enabled');

        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $this->closeQrCode($response['id']);
    }

    public function testCloseQrCodeWithOnDemandFeatureFlagEnabled()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->fixtures->merchant->addFeatures(['close_qr_on_demand']);

        $response = $this->createQrCode();

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $closeResponse = $this->closeQrCode($response['id']);

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $this->runEntityAssertions($closeResponse);
    }

    public function testCloseQrCodeWithQRNotCreatedUsingICICITerminal()
    {
        $this->enableRazorXTreatmentForQrOnDemandClose();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('This feature is not available for your account. Contact support to get it enabled');

        $response = $this->createQrCode();

        $response['qr_string'] = '05240130rzr.qrmoremegast00437171@abcabc27390240121RZPL2070"';

        $this->assertEquals(Status::ACTIVE, $response['status']);

        $this->closeQrCode($response['id']);
    }

    public function testCloseSingleUseQrCodeWithCloseBy()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $this->fixtures->on('live')->merchant->addFeatures(['close_qr_on_demand'],'LiveAccountMer');

        $terminal = $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');
        $this->fixtures->on('live')->terminal->edit($terminal['id'], ['gateway_merchant_id2' => 'razorpay@icici']);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy ,'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals(Status::ACTIVE, $qrCodeEntity['status']);

        $closeResponse = $this->closeQrCode($qrCodeEntity['id'],'live','LiveAccountMer');

        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
    }

    public function testCreateStaticQrWithoutTerminal(): void
    {
        $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi', [
            'merchant_id'         => Account::SHARED_ACCOUNT,
            'gateway_merchant_id' => 'shared_bharat_qr',
        ]);

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');
    }

    public function testDedicatedTerminalSplitzExpWithVariantOn()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

    }

    public function testDedicatedTerminalSplitzExpWithVariantOff()
    {
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "off"
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');


        $vpa    = $this->getLastEntity('vpa', true,'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertStringContainsString($vpa['username'], $qrCodeEntity['qr_string']);
        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);
    }

    public function testDedicatedTerminalSplitzExpWithNullVariant()
    {
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($output);

        $closeBy = Carbon::now(Timezone::IST)->addSeconds(200)->getTimestamp();

        $qrCode = $this->createQrCode(
            ['usage'    => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'close_by' => $closeBy, 'name' => 'Mitasha'], 'live','LiveAccountMer');


        $vpa    = $this->getLastEntity('vpa', true,'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertStringContainsString($vpa['username'], $qrCodeEntity['qr_string']);
        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);
    }

    public function testSelectSecondTerminalForQrCreation()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $this->fixtures->create('terminal:live_dedicated_upi_yesbank_terminal');

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name'  => 'Mitasha'], 'live', 'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->assertEquals($qrCodeEntity['id'], $qrCode['id']);
    }

    public function testCreateQrWithOnDemandFeatureFlagEnabledAndCloseQrOnDemandForYesBank()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->fixtures->on('live')->merchant->addFeatures(['close_qr_on_demand']);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_QR_CODE_ON_DEMAND_CLOSE_FOR_YES_BANK);

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );

    }

    public function testBharatQRWithNoDedicatedTerminal()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->expectExceptionMessage('VPA is required for generating QR');
        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'bharat_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'Mitasha']
        );

    }

    public function testProcessIciciQrPaymentOnSharedTerminalForSingleUseQrViaVPACallbackRoute()
    {
        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000]
        );

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->makeIciciQrPaymentViaUpiTransferRoute($request);

        $this->runQrPaymentAssertions($qrCodeId, $request);
    }

    public function testProcessIciciQrPaymentOnSharedTerminalForMultipleUseQrViaVPACallbackRoute()
    {
        $qrCode = $this->createQrCode(
            [
                'type'  => 'upi_qr',
                'usage' => 'multiple_use'
            ]
        );

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentOnSharedTerminalForSingleUseQrViaVPACallbackRoute'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->makeIciciQrPaymentViaUpiTransferRoute($request);

        $this->runQrPaymentAssertions($qrCodeId, $request);
    }

    //This case should not happen but it is hypothetical case
    public function testProcessIciciQrPaymentOnSharedTerminalViaVPACallbackRouteWithoutRZPPrefix()
    {
        $qrCode = $this->createQrCode(
            [
                'type'  => 'upi_qr',
                'usage' => 'multiple_use'
            ]
        );

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentOnSharedTerminalForSingleUseQrViaVPACallbackRoute'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';

        $this->makeIciciQrPaymentViaUpiTransferRoute($request);

        $this->runQrPaymentAssertions($qrCodeId, $request);
    }

    //unexpected payment: Amount mismatch
    public function testProcessUnexpectedIciciQrPaymentOnSharedTerminalViaVPACallbackRoute()
    {
        $qrCode = $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'usage'          => 'multiple_use',
                'fixed_amount'   => true,
                'payment_amount' => 500,
            ]
        );

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentOnSharedTerminalForSingleUseQrViaVPACallbackRoute'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->makeIciciQrPaymentViaUpiTransferRoute($request);

        $qrPayment        = $this->getDbLastEntity('qr_payment');
        $payment          = $this->getDbLastEntity('payment');
        $upi              = $this->getDbLastEntity('upi');
        $refund           = $this->getDbLastEntity('refund');

        $merchantTranId = $request['content']['merchantTranId'];

        $this->assertEquals($rrn, $upi['npci_reference_id']);
        $this->assertEquals($merchantTranId, $upi['merchant_reference']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
        $this->assertEquals($request['content']['PayerAmount'], sprintf('%.2f', $refund['amount'] / 100));
        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals("Actual payment amount does not match expected payment amount", $qrPayment['unexpected_reason']);
    }

    public function testMultiuseQrPaymentWithoutRZPPrefix()
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage'    => 'multiple_use', 'type' => 'upi_qr',
             'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] =  $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment        = $this->getDbLastEntity('qr_payment','live');
        $payment          = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
    }

    public function testMultiuseQrPaymentWithRZPPrefix()
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage'    => 'multiple_use', 'type' => 'upi_qr',
             'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $this->runQrPaymentAssertions($qrCodeId, $request, 'live');

    }

    public function testMultiuseQrPaymentWithRZPPrefixReconWithART()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage'    => 'multiple_use', 'type' => 'upi_qr',
             'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);
        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->makeUpiIciciPayment($request);

        $qrPayment        = $this->getDbLastEntity('qr_payment','live');
        $payment          = $this->getDbLastEntity('payment','live');
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);

        $requestInternal = $this->testData['testProcessIciciQrPaymentInternal'];

        $requestInternal['content']['BankRRN'] = $rrn;
        $requestInternal['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';
        $response = $this->makeUpiIciciPaymentInternal($requestInternal);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $payment     = $this->getDbLastEntity('payment','live');

        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);

    }

    public function testMultiUseQrPaymentInternalWithPrefix()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage'    => 'multiple_use', 'type' => 'upi_qr',
             'name' => 'Mitasha'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentInternal'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $response = $this->makeUpiIciciPaymentInternal($request);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $payment     = $this->getDbLastEntity('payment','live');

        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testQrPaymentWithSpecialCharUTR()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            ['usage'    => 'multiple_use', 'type' => 'upi_qr',
             'name' => 'Shah'], 'live','LiveAccountMer');

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $this->assertEquals($qrCode['id'], $qrCodeEntity['id']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentInternal'];

        $rrn = '3245@790101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionMessage('The provider reference id format is invalid.');

        $this->makeUpiIciciPaymentInternal($request);
    }

    public function testCreateSingleUseQrCodeWithGatewayErrorException() // Testing exception handling for QR Creation with icici dedicated terminal
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $this->expectExceptionMessage('QrCode creation failed due to error at bank or wallet gateway');
        $this->expectException(BadRequestException::class);

        $this->app['config']->set('gateway.mock_upi_icici', false);

        $iciciGatewayMock = \Mockery::mock('RZP\Gateway\Upi\Icici\Gateway')->makePartial();

        $iciciGatewayMock
            ->shouldReceive('getQrRefId')
            ->andThrow(
                new \RZP\Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_FATAL_ERROR)
            );

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'testCreateSingleUseQrCodeWithErrorFromGateway'],
            'live',
            'LiveAccountMer');
    }

    public function testCreateSingleUseQrCodeWithRuntimeException() // Testing exception handling for QR Creation with icici dedicated terminal
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $this->expectExceptionMessage('QrCode creation failed due to error at bank or wallet gateway');
        $this->expectException(BadRequestException::class);

        $this->app['config']->set('gateway.mock_upi_icici', false);

        $iciciGatewayMock = \Mockery::mock('RZP\Gateway\Upi\Icici\Gateway')->makePartial();

        $iciciGatewayMock
            ->shouldReceive('getQrRefId')
            ->andThrow(
                new \RZP\Exception\RuntimeException("Invalid Response")
            );

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'testCreateSingleUseQrCodeWithErrorFromGateway'],
            'live',
            'LiveAccountMer');
    }

    public function testCreateSingleUseQrCodeWithServerErrorException() // Testing exception handling for QR Creation with yesbank dedicated terminal
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

        $this->expectExceptionMessage('QrCode creation failed due to error at bank or wallet gateway');
        $this->expectException(BadRequestException::class);

        $this->app['config']->set('gateway.mock_upi_yesbank', false);

        $iciciGatewayMock = \Mockery::mock('RZP\Gateway\Upi\Yesbank\Gateway')->makePartial();

        $iciciGatewayMock
            ->shouldReceive('getQrRefId')
            ->andThrow(
                new ServerErrorException('test error', ErrorCode::BAD_REQUEST_QR_CODE_REF_ID_GENERATION_FAILURE)
            );

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name' => 'testCreateSingleUseQrCodeWithErrorFromGateway']);
    }

    public function testPaymentForUnsuccessfulStatusCallback()
    {
        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->fixtures->on('live')->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode([
                                          'usage'          => 'single_use',
                                          'type'           => 'upi_qr',
                                          'fixed_amount'   => true,
                                          'payment_amount' => 4000
                                      ],
                                      'live',
                                      'LiveAccountMer'
        );

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['TxnStatus'] = 'FAILURE';

        $this->makeUpiIciciPayment($request);
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getLastEntity('payment', true, 'live');
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, 'live');

        $this->assertEquals('failed callback', $qrPaymentRequest['failure_reason']);
        $this->assertEquals(null, $qrPayment);
        $this->assertEquals(null, $payment);
    }

    public function testQrCodeCreatedAndClosedWebhookEventsWithTransactionIsolation()
    {
        $this->createPartnerAndSubmerchantMapping();

        $this->mockSplitzTreatmentBulkRequest([["variant" => ["name" => "enable"]]]);

        $expectedEventData = $this->testData[__FUNCTION__];

        $this->expectWebhookEventWithContext(
            'qr_code.created',
            ['entity_type' => 'qr_code', 'event_type' => 'partnership'],
            function (array $event) use ($expectedEventData)
            {
                $this->assertArraySelectiveEquals($expectedEventData, $event);
                $this->assertArrayNotHasKey('context', $event);
            }
        );

        $qrCode = $this->createQrCode(['type'  => 'upi_qr', 'usage' => 'multiple_use']);

        $expectedEventData['event'] = 'qr_code.closed';
        $expectedEventData['payload']['qr_code']['entity']['status'] = 'closed';

        $this->expectWebhookEventWithContext(
            'qr_code.closed',
            ['entity_type' => 'qr_code', 'event_type' => 'partnership'],
            function (array $event) use ($expectedEventData)
            {
                $this->assertArraySelectiveEquals($expectedEventData, $event);
                $this->assertArrayNotHasKey('context', $event);
            }
        );

        $this->closeQrCode($qrCode['id']);
    }

    public function testCreateQrCodeWithPaiseInAmountForDedicatedTerminal()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);

        $this->setMockRazorxTreatment([RazorxTreatment::QR_AMOUNT_MISMATCH_FIX => RazorxTreatment::RAZORX_VARIANT_ON]);

        $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'payment_amount' => 27071,
                'fixed_amount'   => true,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertStringContainsString('am=270.71', $qrCode->getQrString());

        // Should I use getAmount() or getRawAmount() here?
        $this->assertEquals(27071, $qrCode->getAmount());
    }
}
