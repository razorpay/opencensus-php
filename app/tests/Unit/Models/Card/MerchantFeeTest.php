<?php

namespace Tests\Unit\Models\Card;

use Mockery;
use Models\Card;
use Models\Pricing;
use Models\Payment;
// use Tests\TestCase;
use App;
use Models\Payment\Processor\Authorize;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantFeeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/helpers/MerchantFeeTestData.php';

        $this->unknownCard = [
            'number' => '4012001036275556',
            'expiry_month' => '1',
            'expiry_year' => '2017',
            'cvv' => '123',
            'name' => 'Abhay',
        ];

        $this->input = [
            'method' => 'card',
            'card' => [],
            'currency' => "INR",
            'amount'   => 0,
            'email' => "test@razorpay.com",
            'contact'   => "1234567890",
            'notes' => [
                'order_id'  => "3453"
            ],
        ];

        $this->paymentEntity = $this->getDefaultPaymentArray();

        $this->fee = new Pricing\Fee();

        $this->fee->setPricingRepo($this->getMockPricingRepo());
    }

    public function getMockPricingRepo()
    {
        $pricingRuleOne = new Pricing\Entity(array(
                'id' => '1nvp2XPMmaRLxx',
                'plan_id' => '1hDYlICobzOCYt',
                'plan_name' => 'testDefaultPlan',
                'payment_method' => 'card',
                'payment_method_type' => null,
                'payment_network' => null,
                'payment_issuer' => null,
                'amount_range_active' => false,
                'amount_range_min' => 0,
                'amount_range_max' => 0,
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'international' => 0,
            ));

        $pricingRuleTwo = new Pricing\Entity(array (
                'id' => '4pmbgtgNVVDd7x',
                'plan_id' => '1hDYlICobzOCYt',
                'plan_name' => 'testDefaultPlan',
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => NULL,
                'payment_issuer' => NULL,
                'international' => false,
                'amount_range_active' => true,
                'amount_range_min' => 100,
                'amount_range_max' => 200000,
                'percent_rate' => 75,
                'fixed_rate' => 0,
            ));

        $pricingRuleThree = new Pricing\Entity(array(
                'id' => '4pmdaEzu3jmDTx',
                'plan_id' => '1hDYlICobzOCYt',
                'plan_name' => 'testDefaultPlan',
                'payment_method' => 'card',
                'payment_method_type' => 'debit',
                'payment_network' => NULL,
                'payment_issuer' => NULL,
                'international' => false,
                'amount_range_active' => true,
                'amount_range_min' => 200000,
                'amount_range_max' => 1000000000,
                'percent_rate' => 100,
                'fixed_rate' => 0,
            ));

        $pricingPlanAmex = new Pricing\Entity(array(
                'id' => '1OwH8rTI0ejFxx',
                'plan_id' => '1hDYlICobzOCYt',
                'plan_name' => 'testDefaultPlan',
                'payment_method' => 'card',
                'payment_method_type' => null,
                'payment_network' => 'AMEX',
                'payment_issuer' => null,
                'amount_range_active' => false,
                'amount_range_min' => 0,
                'amount_range_max' => 0,
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'international' => 0,
            ));

        $pricingPlanDicl = new Pricing\Entity(array(
                'id' => '1fq0OXpgeyafQx',
                'plan_id' => '1hDYlICobzOCYt',
                'plan_name' => 'testDefaultPlan',
                'payment_method' => 'card',
                'payment_method_type' => null,
                'payment_network' => 'DICL',
                'payment_issuer' => null,
                'amount_range_active' => false,
                'amount_range_min' => 0,
                'amount_range_max' => 0,
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'international' => 0,
            ));

        $pricingPlan = new Pricing\Plan([
            $pricingRuleOne,
            $pricingRuleTwo,
            $pricingRuleThree,
            $pricingPlanAmex,
            $pricingPlanDicl,
        ]);

        return Mockery::mock('Illuminate\Database\Connection',
            function($mock) use ($pricingPlan)
        {
            $mock->shouldReceive("getPricingRulesForCard")
                ->andReturn($pricingPlan);
        });
    }

    public function testCreditCardRuleSelection()
    {
        $this->runMerchantFeeTest("100", "Visa", "4pmbgtgNVVDd7x");
    }

    public function testDebitCardRuleSelection()
    {
        $isDebit = true;

        $this->runMerchantFeeTest("100", "Visa", "4pmbgtgNVVDd7x", $isDebit);

        $this->runMerchantFeeTest("200000", "Visa", "4pmbgtgNVVDd7x", $isDebit);

        $this->runMerchantFeeTest("200100", "Visa", "4pmdaEzu3jmDTx", $isDebit);
    }

    public function testAmexCardRuleSelection()
    {
        $this->runMerchantFeeTest("100", "American Express", "1OwH8rTI0ejFxx");
    }

    public function testDiclCardRuleSelection()
    {
        $this->runMerchantFeeTest("100", "Diners Club", "1fq0OXpgeyafQx");
    }

    protected function runMerchantFeeTest($amount, $networkFullName, $expectedRuleKey, $isDebit = false)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::CARD;

        $payment = new Payment\Entity($paymentArray);

        $payment->card = (new Card\Entity())->build($this->unknownCard);

        $payment->card->setNetwork($networkFullName);

        if ($isDebit)
        {
            $payment->card->setType(Card\Type::DEBIT);
        }

        list($fee, $serviceTax, $ruleKey) = $this->fee->calculateMerchantFees($payment);

        $this->assertEquals($expectedRuleKey, $ruleKey);
    }
}
