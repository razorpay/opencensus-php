<?php

namespace RZP\Models\Invoice;

use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Base\BuilderEx;
use Database\Connection;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Models\Plan\Subscription;
use RZP\Models\SubscriptionRegistration;

class Repository extends Base\Repository
{
    protected $entity = 'invoice';

    protected $expands = [

        // Note: Dotted notation works and expands both line_items
        // followed by taxes of it.

        Entity::LINE_ITEMS . '.' . LineItem\Entity::TAXES,
    ];

    protected $invoiceCountRules = [
        Entity::SUBSCRIPTION_ID   => 'sometimes|string|min:14|max:18',
        Entity::TYPE              => 'sometimes|string|custom',
        Entity::STATUS            => 'sometimes|string',
    ];

    protected $entityFetchParamRules = [
        Entity::TYPE              => 'sometimes|string|custom',
        Entity::PAYMENT_ID        => 'sometimes|string|min:14|max:18',
        Entity::RECEIPT           => 'sometimes|string|min:1|max:40',
        Entity::CUSTOMER_ID       => 'sometimes|string|min:14|max:19',
        Entity::SUBSCRIPTION_ID   => 'sometimes|string|min:14|max:18',
    ];

    protected $proxyFetchParamRules = [
        Entity::BATCH_ID          => 'sometimes|string|min:14|max:20',
        Entity::USER_ID           => 'sometimes|alpha_num',
        Entity::STATUS            => 'sometimes|string',
        Entity::TYPES             => 'sometimes|array|min:1|max:2|custom',
        Entity::CUSTOMER_NAME     => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:255',
        Entity::CUSTOMER_CONTACT  => 'sometimes|contact_syntax',
        Entity::CUSTOMER_EMAIL    => 'sometimes|email',
        Entity::NOTES             => 'sometimes|notes_fetch',
        Entity::SUBSCRIPTION_ID   => 'sometimes|string|min:14|max:18',
        EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
        EsRepository::SEARCH_HITS => 'sometimes|boolean',
        self::EXPAND . '.*'       => 'filled|string|in:payments,payments.card,user,invoice_reminder',
        Entity::IDEMPOTENCY_KEY   => 'sometimes|alpha_num',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID       => 'sometimes|alpha_num',
        Entity::ORDER_ID          => 'sometimes|string|max:20',
        Entity::INTERNAL_REF      => 'sometimes|alpha_num|max:64',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::CUSTOMER_ID,
        Entity::ORDER_ID,
        Entity::SUBSCRIPTION_ID,
        Entity::BATCH_ID,
    ];

    // ---------------------- Custom validation methods --------------

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    protected function validateTypes($attribute, $value)
    {
        foreach ($value as $type)
        {
            Type::checkType($type);
        }
    }

    // ---------------------- Custom validation methods ends ---------

    /**
     * Fetches invoice entity for given public id and merchant, followed by
     * an access check for user info (userId and userRole) passed.
     *
     * @param string          $id
     * @param Merchant\Entity $merchant
     * @param string|null     $userId
     * @param string|null     $userRole
     * @param array           $input
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function findByPublicIdAndMerchantAndUser(
        string $id,
        Merchant\Entity $merchant,
        string $userId = null,
        string $userRole = null,
        array $input = [],
        string $entityType = null)
    {
        Entity::verifyIdAndStripSign($id);

        $query = $this->getQueryForFindWithParams($input);

        $query = $query->merchantId($merchant->getId());

        if (empty($entityType) === false)
        {
            $query = $query->where(Entity::ENTITY_TYPE, $entityType);
        }

        $invoice = $query->findOrFailPublic($id);

        $invoice->merchant()->associate($merchant);

        //
        // If userId is set and userRole is sellerapp we throw 403 if invoice's
        // user id is not same as passed userId.
        //
        if (($userId !== null) and
            (($userRole === Role::SELLERAPP) or ($userRole === Role::SELLERAPP_PLUS)) and
            ($invoice->getUserId() !== $userId))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $invoice->getValidator()->validateLinkShouldBeFound();

        return $invoice;
    }

    /**
     * @deprecated
     */
    public function findByPublicIdAndSubscription(string $invoiceId, Subscription\Entity $subscription)
    {
        return $this->repo->useSlave( function() use ($invoiceId, $subscription)
        {
            Entity::verifyIdAndStripSign($invoiceId);

            return $this->newQuery()
                        ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                        ->findOrFailPublic($invoiceId);
        });
    }

