<?php

namespace RZP\Models\Plan\Subscription;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Schedule\Task;

class Repository extends Base\Repository
{
    protected $entity = 'subscription';

    protected $entityFetchParamRules = [
        Entity::PLAN_ID     => 'filled|string|min:14|max:19',
        Entity::STATUS      => 'filled|string|max:16|custom',
    ];

    protected $proxyFetchParamRules = [
        Entity::CUSTOMER_ID => 'filled|string|min:14|max:19',
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

    public function getSubscriptionsToCharge()
    {
        $subscriptions = $this->getBaseSubscriptionsQuery()
                              ->whereIn(Entity::STATUS, Status::$cronChargeableStatuses)
                              ->whereNull(Entity::ENDED_AT)
                              ->where(function($query)
                              {
                                  $query->where(Entity::AUTH_ATTEMPTS, '=', 0)
                                      ->orWhere(function($query)
                                      {
                                          $query->where(Entity::AUTH_ATTEMPTS, '=', Charge::MAX_AUTH_ATTEMPTS)
                                                ->where(Entity::STATUS, '=', Status::HALTED);
                                      });
                              })
                              ->limit(100)
                              ->get();

        return $subscriptions;
    }

    public function getSubscriptionsToRetry()
    {
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
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->whereNotNull(Entity::START_AT)
                    ->where(Entity::START_AT, '<=', $currentTime)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->get();
    }

    protected function getBaseSubscriptionsQuery()
    {
        $subscriptionIdAttr = $this->dbColumn(Entity::ID);

        $taskEntityIdAttr = $this->repo->schedule_task->dbColumn(Task\Entity::ENTITY_ID);
        $taskNextRunAttr = $this->repo->schedule_task->dbColumn(Task\Entity::NEXT_RUN_AT);

        $subscriptionAttrs = $this->dbColumn('*');

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->select($subscriptionAttrs)
                    ->join(Table::SCHEDULE_TASK, $subscriptionIdAttr, '=', $taskEntityIdAttr)
                    ->where($taskNextRunAttr, '<', $currentTime)
                    ->where(function($query) use ($currentTime)
                        {
                            $query->whereNull(Entity::CURRENT_END)
                                  ->orWhere(Entity::CURRENT_END, '<', $currentTime);
                        })
                    ->with(['plan', 'merchant']);
    }
}
