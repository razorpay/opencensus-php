<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Services\RazorXClient;
use RZP\Models\Order\ProductType;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantFeeTest extends TestCase
{
    use PaymentTrait;
    use TerminalTrait;

    protected $card = [
        'number'       => '4012001036275556',
        'expiry_month' => '1',
        'expiry_year'  => '2035',
        'cvv'          => '123',
        'name'         => 'Abhay',
    ];

    protected $qrCode;

    protected $vpa;

    protected $sharpTerminal;

    protected $terminalsServiceMock;

    protected $input = [
        'method'   => 'card',
        'card'     => [],
        'currency' => 'INR',
        'amount'   => 0,
        'email'    => 'test@razorpay.com',
        'contact'  => '9988776655',
        'notes'    => [
            'order_id' => '3453',
        ],
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantFeeTestData.php';

        parent::setUp();

        $this->fee = new Pricing\Fee();

        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $this->qrCode = $this->fixtures->create('qr_code');

        $this->vpa = $this->fixtures->create('vpa');

        $this->sharpTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }

    public function getMockPricingRepo(
        $withCreditCardRule = false, $withReceiverRule = false, $withDefault = true,
        $pricingRules = [], $procurer = true, $fallbackPricing = false)
    {
        if (count($pricingRules) === 0)
        {
            $pricingRules = $this->getDefaultPricingRules($withCreditCardRule, $withReceiverRule, $withDefault, $procurer);
        }

        $pricingPlan = new Pricing\Plan($pricingRules);

        $mock = Mockery::mock(
            'Models\Pricing\Repository',
            function($mock) use ($pricingPlan, $fallbackPricing)
            {
                $mock->shouldReceive('getPricingPlanById')
                     ->andReturn($pricingPlan);

                $mock->shouldReceive('getPricingPlanByIdWithoutOrgId')
                     ->andReturnUsing(function($planId) use($pricingPlan, $fallbackPricing){

                         if ($fallbackPricing === true)
                         {
                             switch ($planId)
                             {
                                 case Pricing\Fee::DEFAULT_VIRTUAL_UPI_PLAN_ID:
                                     return $this->getDefaultVirtualUpiPlan();
                             }
                         }

                         return $pricingPlan;
                     });
            });

        return $mock;
    }

    protected function getDefaultVirtualUpiPlan()
    {
        $fallbackPricingRuleVPA = new Pricing\Entity([
            'id'                  => 'E9t4ljM1mW3CwI',
            'plan_id'             => Pricing\Fee::DEFAULT_VIRTUAL_UPI_PLAN_ID,
            'plan_name'           => 'VirtualUPIfallback',
            'type'                => 'pricing',
            'product'             => 'primary',
            'feature'             => 'payment',
            'receiver_type'       => Receiver::VPA,
            'payment_method'      => 'upi',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 100,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => 5000,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        return new Pricing\Plan([$fallbackPricingRuleVPA]);
    }

    protected function getDefaultPricingRules($withCreditCardRule, $withReceiverRule, $withDefault, $procurer = true)
    {
        $pricingRuleUpi = new Pricing\Entity([
            'id'                  => '1nvp2XPMasRLxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'upi',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleUpiReceiver = new Pricing\Entity([
            'id'                  => '1nvp2ABMasRLxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'upi',
            'receiver_type'       => 'qr_code',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM
        ]);

        $pricingRuleUpiPosReceiver = new Pricing\Entity([
            'id'                  => '1nvp2POSasRLxx',
            'plan_id'             => 'testplan_1',
            'plan_name'           => 'testDefaultPlanForPos',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'upi',
            'receiver_type'       => 'pos',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleOne = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaRLxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleOneNetwork = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaTLxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => 'MC',
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 200,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleDebit = new Pricing\Entity([
            'id'                  => '1nvp2XPMtaTLxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'debit',
            'receiver_type'       => 'qr_code',
            'payment_network'     => 'VISA',
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 200,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleWithReceiver = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaRLxr',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'receiver_type'       => 'qr_code',
            'payment_network'     => 'VISA',
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 200,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetwork = new Pricing\Entity([
            'id'                  => '1OwH8rTI0ejYxx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'receiver_type'       => 'qr_code',
            'payment_network'     => 'MC',
            'payment_issuer'      => null,
            'amount_range_active' => false,
            'amount_range_min'    => 0,
            'amount_range_max'    => 0,
            'percent_rate'        => 300,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);


        $pricingRuleCredit = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaRLxy',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleDebitPin = new Pricing\Entity([
            'id'                  => '1nvp2TPMmaRLxy',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPinPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'debit',
            'auth_type'           => 'pin',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleTwo = new Pricing\Entity([
            'id'                  => '4pmbgtgNVVDd7x',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'debit',
            'payment_network'     => null,
            'payment_issuer'      => null,
            'international'       => false,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
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
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'card',
            'payment_method_type' => 'debit',
            'payment_network'     => null,
            'payment_issuer'      => null,
            'international'       => false,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
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
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanDicl = new Pricing\Entity([
            'id'                  => '1fq0OXpgeyafQx',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt3x',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB1 = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt4x',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB2 = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt5x',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB3 = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt6x',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB4 = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt4y',
            'plan_id'             => '1hDYlICobzOCYy',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'netbanking',
            'payment_method_type' => null,
            'payment_network'     => 'HDFC',
            'payment_issuer'      => null,
            'amount_range_active' => true,
            'amount_range_min'    => 0,
            'amount_range_max'    => 100000000000,
            'percent_rate'        => 0,
            'fixed_rate'          => 500,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanNetB4Cust = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt4z',
            'plan_id'             => '1hDYlICobzOCYy',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'netbanking',
            'payment_method_type' => null,
            'payment_network'     => 'HDFC',
            'payment_issuer'      => null,
            'amount_range_active' => true,
            'amount_range_min'    => 100000000000,
            'amount_range_max'    => 300000000000,
            'percent_rate'        => 0,
            'fixed_rate'          => 500,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::CUSTOMER,
        ]);


        $pricingPlanWallet = new Pricing\Entity([
            'id'                  => '1fq0O3dewex3df',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanWallet1 = new Pricing\Entity([
            'id'                  => '1fq0O3dewex3ff',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanWallet2 = new Pricing\Entity([
            'id'                  => '1fq0O3dewex3ef',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanWallet3 = new Pricing\Entity([
            'id'                  => '1fq0O3dewex3gf',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanEmi = new Pricing\Entity([
            'id'                  => '1fq0O3demix3gf',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingPlanEmiPlan = new Pricing\Entity([
            'id'                  => '1fq0O3demix3tt',
            'plan_id'             => '1EmiSubPricing',
            'plan_name'           => 'EmiSubPricingP',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
            'emi_duration'        => 9,
        ]);

        $pricingPlanEmiAmex = new Pricing\Entity([
            'id'                  => '1fq0O3demiamex',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleCardRecurring = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaabxy',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testCardRecurring',
            'product'             => 'primary',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleCardEsAutomatic = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaaxyz',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'esautomatic',
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleOptimizer = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaaxya',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'optimizer',
            'payment_method'      => null,
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRulePaymentProcurer= new Pricing\Entity([
            'id'                  => '1nvp2XPMmaaxyk',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'procurer'            => 'merchant',
            'payment_method'      => null,
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
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRules =  [
            $pricingRuleUpi,
            $pricingRuleUpiPosReceiver,
            $pricingRuleOne,
            $pricingRuleOneNetwork,
            $pricingRuleTwo,
            $pricingRuleThree,
            $pricingPlanAmex,
            $pricingPlanDicl,
            $pricingPlanNetB,
            $pricingPlanNetB1,
            $pricingPlanNetB2,
            $pricingPlanNetB3,
            $pricingPlanNetB4,
            $pricingPlanNetB4Cust,
            $pricingPlanWallet,
            $pricingPlanWallet1,
            $pricingPlanWallet2,
            $pricingPlanWallet3,
            $pricingPlanEmiPlan,
            $pricingPlanEmi,
            $pricingPlanEmiAmex,
            $pricingRuleCardRecurring,
            $pricingRuleDebitPin,
            $pricingRuleCardEsAutomatic,
            $pricingRuleOptimizer,
        ];

        if ($withDefault === false)
        {
            $pricingRules = [];
        }

        if ($withCreditCardRule === true)
        {
            $pricingRules[] = $pricingRuleCredit;
        }

        if ($withReceiverRule === true)
        {
            $pricingRules[] = $pricingRuleWithReceiver;

            $pricingRules[] = $pricingPlanNetwork;

            $pricingRules[] = $pricingRuleDebit;

            $pricingRules[] = $pricingRuleUpiReceiver;
        }

        if ($procurer === true){
            $pricingRules[] = $pricingRulePaymentProcurer;

        }

        return $pricingRules;
    }

    protected function getMockInternationalPricingRepo()
    {
        $internationalRule = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaRLzz',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
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
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
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

                $mock->shouldReceive('getPricingPlanByIdWithoutOrgId')
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
                'product'             => 'primary',
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
                'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
            ]);

            $maxRateRuleForWallet = new Pricing\Entity([
                'id'                  => '1fq0O3dewex3MR',
                'plan_id'             => '1hDYlICobzOCYt',
                'plan_name'           => 'testMaxFee',
                'product'             => 'primary',
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
                'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
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

                $mock->shouldReceive('getPricingPlanByIdWithoutOrgId')
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

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $useCreditCardRule = false;

        $this->fee->setPricingRepo($this->getMockPricingRepo($useCreditCardRule));

        // Credit Card rule not available in plan,
        // Unknown Cards will be treated as credit card
        // and subsequent rules will be applied.

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxx'], Card\Type::CREDIT, false, false, 'qr_code');
    }

    public function testOfflineRuleSelection()
    {
        $pricingRuleOffline = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaaxyz',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'offline',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'amount_range_active' => true,
            'amount_range_min'    => 0,
            'amount_range_max'    => 1000,
            'percent_rate'        => 300,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRuleOffline1 = new Pricing\Entity([
            'id'                  => '1nvp2XPMmaaxyw',
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'offline',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'amount_range_active' => true,
            'amount_range_min'    => 1000,
            'amount_range_max'    => 10000,
            'percent_rate'        => 500,
            'fixed_rate'          => 0,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $this->fee->setPricingRepo($this->getMockPricingRepo(false,false,false,[$pricingRuleOffline,$pricingRuleOffline1]));

        $this->runMerchantFeeTestOffline('100',['payment' => '1nvp2XPMmaaxyz']);

        $this->runMerchantFeeTestOffline('5000',['payment' => '1nvp2XPMmaaxyw']);

    }

    public function testCreditCardRuleSelectionWithReceiverRule()
    {

        $useCreditCardRule = true;

        $this->fee->setPricingRepo($this->getMockPricingRepo($useCreditCardRule, true));

        // Credit Card rule not available in plan,
        // Card type unknown will be treated as
        // credit card and their rules will be applied

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::UNKNOWN);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxr'], Card\Type::CREDIT, false, false, 'qr_code');

        $this->runMerchantFeeTest('200100', 'MasterCard', ['payment' => '1OwH8rTI0ejYxx'], Card\Type::CREDIT, false, false, 'qr_code');
    }

    public function testCreditCardRuleSelectionWithJustReceiverRule()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(false, true, false));

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxr'], Card\Type::CREDIT, false, false, 'qr_code');

        $this->expectException(\RZP\Exception\LogicException::class);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLxy'], Card\Type::CREDIT);
    }

    public function testCorporateCardRuleWithoutNetwork()
    {
        $corporateRule = new Pricing\Entity([
            'id'                    => '1nvp2XPMmaCORP',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => 'payment',
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_method_subtype'=> 'business',
            'payment_network'       => null,
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $nonCorporateRule = new Pricing\Entity([
            'id'                    => '1nvp2XPnonCorp',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => 'payment',
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_network'       => 'VISA',
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRules = [$corporateRule, $nonCorporateRule];

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, $pricingRules));

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaCORP'], Card\Type::CREDIT, false, false, null, null, null, "business");

    }

    public function testCorporateCardRuleWithNetwork()
    {
        $corporateRule = new Pricing\Entity([
            'id'                    => '1nvp2XPMmaCORP',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => 'payment',
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_method_subtype'=> 'business',
            'payment_network'       => 'VISA',
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $nonCorporateRule = new Pricing\Entity([
            'id'                    => '1nvp2XPnonCorp',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => 'payment',
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_network'       => 'VISA',
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRules = [$corporateRule, $nonCorporateRule];

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, $pricingRules));

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaCORP'], Card\Type::CREDIT, false, false, null, null, null, "business");

        // with razorx
        $this->mockRazorx();

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaCORP'], Card\Type::CREDIT, false, false, null, null, null, "business");
    }

    public function testMagicCheckoutPricing()
    {
        $nonCorporateRule = new Pricing\Entity([
            'id'                    => '1nvp2XPnonCorp',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => 'payment',
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_network'       => 'VISA',
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $magicRule = new Pricing\Entity([
            'id'                    => '1nvp2XPMmaMAGIC',
            'plan_id'               => '1hDYlICobzOCYt',
            'plan_name'             => 'testMaxFee',
            'product'               => 'primary',
            'feature'               => Pricing\Feature::MAGIC_CHECKOUT,
            'payment_method'        => 'card',
            'payment_method_type'   => 'credit',
            'payment_method_subtype'=> 'business',
            'payment_network'       => 'VISA',
            'payment_issuer'        => null,
            'amount_range_active'   => false,
            'amount_range_min'      => 0,
            'amount_range_max'      => 0,
            'percent_rate'          => 200,
            'fixed_rate'            => 0,
            'international'         => 0,
            'min_fee'               => 0,
            'max_fee'               => 1000,
            'fee_bearer'            => Merchant\FeeBearer::PLATFORM,
        ]);

        $pricingRules = [$magicRule, $nonCorporateRule];

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, $pricingRules));

        $this->runMerchantFeeTest(
            '200100', 'Visa',
            ['payment' => '1nvp2XPnonCorp', Pricing\Feature::MAGIC_CHECKOUT => '1nvp2XPMmaMAGIC'],
            Card\Type::CREDIT, false, false, null, null, null, "business", true);

        // with razorx
        $this->mockRazorx();

        $this->runMerchantFeeTest(
            '200100', 'Visa',
            ['payment' => '1nvp2XPnonCorp', Pricing\Feature::MAGIC_CHECKOUT => '1nvp2XPMmaMAGIC'],
            Card\Type::CREDIT, false, false, null, null, null, "business", true);
    }

    public function testCreditCardRuleWithDifferentNetworkAndReceiver()
    {
        $pricingRules = $this->getDefaultPricingRules(false, true, false);

        unset($pricingRules[1]);

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, $pricingRules));

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '1nvp2XPMmaRLxr'], Card\Type::CREDIT, false, false, 'qr_code');

        $this->expectException(\RZP\Exception\LogicException::class);

        $this->runMerchantFeeTest('200100', 'MasterCard', ['payment' => '1nvp2XPMmaRLxr'], Card\Type::CREDIT, false, false, 'qr_code');
    }

    public function testUpiRule()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, true));

        $this->runMerchantTestForUpi('100', ['payment' => '1nvp2XPMasRLxx']);

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, true, true));

        $this->runMerchantTestForUpi('100', ['payment' => '1nvp2ABMasRLxx'], 'qr_code');
    }

    /**
     * VPA fallback pricing should be picked, since receiver_type is VPA
     */
    public function testUpiRuleForVPAPayments()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(
            false, false, true, [], true, true));

        $this->runMerchantTestForUpi('100', ['payment' => 'E9t4ljM1mW3CwI'], Receiver::VPA);
    }

    public function testUpiRuleForPOSPayments()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(
            false, true, true, [], true, true));

        $this->runMerchantTestForUpi('100', ['payment' => '1nvp2POSasRLxx'], Receiver::POS);
    }

    /**
     * For UPI PL payments Default UPI pricing should be picked
     */
    public function testUpiRuleForPlPayments()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(
            false, false, true, [], true, true));

        $order = $this->fixtures->create('order', [
            'product_type' => ProductType::PAYMENT_LINK_V2
        ]);

        $this->runMerchantTestForUpi('100', ['payment' => '1nvp2XPMasRLxx'], Receiver::VPA, $order);
    }

    public function testDebitCardPinRule()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, true));

        $this->runMerchantFeeTest('301', 'Visa', ['payment' => '1nvp2TPMmaRLxy'], Card\Type::DEBIT, false, false, null, 'pin');
    }

    public function testDebitPaymentsWithReceiver()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo(false, true, false));

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMtaTLxx'], Card\Type::DEBIT, false, false,  'qr_code');

        $pricingRules = $this->getDefaultPricingRules(false, true, false);

        unset($pricingRules[0]);

        unset($pricingRules[1]);

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, $pricingRules));

        $this->expectException(\RZP\Exception\LogicException::class);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMtaTLxx'], Card\Type::CREDIT, false, false,  'qr_code');
    }

    public function testDebitCardRuleSelection()
    {
        // Debit Cards and subsequent amount range rules

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '4pmbgtgNVVDd7x'], Card\Type::DEBIT);

        $this->runMerchantFeeTest('200000', 'Visa', ['payment' => '4pmbgtgNVVDd7x'], Card\Type::DEBIT);

        $this->runMerchantFeeTest('200100', 'Visa', ['payment' => '4pmdaEzu3jmDTx'], Card\Type::DEBIT);
    }

    public function testDebitCardRuleSelectionWithRecurring()
    {
        // Debit Cards and subsequent amount range rules

        $expectedPricingRules = [
                                    'payment'          => '4pmbgtgNVVDd7x',
                                    'recurring'        => '1nvp2XPMmaabxy',
                                ];

        $this->runMerchantFeeTest('100', 'Visa', $expectedPricingRules, Card\Type::DEBIT, true);

        $this->runMerchantFeeTest('200000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, true);

        $expectedPricingRules['payment'] = '4pmdaEzu3jmDTx';

        $this->runMerchantFeeTest('200100', 'Visa', $expectedPricingRules, Card\Type::DEBIT, true);
    }

    public function testAmexCardRuleSelection()
    {
        $this->runMerchantFeeTest('100', 'American Express', ['payment' => '1OwH8rTI0ejFxx'], Card\Type::CREDIT);
    }

    public function testDiclCardRuleSelection()
    {
        $this->runMerchantFeeTest('100', 'Diners Club', ['payment' => '1fq0OXpgeyafQx'], Card\Type::CREDIT);
    }

    public function testInternationalCardRuleSelection()
    {
        $isCardInternational = true;

        $this->fee->setPricingRepo($this->getMockInternationalPricingRepo());

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'Visa', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::UNKNOWN, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'American Express', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'American Express', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'American Express', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::UNKNOWN, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'Maestro', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::CREDIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'Maestro', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::DEBIT, false, $isCardInternational);

        $this->runMerchantFeeTest('100', 'Maestro', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::UNKNOWN, false, $isCardInternational);
    }

    public function testInternationalCardRuleSelectionWithNoMatchingRule()
    {
        $isCardInternational = true;

        $this->runMerchantFeeTestWithException('100', 'Visa', ['payment' => '1nvp2XPMmaRLzz'], Card\Type::CREDIT, false, $isCardInternational);
    }

    public function testNetBankingRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $this->runMerchantFeeTestNetB('100', 'SIBL', ['payment' => '1fq0OXpgrfrt3x']);

        $this->runMerchantFeeTestNetB('200000', 'SIBL', ['payment' => '1fq0OXpgrfrt6x']);

        $this->runMerchantFeeTestNetB('200000', 'SBMY', ['payment' => '1fq0OXpgrfrt4x']);

        $this->runMerchantFeeTestNetB('200', 'SBMY', ['payment' => '1fq0OXpgrfrt5x']);
    }

    public function testNetBankingRuleSelectionForCustomerFeeBearer()
    {
        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => Merchant\FeeBearer::CUSTOMER]);

        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $this->fixtures->merchant->setFeeBearer('customer');


        $this->runMerchantFeeTestNetB('200000000000', 'HDFC', ['payment' => '1fq0OXpgrfrt4z']);

        $this->fixtures->merchant->setFeeBearer('platform');

        $pricing = new Pricing\Entity([
            'id'                  => '1fq0OXpgrfrt4y',
            'plan_id'             => '1hDYlICobzOCYy',
            'plan_name'           => 'testDefaultPlan',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method'      => 'netbanking',
            'payment_method_type' => null,
            'payment_network'     => 'HDFC',
            'payment_issuer'      => null,
            'amount_range_active' => true,
            'amount_range_min'    => 0,
            'amount_range_max'    => 100000000000,
            'percent_rate'        => 0,
            'fixed_rate'          => 500,
            'international'       => 0,
            'min_fee'             => 0,
            'max_fee'             => null,
            'fee_bearer'          => Merchant\FeeBearer::PLATFORM,
        ]);

        $this->fee->setPricingRepo($this->getMockPricingRepo(false, false, false, [$pricing]));
        try
        {
            $this->runMerchantFeeTestNetB('100', 'HDFC', ['payment' => '1fq0OXpgrfrt4y']);
        }
        catch(Exception\BadRequestException $ex)
        {
            $this->assertEquals('BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT', $ex->getCode());

            $this->assertEquals('The fees calculated for payment is greater than the payment amount. Please provide a higher amount', $ex->getMessage());

            return;
        }

        $this->fail();
    }

    // in postpaid mode, test should pass even when fee is greater than amount, here the data is such that fee will be grater than amount
    public function testNetBankingRuleSelectionForPlatformFeeBearerPostPaidModeAndFeeLessThanAmount()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $this->fixtures->merchant->setFeeBearer('platform');

        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);

        $amount = 100;

        $response = $this->runMerchantFeeTestNetB($amount, 'HDFC', ['payment' => '1fq0OXpgrfrt4y']);

        $fee = $response[0];

        $this->assertGreaterThan($amount, $fee);

    }
    public function testDynamicFeeBearerRuleSelectionWithMultipleFeeBearerException()
    {
        $plans = [
            [
                'plan_id'             => 'TestPlan1',
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'org_id'              => '100000razorpay',
                'type'                => 'pricing',
                'feature'             => 'payment',
                'fee_bearer'          => 'platform',
            ],
            [
                'plan_id'             => 'TestPlan1',
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'org_id'              => '100000razorpay',
                'type'                => 'pricing',
                'feature'             => 'esautomatic',
                'fee_bearer'          => 'customer',
            ],


        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'dynamic',
            'pricing_plan_id'   => 'TestPlan1',
        ];

        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);

        $this->fixtures->merchant->addFeatures(['es_automatic']);

        // begin test
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = 1000;

        $paymentArray['bank'] = 'hdfc';

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::NETBANKING;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount(1000);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        $fee = (new Pricing\Fee);

        $this->expectException(Exception\LogicException::class);

        $this->expectExceptionMessage("Expected only one type of feebearer");

        $fee->calculateMerchantFees($payment);
    }

    public function testRuleLevelFeeModelPostpaidForNetBanking()
    {
        $this->app['rzp.mode'] = Mode::LIVE;
        $plan = [
                'plan_id'             => 'TestPlan1',
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'org_id'              => '100000razorpay',
                'type'                => 'pricing',
                'feature'             => 'payment',
                'fee_bearer'          => 'platform',
                'fee_model'           => 'postpaid',
            ];


        $this->fixtures->create('pricing', $plan);

        $merchantAttributes = [
            'id'                => 'GfjiTEOfQJJBBX',
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => 'TestPlan1',
            'fee_model'         => 'prepaid'
        ];

        $merchant = $this->fixtures->create('merchant',$merchantAttributes);

        $balance = $this->fixtures->create('balance', ['id' => $merchant->getId(), 'merchant_id' => $merchant->getId()]);

        // begin test
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = 1000;

        $paymentArray['bank'] = 'hdfc';

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::NETBANKING;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount(1000);

        $merchant = Merchant\Entity::find('GfjiTEOfQJJBBX');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => 'payment',
            'amount'      => 100 * 100,   // in paisa
            'merchant_id' => 'GfjiTEOfQJJBBX'
        ]);

        $payment->transaction()->associate($transaction);

        $fee = (new Pricing\Fee);

        //Razorx FeatureFlag FEE_MODEL_OVERRIDE disabled
        $this->mockRazorxControl();
        $fee->calculateMerchantFees($payment);

        $this->assertEquals($payment->transaction->getFeeModel(),null);

        //Razorx FeatureFlag FEE_MODEL_OVERRIDE enabled
        $this->mockRazorx();

        $fee->calculateMerchantFees($payment);

        $this->assertEquals($payment->transaction->getFeeModel(),'postpaid');
    }

    public function testWalletRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        // Credit Card rule not available in plan,
        // Card type unknown will be treated as
        // debit card and their rules will be applied

        $this->runMerchantFeeTestWallet('mobikwik', ['payment' => '1fq0O3dewex3df']);

        $this->runMerchantFeeTestWallet('paytm', ['payment' => '1fq0O3dewex3ef']);

        $this->runMerchantFeeTestWallet('payumoney', ['payment' => '1fq0O3dewex3ff']);

        $this->runMerchantFeeTestWallet('payzapp', ['payment' => '1fq0O3dewex3gf']);
    }

    public function testEmiRuleSelection()
    {
        $this->fee->setPricingRepo($this->getMockPricingRepo());

        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->runMerchantFeeTestEmi('Visa', ['payment' => '1fq0O3demix3gf']);

        $this->runMerchantFeeTestEmi('American Express', ['payment' => '1fq0O3demiamex']);

        $this->runMerchantFeeTestEmiWithMerchantSubvention('Visa', ['payment' => '1fq0O3demix3gf',
            'emi' => '1fq0O3demix3tt']);
    }

    public function testInterstateGstForCard()
    {
        $this->fee->setPricingRepo($this->getMockMaxFeePricingRepo());

        // create merchant
        $merchant = $this->fixtures->create('merchant');

        $balance = $this->fixtures->create('balance', ['id' => $merchant->getId(), 'merchant_id' => $merchant->getId()]);

        $merchantDetails = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => $merchant->getId(),
                'gstin'       => '20kjsngjk2139',
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

            $merchant = Merchant\Entity::find('10000000000000');

            $payment->merchant()->associate($merchant);

            $payment->associateTerminal($this->sharpTerminal);

            $card = (new Card\Entity)->build($this->card);

            $payment->card()->associate($card);

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
        list($fee, $tax, $feesSplit) = $this->runMerchantFeeTest($amount, 'Visa', ['payment' => '1nvp2XPMmaRLMR'], $cardType);

        $this->assertFeesAndTax($fee, $tax, $feesSplit->toArray(), $expectedFee, $expectedTax, $feeComponents);
    }

    protected function runFeeTestWithMaxFeeForWallet($amount, $expectedFee, $expectedTax, $feeComponents)
    {
        list($fee, $tax, $feesSplit) = $this->runMerchantFeeTestWallet('mobikwik', ['payment' => '1fq0O3dewex3MR'], $amount);

        $this->assertFeesAndTax($fee, $tax, $feesSplit->toArray(), $expectedFee, $expectedTax, $feeComponents);
    }

    protected function runMerchantFeeTest($amount, $network, array $expectedRules, $cardType, $isRecurring = false, $isCardInternational = false, $receiver = null, $authType = null, $procurer = null, $subType = null, $oneccOrder = false)
    {
        $payment = $this->createPaymentEntityForCard($amount, $network, $expectedRules, $cardType, $isRecurring, $isCardInternational, $receiver, $authType, $procurer, $subType, $oneccOrder);

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
            $this->assertEquals('SERVER_ERROR_PRICING_RULE_ABSENT', $ex->getCode());

            $this->assertEquals('Invalid rule count: 0, Merchant Id: 10000000000000', $ex->getMessage());

            return;
        }

        $this->fail();
    }

    protected function createPaymentEntityForCard($amount, $network, array $expectedRules, $cardType, $isRecurring = false, $isCardInternational = false, $receiver = null, $authType = null, $procurer = null, $subType = null, $oneccOrder = false)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::CARD;

        $payment = new Payment\Entity($paymentArray);

        $card = (new Card\Entity)->build($this->card);

        $payment->card()->associate($card);

        $payment->card->setNetwork($network);

        $payment->card->setType($cardType);

        $payment->card->setSubType($subType);

        $payment->card->setInternational($isCardInternational);

        if ($receiver === Receiver::QR_CODE)
        {
            $payment->receiver()->associate($this->qrCode);
        }

        $payment->setInternational();

        $payment->setRecurring($isRecurring);

        $payment->setBaseAmount($amount);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $terminal = Terminal\Entity::find('1n25f6uN5S1Z5a');

        if (empty($procurer) === false)
        {
            $data = ["procurer"=>$procurer];

            $tid = $terminal["id"];

            $this->editTerminal($tid, $data);

            $terminal->reload();
        }

        if ($oneccOrder === true) {
            $order = $this->fixtures->order->create1ccOrderWithLineItems();
            $payment->order()->associate($order);
        }

        $payment->associateTerminal($terminal);

        $payment->setAuthType($authType);

        return $payment;
    }

    protected function runMerchantFeeTestOffline($amount,array $expectedRules = [])
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::OFFLINE;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount($amount);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);

        return [$fee, $tax, $feesSplit];
    }

    protected function runMerchantFeeTestNetB($amount, $bank, array $expectedRules)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray['bank'] = $bank;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::NETBANKING;

        $payment = new Payment\Entity($paymentArray);

        $payment->setBaseAmount($amount);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);

        return [$fee, $tax, $feesSplit];
    }

    protected function runMerchantFeeTestWallet($wallet, array $expectedRules, $amount = 50000)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['wallet'] = $wallet;

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::WALLET;

        $payment = new Payment\Entity($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        $payment->setBaseAmount($amount);

        list($fee, $tax, $feesSplit) = $this->fee->calculateMerchantFees($payment);

        $this->assertPricingRules($expectedRules, $feesSplit);

        return [$fee, $tax, $feesSplit];
    }


    protected function runMerchantTestForUpi($amount, array $expectedRules, $receiver = null, $order = null)
    {
        $paymentArray = $this->getDefaultPaymentEntityArray();

        $paymentArray['amount'] = $amount;

        $paymentArray[Payment\Entity::METHOD] = Payment\Method::UPI;

        $payment = new Payment\Entity;
        $payment->generate($paymentArray);
        $payment->fill($paymentArray);

        $payment->setBaseAmount($amount);

        if(empty($order) === false)
        {
            $payment->order()->associate($order);
        }

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        if ($receiver == Receiver::QR_CODE)
        {
            $payment->receiver()->associate($this->qrCode);
        }

        if($receiver === Receiver::VPA)
        {
            $payment->receiver()->associate($this->vpa);
        }

        if($receiver === Receiver::POS)
        {
            $payment->setReceiverType(Receiver::POS);
        }

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

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        $card = (new Card\Entity)->build($this->card);

        $payment->card()->associate($card);

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

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->associateTerminal($this->sharpTerminal);

        $card = (new Card\Entity)->build($this->card);

        $payment->card()->associate($card);

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

        $totalFee = 0;

        foreach ($feeSplit as $feeSplitComponent)
        {
            $totalFee += $feeSplitComponent['amount'];

            $componentName = $feeSplitComponent['name'];

            $this->assertEquals($feeSplitComponent['amount'], $expectedFeeSplit[$componentName]);
        }

        $this->assertEquals($totalFee, $expectedFee);
    }

    public function testDebitCardRuleSelectionWithEsAutomatic()
    {
        $this->fixtures->merchant->addFeatures('es_automatic');

        $expectedPricingRules = [
            'payment'          => '4pmbgtgNVVDd7x',
            'esautomatic'      => '1nvp2XPMmaaxyz',
        ];

        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false);

        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, true);

        $this->runMerchantFeeTest('100', 'Maestro', $expectedPricingRules, Card\Type::DEBIT, false);

        $this->runMerchantFeeTest('100', 'Maestro', $expectedPricingRules, Card\Type::DEBIT, true);

        $expectedPricingRules['payment'] = '1nvp2XPMmaRLxx';

        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::CREDIT, false);

        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::CREDIT, true);

        $this->runMerchantFeeTest('100', 'Maestro', $expectedPricingRules, Card\Type::CREDIT, false);

        $this->runMerchantFeeTest('100', 'Maestro', $expectedPricingRules, Card\Type::CREDIT, true);
    }

    public function testDebitCardRuleSelectionWithOptimizerWithSmartRouterProvider()
    {

        $this->fixtures->merchant->addFeatures(['raas','optimizer_smart_router']);

        $expectedPricingRules = [
            'payment'        => '4pmbgtgNVVDd7x',
            'optimizer'      => '1nvp2XPMmaaxya',
        ];

        //without razorx
        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false, null, null, null, "merchant");

        // with razorx zero pricing rule
        $this->mockRazorx();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        },1);

        $expectedPricingRules = [
            'payment'        => '1nvp2XPMmaaxyk',
            'optimizer'      => '1nvp2XPMmaaxya',
        ];
        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false, null, null, null, "merchant");
    }

    public function testDebitCardRuleSelectionWithOptimizerWithoutSmartRouterProvider()
    {

        $this->fixtures->merchant->addFeatures(['raas']);

        $expectedPricingRules = [
            'payment'        => '4pmbgtgNVVDd7x',
            'optimizer'      => '1nvp2XPMmaaxya',
        ];

        //without razorx
        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false, null, null, null, "merchant");

        // with razorx zero pricing rule
        $this->mockRazorx();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        },1);

        $expectedPricingRules = [
            'payment'        => '1nvp2XPMmaaxyk',
            'optimizer'      => '1nvp2XPMmaaxya',
        ];
        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false, null, null, null, "merchant");
    }


    public function testDebitCardRuleSelectionWithOptimizerWithProcurerZeroPricingRule()
    {
        $this->fixtures->merchant->addFeatures(['raas']);

        $rules = $this->getMockPricingRepo(false, false, true, [], false);

        $this->fee->setPricingRepo($rules);

        $this->mockRazorx();

        $this->mockTerminalsServiceSendRequest(function() {
            return $this->getDefaultTerminalServiceResponse();
        },1);

        $expectedPricingRules = [
            'payment'        => '1ZeroPricingR1',
            'optimizer'      => '1nvp2XPMmaaxya',
        ];
        $this->runMerchantFeeTest('1000', 'Visa', $expectedPricingRules, Card\Type::DEBIT, false, null, null, null, "merchant");
    }


    private function mockRazorxControl()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return "control";
                }));
    }

    private function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return "on";
                }));
    }


}
