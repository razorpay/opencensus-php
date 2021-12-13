<?php

namespace RZP\Models\Order;

use App;
use Illuminate\Support\Arr;
use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Offer\EntityOffer;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ExternalRepo;

class Repository extends Base\Repository
{
    use ExternalRepo, ExternalCore;

    protected $entity = 'order';

    protected $entityFetchParamRules = [
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
        Entity::RECEIPT         => 'sometimes|string|max:40',
        self::EXPAND . '.*'     => 'filled|string|in:payments,payments.card,virtual_account,transfers',
    ];

    protected $proxyFetchParamRules = [
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::NOTES           => 'sometimes|notes_fetch',
        self::EXPAND . '*'      => 'filled|string|in:virtual_account,transfers',
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
        $orders = $this->newQueryWithConnection($this->getSlaveConnection())
                       ->merchantId($merchantId)
                       ->betweenTime($from, $to)
                       ->with('payments')
                       ->take($count)
                       ->skip($skip)
                       ->latest()
                       ->get();

        return $orders;
    }

    public function fetchMultipleOrdersBasedOnIds($orderIds)
    {
        return $this->newQuery()
            ->whereIn('id', $orderIds)
            ->get();
    }

    public function bulkUpdatePgRouterSynced(array $orderIds)
    {
        $data = $this->newQueryWithoutTimestamps()
            ->whereIn('id', $orderIds);

        return $data->update(['pg_router_synced' => true]);
    }

    public function saveOrFail($entity, array $options = [])
    {
        if ($entity->isExternal() === false)
        {
            $currentOrder = $this->newQuery()->where('id', '=', $entity->getId())->get();

            parent::saveOrFail($entity, $options);

            try
            {
                $mode = App::getFacadeRoot()['rzp.mode'];

                if ((isset($mode) === true) and
                    ($mode === 'live'))
                {
                    $variant = App::getFacadeRoot()->razorx->getTreatment(
                        $entity->getId(),
                        Merchant\RazorxTreatment::PG_ROUTER_ORDER_SHOULD_DISPATCH_TO_QUEUE,
                        $mode
                    );

                    if ($variant === 'on')
                    {
                        $core = new Core();

                        if (count($currentOrder->toArray()) > 0)
                        {
                            $currentOrder = $currentOrder->toArray()[0];

                            if ($currentOrder[Entity::PG_ROUTER_SYNCED] === 1)
                            {
                                $updatedOrder = $entity->toArray();

                                unset($updatedOrder['merchant'], $updatedOrder['bank_account'], $updatedOrder['offers']);

                                $data = array_map('unserialize', array_diff_assoc(array_map('serialize', $updatedOrder),
                                    array_map('serialize', $currentOrder)));

                                $data['id'] = $entity->getId();

                                $data['updated_at'] = $entity->getUpdatedAt();

                                unset($data['merchant'], $data['bank_account'], $data['offers']);

                                if ((isset($data['notes']) === true) and
                                    (Arr::isAssoc($data['notes']) === false))
                                {
                                    $data['notes'] = array_combine($data['notes'], $data['notes']);
                                }

                                if ((isset($updatedOrder['notes']) === true) and
                                    (Arr::isAssoc($updatedOrder['notes']) === false))
                                {
                                    $updatedOrder['notes'] = array_combine($updatedOrder['notes'], $updatedOrder['notes']);
                                }

                                if ((isset($data['notes']) === false) or
                                    (count($data['notes']) === 0))
                                {
                                    $data['notes'] = null;
                                }

                                if ((isset($updatedOrder['notes']) === false) or
                                    (count($updatedOrder['notes']) === 0))
                                {
                                    $updatedOrder['notes'] = null;
                                }

                                $requestData = [
                                    'order_update_request' => $data,
                                    'order_sync_request' => $updatedOrder
                                ];

                                $requestData['mode'] = $mode;

                                $core->dispatchUpdatedOrderToPGRouter($requestData);
                            }
                        }
                        else
                        {
                            $data = $entity->toArray();

                            $data['mode'] = $mode;

                            unset($data['merchant'], $data['bank_account'], $data['offers']);

                            if ((isset($data['notes']) === true) and
                                (Arr::isAssoc($data['notes']) === false)) {
                                $data['notes'] = array_combine($data['notes'], $data['notes']);
                            }

                            $core->dispatchOrderToPGRouter($data);

                            $entity->setAttribute(Entity::PG_ROUTER_SYNCED, true);

                            parent::saveOrFail($entity, $options);
                        }
                    }
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    null,
                    ['order_id' => $entity->getId()]
                );
            }
        }
        else
        {
            return $this->saveExternalEntity($entity);
        }
    }

    public function save($order, array $options = array())
    {
        if ($order->isExternal() === false)
        {
            return parent::save($order, $options);
        }

        $order = $this->saveExternalEntity($order);

        return $order;
    }

    public function reload(&$order)
    {
        if ($order->isExternal() === false)
        {
            return parent::reload($order);
        }
        return $order;
    }
}
