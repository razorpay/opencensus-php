<?php

namespace RZP\Tests\Functional\Partner\Commission;

use DB;

use App;
use Mail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Mode;
use RZP\Models\Partner;
use RZP\Constants\Timezone;
use RZP\Models\Partner\Config;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\Partner\Commission;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Mail\Merchant\CommissionInvoice;
use RZP\Mail\Merchant\CommissionOpsInvoice;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\Merchant\CommissionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Partner\Commission\Type as CommissionType;
use RZP\Models\Partner\Commission\Constants as CommissionConstants;

class CommissionCreateTest extends TestCase
{
    use CommissionTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CommissionCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testImplicitVariableOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $this->checkClearOnHoldAndSettlement($commission, Config\Entity::DEFAULT_TDS_PERCENTAGE/100);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getMerchantPricingAmount() - $commissionComponent->getCommissionPricingAmount());
    }

    public function testInvoiceOnHoldClear()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['invoice_ids'] = [$invoice->getId()];
        $this->runRequestResponseFlow($testData);

        // check that invoice status isn't updated
        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('issued', $invoice->getStatus());

        // get commission transactions and verify on hold flag is cleared
        $commission = $this->getDbEntityById('commission', $commission['id']);
        $commTransaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals(0, $commTransaction->getOnHold());

        // check that no adjustment entries are created
        $tdsAdjustment = $this->getDbLastEntity('adjustment');
        $this->assertNull($tdsAdjustment);
    }

    private function createTaxes()
    {
        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYjuyZsOlMK',
                'rate' => 90000,
                'rate_type' => 'percentage',
                'name' => 'CGST 9%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );
        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYqgYcqpr8q',
                'rate' => 90000,
                'rate_type' => 'percentage',
                'name' => 'SGST 9%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );

        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYf1tTUs2Vh',
                'rate' => 180000,
                'rate_type' => 'percentage',
                'name' => 'IGST 18%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );
    }

    public function testInvoiceCompleteFlow()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount' => 944,
                'gross_amount' => 944,
                'tax_amount' => 144,
                'net_amount' => 944,
                'tax_inclusive' => true,
            ]
        ];

        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('under_review', $invoice['status']);

        $app = App::getFacadeRoot();

        $app['workflow']->setMethod('DELETE');

        $testData = $this->testData['testInvoiceActionApproved'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // Check that when the CommissionTdsSettlement job is triggered,
        // the dirty data set in the workflow singleton should be reset or get updated as per the job flow.
        // Taking an example of workflow data gets resetted as the HTTP method wouldn't be DELETE in the flow above
        $this->assertFalse($app['workflow']->getMethod() === 'DELETE');

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('processed', $invoice['status']);
    }

    public function testImplicitVariableOnHoldClearForHighTdsPercentage()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $this->checkClearOnHoldAndSettlement($commission, Config\Entity::TDS_PERCENTAGE_FOR_MISSING_DETAILS/100);
    }

    public function testCommissionSettlementForNonActivePartner()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        // capture payment
        $this->startTest($testData);

        list($_, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testClearOnHoldForCommission'];

        $testData['request']['url'] = '/commissions/partner/'.$commission['partner_id'].'/on_hold/clear';

        // clear on_hold for partner
        $this->runRequestResponseFlow($testData);

        // non-active partner details
        $merchantDetailAttributes = [
            Entity::ACTIVATION_STATUS  => Partner\Activation\Constants::UNDER_REVIEW,
            Entity::LOCKED             => false,
            Entity::SUBMITTED          => true,
        ];

        // add activation details in merchant_detail entity
        $this->fixtures->merchant_detail->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchantDetailAttributes);

        $this->ba->cronAuth();

        Carbon::setTestNow(Holidays::getNthWorkingDayFrom(Carbon::now(), 5)->addHour(10));

        $testData = $this->testData['testInitiateCommissionSettlement'];

        // commission settlement initiate
        $this->runRequestResponseFlow($testData);

        $settlementTransaction = $this->getDbLastEntity('transaction');

        // check that settlement is not created
        $this->assertNotEquals('settlement', $settlementTransaction->getType());

        $this->assertEquals(Channel::YESBANK, $settlementTransaction->getChannel());

        $settlement = $this->getDbLastEntity('settlement');

        $this->assertNull($settlement);

        $this->initiateTransfer(Channel::YESBANK, 'settlement', 'settlement');

        // check that fund transfer attempt is created
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        // check that fund transfer didn't happen
        $this->assertNull($attempt);
    }

    public function testInvoiceGenerate()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->startTest($testData);

        // calling generate invoice twice should still create only one invoice
        $this->startTest($testData);

        $invoices = $this->getDbEntities('commission_invoice');
        $this->assertCount(1, $invoices);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => 'DefaultPartner',
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 1770,
            'tax_amount' => 270,
        ];

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount' => 1770,
                'gross_amount' => 1770,
                'tax_amount' => 270,
                'net_amount' => 1770,
                'tax_inclusive' => true,
            ]
        ];

        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', $partner->getId());

        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('under_review', $invoice['status']);

        Mail::assertSent(CommissionOpsInvoice::class, 1);
        Mail::assertSent(CommissionInvoice::class, 1);

        $testData = $this->testData['testInvoiceFetch'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->runRequestResponseFlow($testData);
    }

    public function testInvoiceGenerateForLineItemsLessThanRupee()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 17,
            'debit'  => 0,
            'fee'    => 17,
            'tax'    => 2,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertNull($invoice);
    }

    public function testCaptureCommission()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);
    }


    public function testCaptureCommissionByPartner()
    {
        list($partner) = $this->createSampleCommission();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commissions/partner/'.$partner->getId().'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testBulkCaptureByPartner()
    {
        list($partner) = $this->createSampleCommission();

        $this->createSampleCommission(
            ['id' => 'SampleMerchant'],
            ['id' => 'SampleAppIdOne'],
            ['id' => 'SubmerchantOne']);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['partner_ids'] = [$partner->getId(), 'SampleMerchant'];

        $this->runRequestResponseFlow($testData);
    }

    public function testImplicitFixedOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'    => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getCommissionPricingAmount());
    }

    /**
     * checks that explicit commission is created on capture along with fee break up
     */
    public function testExplicitOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getCommissionPricingAmount());
    }

    /**
     * checks that explicit commission is created on capture along with fee break up for international payments
     */
    public function testExplicitOnInternationalPayment()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount']   = 4000;
        $payment['currency'] = 'USD';

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, ['convert_currency' => 1]);

        $response = $this->doAuthPaymentOAuth($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->setSubmerchantPrivateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = $payment->getAmount();
        $testData['request']['content']['currency'] = $payment->getCurrency();

        $testData['request']['url'] = '/payments/'.$response['razorpay_payment_id'].'/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    /**
     * checks that explicit commission and fee break up are not created on capture but commission entity is created
     */
    public function testExplicitForRecordOnly()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'    => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertTrue($feeBreakups->isEmpty());

        $this->assertTrue($commission['record_only']);
    }

    /**
     * checks that capture works fine even when there is a missing rule when calculating explicit commission
     */
    public function testExplicitPricingRuleAbsent()
    {
        $testData = $this->setUpCommissionCreate();

        $this->fixtures->create('pricing', [
            'plan_id'      => '180PartnerPlan',
            'percent_rate' => '180',
            'feature'      => 'transfer', // no rule for payment
        ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => '180PartnerPlan',
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount(0, $commissions);

        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertTrue($feeBreakups->isEmpty());
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit and explicit plans are present
     */
    public function testImplicitVariableAndExplicit()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $implicitCommission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $explicitCommission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));

        $implicitCommissionComponent = $commissionComponents->filter(function($component) use ($implicitCommission) {
            return ($component->getCommissionId() === $implicitCommission[Commission\Entity::ID]);
        })->first();

        $explicitCommissionComponent =  $commissionComponents->filter(function($component) use ($explicitCommission) {
            return ($component->getCommissionId() === $explicitCommission[Commission\Entity::ID]);
        })->first();

        $this->assertEquals($implicitCommission[Commission\Entity::FEE] - $implicitCommission[Commission\Entity::TAX], $implicitCommissionComponent->getCommissionPricingAmount());

        $this->assertEquals($explicitCommission[Commission\Entity::FEE] - $explicitCommission[Commission\Entity::TAX], $explicitCommissionComponent->getCommissionPricingAmount());

        $this->assertExplicitCommissionFeeBreakUp($payment, $explicitCommission);
    }

    /**
     * Assert that payment is getting created using fees fetched for customer bearer merchant
     * which includes explicit partner charges when both fees fetch and payment create are on bearer auth
     */
    public function testCustomerBearerPaymentCreateBearerAuth()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

       $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $requestData = $this->testData['testCustomerBearerExplicitBearerAuth'];

        $feesData = $this->runRequestResponseFlow($requestData);

        $payment = $this->getDefaultPaymentArray();
        $payment['fee']    = $feesData['display']['fees'] * 100;
        $payment['amount'] = $feesData['display']['amount'] * 100;

        $response = $this->doAuthPaymentOAuth($payment);

        $paymentEntity = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals($payment['fee'], $paymentEntity->getFee());
    }

    /**
     * Asserts that the commission must not be added since the request is not through the partner / bearer auth
     */
    public function testCustomerBearerExplicitPublicAuth()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $this->fixtures->merchant->edit($subMerchant->getId(),
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            $app->getId(),
            $subMerchant->getId(),
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->setSubmerchantPublicAuth($subMerchant->getId());

        $this->startTest();
    }

    /**
     * Check that when payment fees is fetched using bearer auth and includes commission fees
     * then payment authorization fails if for payment create, merchant auth is used
     */
    public function testCustomerBearerPaymentCreateBearerAndPublicAuth()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $requestData = $this->testData['testCustomerBearerExplicitBearerAuth'];

        $feesData = $this->runRequestResponseFlow($requestData);

        $payment = $this->getDefaultPaymentArray();
        $payment['fee']    = $feesData['display']['fees'] * 100;
        $payment['amount'] = $feesData['display']['amount'] * 100;

        $key = $this->fixtures->create('key', ['merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $key = 'rzp_test_' . $key->getKey();

        $this->app->forgetInstance('basicauth');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment, $key)
        {
            $this->doAuthPayment($payment, null, $key);
        });
    }

    /**
     * Asserts that explicit commission gets created when customer bearer payment is captured
     */
    public function testCustomerBearerExplicitOnPaymentCapture()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee'         => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee_bearer'  => FeeBearer::CUSTOMER,
        ];

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = 400000;

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    /**
     * Checks that capture works on an already authorized payment even after editing the explicit pricing plan.
     * Also checks that commission and fee break ups are created for explicit commission
     */
    public function testCustomerBearerOnExistingAuthorizedPayment()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee'         => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee_bearer'  => FeeBearer::CUSTOMER,
        ];

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $this->fixtures->pricing->edit('C6rNP4gZXcnZWM',
            [
                'percent_rate' => 10,
            ]);

        $this->fixtures->pricing->edit('C6rNP7QE0mIzpW',
            [
                'percent_rate' => 10,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = 400000;

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    public function testImplicitVariableAndExplicitForSubvention()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'commission_model'       => Config\CommissionModel::SUBVENTION,
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        $this->assertEquals(Config\CommissionModel::SUBVENTION, $commission['model']);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit and explicit plans are present and the fee model is postpaid
     */
    public function testImplicitVariableAndExplicitPostpaid()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->setPostpaidFeeModel(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->startTest($testData);

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));
    }

    /**
     * checks that tax is charged on commissions even when tax is not charged on merchant fee
     * when payment less than 2k
     */
    public function testGSTForPaymentsLessThan2K()
    {
        $testData = $this->setUpCommissionCreate(['amount' => 1000 * 100]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    protected function setUpCommissionCreate($paymentAttributes = [])
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createImplicitPricingPlan();

        $defaultPaymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 4000 * 100,
        ];

        $paymentAttributes = array_merge($defaultPaymentAttributes, $paymentAttributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        return $testData;
    }

    protected function assertAndGetCommissionByType(string $type, int $totalCount = 1)
    {
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount($totalCount, $commissions);

        $commissionByType = null;

        foreach ($commissions as $commission)
        {
            if ($commission['type'] === $type)
            {
                $commissionByType = $commission;
                break;
            }
        }

        $this->assertNotEmpty($commissionByType);

        $this->assertTransactionData($commissionByType);

        if ($type === CommissionType::IMPLICIT)
        {
            $this->assertFalse($commissionByType['record_only']);
        }
        else
        {
            // explicit commission can never be of subvention model
            $this->assertEquals(Config\CommissionModel::COMMISSION, $commissionByType['model']);
        }

        return [$payment, $commissionByType];
    }

    protected function assertTransactionData(array $commission)
    {
        if (($commission['record_only'] === true) or ($commission['model'] === Config\CommissionModel::SUBVENTION))
        {
            return;
        }

        $transaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals($commission['credit'], $transaction->getCredit());
        $this->assertEquals($commission['credit'], $transaction->getAmount());

        $this->assertEquals(0, $transaction->getFee());
        $this->assertEquals(0, $transaction->getTax());

        $this->assertTrue($transaction->isOnHold());

        // channel should always be yes_bank for commission settlement
        $this->assertEquals(Channel::YESBANK, $transaction->getChannel());
    }

    protected function assertExplicitCommissionFeeBreakUp($payment, $commission)
    {
        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertNotEmpty($feeBreakups);

        $totalFee = 0;
        $totalTax = 0;

        foreach ($feeBreakups as $breakup)
        {
            $totalFee += $breakup->getAmount();

            if ($breakup->getName() === CommissionConstants::COMMISSION_BREAK_UP_PREFIX . 'tax')
            {
                $totalTax += $breakup->getAmount();
            }
        }

        $this->assertEquals($totalFee, $commission['fee']);
        $this->assertEquals($totalTax, $commission['tax']);

        $this->assertFalse($commission['record_only']);
    }

    protected function checkClearOnHoldAndSettlement($commission, $tdsPercentage)
    {
        $testData = $this->testData['testClearOnHoldForCommission'];

        $testData['request']['url'] = '/commissions/partner/'.$commission['partner_id'].'/on_hold/clear';

        $this->runRequestResponseFlow($testData);

        // check that adjustment is created for tds
        $tdsAdjustment = $this->getDbLastEntity('adjustment');

        $baseCommission = $commission['credit'] - $commission['tax'];

        $tds = $this->getFeeWithoutTax($baseCommission, $tdsPercentage);

        $this->assertEquals(-1 * $tds, $tdsAdjustment['amount']);
        $this->assertEquals(Channel::YESBANK, $tdsAdjustment['channel']);

        // check adjustment transaction data
        $tdsTransaction = $this->getDbLastEntity('transaction');

        $this->assertEquals($tds, $tdsTransaction->getDebit());
        $this->assertEquals('adjustment', $tdsTransaction->getType());
        $this->assertEquals(Channel::YESBANK, $tdsTransaction->getChannel());

        // get commission transactions and verify on hold flag is cleared
        $commTransaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals(0, $commTransaction->getOnHold());

        // trigger settlement on this commission

        $merchantDetailAttributes = [
            Entity::ACTIVATION_STATUS  => Partner\Activation\Constants::ACTIVATED,
            Entity::LOCKED             => false,
            Entity::SUBMITTED          => true,
        ];

        $this->fixtures->merchant_detail->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchantDetailAttributes);

        $this->ba->cronAuth();

        Carbon::setTestNow(Holidays::getNthWorkingDayFrom(Carbon::now(), 5)->addHour(10));

        $testData = $this->testData['testInitiateCommissionSettlement'];

        $this->runRequestResponseFlow($testData);

        // check that settlement transaction is created
        $settlementTransaction = $this->getDbLastEntity('transaction');

        $this->assertEquals('settlement', $settlementTransaction->getType());
        $this->assertEquals(Channel::YESBANK, $settlementTransaction->getChannel());

        $this->assertEquals($commission['credit'] - $tds, $settlementTransaction->getAmount());

        $this->initiateTransfer(Channel::YESBANK, 'settlement', 'settlement');

        // check that fund transfer attempt is created
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');
        $this->assertEquals('NEFT', $attempt->getMode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }
}
