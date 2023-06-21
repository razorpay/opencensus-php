<?php

namespace RZP\Models\Order;

use App;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RZP\Base\Common;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Offer;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Offer\EntityOffer;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ExternalRepo;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Trace\TraceCode;

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
        Entity::REFERENCE8      => 'sometimes|nullable|string',
        Entity::STATUS          => 'sometimes|in:created,attempted,paid',
        Entity::NOTES           => 'sometimes|notes_fetch',
        self::EXPAND . '*'      => 'filled|string|in:virtual_account,transfers',
    ];

    protected $appFetchParamRules = [
        Entity::REFERENCE8      => 'sometimes|nullable|string',
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

    public function getPaginatedCODOrders(array $params,
                                          string $merchantId = null,
                                          string $connectionType = null)
    {
        $connection = $this->getConnectionFromType($connectionType);

        $query = $this->newQueryWithConnection($connection);

        $orderMetaTable = Table::ORDER_META;
        $amountCol = $this->dbColumn(Entity::AMOUNT);
        $idCol = $this->dbColumn(Entity::ID);
        $receiptColumn = $this->dbColumn(Entity::RECEIPT);
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT);
        $statusCol = $this->dbColumn(Entity::STATUS);
        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);
        $typeCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::TYPE;
        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $query = $query
            ->select($idCol,$receiptColumn,$amountCol,$createdAtCol,$valueCol)
            ->join($orderMetaTable, $idCol, '=', $orderMetaTable . '.order_id')
            ->where($statusCol, '=',Status::PLACED )
            ->where($typeCol, '=',Fields::ONE_CLICK_CHECKOUT )
            ->where($merchantIdCol , '=', $merchantId );


        $this->addQueryParamOrderId($query, $params);

        $this->addQueryParamMerchantOrderId($query, $params);

        $this->addQueryParamCodEligibilityRiskTier($query, $params);

        $this->addQueryParamReviewStatus($query, $params);

        $this->addQueryParamReviewMode($query, $params);

        $this->buildQueryWithParams($query,$params);

        $query->orderBy($this->dbColumn(Common::CREATED_AT), 'desc');

        $paginatedResult = $this->getPaginated($query, $params);

        return $paginatedResult;
    }

    public function getPaginatedPrepayOrders(array $params,
                                          string $merchantId = null,
                                          string $connectionType = null)
    {
        $connection = $this->getConnectionFromType($connectionType);

        $query = $this->newQueryWithConnection($connection);

        $orderMetaTable = Table::ORDER_META;
        $amountCol = $this->dbColumn(Entity::AMOUNT);
        $idCol = $this->dbColumn(Entity::ID);
        $receiptColumn = $this->dbColumn(Entity::RECEIPT);
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT);
        $statusCol = $this->dbColumn(Entity::STATUS);
        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);
        $typeCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::TYPE;
        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $query = $query
            ->select($idCol,$receiptColumn,$amountCol,$createdAtCol,$valueCol)
            ->join($orderMetaTable, $idCol, '=', $orderMetaTable . '.order_id')
            ->where($statusCol, '=',Status::PLACED )
            ->where($typeCol, '=',Fields::ONE_CLICK_CHECKOUT )
            ->where($merchantIdCol , '=', $merchantId );

        $this->addQueryParamMagicPaymentLink($query, $params);

        $this->addQueryParamOrderId($query, $params);

        $this->addQueryParamCodEligibilityRiskTier($query, $params);

        $this->addQueryParamMerchantOrderId($query, $params);

        $this->buildQueryWithParams($query,$params);

        $query->orderBy($this->dbColumn(Common::CREATED_AT), 'desc');

        $paginatedResult = $this->getPaginated($query, $params);

        return $paginatedResult;
    }

    public function getPrepayOrder(string $orderId,
                                          string $merchantId = null,
                                          string $connectionType = null)
    {
        $connection = $this->getConnectionFromType($connectionType);

        $query = $this->newQueryWithConnection($connection);

        $orderMetaTable = Table::ORDER_META;
        $amountCol = $this->dbColumn(Entity::AMOUNT);
        $idCol = $this->dbColumn(Entity::ID);
        $receiptColumn = $this->dbColumn(Entity::RECEIPT);
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT);
        $statusCol = $this->dbColumn(Entity::STATUS);
        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);
        $typeCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::TYPE;
        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $query = $query
            ->select($idCol,$receiptColumn,$amountCol,$createdAtCol,$valueCol)
            ->join($orderMetaTable, $idCol, '=', $orderMetaTable . '.order_id')
            ->where($statusCol, '=',Status::PLACED )
            ->where($typeCol, '=',Fields::ONE_CLICK_CHECKOUT )
            ->where($merchantIdCol , '=', $merchantId );

        return $query->find($orderId);
    }
    private function addQueryParamOrderId($query,array & $params)
    {
        $idCol = $this->dbColumn(Entity::ID);

        if (isset($params[Fields::ID]))
        {
            $query->where($idCol,'=',$params[Entity::ID]);

            unset($params[Entity::ID]);
        }
    }

    private function addQueryParamMerchantOrderId($query,array & $params)
    {
        $receiptColumn = $this->dbColumn(Entity::RECEIPT);

        if (isset($params[Entity::RECEIPT]))
        {
            $query->where($receiptColumn,'=',$params[Entity::RECEIPT]);

            unset($params[Entity::RECEIPT]);
        }
    }

    private function addQueryParamCodEligibilityRiskTier($query,array & $params)
    {
        $orderMetaTable = Table::ORDER_META;

        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $riskTierFilter = $valueCol.'->'.Fields::COD_INTELLIGENCE.'->'.Fields::COD_ELIGIBILITY_RISK_TIER;

        if (isset($params[Fields::COD_ELIGIBILITY_RISK_TIER]))
        {
            $query->where($riskTierFilter,'=',$params[Fields::COD_ELIGIBILITY_RISK_TIER]);

            unset($params[Fields::COD_ELIGIBILITY_RISK_TIER]);
        }
    }

    private function addQueryParamMagicPaymentLink($query,array & $params)
    {
        $orderMetaTable = Table::ORDER_META;

        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $magicPaymentLinkStatus = $valueCol.'->'.Fields::MAGIC_PAYMENT_LINK.'->'.Fields::MAGIC_PAYMENT_LINK_STATUS;

        $query
            ->whereNotNull($magicPaymentLinkStatus)
            ->where($magicPaymentLinkStatus,'!=', Order1cc\Constants::PL_MAPPED_AWAITED);

        if (isset($params[Fields::MAGIC_PAYMENT_LINK_STATUS_KEY]))
        {
            $query->where(
                $magicPaymentLinkStatus,'=',
                Order1cc\Constants::MAGIC_PAYMENT_LINK_STATUS_MAPPING[$params[Fields::MAGIC_PAYMENT_LINK_STATUS_KEY]]);

            unset($params[Fields::MAGIC_PAYMENT_LINK_STATUS_KEY]);
        }
    }

    private function addQueryParamReviewMode($query,array & $params)
    {
        $orderMetaTable = Table::ORDER_META;

        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $reviewedByFilter = $valueCol.'->'.Fields::REVIEWED_BY;

        if (isset($params[Fields::REVIEW_MODE]))
        {
            if($params[Fields::REVIEW_MODE] == Order1cc\Constants::AUTOMATION_FLAG)
            {
                $query->where($reviewedByFilter, '=', Order1cc\Constants::COD_AUTOMATION_REVIEW_EMAIL);
            }else
            {
                $query->where($reviewedByFilter, '!=', Order1cc\Constants::COD_AUTOMATION_REVIEW_EMAIL);
            }
        }

        unset($params[Fields::REVIEW_MODE]);
    }

    private function addQueryParamReviewStatus($query,array & $params)
    {
        $orderMetaTable = Table::ORDER_META;

        $valueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;

        $reviewStatusFilter = $valueCol.'->'.Fields::REVIEW_STATUS;

        if (isset($params[Fields::REVIEW_STATUS]))
        {
            $query->where(function($query) use ($reviewStatusFilter, $params) {
                $key = array_search('null', $params[Fields::REVIEW_STATUS]);
                if ($key !== false)
                {
                    array_splice($params[Fields::REVIEW_STATUS], $key, 1);
                }
                $query->whereIn($reviewStatusFilter,$params[Fields::REVIEW_STATUS]);

                unset($params[Fields::REVIEW_STATUS]);

                if ($key !== false)
                {
                    $query->orWhereNull($reviewStatusFilter);
                }
            });
        }
        unset($params[Fields::REVIEW_STATUS]);
    }

    public function fetchPendingOrders(int $offset, string $connectionType = null)
    {
        $connection = $this->getConnectionFromType($connectionType);

        $orderMetaTable = Table::ORDER_META;
        $merchantConfigTable = Table::MERCHANT_1CC_CONFIGS;

        $idCol = $this->dbColumn(Entity::ID);
        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);
        $statusCol = $this->dbColumn(Entity::STATUS);
        $orderMetaUpdatedAtCol = $orderMetaTable.'.'.Entity::UPDATED_AT;
        $deletedAtCol = $merchantConfigTable.'.'.Entity::DELETED_AT;

        $typeCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::TYPE;
        $orderMetaValueCol = $orderMetaTable.'.'.\RZP\Models\Order\OrderMeta\Entity::VALUE;
        $merchantConfigValueCol = $merchantConfigTable.'.'.\RZP\Models\Merchant\Merchant1ccConfig\Entity::VALUE;
        $configCol = $merchantConfigTable.'.'.\RZP\Models\Merchant\Merchant1ccConfig\Entity::CONFIG;
        $reviewStatusFilter = $orderMetaValueCol.'->'.Fields::REVIEW_STATUS;

        $reviewStatusVar = $reviewStatusFilter .' AS '.Fields::REVIEW_STATUS;
        $merchantConfigValueColVar = $merchantConfigValueCol.' AS '.Type::PLATFORM;

        $reviewStatusArray = [Order1cc\Constants::APPROVAL_INITIATED,
            Order1cc\Constants::CANCEL_INITIATED,
            Order1cc\Constants::HOLD_INITIATED];


        return $this->newQueryWithConnection($connection)
            ->select($idCol, $merchantIdCol, $reviewStatusVar, $merchantConfigValueColVar)
            ->join($orderMetaTable, $idCol, '=', $orderMetaTable . '.order_id')
            ->join($merchantConfigTable, $merchantIdCol, '=', $merchantConfigTable.'.merchant_id')
            ->where($statusCol, '=', Status::PLACED )
            ->where($typeCol, '=', Fields::ONE_CLICK_CHECKOUT)
            ->where($configCol, '=', Type::PLATFORM)
            ->where($deletedAtCol, '=', null)
            ->whereIn($reviewStatusFilter, $reviewStatusArray)
            ->where($orderMetaUpdatedAtCol, '<', Carbon::now()->subHours(env('MAGIC_RTO_ACTION_RETRY_TIME_PERIOD_HOURS',2))->toDateTimeString())
            ->where($orderMetaUpdatedAtCol, '>', Carbon::now()->subDays(env('MAGIC_RTO_ACTION_RETRY_TIME_PERIOD_DAYS',2))->toDateTimeString())
            ->skip($offset)
            ->limit(500)
            ->get();
    }

}
