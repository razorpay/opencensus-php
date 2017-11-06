<?php

namespace RZP\Models\Order;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Offer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param boolean         $partialPayment
     *
     * @return Entity
     */
    public function create(
        array $input,
        Merchant\Entity $merchant,
        bool $partialPayment = false)
    {
        $this->trace->info(
            TraceCode::ORDER_CREATE_REQUEST,
            $input
        );

        $order = new Entity;

        // Needs to be associated first cause merchant entity is required
        // in orders create validators.
        $order->merchant()->associate($merchant);

        $order->build($input);

        if ($partialPayment === true)
        {
            $order->allowPartialPayment();
        }

        $order->getValidator()->validateMerchantSpecificData();

        if (isset($input[Entity::OFFER_ID]) === true)
        {
            $offerId = $input[Entity::OFFER_ID];

            $this->validateAndAssociateOffer($order, $offerId);
        }

        $this->repo->saveOrFail($order);

        $this->trace->info(
            TraceCode::ORDER_CREATED,
            ['order_id' => $order->getId()]
        );

        return $order;
    }

    /**
     * Returns formatted data of order to be used by checkout.
     * Includes:
     * - Amount fields
     * - TPV data
     *
     * @param Entity          $order
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getFormattedDataForCheckout(
        Entity $order,
        Merchant\Entity $merchant): array
    {
        $data = [
            Entity::PARTIAL_PAYMENT => $order->isPartialPaymentAllowed(),
            Entity::AMOUNT          => $order->getAmount(),
            Entity::AMOUNT_PAID     => $order->getAmountPaid(),
            Entity::AMOUNT_DUE      => $order->getAmountDue(),
        ];

        if ($merchant->isTPVRequired() === true)
        {
            $data += [
                Entity::BANK           => $order->getBank(),
                Entity::ACCOUNT_NUMBER => $order->getMaskedAccountNumber(),
            ];
        }

        return $data;
    }

    protected function validateAndAssociateOffer(Entity $order, string $offerId)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($offerId, $this->merchant);

        $offerChecker = new Offer\Checker($offer, true);

        if ($offerChecker->checkOfferApplicableOnOrder($order) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER, null,
            [
                'offer_id' => $offerId,
                'order_id' => $order->getId()
            ]);
        }

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_ORDER,
            [
                'offer_id' => $offerId,
                'order_id' => $order->getId()
            ]);

        $order->offer()->associate($offer);
    }
}
