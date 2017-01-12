<?php

namespace RZP\Models\Transfer;

use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transfer\Entity;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::SOURCE_ID           => 'sometimes|alpha_num|min:14',
        Entity::TO_ID               => 'sometimes|alpha_num|min:14'
    ];

    public function fetchByAccountIdAndMerchant(
        string $accountId,
        Merchant\Entity $marketplace,
        bool $fail = true)
    {
        $query = $this->newQuery()
                      ->where(Entity::SOURCE_TYPE, E::PAYMENT)
                      ->where(Entity::TO_TYPE, 'merchant')
                      ->where(Entity::TO_ID, $accountId)
                      ->merchantId($marketplace->getId());

        if ($fail === true)
        {
            $data = $query->firstOrFailPublic();
        }
        else
        {
            $data = $query->get();
        }

        return $data;
    }

    /**
     * Fetch all transfers from a merchant, done on a payment
     *
     * @param  string          $paymentId
     * @param  Merchant\Entity $marketplace
     */
    public function fetchBySourcePaymentIdAndMerchant(string $paymentId, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, E::PAYMENT)
                    ->where(Entity::SOURCE_ID, $paymentId)
                    ->merchantId($merchant->getId())
                    ->get();
    }

    public function fetchBySourcePaymentToAccountAndMerchant(
        string $paymentId,
        string $accountId,
        Merchant\Entity $marketplace)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, E::PAYMENT)
                    ->where(Entity::SOURCE_ID, $paymentId)
                    ->where(Entity::TO_TYPE, 'merchant')
                    ->where(Entity::TO_ID, $accountId)
                    ->merchantId($marketplace->getId())
                    ->get();
    }
}
