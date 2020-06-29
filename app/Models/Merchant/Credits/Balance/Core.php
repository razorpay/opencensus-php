<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Models\Merchant;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

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
            $creditBalance = $this->repo->credit_balance->getMerchantCreditBalanceByTypeAndProduct(
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

}

