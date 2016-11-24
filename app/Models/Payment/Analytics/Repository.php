<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payment_analytics';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::CHECKOUT_ID     => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    );

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->where(Entity::CHECKOUT_ID, '=', $checkoutId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->latest()
                    ->get();
    }

    public function findForPayment($paymentId)
    {
        $repo = $this->repo;

        $results =  $repo->where(Entity::PAYMENT_ID, '=', $paymentId);

        return $results->get();
    }
}