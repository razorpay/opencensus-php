<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Base\PublicCollection;

class MerchantFeeTest extends TestCase
{
    use PaymentTrait;

    protected $card = [
        'number' => '4012001036275556',
        'expiry_month' => '1',
        'expiry_year' => '2035',
        'cvv' => '123',
        'name' => 'Abhay',
    ];

    protected $input = [
        'method' => 'card',
        'card' => [],
        'currency' => "INR",
        'amount'   => 0,
        'email' => "test@razorpay.com",
        'contact'   => '9988776655',
        'notes' => [
            'order_id'  => "3453"
        ],
    ];

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantFeeTestData.php';

        parent::setUp();

        $this->fee = new Pricing\Fee();

        $this->fee->setPricingRepo($this->getMockPricingRepo());
    }

    public function getMockPricingRepo($withCreditCardRule = false)
    {
        $pricingRuleOne = new Pricing\Entity([
                'id'                  => '1nvp2XPMmaRLxx',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingRuleCredit = new Pricing\Entity([
                'id'                  => '1nvp2XPMmaRLxy',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingRuleTwo = new Pricing\Entity([
                'id'                  => '4pmbgtgNVVDd7x',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'international'       => false,
                'min_fee'             => 0,
                'max_fee'             => null,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 200000,
                'percent_rate'        => 75,
                'fixed_rate'          => 0,
            ]);

        $pricingRuleThree = new Pricing\Entity([
                'id'                  => '4pmdaEzu3jmDTx',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'international'       => false,
                'min_fee'             => 0,
                'max_fee'             => null,
                'amount_range_active' => true,
                'amount_range_min'    => 200000,
                'amount_range_max'    => 1000000000,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
            ]);

        $pricingPlanAmex = new Pricing\Entity([
                'id'                  => '1OwH8rTI0ejFxx',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'AMEX',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanDicl = new Pricing\Entity([
                'id'                  => '1fq0OXpgeyafQx',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'DICL',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanNetB = new Pricing\Entity([
                'id'                  => '1fq0OXpgrfrt3x',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'SIBL',
                'payment_issuer'      => null,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanNetB1 = new Pricing\Entity([
                'id'                  => '1fq0OXpgrfrt4x',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => true,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 100000000000,
                'percent_rate'        => 0,
                'fixed_rate'          => 50,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanNetB2 = new Pricing\Entity([
                'id'                  => '1fq0OXpgrfrt5x',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanNetB3 = new Pricing\Entity([
                'id'                  => '1fq0OXpgrfrt6x',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'SIBL',
                'payment_issuer'      => null,
                'amount_range_active' => true,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 100000000000,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanWallet = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3df',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'mobikwik',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanWallet1 = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3ff',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'payumoney',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanWallet2 = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3ef',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'paytm',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanWallet3 = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3gf',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingPlanEmi = new Pricing\Entity([
                'id'                  => '1fq0O3demix3gf',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

         $pricingPlanEmiPlan = new Pricing\Entity([
                'id'                  => '1fq0O3demix3tt',
                'plan_id'             => '1EmiSubPricing',
                'plan_name'           => 'EmiSubPricingP',
                'feature'             => 'emi',
                'payment_method'      => 'emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
                'emi_duration'        => 9,
            ]);

        $pricingPlanEmiAmex = new Pricing\Entity([
                'id'                  => '1fq0O3demiamex',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emi',
                'payment_method_type' => null,
                'payment_network'     => 'AMEX',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

         $pricingRuleCardRecurring = new Pricing\Entity([
                'id'                  => '1nvp2XPMmaabxy',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testCardRecurring',
                'feature'             => 'recurring',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
            ]);

        $pricingRules = [
            $pricingRuleOne,
            $pricingRuleTwo,
            $pricingRuleThree,
            $pricingPlanAmex,
            $pricingPlanDicl,
            $pricingPlanNetB,
            $pricingPlanNetB1,
            $pricingPlanNetB2,
            $pricingPlanNetB3,
            $pricingPlanWallet,
            $pricingPlanWallet1,
            $pricingPlanWallet2,
            $pricingPlanWallet3,
            $pricingPlanEmiPlan,
            $pricingPlanEmi,
            $pricingPlanEmiAmex,
            $pricingRuleCardRecurring,
        ];

        if ($withCreditCardRule)
        {
            $pricingRules[] = $pricingRuleCredit;
        }

        $pricingPlan = new Pricing\Plan($pricingRules);

        $mock = Mockery::mock(
            'Models\Pricing\Repository',
            function($mock) use ($pricingPlan)
            {
                $mock->shouldReceive('getPricingPlanById')
                     ->andReturn($pricingPlan);
            });

        return $mock;
    }

    protected function getMockInternationalPricingRepo()
    {
        $internationalRule = new Pricing\Entity([
                'id'                  => '1nvp2XPMmaRLzz',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => true,
                'min_fee'            => 0,
                'max_fee'            => null,
            ]);

        $pricingRules = [
            $internationalRule
        ];

        $pricingPlan = new Pricing\Plan($pricingRules);

        $mock = Mockery::mock(
            'Models\Pricing\Repository',
            function($mock) use ($pricingPlan)
            {
                $mock->shouldReceive('getPricingPlanById')
                     ->andReturn($pricingPlan);
            });

        return $mock;
    }

    protected function getMockMaxFeePricingRepo()
    {
            $maxRateRuleForCard = new Pricing\Entity([
                'id'                  => '1nvp2XPMmaRLMR',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testMaxFee',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => 1000,
            ]);

            $maxRateRuleForWallet = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3MR',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testMaxFee',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'mobikwik',
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => 0,
                'amount_range_max'    => 0,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => 2000,
            ]);

        $pricingRules = [
            $maxRateRuleForCard,
            $maxRateRuleForWallet
        ];

        $pricingPlan = new Pricing\Plan($pricingRules);

        $mock = Mockery::mock(
            'Models\Pricing\Repository',
            function($mock) use ($pricingPlan)
            {
                $mock->shouldReceive('getPricingPlanById')
                     ->andReturn($pricingPlan);
            });

        return $mock;
    }

    /**
     * Credit cards that don't have if have
     * no definite rule to fall back,
     * will fall back to debit card rules
     */
    public function testCreditCardRuleSelection()
    {
        $useCreditCardRule = true;

        $this->fee->setPricingRepo($this->getMockPricingRepo($useCreditCardRule));

        // Credit Card rule not available in plan,
        // Card type unknown will be treated as
        // credit card and their rules will be applied

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("200000", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("200100", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::CREDIT);

        $this->runMerchantFeeTest("200000", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::CREDIT);

        $this->runMerchantFeeTest("200100", "Visa", ["payment" => "1nvp2XPMmaRLxy"], Card\Type::CREDIT);

        $useCreditCardRule = false;

        $this->fee->setPricingRepo($this->getMockPricingRepo($useCreditCardRule));

        // Credit Card rule not available in plan,
        // Unknown Cards will be treated as credit card
        // and subsequent rules will be applied.

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("200000", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("200100", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::CREDIT);

        $this->runMerchantFeeTest("200000", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::CREDIT);

        $this->runMerchantFeeTest("200100", "Visa", ["payment" => "1nvp2XPMmaRLxx"], Card\Type::CREDIT);
    }

    public function testDebitCardRuleSelection()
    {
        // Debit Cards and subsequent amount range rules

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "4pmbgtgNVVDd7x"], Card\Type::DEBIT);

        $this->runMerchantFeeTest("200000", "Visa", ["payment" => "4pmbgtgNVVDd7x"], Card\Type::DEBIT);

        $this->runMerchantFeeTest("200100", "Visa", ["payment" => "4pmdaEzu3jmDTx"], Card\Type::DEBIT);
    }

    public function testDebitCardRuleSelectionWithRecurring()
    {
        // Debit Cards and subsequent amount range rules

        $expectedPricingRules = [
                                    "payment"          => "4pmbgtgNVVDd7x",
                                    "recurring"        => "1nvp2XPMmaabxy",
                                ];

        $this->runMerchantFeeTest("100", "Visa", $expectedPricingRules, Card\Type::DEBIT, true);

        $this->runMerchantFeeTest("200000", "Visa", $expectedPricingRules, Card\Type::DEBIT, true);

        $expectedPricingRules['payment'] = '4pmdaEzu3jmDTx';

        $this->runMerchantFeeTest("200100", "Visa", $expectedPricingRules, Card\Type::DEBIT, true);
    }

    public function testAmexCardRuleSelection()
    {
        $this->runMerchantFeeTest("100", "American Express", ["payment" => "1OwH8rTI0ejFxx"], Card\Type::CREDIT);
    }

    public function testDiclCardRuleSelection()
    {
        $this->runMerchantFeeTest("100", "Diners Club", ["payment" => "1fq0OXpgeyafQx"], Card\Type::CREDIT);
    }

    public function testInternationalCardRuleSelection()
    {
        $isCardInternational = true;

        $this->fee->setPricingRepo($this->getMockInternationalPricingRepo());

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "Visa", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::UNKNOWN, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "American Express", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "American Express", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "American Express", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::UNKNOWN, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "Maestro", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "Maestro", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest("100", "Maestro", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::UNKNOWN, false, $isCardInternational);
    }

    public function testInternationalCardRuleSelectionWithNoMatchingRule()
    {
        $isCardInternational = true;

        $this->runMerchantFeeTestWithException("100", "Visa", ["payment" => "1nvp2XPMmaRLzz"], Card\Type::CREDIT, false, $isCardInternational);
    }

    public function testNetBankingRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $this->runMerchantFeeTestNetB("100", "SIBL", ["payment" => "1fq0OXpgrfrt3x"]);

        $this->runMerchantFeeTestNetB("200000", "SIBL", ["payment" => "1fq0OXpgrfrt6x"]);

        $this->runMerchantFeeTestNetB("200000", "SBMY", ["payment" => "1fq0OXpgrfrt4x"]);

        $this->runMerchantFeeTestNetB("200", "SBMY", ["payment" => "1fq0OXpgrfrt5x"]);

    }

    public function testWalletRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        // Credit Card rule not available in plan,
        // Card type unknown will be treated as
        // debit card and their rules will be applied

        $this->runMerchantFeeTestWallet("mobikwik", ["payment" => "1fq0O3dewex3df"]);

        $this->runMerchantFeeTestWallet("paytm", ["payment" => "1fq0O3dewex3ef"]);

        $this->runMerchantFeeTestWallet("payumoney", ["payment" => "1fq0O3dewex3ff"]);

        $this->runMerchantFeeTestWallet("payzapp", ["payment" => "1fq0O3dewex3gf"]);
    }

    public function testEmiRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->runMerchantFeeTestEmi("Visa", ["payment" => "1fq0O3demix3gf"]);

        $this->runMerchantFeeTestEmi("American Express", ["payment" => "1fq0O3demiamex"]);

        $this->runMerchantFeeTestEmiWithMerchantSubvention("Visa", ["payment" => "1fq0O3demix3gf",
            "emi" => "1fq0O3demix3tt"]);
    }

    public function testInterstateGstForCard()
    {
        $this->fee->setPricingRepo($this->getMockMaxFeePricingRepo());

        // create merchant
        $merchant = $this->fixtures->create('merchant');

        $merchantDetails = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => $merchant->getId(),
                'gstin' => '20kjsngjk2139',
            ]);

        foreach ($this->testData[__FUNCTION__] as $data)
        {
            // create payment
            $amount = $data['amount'];

            $paymentArray = $this->getDefaultPaymentEntityArray();

            $paymentArray['merchant_id'] = $merchant->getId();

            $paymentArray['amount'] = $amount;

            $paymentArray[Payment\Entity::METHOD] = Payment\Method::CARD;

            $payment = new Payment\Entity($paymentArray);

            $payment->setAttribute(Payment\Entity::INTERNATIONAL, false);

            $payment->card = (new Card\Entity)->build($this->card);

            $payment->card->setNetwork('Visa');

            $payment->card->setType($data['card_type']);

            $payment->setBaseAmount($amount);

            list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

            $this->assertFeesAndTax(
                $fee, $tax, $feesSplit->toArray(),
                $data['fee'], $data['tax'], $data['fee_components']);
        }
    }

    public function testFeeWithMaxFeeForCard()
    {
        $this->fee->setPricingRepo($this->getMockMaxFeePricingRepo());

        foreach ($this->testData[__FUNCTION__] as $data)
        {
            $this->runFeeTestWithMaxFeeForCard($data['amount'],
                                                $data['card_type'],
                                                $data['fee'],
                                                $data['tax'],
                                                $data['fee_components']);
        }
    }

    public function testFeeWithMaxFeeForWallet()
    {
        $this->fee->setPricingRepo($this->getMockMaxFeePricingRepo());

        foreach ($this->testData[__FUNCTION__] as $data)
        {
            $this->runFeeTestWithMaxFeeForWallet($data['amount'],
                                                  $data['fee'],
                                                  $data['tax'],
                                                  $data['fee_components']);
        }
    }

    protected function runFeeTestWithMaxFeeForCard($amount, $cardType, $expectedFee, $expectedTax, $feeComponents)
    {
        list($fee, $tax, $feesSplit) = $this->runMerchantFeeTest($amount, "Visa", ["payment" => "1nvp2XPMmaRLMR"], $cardType);

        $this->assertFeesAndTax($fee, $tax, $feesSplit->toArray(), $expectedFee, $expectedTax, $feeComponents);
    }

    protected function runFeeTestWithMaxFeeForWallet($amount, $expectedFee, $expectedTax, $feeComponents)
    {
        list($fee, $tax, $feesSplit) = $this->runMerchantFeeTestWallet("mobikwik", ["payment" => "1fq0O3dewex3MR"], $amount);

        $this->assertFeesAndTax($fee, $tax, $feesSplit->toArray(), $expectedFee, $expectedTax, $feeComponents);
    }

    protected function runMerchantFeeTest($amount, $network, array $expectedRules, $cardType, $isRecurring = false, $isCardInternational = false)
    {
        $payment = $this->createPaymentEntityForCard($amount, $network, $expectedRules, $cardType, $isRecurring, $isCardInternational);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);

        return [$fee, $tax, $feesSplit];
    }

    protected function runMerchantFeeTestWithException($amount, $network, array $expectedRules, $cardType, $isRecurring = false, $isCardInternational = false)
    {
        $payment = $this->createPaymentEntityForCard($amount, $network, $expectedRules, $cardType, $isRecurring, $isCardInternational);

        $payment['id'] = 'testPay1234567';

        try
        {
             $this->fee->calculateMerchantFees($payment);
        }
        catch(Exception\LogicException $ex)
        {
            $this->assertEquals("SERVER_ERROR_PRICING_RULE_ABSENT", $ex->getCode());

            $this->assertEquals("Invalid rule count: 0, Payment Id: testPay1234567", $ex->getMessage());

            return;
        }

        $this->fail();
    }

    protected function createPaymentEntityForCard($amount, $network, array $expectedRules, $cardType, $isRecurring = false, $isCardInternational = false)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::CARD;

        $payment = new Payment\Entity($paymentArray);

        $payment->card = (new Card\Entity)->build($this->card);

        $payment->card->setNetwork($network);

        $payment->card->setType($cardType);

        $payment->card->setInternational($isCardInternational);

        $payment->setInternational();

        $payment->setRecurring($isRecurring);

        $payment->setBaseAmount($amount);

        return $payment;
    }

    protected function runMerchantFeeTestNetB($amount, $bank, array $expectedRules)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray['bank'] = $bank;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::NETBANKING;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount($amount);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);
    }

    protected function runMerchantFeeTestWallet($wallet, array $expectedRules, $amount = 50000)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['wallet'] = $wallet;

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::WALLET;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount($amount);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);

        return [$fee, $tax, $feesSplit];
    }

    protected function runMerchantFeeTestEmi($network, array $expectedRules)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = 500000;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::EMI;

        $paymentArray[Payment\Entity::EMI_PLAN_ID] = '10101010101010';

        $payment = new Payment\Entity($paymentArray);

        $payment->card = (new Card\Entity)->build($this->card);

        $payment->card->setNetwork($network);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);
    }

    protected function runMerchantFeeTestEmiWithMerchantSubvention($network, array $expectedRules)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = 500000;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::EMI;

        $paymentArray[Payment\Entity::EMI_PLAN_ID] = '10101010101010';

        $payment = new Payment\Entity($paymentArray);

        $payment->card = (new Card\Entity)->build($this->card);

        $payment->card->setNetwork($network);

        $payment->setBaseAmount(500000);

        $this->fixtures->merchant->addFeatures('emi_merchant_subvention');

        list($fee, $serviceTax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);
    }

    protected function assertPricingRules(array $expectedRules, $feesSplit)
    {
        foreach ($expectedRules as $feature => $pricingRuleId)
        {
            $originalPricingRuleId = $this->getPricingRuleForFeature($feesSplit, $feature);

            $this->assertEquals($pricingRuleId, $originalPricingRuleId);
        }
    }

    protected function getPricingRuleForFeature($feesSplit, $feature)
    {
       foreach ($feesSplit as $feeSplit)
       {
            if ($feeSplit->getName() === $feature)
            {
                return $feeSplit->getPricingRule();
            }
       }
    }

    protected function assertFeesAndTax($fee, $tax, $feeSplit, $expectedFee, $expectedTax, $expectedFeeSplit)
    {
        $this->assertEquals($expectedFee, $fee);

        $this->assertEquals($expectedTax, $tax);

        foreach ($feeSplit as $feeSplitComponent)
        {
            $componentName = $feeSplitComponent['name'];

            $this->assertEquals($feeSplitComponent['amount'], $expectedFeeSplit[$componentName]);
        }
    }
}