    protected function validateInvoicesCountParams(array $params)
    {
        $invoiceCountRules = $this->invoiceCountRules;

        (new JitValidator)->rules($invoiceCountRules)
                          ->caller($this)
                          ->input($params)
                          ->validate();
    }

    public function getInvoicesCount(array $params)
    {
        $this->validateInvoicesCountParams($params);

        $query = $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo());

        $merchantId = optional($this->merchant)->getId();

        $this->buildQueryWithParams($query, $params);

        if ($merchantId !== null)
        {
            $query = $query->merchantId($merchantId);
        }

        $invoiceCount = $query->count();

        return ['count' => $invoiceCount];
    }

    /**
     * @deprecated
     */
    public function getInvoicesForIssuedNotificationToCustomer($medium)
    {
        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->where($medium . '_status', '=', NotifyStatus::PENDING)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::SCHEDULED_AT, '<=', $currentTime)
                    ->with(Entity::ORDER)
                    ->get();
    }

    public function getInvoicesForExpiringNotificationToCustomer()
    {
        //
        // To be implemented later, As incremental feature.
        //

        return new Base\PublicCollection;
    }

    /**
     * Gets all ISSUED invoice which are past EXPIRE_BY and marks them as EXPIRED.
     * Invoices which are in DRAFT/PAID/CANCELLED status are not affected.
     * @param  int $limit
     * @return Base\PublicCollection
     */
    public function getIssuedAndPastExpiredByInvoices(int $limit = 5000)
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $nowMinus14days = Carbon::now(Timezone::IST)->addDays(-14)->getTimestamp();

        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->select(Entity::ID)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->whereBetween(Entity::EXPIRE_BY, array($nowMinus14days, $now))
                    ->limit($limit)
                    ->pluck(Entity::ID);
    }


    public function getPastInvoicesByStatusAndMerchatId(
        int $pastTime,
        array $statuses = [],
        array $merchantIds = [],
        int $limit = 500,
        string $type = Type::LINK)
    {
        $pastTimeMinus14days = Carbon::createFromTimestamp($pastTime, Timezone::IST)->addDays(-14)->getTimestamp();

        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->select(Entity::ID)
                    ->where(Entity::TYPE, '=' ,$type)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->whereIn(Entity::STATUS, $statuses)
                    ->whereBetween(Entity::UPDATED_AT, array($pastTimeMinus14days, $pastTime))
                    ->limit($limit)
                    ->pluck(Entity::ID);
    }

    /**
     * @deprecated
     */
    public function fetchIssuedInvoicesOfSubscription(Subscription\Entity $subscription)
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->with(Entity::ORDER)
                    ->get();
    }

    public function fetchIssuedInvoicesOfSubscriptionId(string $subscriptionId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::SUBSCRIPTION_ID, '=', $subscriptionId)
            ->where(Entity::STATUS, '=', Status::ISSUED)
            ->with(Entity::ORDER)
            ->first();
    }

    /**
     * @deprecated
     */
    public function fetchIssuedAndNotHaltedInvoiceForSubscription(Subscription\Entity $subscription)
    {
        $invoices = $this->newQuery()
                         ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                         ->where(Entity::STATUS, '=', Status::ISSUED)
                         ->where(function($query)
                         {
                                $query->where(Entity::SUBSCRIPTION_STATUS, '!=', Status::HALTED)
                                      ->orWhereNull(Entity::SUBSCRIPTION_STATUS);
                         })
                         ->with(Entity::ORDER)
                         ->get();

        if ($invoices->count() !== 1)
        {
            throw new Exception\LogicException(
                'There should have been exactly one invoice for this',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'auth_attempts'     => $subscription->getAuthAttempts(),
                    'error_status'      => $subscription->getErrorStatus(),
                    'status'            => $subscription->getStatus(),
                ]);
        }

        return $invoices->first();
    }

    /**
     * @deprecated
     * @param Subscription\Entity $subscription
     *
     * @return Entity
     * @throws Exception\LogicException
     */
    public function fetchLatestInvoiceOfPendingSubscription(Subscription\Entity $subscription)
    {
        if ($subscription->isPending() === false)
        {
            throw new Exception\LogicException(
                'This should have been called only for a pending subscription',
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_NOT_PENDING,
                [
                    'subscription_id'   => $subscription->getId(),
                ]);
        }

        $invoice = $this->newQuery()
                        ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                        ->where(Entity::STATUS, '=', Status::ISSUED)
                        ->where(Entity::BILLING_START, '=', $subscription->getCurrentStart())
                        ->where(Entity::BILLING_END, '=', $subscription->getCurrentEnd())
                        ->where(function($query)
                        {
                            $query->where(Entity::SUBSCRIPTION_STATUS, '!=', Status::HALTED)
                                  ->orWhereNull(Entity::SUBSCRIPTION_STATUS);
                        })
                        ->firstOrFail();

        return $invoice;
    }

    /**
     * Returns counts of payment which are succeeding(i.e. either created,
     * authorized or captured) for given invoice.
     *
     * This method gets used in validation(in conjunction with invoice being
     * in 'issued' state) when expiring/canceling an invoice, we don't allow the
     * former when there are succeeding payments.
     *
     * @param Entity $invoice
     *
     * @return int
     */
    public function getSucceedingPaymentsCount(Entity $invoice): int
    {
        return $invoice->payments()
                       ->whereIn(
                            Payment\Entity::STATUS,
                            [
                                Payment\Status::CREATED,
                                Payment\Status::AUTHORIZED,
                                Payment\Status::CAPTURED,
                            ])
                       ->count();
    }

    /**
     * @deprecated
     * Currently reporting is only available for link type.
     * This query is used in reporting and here we're adding where type=link
     * condition.
     *
     * @todo: Fix this!
     *
     * Ideally there should be two entities - PaymentLink and Invoice
     * OR some refactoring in entity report generation to pass around additional
     * query parameters conditionally or anyhow.
     *
     * @param       $merchantId
     * @param       $from
     * @param       $to
     * @param       $count
     * @param       $skip
     * @param array $relations
     *
     * @return
     */
    public function fetchEntitiesForReport(
        $merchantId,
        $from,
        $to,
        $count,
        $skip,
        $relations = [])
    {
        $query = $this->getFetchBetweenTimestampQuery($merchantId, $from, $to);

        $query->where(Entity::TYPE, Type::LINK);

        if (count($relations) > 0)
        {
            $query->with(...$relations);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->get();
    }

    /**
     * Gets list of draft invoices of given batch ids. If a non-empty array of
     * ids are passed only those out of total invoices of batch are returned.
     * This method is used in BatchIssue job.
     * @deprecated
     * @param  string $batchId
     * @param  array  $ids
     *
     * @return Base\PublicCollection
     */
    public function findDraftsByBatchIdAndPublicIds(
        string $batchId,
        array $ids = []): Base\PublicCollection
    {
        return $this->repo->useSlave( function() use ($batchId)
        {
            $query = $this->newQuery()
                          ->where(Entity::BATCH_ID, $batchId)
                          ->where(Entity::STATUS, Status::DRAFT);

            if (empty($ids) === false)
            {
                Entity::verifyIdAndSilentlyStripSignMultiple($ids);

                $query->whereIn(Entity::ID, $ids);
            }

            return $query->get();
        });
    }

    public function findIssuedByBatchId(string $batchId)
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
            ->select(Entity::ID)
            ->where(Entity::BATCH_ID, $batchId)
            ->where(Entity::STATUS, Status::ISSUED)
            ->pluck(Entity::ID);
    }

    public function findIssuedByBatchIdWithLimit(string $batchId, int $limit = 1000): Base\PublicCollection
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::BATCH_ID, $batchId)
            ->where(Entity::STATUS, Status::ISSUED)
            ->limit($limit)
            ->get();
    }

    /**
     * @deprecated
     */
    public function findByBatchIdAndReceipts(
        string $batchId,
        array $receipts = []): Base\PublicCollection
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->where(Entity::BATCH_ID, $batchId)
                    ->whereIn(Entity::RECEIPT, $receipts)
                    ->get();
    }

    public function fetchByIdempotentKey(string $idempotentKey)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, '=', $idempotentKey)
                    ->first();
    }

    /**
     * @deprecated
     */
    public function getNonDraftInvoiceCountByBatchId(string $batchId): int
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                    ->where(Entity::BATCH_ID, $batchId)
                    ->where(Entity::STATUS, '!=', Status::DRAFT)
                    ->count();
    }

    /**
     * @deprecated
     */
    public function getNonDraftInvoiceCountByBatchIds(array $batchIds): array
    {
        $collection = $this->newQueryWithConnection($this->getSlaveConnection())
                           ->selectRaw(Entity::BATCH_ID . ', COUNT(1) as count')
                           ->where(Entity::MERCHANT_ID, $this->merchant->getId())
                           ->whereIn(Entity::BATCH_ID, $batchIds)
                           ->where(Entity::STATUS, '!=', Status::DRAFT)
                           ->groupBy(Entity::BATCH_ID)
                           ->get();

        //  Converts collection results to needed format:
        //  [
        //      {
        //          'batch_id': 'batch_xyz',
        //          'count':     10
        //      },
        //      ..
        //  ]

        return $collection->map(
                function ($entity, $key)
                {
                    return $entity->getAttributes();
                })->toArray();
    }

    /**
     * Gets aggregate invoice stats per status for a given batch.
     *
     * Returns an array like:
     *  {
     *      'draft': 10,
     *      'issued': 10,
     *      'paid': 5,
     *      'expired': 1
     *  }
     *
     * @param  Batch\Entity $batch
     *
     * @return array
     */
    public function getInvoiceStatsForBatch(Batch\Entity $batch): array
    {
        /** @var Base\PublicCollection $collection */
        $collection = $this->newQueryWithConnection($this->getDataWarehouseConnectionForInvoiceRepo())
                           ->selectRaw(Entity::STATUS . ', COUNT(*) AS count')
                           ->where(Entity::BATCH_ID, '=', $batch->getId())
                           ->groupBy(Entity::STATUS)
                           ->pluck('count', Entity::STATUS);

        return array_map('intval', $collection->all());
    }

    /**
     * Returns true if given receipt is in use by the merchant for one of the non-cancelled or non-expired invoices
     * @param  Entity  $invoice
     * @param  string  $receipt
     * @return boolean
     */
    public function isDuplicateReceipt(Entity $invoice, string $receipt): bool
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                ->merchantId($invoice->getMerchantId())
                ->where(Entity::RECEIPT, $receipt)
                ->whereNotIn(Entity::STATUS, [Status::CANCELLED, Status::EXPIRED])
                ->where(Entity::ID, '!=', $invoice->getId())
                ->count() > 0;
    }

    public function findDuplicateInvoiceByInternalRefForMerchant(Entity $invoice, string $merchantId)
    {
        return $this->repo->useSlave( function() use ($invoice, $merchantId)
        {
            $nowMinus5days = Carbon::now(Timezone::IST)->addDays(-5)->getTimestamp();

            return $this->newQuery()
                        ->merchantId($merchantId)
                        ->where(Entity::CREATED_AT, '>=', $nowMinus5days)
                        ->where(Entity::INTERNAL_REF, $invoice->getInternalRef())
                        ->where(Entity::ID, '!=', $invoice->getId())
                        ->first();
        });
    }

    public function fetchForEntityType(array $input, string $merchantId, string $entityType = null)
    {
        $input[Entity::ENTITY_TYPE] = $entityType;

        return $this->repo->invoice->fetch($input, $merchantId, ConnectionType::SLAVE);
    }

    public function findByMerchantAndTokenRegistration(
        Merchant\Entity $merchant,
        SubscriptionRegistration\Entity $tokenRegistration)
    {
        $invoice = $this->newQueryWithConnection($this->getSlaveConnection())
            ->merchantId($merchant->getId())
            ->where(Entity::ENTITY_TYPE, E::SUBSCRIPTION_REGISTRATION)
            ->where(Entity::ENTITY_ID, $tokenRegistration->getId())
            ->first();

        return $invoice;
    }

    public function findByPaymentIdDocumentType($paymentId, $entityType, $documentType)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ENTITY_ID, $paymentId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::TYPE, $documentType)
            ->first();
    }

    public function findByPaymentIds($paymentIds, $entityType, $merchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->whereIn(Entity::ENTITY_ID, $paymentIds)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->get();
    }

    public function findByMerchantIdDocumentTypeDocumentNumber(
        string $merchantId, string $documentType , string $documentNumber)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::RECEIPT, $documentNumber)
            ->where(Entity::TYPE, $documentType)
            ->latest()
            ->first();
    }

    protected function addQueryParamPaymentId(BuilderEx $query, array $params)
    {
        $this->joinQueryPayment($query);

        $paymentId = $params[Entity::PAYMENT_ID];

        $paymentIdAttribute = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $query->where($paymentIdAttribute, '=', $paymentId);

        $query->select($query->getModel()->getTable() . '.*');
    }

    protected function addQueryParamTypes(BuilderEx $query, array $params)
    {
        $typeAttribute = $this->dbColumn(Entity::TYPE);

        $query->whereIn($typeAttribute, $params[Entity::TYPES]);
    }

    protected function joinQueryPayment($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ?? [];

        foreach ($joins as $join)
        {
            if ($join->table === $this->repo->payment->getTableName())
            {
                return;
            }
        }

        $invoiceOrderId = $this->dbColumn(Entity::ORDER_ID);
        $paymentOrderId = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);

        $query->join($this->repo->payment->getTableName(), $invoiceOrderId, '=', $paymentOrderId);
    }

    /**
     * @override
     *
     * To eager lazy load order relation along with invoices.
     *
     * @param array               $params
     * @param \RZP\Base\BuilderEx $query
     */
    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->with(Entity::ORDER);
    }

    protected function addQueryParamStatuses(BuilderEx $query, array $params)
    {
        $typeAttribute = $this->dbColumn(Entity::STATUS);

        $query->whereIn($typeAttribute, $params[Entity::STATUSES]);
    }

    protected function addQueryParamInternational($query, $params)
    {
        $currencyAttribute = $this->dbColumn(Entity::CURRENCY);

        $international = $params[Entity::INTERNATIONAL];

        $operator = ($international === '1') ? '!=' : '=';

        $query->where($currencyAttribute, $operator, Currency::INR);
    }

    /**
     * Filtering all the subscriptions invoices.
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamSubscriptions($query, $params)
    {
        $subscriptionsAttribute = $this->dbColumn(Entity::SUBSCRIPTION_ID);

        $subscriptions = $params[Entity::SUBSCRIPTIONS];

        $operator = ($subscriptions === '1') ? '!=' : '=';

        $query->where($subscriptionsAttribute, $operator, null);
    }

    protected function getDataWarehouseConnectionForInvoiceRepo()
    {
        if ($this->app['env'] === Environment::TESTING)
        {
            return Config::get('database.default');
        }

        $mode = $mode ?? $this->app['rzp.mode'];

        // Adding return type as slave for now. hotfix to reduce replication lag in TiDb
        return ($mode === Mode::TEST) ? Connection::SLAVE_TEST : Connection::SLAVE_LIVE;
    }

    public function fetchInvoicesByEntity(string $entityId, string $entityType)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->get();
    }

    public function fetchInvoicesByEntityAndType(string $entityId, string $entityType, string $type)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->where(Entity::TYPE, $type)
            ->get();
    }

    public function fetchYesterdaysFailedInvoicesByType(string $type)
    {
        $currentTime = Carbon::now(Timezone::IST);
        $to = $currentTime->startOfDay()->getTimestamp();
        $from = $currentTime->subDay()->startOfDay()->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::TYPE, $type)
            ->where(Entity::CREATED_AT, '>=', $from)
            ->where(Entity::CREATED_AT, '<', $to)
            ->where(Entity::STATUS, Status::FAILED)
            ->get();
    }
}
