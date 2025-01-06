<?php

namespace Functional\QrCode;

use Queue;
use Mockery;
use Carbon\Carbon;


use RZP\Exception\LogicException;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Terminal\Shared;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Merchant\Account;
use RZP\Models\Order;
use RZP\Models\Feature;
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
use RZP\Models\QrCode\NonVirtualAccountQrCode;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;
use RZP\Tests\Traits\TestsWebhookEvents;


class UpiIciciBankQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use TestsWebhookEvents;
    use PartnerTrait;
    use WorkflowTrait;


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

        $this->bqrTerminalLive = $this->fixtures->on('live')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:bharat_qr_terminal');

        $this->bqrTerminal = $this->fixtures->on('test')->create('terminal:bharat_qr_terminal_upi');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $this->vpaTerminal = $this->fixtures->create('terminal:vpa_shared_terminal_icici');

        $this->app['config']->set('gateway.mock_upi_icici', true);
        $this->config['gateway.mock_upi_mozart'] = true;

        $this->mockHolygrailIciciFlowSplitzExerpiementEnabled();

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

        $this->setMockSplitzTreatment([
            'M25grFTOPZEGQS' => 'on'
        ]);

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

        $this->setMockSplitzTreatment([
            'MNYQsdMtB1cYxV' => 'on'
        ]);

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

        $this->setMockSplitzTreatment([
            'MNYQsdMtB1cYxV' => 'on'
        ]);

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
                            "value" => "off",
                        ],
                        [
                            "key" => "holygrail",
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

        $this->setMockSplitzTreatment([
            'M25grFTOPZEGQS' => 'on'
        ]);

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

        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "holygrail",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
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
        $request['content']['merchantTranId'] = $qrCodeEntity['reference'].'qrv2';

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

    public function testDelayedCallbackOnSingleUseQrCode()
    {

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
        $this->markTestSkipped("Multiple use QR Cannot be close");
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

    public function testProcessIciciQrPaymentWithPayerAccountType()
    {
        //TODO: Fix this Test
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

        $qrCode = $this->createQrCode(['customer_id' => 'cust_100000customer']);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];

        $rrn = '000011100101';
        $payerAccountType = 'CREDIT|0123456';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = 'RZP' . $qrCodeId . 'qrv2';
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
        //TODO: Fix this Test
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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
        //TODO: Fix this Test
        $this->markTestSkipped("BQR Disable for Non Ezetap Merchants");

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

    public function testDedicatedTerminalSplitzExpWithVariantOn()
    {
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
        $this->markTestSkipped("skipping Test");

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
        $this->markTestSkipped("skipping Test");

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
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "holygrail",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
        $this->mockSplitzTreatment($output);

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
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "holygrail",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
        $this->mockSplitzTreatment($output);

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
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminalLive->getId(), ['merchant_id' => 'LiveAccountMer']);

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
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminalLive->getId(), ['merchant_id' => 'LiveAccountMer']);

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
        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminalLive->getId(), ['merchant_id' => 'LiveAccountMer']);
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

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('Qr payment processing failed');

        $this->makeUpiIciciPaymentInternal($request);
    }

    public function testPaymentForUnsuccessfulStatusCallback()
    {
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "holygrail",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
        $this->mockSplitzTreatment($output);

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

    public function createPricingForOffline()
    {
        $posQRPricingPlan = [
            'plan_id' => '1hDYlICobzOCYt',
            'plan_name' => 'TestMerchantPosUPIPricingPlan1',
            'payment_method' => 'upi',
            'org_id' => '100000razorpay',
            'type' => 'pricing',
            'feature' => 'payment',
            'receiver_type' => 'offline',
            'fee_bearer' => 'platform',
            'percent_rate' => 0,
            'fixed_rate' => 0,
            'channel' => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);
    }

    public function testProcessReconViaInternalRouteWhenExceptionIsReceivedFromScrooge()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);
        $this->createPricingForOffline();
        $this->ba->scroogeAuth();
        $scroogeMock = Mockery::mock('RZP\Services\Scrooge');
        $scroogeMock->allows('createNewRefundV2')->withAnyArgs()->andReturns(['code' => 400]);
        $this->app->instance('scrooge', $scroogeMock);

        $qrCode = $this->createQrCode(['request_source' => 'ezetap','usage' => 'single_use']);
        $qrCodeId = $qrCode['id'];

        $qrCode   = $this->closeQrCode($qrCodeId);
        $this->assertEquals('closed', $qrCode['status']);
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPaymentInternal'];
        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $response = $this->makeUpiIciciPaymentInternal($request);

        $payment  = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($response['payment']['amount'], $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
    }

    public function testDelayedCallbackOnSingleUseQrCodeForPosQr()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $posQRPricingPlan = [
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'TestMerchantPosUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'offline',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'channel'             => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);

        $qrCode = $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 4000,
                'name'  => 'Mitasha'],
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
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

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);

        $request['content']['BankRRN'] = '015306767324';
        $this->makeUpiIciciPayment($request);

        $qrPayment = $this->getLastEntity('qr_payment', true, 'test');
        $payment   = $this->getLastEntity('payment', true, 'test');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals('single_use', $qrCode['usage']);

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testDelayedCallbackOnSingleUseQrCodeForNonPosQr()
    {

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

        $this->assertEquals(true, $qrPayment['expected']);
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

        $this->assertEquals(false, $qrPayment['expected']);
        $this->assertEquals('refunded', $payment['status']);
    }

    public function testPaymentCreationViaReconForKhatabookStaticQr()
    {
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_gateway_unrecognised_payment_process') => 'on',
                $this->config->get('app.recon_unexpected_qr_payment_via_upi_route')=> 'on',
            ]
        );

        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */

        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminalLive->getId(), ['merchant_id' => 'LiveAccountMer']);
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');

        $qrCode = $this->createQrCode(
            [
                'usage'        => 'multiple_use',
                'type'         => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $this->fixtures->create(
            'qr_code_config',
            [
                'id'           => "NrLfslWOnumXAJ",
                'merchant_id'  => 'LiveAccountMer',
                'config_key'   => "static_qr",
                'config_value' => '{"102IciciDedTml":"' . $qrCodeId . '"}',
            ]
        );

        $qcc   = $this->getDbLastEntity('qr_code_config','live');
        $this->assertNotNull($qcc);

        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $payment     = $this->getDbLastEntity('payment','live');
        $upi         = $this->getDbLastEntity('upi','live');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment_id'], $payment['id']);
    }

    public function testPaymentCreationViaUpiUnexpecterRouteIfSQRDoesNotExists()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE   => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        /**
         *The Terminal didn't have merchant ID as LiveAccountMer, so this is being adjusted for the terminal.
         *This change is not being made during setup because there are tests that involve cases without a dedicated terminal.
         */
        $this->fixtures->on('live')->edit('terminal', $this->bqrTerminalLive->getId(), ['merchant_id' => 'LiveAccountMer']);
        $terminal = $this->fixtures->create('terminal:dedicated_upi_icici_terminal');
        $this->createQrCode(
            [
                'usage'        => 'multiple_use',
                'type'         => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $qrCodeId = 'ABCDEFGHIJKLMN';

        $this->fixtures->create(
            'qr_code_config',
            [
                'id'           => "NrLfslWOnumXAJ",
                'merchant_id'  => 'LiveAccountMer',
                'config_key'   => "static_qr",
                'config_value' => '{"102IciciDedTml":"' . $qrCodeId . '"}',
            ]
        );

        $qcc   = $this->getDbLastEntity('qr_code_config','live');
        $this->assertNotNull($qcc);

        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);

        $payment     = $this->getDbLastEntity('payment');
        $upi         = $this->getDbLastEntity('upi');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
        $this->assertEquals($payment['id'], $upi['payment_id']);
        $this->assertEquals($response['payment_id'], $payment['id']);
        $this->assertEquals('100DemoAccount', $payment['merchant_id']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('123456789012', $payment['reference16']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_ICICI, $payment['gateway']);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $this->assertNull($qrPayment);
    }

}
