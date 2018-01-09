<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\LineItem;
use RZP\Error\ErrorCode;
use RZP\Models\Plan\Subscription;

class Repository extends Base\Repository
{
    protected $entity = 'invoice';

    protected $expands = [

        // Note: Dotted notation works and expands both line_items
        // followed by taxes of it.

        Entity::LINE_ITEMS . '.' . LineItem\Entity::TAXES,
    ];

    protected $entityFetchParamRules = [
        Entity::TYPE              => 'sometimes|string|custom',
        Entity::PAYMENT_ID        => 'sometimes|string|min:14|max:18',
        Entity::RECEIPT           => 'sometimes|string|min:1|max:40',
        Entity::CUSTOMER_ID       => 'sometimes|string|min:14|max:19',
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
        self::EXPAND . '.*'       => 'string|in:payments,payments.card,user',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID       => 'sometimes|alpha_num',
        Entity::ORDER_ID          => 'sometimes|string|max:20',
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
        array $input = [])
    {
        $invoice = $this->findByPublicIdAndMerchant($id, $merchant, $input);

        //
        // If userId is set and userRole is sellerapp we throw 403 if invoice's
        // user id is not same as passed userId.
        //
        if (($userId !== null) and
            ($userRole === Constants::SELLERAPP_ROLE) and
            ($invoice->getUserId() !== $userId))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        return $invoice;
    }

    public function findByPublicIdAndSubscription(string $invoiceId, Subscription\Entity $subscription)
    {
        Entity::verifyIdAndStripSign($invoiceId);

        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->findOrFailPublic($invoiceId);
    }

    public function getInvoicesForIssuedNotificationToCustomer($medium)
    {
        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQuery()
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
     *
     * @return Base\PublicCollection
     */
    public function getIssuedAndPastExpiredByInvoices()
    {
        $currentTime = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->with(Entity::ORDER)
                    ->get();
    }

    public function fetchIssuedInvoicesOfSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->with(Entity::ORDER)
                    ->get();
    }

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
     *
     * @param  string $batchId
     * @param  array  $ids
     *
     * @return Base\PublicCollection
     */
    public function findDraftsByBatchIdAndPublicIds(
        string $batchId,
        array $ids = []): Base\PublicCollection
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
    }

    public function findByBatchIdAndReceipts(
        string $batchId,
        array $receipts = []): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::BATCH_ID, $batchId)
                    ->whereIn(Entity::RECEIPT, $receipts)
                    ->get();
    }

    public function getNonDraftInvoiceCountByBatchId(string $batchId): int
    {
        return $this->newQuery()
                    ->where(Entity::BATCH_ID, $batchId)
                    ->where(Entity::STATUS, '!=', Status::DRAFT)
                    ->count();
    }

    public function getNonDraftInvoiceCountByBatchIds(array $batchIds): array
    {
        $collection = $this->newQuery()
                           ->selectRaw(Entity::BATCH_ID . ', COUNT(1) as count')
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
}
