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
     * @param $input
     * @param $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
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

        $order->getValidator()->validateMerchantSpecificData($order);

        if (isset($input[Entity::OFFER_ID]) === true)
        {
            $offerId = $input[Entity::OFFER_ID];

            $this->validateAndAssociateOffer($order, $offerId);
        }

        $this->repo->saveOrFail($order);

        return $order;
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
