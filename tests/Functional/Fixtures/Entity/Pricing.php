<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models;
use RZP\Models\Pricing\Entity;
use RZP\Models\Pricing\Feature;
use RZP\Models\Merchant\Balance\Type;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class Pricing extends Base
{
    use DbEntityFetchTrait;

    const DEFAULT_PRICING_PLAN_ID    = '1hDYlICobzOCYt';
    const DEFAULT_COMMISSION_PLAN_ID = 'C6rNP3xJcsMXQY';

    public function create(array $attributes = [])
    {
        if (empty($attributes) === true)
        {
            return parent::create($attributes);
        }

        $accountType = $this->getAccountType($attributes);

        $defaultValues = [
            Entity::CHANNEL             => null,
            Entity::ACCOUNT_TYPE        => $accountType,
            Entity::APP_NAME            => null,
            Entity::PAYMENT_METHOD_TYPE => null,
            Entity::PAYMENT_NETWORK     => null,
            Entity::PAYMENT_ISSUER      => null,
            Entity::PERCENT_RATE        => 0,
            Entity::FIXED_RATE          => 0,
            Entity::AMOUNT_RANGE_ACTIVE => 0,
            Entity::AMOUNT_RANGE_MIN    => null,
            Entity::AMOUNT_RANGE_MAX    => null,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $pricing = parent::create($attributes);

        return $pricing;
    }

    protected function getDefultPlanArray($pricingPlanId = self::DEFAULT_PRICING_PLAN_ID)
    {
        $rows = [
            [
                'id'                  => '1nvp2XPMmaRLxb',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1OwH8rTI0ejFxS',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'AMEX',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1fq0OXpgeyafQq',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => 'DICL',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1nwo5YENadEFvf',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'international'       => 1,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOyaqpB',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOyaqpC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'HDFC',
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqPP',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'nach',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqQQ',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'nach',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpD',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpeOJaqpF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpfOJaqqG',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'debitcard',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD01Xpe3JaqpG',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'debitcard',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD0BXpfOJaqqE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar_fp',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zD01Xpe3JaqpF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar_fp',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyrD',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'international'       => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyaF',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3QYFf1zbys6',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'aeps',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 250,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zhyaE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 500,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYf21zbybG',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybGCYf2',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybGCab2',
                'plan_id'             => 'A8UwvIbaL8n4Q8',
                'plan_name'           => 'testDefaultQrPlan',
                'feature'             => 'payment',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'receiver_type'       => 'qr_code',
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybabab2',
                'plan_id'             => 'ArGUUem5z3UADv',
                'plan_name'           => 'testDefaultEmiPlan',
                'feature'             => 'payment',
                'payment_method'      => 'emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab2',
                'plan_id'             => 'A8UwvIbaL8n4Q8',
                'plan_name'           => 'testDefaultQrPlan',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 100,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'receiver_type'       => 'qr_code',
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab3',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'cardless_emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab4',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'fund_account_validation',
                'payment_method'      => 'bank_account',
                'percent_rate'        => 0,
                'fixed_rate'          => 300,
                'max_fee'             => 300,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab5',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'paylater',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacab6',
                'plan_id'             => 'E9t4ljLBnt2cad',
                'plan_name'           => 'testDefaultVpaPlan',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 200,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'receiver_type'       => 'vpa',
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacap0',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'cred',
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacap1',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'twid',
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacap2',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'cardless_emi',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => 'walnut369',
                'percent_rate'        => 500,
                'fixed_rate'          => 0,
                'max_fee'             => 5000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybacap3',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'trustly',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybadbq4',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'poli',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybadbq5',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'sofort',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybadbq6',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'app',
                'payment_method_type' => null,
                'payment_network'     => 'giropay',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybadbq7',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'intl_bank_transfer',
                'payment_method_type' => null,
                'payment_network'     => 'ach',
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE31zbybadbq8',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'payment',
                'payment_method'      => 'intl_bank_transfer',
                'payment_method_type' => null,
                'payment_network'     => 'swift',
                'payment_issuer'      => null,
                'percent_rate'        => 500,
                'fixed_rate'          => 0,
                'org_id'              => '100000razorpay',
            ],
        ];

        return $rows;
    }

    public function createTestPlanForNoOndemandAndEsAutomaticPricing()
    {
        $pricingPlanId = '1BFFkd38fFGbnh';

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ]

        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createTestPlanForPosPayments()
    {
        $pricingPlanId = '1zD0BpqeO1qqpB';

        $rows = [
            [
                'id'                  => '1zD0Bpqeupipos',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlanForPosUpi',
                'product'             => 'primary',
                'procurer'            => 'razorpay',
                'feature'             => 'payment',
                'payment_method'      => 'upi',
                'receiver_type'       => 'pos',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 0,
                'international'       => 0,
                'min_fee'             => 0,
                'max_fee'             => null,
                'fee_bearer'          => 'platform',
            ],
            [
                'id'                  => '1zD0Bpqcardpos',
                'plan_id'             => '1zD0BpqeO1qqpB',
                'plan_name'           => 'testDefaultPlanForPosCard',
                'product'             => 'primary',
                'feature'             => 'payment',
                'payment_method'      => 'card',
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
                'fee_bearer'          => 'platform',
            ]
        ];

        $this->addPricingRulesToDb($rows);

    }

    public function createOndemandPercentRatePricingPlan()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1GuENK6Hl2BWGg',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'settlement_ondemand',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 200,
                'org_id'              => '100000razorpay',
            ],
        ];
        $this->addPricingRulesToDb($rows);
    }

    public function createOndemandFixedRatePricingPlan()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1GuENK6Hl2BWGg',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'settlement_ondemand',
                'payment_method'      => 'fund_transfer',
                'fixed_rate'          => 500,
                'org_id'              => '100000razorpay',
            ],
        ];
        $this->addPricingRulesToDb($rows);
    }

    public function createPricingPlanForICICISubMerchant($feeBearer = 'platform')
    {
        $planName = 'EcomPLATnewINTMAIN';

        if ($feeBearer === 'customer')
        {
            $planName = 'EcomCustNewINT';
        }

        $rows = [
            [
                'id'                  => '1GuENK6Hl2BWGg',
                'plan_id'             => '1ycviEdCgurrFI',
                'plan_name'           => $planName,
                'feature'             => 'icici_pricing_automation',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 200,
                'org_id'              => '100000razorpay',
                'fee_bearer'          => $feeBearer,
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsPricingPlanWithDefaultMethodNull()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zbxaE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'fixed_rate'          => 546,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createDefaultPlan()
    {
        $rows = $this->getDefultPlanArray();

        $this->addPricingRulesToDb($rows);
    }

    public function editDefaultPlan($attributes = [])
    {
        $rows = $this->getDefultPlanArray();

        $this->editPricingPlan($rows, $attributes);
    }

    public function createDefaultBankingPlan()
    {
        $this->addPricingRulesToDb(Models\Pricing\DefaultPlan::getBankingPlanData());

        $this->addPricingRulesToDb(Models\Pricing\DefaultPlan::getZeroBankingPlanData());
    }

    public function createStandardPlan()
    {
        $pricingPlanId = '1A0Fkd38fGZPVC';

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdf0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1pteg2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'transfer',
                'percent_rate'   => 240,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd7',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'paylater',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd8',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'card',
                'percent_rate'   => 20,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTd9',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payout',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTe1',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'settlement_ondemand',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zbyeGCTe2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => 12,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createStandardPricingPlanForFeeBearer($feeBearer)
    {
        $pricingPlanId = '3R0Ssm31kRSKSS';

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1osdf0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1pteg2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'esautomatic',
                'payment_method' => 'transfer',
                'percent_rate'   => 240,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd7',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'esautomatic',
                'payment_method' => 'paylater',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd8',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'esautomatic',
                'payment_method' => 'card',
                'percent_rate'   => 20,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTd9',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payout',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTe1',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'settlement_ondemand',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
            [
                'id'             => '1zE31zbyeGCTe2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_pricing_plan_for_fee_bearer',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => 12,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'fee_bearer'     => $feeBearer,
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createZeroPricingplan()
    {
        $pricingPlanId = '10ZeroPricingP';

        $rows = [
            [
                'id'             => '1ZeroPricingR1',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR3',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR7',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ZeroPricingR8',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'ZeroPricingPlan',
                'feature'        => 'fund_account_validation',
                'payment_method' => 'bank_account',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsDefaultPricingplan()
    {
        $pricingPlanId = Models\Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_ID;

        $rows = [
            [
                'id'                  => 'DfltPricingPR1',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'percent_rate'        => 0,
                'fixed_rate'          => 499,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR2',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'percent_rate'        => 0,
                'fixed_rate'          => 999,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR3',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'percent_rate'        => 0,
                'fixed_rate'          => 1999,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => 4294967295,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR4',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 499,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR5',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 999,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR6',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'upi',
                'percent_rate'        => 0,
                'fixed_rate'          => 1999,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => 4294967295,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR7',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'netbanking',
                'percent_rate'        => 0,
                'fixed_rate'          => 499,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR8',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'netbanking',
                'percent_rate'        => 0,
                'fixed_rate'          => 999,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingPR9',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => 'netbanking',
                'percent_rate'        => 0,
                'fixed_rate'          => 1999,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => 4294967295,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsDefaultPricingV2Plan()
    {
        $pricingPlanId = Models\Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;

        $rows = [
            [
                'id'                  => 'DfltPricingP10',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 799,
                'amount_range_active' => 1,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingP11',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1199,
                'amount_range_active' => 1,
                'amount_range_min'    => 100000,
                'amount_range_max'    => 2500000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'DfltPricingP12',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'IRDefltPricingPlan',
                'feature'             => 'refund',
                'payment_method'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1499,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500000,
                'amount_range_max'    => 4294967295,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createEmiMerchantSubventionPlan()
    {
        $pricingPlanId = '1EmiSubPricing';

        $rows = [
            [
                'id'             => '1EmiSubPricing',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'EmiSubPricingP',
                'feature'        => 'emi',
                'payment_method' => 'card',
                'percent_rate'   => 549,
                'fixed_rate'     => 0,
                'emi_duration'   => 9,
                'payment_issuer' => 'ICIC',
                'org_id'         => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createCredPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $row = [
            'id'                  => '1zE3CYqf1zbycT',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payment',
            'payment_method'      => 'cred',
            'percent_rate'        => 1500,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
        ];
    }

    public function createEmiPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payment',
            'payment_method'      => 'emi',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 250,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createPayoutPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYz';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 50,
            'fixed_rate'          => 80,
            'org_id'              => '100000razorpay',
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createRBLDirectPayoutPricingPlan()
    {
        $pricingPlanId = '1hDYlICobzOCYt';

        $row = [
            'id'                  => '1ZE3CYqf1zbyaF',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testRBLPlan',
            'product'             => 'banking',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 500,
            'org_id'              => '100000razorpay',
            'account_type'        => 'direct',
            'channel'             => 'rbl',
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createInstantRefundsPricingPlan()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zbyaE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'fixed_rate'          => 100,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyAC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'upi',
                'fixed_rate'          => 100,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => '1zE3CYqf1zbyCC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'netbanking',
                'fixed_rate'          => 100,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsPricingPlanOnOrg($orgId)
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zbyaE',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'fixed_rate'          => 873,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => $orgId,
            ],
            [
                'id'                  => '1zE3CYqf1zbyAC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'upi',
                'fixed_rate'          => 873,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => $orgId,
            ],
            [
                'id'                  => '1zE3CYqf1zbyCC',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'netbanking',
                'fixed_rate'          => 873,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => $orgId,
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsModeLevelPricingPlan()
    {
        $pricingPlanId = self::DEFAULT_PRICING_PLAN_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zbyaD',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'payment_method_type' => 'IMPS',
                'fixed_rate'          => 600,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsModeLevelPricingPlanV2()
    {
        $pricingPlanId = Models\Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zby00',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'payment_method_type' => 'IMPS',
                'fixed_rate'          => 600,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createInstantRefundsModeLevelPricingPlanV2NEFT()
    {
        $pricingPlanId = Models\Pricing\Fee::DEFAULT_INSTANT_REFUNDS_PLAN_V2_ID;

        $rows = [
            [
                'id'                  => '1zE3CYqf1zby00',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'testDefaultPlan',
                'feature'             => 'refund',
                'payment_method'      => 'card',
                'payment_method_type' => 'NEFT',
                'fixed_rate'          => 350,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 50000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    protected function getDefaultCommissionPlanArray()
    {
        $rows = [
            [
                'id'             => 'C6rNP4gZXcnZWM',
                'plan_id'        => self::DEFAULT_COMMISSION_PLAN_ID,
                'plan_name'      => 'CommDefaultPlan',
                'feature'        => 'payment',
                'type'           => 'commission',
                'payment_method' => 'card',
                'percent_rate'   => 20, // 0.2%
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => 'C6rNP7QE0mIzpW',
                'plan_id'        => self::DEFAULT_COMMISSION_PLAN_ID,
                'plan_name'      => 'CommDefaultPlan',
                'feature'        => 'payment',
                'type'           => 'commission',
                'payment_method' => 'netbanking',
                'percent_rate'   => 20,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
        ];

        return $rows;
    }

    public function createDefaultCommissionPlan()
    {
        $rows = $this->getDefaultCommissionPlanArray();

        $this->addPricingRulesToDb($rows);
    }

    public function editDefaultCommissionPlan($attributes = [])
    {
        $rows = $this->getDefaultCommissionPlanArray();

        $this->editPricingPlan($rows, $attributes);
    }

    public function createPricingPlanForDifferentOrg($orgId)
    {
        $pricingPlanId = '1hDYlICxbxOCYx';

        $row = [
            'id'                  => '1zE3CYqf1zbyaE',
            'plan_id'             => $pricingPlanId,
            'plan_name'           => 'testDefaultPlan',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 50,
            'fixed_rate'          => 80,
            'org_id'              => $orgId,
        ];

        $this->addPricingRulesToDb([$row]);
    }

    public function createStandardPricingPlanForDifferentOrg($pricingPlanId, $orgId)
    {
        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1osdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1osdf0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1pteg2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1pteg2FFEebmiI',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
                'international'  => 1,
            ],
            [
                'id'             => '1zE31zbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTd6',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'transfer',
                'percent_rate'   => 240,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTd7',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'paylater',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTd8',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'esautomatic',
                'payment_method' => 'card',
                'percent_rate'   => 20,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTd9',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payout',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTe1',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'settlement_ondemand',
                'payment_method' => 'fund_transfer',
                'percent_rate'   => 18,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
            [
                'id'             => '1zE31zbyeGCTe2',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emi',
                'percent_rate'   => 12,
                'fixed_rate'     => 0,
                'org_id'         => $orgId,
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function createBankTransferMultiPricingPlan()
    {
        $pricingPlanId = 'btMultiPricing';

        $rows = [
            [
                'id'                  => 'BtPercentPrici',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 1600,
                'fixed_rate'          => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 10000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'BtPercentFlatP',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 10000,
                'amount_range_max'    => 1000000000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);

        return $pricingPlanId;
    }

    public function createPromotionalPlan()
    {
        $pricingPlanId = '1In3Yh5Mluj605';

         $rows = [
            [
                'id'             => '1AXp2Xd3t5aRLX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1xsdf0GGDdalfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'netbanking',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1osdx0GGDdaHfF',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'emandate',
                'percent_rate'   => 0,
                'fixed_rate'     => 1000,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ptex2HHEebmhH',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'wallet',
                'percent_rate'   => 2000,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1ptex2FFEebmgG',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'transfer',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31xbyeGCTd4',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'account',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => '1zE31zxyeGCTd5',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'transfer',
                'payment_method' => 'customer',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'                  => 'BtPzrcentPrici',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 1600,
                'fixed_rate'          => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 10000,
                'org_id'              => '100000razorpay',
            ],
            [
                'id'                  => 'BtPexcentFlatP',
                'plan_id'             => $pricingPlanId,
                'plan_name'           => 'Bank Transfer Multi Pricing',
                'feature'             => 'payment',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'fixed_rate'          => 1500,
                'amount_range_active' => 1,
                'amount_range_min'    => 10000,
                'amount_range_max'    => 1000000000,
                'org_id'              => '100000razorpay',
            ],
        ];

        $this->addPricingRulesToDb($rows);

        return $pricingPlanId;
    }

    protected function getTwoPercentPricingPlanArray($attributes = [])
    {
        $pricingPlanId = $attributes['plan_id'] ?? Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN;

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRPX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 200,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'type'           => 'pricing',
            ],
        ];

        return $rows;
    }

    public function createTwoPercentPricingPlan($attributes = [])
    {

        $rows = $this->getTwoPercentPricingPlanArray($attributes);

        $this->addPricingRulesToDb($rows);
    }

    public function  editTwoPercentPricingPlan($attributes = [])
    {
        $rows = $this->getTwoPercentPricingPlanArray();

        $this->editPricingPlan($rows, $attributes);
    }


    public function createImplicitPartnerPricingPlan($attributes = [])
    {
        $pricingPlanId = $attributes['plan_id'] ?? Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN;

        $rows = [
            [
                'id'             => '1ABp2Xd3t5aRQX',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'standard_plan',
                'feature'        => 'payment',
                'payment_method' => 'card',
                'percent_rate'   => 180,
                'fixed_rate'     => 0,
                'org_id'         => '100000razorpay',
                'type'           => 'pricing',
            ],
        ];

        $this->addPricingRulesToDb($rows);
    }

    public function editAllPricingPlanRules(string $planId, array $attributes = [])
    {
        $planRules = $this->getDbEntities('pricing', ['plan_id' => $planId],'live')->toArray();

        $rules = [];

        foreach ($planRules as $rule)
        {
            $rules[] = ['id' => $rule['id']];
        }

        $this->editPricingPlan($rules, $attributes);
    }

    public function createDefaultPartnerCommissionPlan()
    {
        $this->addPricingRulesToDb(Models\Pricing\DefaultPlan::getPartnerCommissionPlanData());
    }

    public function createDefaultPlanForSubmerchantsOfOnboardedPartners()
    {
        $this->addPricingRulesToDb(Models\Pricing\DefaultPlan::getSubmerchantPricingOfOnboardedPartners());
    }

    public function addPricingRulesToDb($rows)
    {
        foreach ($rows as $row)
        {
            $this->create($row);
        }
    }

    protected function getAccountType(array $attributes)
    {
        $product = $attributes[Entity::PRODUCT] ?? Type::PRIMARY;
        $feature = $attributes[Entity::FEATURE] ?? Feature::PAYOUT;

        $accountType = null;

        // Set account_type as shared only if feature is payout and product is banking
        if (($feature === Feature::PAYOUT) and ($product === Type::BANKING))
        {
            $accountType = AccountType::SHARED;
        }

        return $accountType;
    }

    protected function editPricingPlan($rows, $attributes)
    {
        foreach ($rows as $row)
        {
            $this->edit($row['id'], array_replace($row, $attributes));
        }
    }

    public function createUpiTransferPricingPlan(array $attributes = [])
    {
        $pricingPlanId = 'upiTrnsfrPrcng';

        $rows = array_merge(
            [
                //'id'             => '1zE31zbybacaaa',
                'plan_id'        => $pricingPlanId,
                'plan_name'      => 'Upi Transfer pricing',
                'feature'        => 'payment',
                'payment_method' => 'upi',
                'percent_rate'   => 100,
                'fixed_rate'     => 0,
                'max_fee'        => 5000,
                'receiver_type'  => 'vpa',
                'org_id'         => '100000razorpay',
            ],
            $attributes
        );

        $this->addPricingRulesToDb(array($rows));

        return $pricingPlanId;
    }

    public function createBankTransferPercentPricingPlan(array $attributes = [])
    {
        $pricingPlanId = 'BtPercentPrcng';

        $row = array_merge(
            [
                'plan_id'               => $pricingPlanId,
                'plan_name'             => 'Bank transfer percent pricing',
                'feature'               => 'payment',
                'payment_method'        => 'bank_transfer',
                'percent_rate'          => 100,
                'fixed_rate'            => 0,
                'org_id'                => '100000razorpay',
            ],
            $attributes
        );

        $this->addPricingRulesToDb(array($row));

        return $pricingPlanId;
    }

    public function createBankTransferFixedPricingPlan(array $attributes = [])
    {
        $pricingPlanId = 'BtFixedPricing';

        $row = array_merge(
            [
                'plan_id'               => $pricingPlanId,
                'plan_name'             => 'Bank transfer percent pricing',
                'feature'               => 'payment',
                'payment_method'        => 'bank_transfer',
                'percent_rate'          => 0,
                'fixed_rate'            => 1000,
                'org_id'                => '100000razorpay',
            ],
            $attributes
        );

        $this->addPricingRulesToDb(array($row));

        return $pricingPlanId;
    }

    public function createPricingPlanWithoutMethods($pricingPlanId,$methods):string
    {
        $planName = "dummyPlanName";
        $rows = $this->getDefultPlanArray($pricingPlanId);
        $newRows = [];
        foreach ($rows as $row) {
            if(in_array($row['payment_method'],$methods,true)) {
                continue;
            }
            //removing aadhar_fp as it fails price creation
            if(isset($row['payment_method_type']) && $row['payment_method_type'] ==='aadhaar_fp') {
                continue;
            }
            $row['id'] = random_alphanum_string(14);
            $row['plan_name'] = $planName;
            $newRows[] = $row;
        }

        $this->addPricingRulesToDb($newRows);

        return $pricingPlanId;
    }
}
