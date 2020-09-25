<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
    public function create($merchant, $input)
    {
        $this->trace->info(
            TraceCode::CREDIT_BALANCE_CREATE_REQUEST,
            [
                'input'         => $input,
                'merchant_id'   => $merchant->getId(),
            ]);

        $creditBalance = (new Entity)->build($input);

        $creditBalance->merchant()->associate($merchant);

        $this->repo->saveOrFail($creditBalance);

        return $creditBalance;
    }

    public function createOrFetchCreditBalanceOfMerchant(
                                Merchant\Entity $merchant,
                                $type,
                                $product,
                                $creditsExpiry = null)
    {
        // If credit has expiry, we will have to create a new
        // balance for the merchant. This is to keep the
        // order in which the credits should be consumed
        // that is credit balance which expires soon should
        // be used first and this will also avoid the pain
        // of maintaining expiry of the balance based on the
        // credits the merchant has.

        if ($creditsExpiry === null)
        {
            $creditBalance = $this->repo->credit_balance->findMerchantCreditBalanceByTypeAndProduct(
                                                                    $merchant->getId(),
                                                                    $type,
                                                                    $product);

            if ($creditBalance !== null)
            {
                return $creditBalance;
            }
        }

        $input = [
            Entity::TYPE        => $type,
            Entity::PRODUCT     => $product,
        ];

        if ($creditsExpiry !== null)
        {
            $input[Entity::EXPIRED_AT] = $creditsExpiry;
        }

        $creditBalance = $this->create($merchant, $input);

        return $creditBalance;
    }

    public function getMerchantCreditBalanceAggregatedByProductForEveryType(string $merchantId, string $product)
    {
        $credits = $this->repo->credit_balance->getMerchantCreditBalanceAggregatedByProductForEveryType(
                                                                                $merchantId,
                                                                                $product);
        $data = [];


        foreach ($credits as $type => $credit)
        {
            $data[$type] = (new Credits\Core)->getCreditInAmount($credit, $product);
        }

        return $data;
    }

    public function getCreditsBalancesOfMerchantForProduct(Merchant\Entity $merchant, string $product)
    {
        $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCreditsForProductForDashboard(
                                                                $merchant->getId(),
                                                                $product);

        return $creditBalances;
    }
}

