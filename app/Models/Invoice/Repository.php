<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Plan\Subscription;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'invoice';

    protected $entityFetchParamRules = [
        Entity::PAYMENT_ID => 'sometimes|string|min:14|max:18',
        Entity::RECEIPT    => 'sometimes|string|min:1|max:40',
    ];

    protected $proxyFetchParamRules = [
        Entity::USER_ID           => 'sometimes|alpha_num',
        Entity::STATUS            => 'sometimes|string',
        Entity::TYPE              => 'sometimes|string|max:16',
        Entity::CUSTOMER_NAME     => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:255',
        Entity::CUSTOMER_CONTACT  => 'sometimes|contact_syntax',
        Entity::CUSTOMER_EMAIL    => 'sometimes|email',
        Entity::NOTES             => 'sometimes|string|min:1|max:40',
        EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
        // TODO: Enable this once the expand pr is back merged.
        // EsRepository::SEARCH_HITS => 'sometimes|boolean',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::ORDER_ID    => 'sometimes|string|max:20',
    ];

    /**
     * Fetches invoice entity for given public id and merchant, followed by
     * an access check for user info (userId and userRole) passed.
     *
     * @param string          $id
     * @param Merchant\Entity $merchant
     * @param string|null     $userId
     * @param string|null     $userRole
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function findByPublicIdAndMerchantAndUser(
        string $id,
        Merchant\Entity $merchant,
        string $userId = null,
        string $userRole = null)
    {
        $invoice = $this->findByPublicIdAndMerchant($id, $merchant);

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

    public function getInvoicesForIssuedNotificationToCustomer($medium)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where($medium . '_status', '=', NotifyStatus::PENDING)
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::SCHEDULED_AT, '<=', $currentTime)
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
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }

    /**
     * @deprecated
     *
     * Once an invoice is issued for a subscription, it MUST
     * be charged, irrespective of whether the invoice has been expired
     * or the subscription has been cancelled.
     * The only case when the invoice will not be charged is when
     * the sub status of the invoice is set to on_hold.
     *
     * @return Base\PublicCollection
     */
    public function getSubscriptionInvoicesToCharge()
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->where(function ($query)
                        {
                            $query->where(Entity::SUB_STATUS, '!=', Status::ON_HOLD)
                                  ->orWhereNull(Entity::SUB_STATUS);
                        })
                    ->whereNotNull(Entity::SUBSCRIPTION_ID)
                    ->with('subscription')
                    ->get();
    }

    public function fetchIssuedInvoicesOfSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->where(Entity::STATUS, '=', Status::ISSUED)
                    ->get();
    }

    public function getNonFailedPaymentsCount(Entity $invoice)
    {
        return $invoice->payments()
                       ->where(Payment\Entity::STATUS, '!=', Payment\Status::FAILED)
                       ->count();
    }

    protected function addQueryParamPaymentId($query, $params)
    {
        $this->joinQueryPayment($query);

        $paymentId = $params[Entity::PAYMENT_ID];
        Entity::stripSignWithoutValidation($paymentId);

        $paymentIdAttribute = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $query->where($paymentIdAttribute, '=', $paymentId);

        $query->select($query->getModel()->getTable() . '.*');
    }

    protected function addQueryParamOrderId($query, $params)
    {
        $orderId = (new Order\Entity)->verifyIdAndSilentlyStripSign($params[Entity::ORDER_ID]);

        $orderIdAttribute = $this->manager->invoice->getAttributeWithTableName(Entity::ORDER_ID);

        $query->where($orderIdAttribute, '=', $orderId);
    }

    protected function joinQueryPayment($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ?? [];

        foreach ($joins as $join)
        {
            if ($join->table === $this->manager->payment->getTableName())
            {
                return;
            }
        }

        $invoiceOrderId = $this->getAttributeWithTableName(Entity::ORDER_ID);
        $paymentOrderId = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ORDER_ID);

        $query->join($this->manager->payment->getTableName(), $invoiceOrderId, '=', $paymentOrderId);
    }
}
