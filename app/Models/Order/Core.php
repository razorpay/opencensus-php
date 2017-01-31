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

        $order = (new Entity)->build($input);

        $order->merchant()->associate($merchant);

        $order->getValidator()->validateMerchantSpecificData($order);

        if (isset($input[Entity::OFFER_ID]) === true)
        {
            $offerId = $input[Entity::OFFER_ID];

            $order = $this->validateAndAssociateOffer($order, $offerId);
        }

        $this->repo->saveOrFail($order);

        return $order;
    }

    protected function validateAndAssociateOffer(Entity $order, string $offerId)
    {
        $offer = $this->repo->offer->findByPublicIdAndMerchant($offerId, $this->merchant);

        $offerChecker = new Offer\Checker($offer);

        if ($offerChecker->checkOfferApplicableOnOrder($order) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_OFFER_INVALID_FOR_ORDER);
        }

        $order->offer()->associate($offer);

        return $order;
    }
}
