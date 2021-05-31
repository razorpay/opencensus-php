<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Constants\Environment;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\NewAnalytics;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\NewAnalytics\Transformer;

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

    public function fetch(array $params,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        // in prod, irrespective of connection in argument, for payment analytics we will always fetch from warehouse/tidb
        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = ConnectionType::DATA_WAREHOUSE_NO_FALLBACK;
        }

        $entities = parent::fetch($params, $merchantId, $connectionType);

        return $entities;
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
