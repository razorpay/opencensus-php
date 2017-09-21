<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Base\Collection;
use RZP\Models\Schedule\Task;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'subscription';

    protected $entityFetchParamRules = [
        Entity::PLAN_ID     => 'filled|string|min:14|max:19',
        Entity::STATUS      => 'filled|string|max:16|custom',
    ];

    protected $proxyFetchParamRules = [
        Entity::CUSTOMER_ID     => 'filled|string|min:14|max:19',
        Entity::CUSTOMER_EMAIL  => 'filled|string|min:1|max:255'
    ];

    protected $appFetchParamRules = [
        Entity::ERROR_STATUS    => 'filled|string|max:32',
        Entity::SCHEDULE_ID     => 'filled|string|size:14',
        Entity::MERCHANT_ID     => 'filled|string|size:14',
        Entity::TOKEN_ID        => 'filled|string|min:14|max:20',
        Entity::AUTH_ATTEMPTS   => 'filled|integer|min:1|max:5',
    ];

    protected $signedIds = [
        Entity::PLAN_ID,
        Entity::CUSTOMER_ID,
        Entity::SCHEDULE_ID,
        Entity::MERCHANT_ID,
        Entity::TOKEN_ID,
    ];

    /**
     * Subscriptions which need to be picked up by the charge
     * cron. This includes active subscriptions, and also halted
     * subscriptions since we still create invoices for these
     * subscriptions, without charging them.
     *
     * @return Collection Subscriptions
     */
    public function getSubscriptionsToCharge()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $query = $this->getBaseSubscriptionsQuery()
                      ->whereIn(Entity::STATUS, Status::$cronChargeableStatuses)
                      ->whereNull(Entity::ENDED_AT)
                      ->whereNull(Entity::CANCEL_AT)
                      ->where(function($query) use ($currentTime)
                      {
                          $query->where(Entity::AUTH_ATTEMPTS, '=', 0)
                                ->orWhere(function($query)
                                {
                                    $query->where(Entity::AUTH_ATTEMPTS, '=', Charge::MAX_AUTH_ATTEMPTS)
                                          ->where(Entity::STATUS, '=', Status::HALTED);
                                });
                      })
                      ->where(function($query) use ($currentTime)
                        {
                            $query->whereNull(Entity::CURRENT_END)
                                  ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                        })
                      ->limit(100);

        return $query->get();
    }

    public function getSubscriptionsToRetry()
    {
        //
        // This will also pick up all the subscriptions which are
        // scheduled to be cancelled at cycle end.
        //

        return $this->getBaseSubscriptionsQuery()
                    ->where(Entity::STATUS, '=', Status::PENDING)
                    ->whereNotNull(Entity::ERROR_STATUS)
                    ->where(Entity::AUTH_ATTEMPTS, '>', 0)
                    ->where(Entity::AUTH_ATTEMPTS, '<', Charge::MAX_AUTH_ATTEMPTS)
                    ->limit(100)
                    ->get();
    }

    public function getSubscriptionsToExpire()
    {
        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->whereNotNull(Entity::START_AT)
                    ->where(Entity::START_AT, '<=', $currentTime)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->get();
    }

    public function getSubscriptionsToCancel()
    {
        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->whereNotNull(Entity::CANCEL_AT)
                    ->where(Entity::CANCEL_AT, '<=', $currentTime)
                    ->whereNotIn(Entity::STATUS, Status::$terminalStatuses)
                    ->get();
    }

    protected function getBaseSubscriptionsQuery()
    {
        $subscriptionIdAttr = $this->dbColumn(Entity::ID);

        $taskEntityIdAttr = $this->repo->schedule_task->dbColumn(Task\Entity::ENTITY_ID);
        $taskNextRunAttr = $this->repo->schedule_task->dbColumn(Task\Entity::NEXT_RUN_AT);

        $subscriptionAttrs = $this->dbColumn('*');

        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->select($subscriptionAttrs)
                    ->join(Table::SCHEDULE_TASK, $subscriptionIdAttr, '=', $taskEntityIdAttr)
                    ->where($taskNextRunAttr, '<', $currentTime)
                    ->with(['plan', 'merchant']);
    }

    protected function validateStatus($attribute, $value)
    {
        if (Status::isStatusValid($value) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_INVALID_STATUS,
                Entity::STATUS,
                [
                    'status' => $value
                ]);
        }
    }

    protected function addQueryParamCustomerEmail($query, $params)
    {
        $this->joinQueryCustomer($query);

        $customerEmail = $this->repo->customer->dbColumn(Customer\Entity::EMAIL);

        $query->where($customerEmail, '=', $params[Entity::CUSTOMER_EMAIL]);

        $query->select($this->getTableName() . '.*');
    }

    protected function joinQueryCustomer($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = $joins ?: [];

        $customerTable = Table::getTableNameForEntity(Constants\Entity::CUSTOMER);

        foreach ($joins as $join)
        {
            if ($join->table === $customerTable)
            {
                return;
            }
        }

        $subscriptionCustomerId = $this->dbColumn(Entity::CUSTOMER_ID);
        $customerId = $this->repo->customer->dbColumn(Customer\Entity::ID);

        $query->join($customerTable, $subscriptionCustomerId, '=', $customerId);
    }
}
