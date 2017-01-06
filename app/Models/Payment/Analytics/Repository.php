<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'payment_analytics';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::CHECKOUT_ID     => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    );

    public function findForPayment($paymentId, $findRecent = false)
    {
        $results = $this->newQuery()
                        ->where(Entity::PAYMENT_ID, '=', $paymentId);

        if ($findRecent === true)
        {
            $result = $results->orderBy(Entity::CREATED_AT, 'desc')
                              ->first();

            return $result;
        }

        return $results->get();
    }

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Payment\Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->where(Entity::CHECKOUT_ID, '=', $checkoutId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->latest()
                    ->get();
    }
}
