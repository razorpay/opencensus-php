<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\NewAnalytics\Transformer;
use RZP\Models\Payment\NewAnalytics;

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

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::PAYMENT_ID, 'desc');
    }


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

    public function saveOrFail($entity, array $options = array())
    {

        $entity = $this->transaction(function () use (& $entity, $options)
        {
            $entityExist = $entity->exists;

            parent::saveOrFail($entity, $options);

            $newEntity = Transformer::getNewAnalyticsEntity($entity);

            if ($entityExist == false)
            {
                $this->repo->saveOrFail($newEntity);
            }
            else
            {
                $fetchedEntity = (new NewAnalytics\Repository())->findByPaymentId($entity->getPaymentId());

                if ($fetchedEntity !== null)
                {
                    $newEntity = Transformer::getUpdatedNewAnalyticsEntity($fetchedEntity, $entity);

                    $this->repo->saveOrFail($newEntity);
                }
            }
            return $entity;
        });

        return $entity;
    }
}
