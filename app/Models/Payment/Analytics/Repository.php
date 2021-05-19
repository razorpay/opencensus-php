<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'payment_analytics';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_dash',
        Entity::CHECKOUT_ID     => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    );

    protected $signedIds = [
        Entity::PAYMENT_ID,
    ];


    // called for callback
    public function findForPayment($paymentId)
    {
        $timestamp = time() - Entity::SEARCH_WINDOW;

        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->get();
    }

    public function findLatestByPayment($paymentId)
    {
        $timestamp = time() - Entity::SEARCH_WINDOW;

        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
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
