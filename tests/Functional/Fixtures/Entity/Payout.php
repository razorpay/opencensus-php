<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Pricing;

class Payout extends Base
{
    use TransactionTrait;

    public function create(array $attributes = [])
    {
        $pricingRuleId = null;

        // If pricing rule Id is passed as a param, we set the fees and tax to 500 and 90 default
        if (isset($attributes['pricing_rule_id']) === true)
        {
            $pricingRuleId = $attributes['pricing_rule_id'];

            unset($attributes['pricing_rule_id']);
        }

        $defaultValues = [
            'customer_id'       => '100000customer',
            'destination_id'    => '1000000lcustba',
            'destination_type'  => 'bank_account',
            'balance_id'        => '10000000000000',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payout = parent::create($attributes);

        if (empty($pricingRuleId) === false)
        {
            $payout->setPricingRuleId($pricingRuleId);

            list($fees, $tax, $feesSplit) = (new Pricing\PayoutFee)->calculateMerchantFees($payout);

            $payout->setFees($fees);

            $payout->setTax($tax);
        }

        $txn = $this->createTransactionFromPayout($payout);

        $txn->setAttribute(\RZP\Models\Transaction\Entity::SETTLED_AT, $payout->getCreatedAt());

        $txn->saveOrFail();

        $payout->setFees($txn->getFee());

        $payout->setTax($txn->getTax());

        $payout->saveOrFail();

        return $payout;
    }

    public function createPayoutWithoutTransaction(array $attributes = [])
    {
        $defaultValues = [
            'customer_id'       => '100000customer',
            'destination_id'    => '1000000lcustba',
            'destination_type'  => 'bank_account',
            'balance_id'        => '10000000000000',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payout = parent::create($attributes);

        return $payout;
    }
}
