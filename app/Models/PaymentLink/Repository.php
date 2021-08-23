<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'payment_link';

    protected $expands = [
        Entity::PAYMENT_PAGE_ITEMS,
        Entity::PAYMENT_PAGE_ITEMS . '.' . PaymentPageItem\Entity::ITEM,
    ];

    const SUCCEEDING_PP_PAYMENTS_SLAVE_EXPERIMENT = 'succeeding_pp_payments_slave_experiment';

    /**
     * Gets all ACTIVE status payment links which are past EXPIRE_BY.
     * Payment links which are in INACTIVE status are not affected.
     *
     * @return Base\PublicCollection
     */
    public function getActiveAndPastExpireByPaymentLinks(): Base\PublicCollection
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }

    /**
     * Returns count of units purchased of which payments are still succeeding (i.e. created or authorized).
     * This method gets used in determining if enough slots are available to initiate a payment.
     *
     * @param  Entity $paymentLink
     * @return int
     */
    public function getSucceedingPaymentUnits(Entity $paymentLink): int
    {
        return $paymentLink->payments()
                           ->whereIn(
                               Payment\Entity::STATUS,
                               [
                                   Payment\Status::CREATED,
                                   Payment\Status::AUTHORIZED,
                               ])
                           ->get()
                           ->sum(function (Payment\Entity $p)
                              {
                                  return (int) ($p->getNotes()[Entity::UNITS] ?? 1);
                              });
    }

    public function getSucceedingPayments(Entity $paymentLink)
    {
        $variant = $this->app->razorx->getTreatment(
            $paymentLink->getMerchantId(),
            self::SUCCEEDING_PP_PAYMENTS_SLAVE_EXPERIMENT,
            $this->app['rzp.mode']
        );

        if ($variant === 'on')
        {
            return $this->repo->useSlave( function() use ($paymentLink)
            {
                return $paymentLink->payments()
                    ->whereIn(
                        Payment\Entity::STATUS,
                        [
                            Payment\Status::CREATED,
                            Payment\Status::AUTHORIZED,
                        ])
                    ->get();
            });
        }

        return $paymentLink->payments()
                           ->whereIn(
                               Payment\Entity::STATUS,
                               [
                                   Payment\Status::CREATED,
                                   Payment\Status::AUTHORIZED,
                                   ])
                           ->get();
    }

    /**
     * Finds payment link entity by public id constrained to not being marked
     * inactive with reason deactivated(manually).
     *
     * @param  string $id
     * @return Entity
     */
    public function findActiveByPublicId(string $id): Entity
    {
        $entity = $this->findByPublicId($id);

        // No direct query with filter because index is as (status, status_reason).
        if ($entity->isDeactivated() === true)
        {
            $merchantDetails = $entity->getMerchantSupportDetails();

            $orgBrandingDetails = $entity->getMerchantOrgBrandingDetails();

            $data['merchant'] = $merchantDetails;

            $data['org'] = $orgBrandingDetails;

            $data['entity'] = [Entity::ID => $entity->getPublicId()];

            $data['mode'] = $this->app['rzp.mode'];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, $data, 'This page has been deactivated');
        }

        return $entity;
    }

    public function getAllPaymentPagesForMigration($limit = 1000)
    {
        $id            = $this->repo->payment_link->dbColumn(Entity::ID);

        $paymentPageId = $this->repo->payment_page_item->dbColumn(PaymentPageItem\Entity::PAYMENT_LINK_ID);

        $attributes = $this->repo->payment_link->dbColumn('*');

        return $this->newQuery()
                    ->select($attributes)
                    ->leftJoin(Table::PAYMENT_PAGE_ITEM, $paymentPageId, '=', $id)
                    ->whereNull($paymentPageId)
                    ->limit($limit)
                    ->get();
    }

    public function getAllPaymentPagesForMigrationOfMinPurchase(int $timestamp, $limit = 1000)
    {
        return $this->newQuery()
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->orderBy(Entity::CREATED_AT)
                    ->limit($limit)
                    ->get();
    }
}
