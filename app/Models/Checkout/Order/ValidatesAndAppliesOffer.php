<?php

namespace RZP\Models\Checkout\Order;

use RZP\Constants\Entity as ConstantsEntity;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Offer\Checker as OfferChecker;
use RZP\Models\Offer\Constants as OfferConstants;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Models\Payment\Method;
use RZP\Trace\TraceCode;

trait ValidatesAndAppliesOffer
{
    /**
     * @param Entity $checkoutOrder
     *
     * @return void
     *
     * @throws BadRequestException
     */
    public function validateAndApplyUPIOfferIfApplicable(Entity $checkoutOrder): void
    {
        if ($checkoutOrder->getReceiverType() !== ConstantsEntity::QR_CODE) {
            return;
        }

        $offer = $this->validateAndFetchOffer($checkoutOrder);

        if ($offer === null) {
            return;
        }

        $this->modifyAmountForDiscountedUPIOfferIfApplicable($checkoutOrder, $offer);
    }

    /**
     * @param OrderEntity $order   Order entity whose associated offers need to be scanned
     * @param string      $offerId Primary Key of the offer tha needs to be fetched from offers associated to the order
     *
     * @return OfferEntity|null    Offer entity whose primary key is the $offerId passed in input params
     */
    protected function fetchOfferFromOrder(OrderEntity $order, string $offerId): ?OfferEntity
    {
        $offers = $order->offers;

        $offer = null;

        if (
            $offers->count() === 1 &&
            $order->isOfferForced() === true
        ) {
            $offer = $offers->first();

            $offerId = $offer->getId();
        }

        if ($offerId === '') {
            return null;
        }

        return $offer ?: $offers->first(
            static function (OfferEntity $offer, $key) use ($offerId) {
                return $offer->getId() === $offerId;
            }
        );
    }

    /**
     * Applies offer by discounting the amount if the offer is an instant offer
     * for UPI payment method.
     *
     * @param Entity      $checkoutOrder
     * @param OfferEntity $offer
     *
     * @return void
     */
    protected function modifyAmountForDiscountedUPIOfferIfApplicable(Entity $checkoutOrder, OfferEntity $offer): void
    {
        if (
            $offer->getPaymentMethod() === Method::UPI &&
            $offer->getOfferType() === OfferConstants::INSTANT_OFFER
        ) {
            $orderAmount = $checkoutOrder->getAmount();

            $discountedAmount = $offer->getDiscountedAmount($orderAmount);

            $checkoutOrder->setDiscountedAmount($discountedAmount);

            $checkoutOrder->setDiscount($orderAmount - $discountedAmount);

            // Update the offer_id with the applied offer's id.
            // Necessary step as offer_id won't be present in input if the order
            // has a forced UPI offer.
            $checkoutOrder->setOfferId($offer->getId());

            return;
        }

        // Unset offer_id to indicate that the offer wasn't applied.
        $checkoutOrder->unsetOfferId();

        $this->trace->info(
            TraceCode::OFFER_ID_NOT_APPLICABLE_ON_CHECKOUT_ORDER,
            [
                'checkout_order_id' => $checkoutOrder->getId(),
                'order_id' => optional($checkoutOrder->order)->getId(),
                'offer_id' => $offer->getId(),
            ]
        );
    }

    /**
     * @param Entity $checkoutOrder
     *
     * @return OfferEntity|null
     *
     * @throws BadRequestException
     */
    protected function validateAndFetchOffer(Entity $checkoutOrder): ?OfferEntity
    {
        if ($checkoutOrder->order === null) {
            return null;
        }

        $offerId = $checkoutOrder->getOfferId();

        $offerId = OfferEntity::silentlyStripSign($offerId);

        $offers = $checkoutOrder->order->offers;

        // If offer is present in the request, we need to validate it against the order.
        if ($offerId !== '' && $offers->contains($offerId) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER,
                null,
                [
                    'offer_id' => OfferEntity::getSignedId($offerId),
                    'order_id' => $checkoutOrder->order->getPublicId(),
                ]
            );
        }

        $offer = $this->fetchOfferFromOrder($checkoutOrder->order, $offerId);

        if ($offer === null) {
            return null;
        }

        $checker = new OfferChecker($offer, true);

        if (! $checker->checkValidityOnOrder($checkoutOrder->order))
        {
            return null;
        }

        return $offer;
    }
}
