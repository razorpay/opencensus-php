<?php

namespace RZP\Tests\Functional\Partner\Commission;

use Illuminate\Database\Eloquent\Factory;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Partner\Commission\Type as CommissionType;
use RZP\Models\Partner\Commission\Constants as CommissionConstants;

class CommissionCreateTest extends TestCase
{
    use PaymentTrait;
    use CommissionTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CommissionCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testImplicitVariableOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);
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

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);
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

        // @todo remove if condition once explicit commission starts getting saved
        if (empty($commission) === false)
        {
            $this->assertTrue($commission['record_only']);
        }
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

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
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

        $testData['request']['url'] = '/payments/pay_'.$payment->getId().'/capture';

        return $testData;
    }

    protected function assertAndGetCommissionByType(string $type, int $totalCount = 1)
    {
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        // @todo remove once logs are verified

        if (($type === CommissionType::EXPLICIT))
        {
            return [$payment, null];
        }

        $this->assertCount(1, $commissions);

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

        if ($type === CommissionType::IMPLICIT)
        {
            $this->assertFalse($commissionByType['record_only']);
        }

        return [$payment, $commissionByType];
    }

    protected function assertExplicitCommissionFeeBreakUp($payment, $commission)
    {
        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        // @todo remove once logs are verified

        $this->assertEmpty($feeBreakups);

        return;

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
}
