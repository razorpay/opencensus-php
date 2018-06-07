<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Models\Offer\EntityOffer;

class Repository extends Base\Repository
{
    protected $entity = 'order';

    protected $entityFetchParamRules = [
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::RECEIPT         => 'sometimes|string|max:40',
    ];

    protected $proxyFetchParamRules = [
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::NOTES           => 'sometimes|notes_fetch',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::ACCOUNT_NUMBER  => 'sometimes|string|max:50|min:5',
    ];

    public function fetchForPayment($payment)
    {
        if ($payment->hasRelation('order'))
        {
            return $payment->order;
        }

        $orderId = $payment->getApiOrderId();

        $order = $this->findOrFail($orderId);

        $payment->order()->associate($order);

        return $order;
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $entityToRelationFetchMap = [])
    {
        $orders = $this->newQuery()
                       ->merchantId($merchantId)
                       ->betweenTime($from, $to)
                       ->with('payments')
                       ->take($count)
                       ->skip($skip)
                       ->latest()
                       ->get();

        return $orders;
    }

    public function attachOfferToOrder(Entity $order, Offer\Entity $offer)
    {
        $order->offers()->attach($offer->getId(), [
            EntityOffer\Entity::ENTITY_TYPE => $this->entity,
        ]);
    }
}
