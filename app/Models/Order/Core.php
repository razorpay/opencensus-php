<?php

namespace RZP\Models\Order;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Offer;
use RZP\Trace\TraceCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as FeatureConstants;

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

        $this->validateReceiptUniqueness($order);

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
                Entity::METHOD         => $order->getMethod(),
            ];
        }
        else if ($order->getBank() !== null)
        {
            $data += [
                Entity::BANK           => $order->getBank(),
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

    /**
     * Validates the uniqueness of the receipt for featured merchants. The uniqueness here, is within the orders of that
     * particular merchant and not across all the merchants.
     *
     * @param Entity $order
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateReceiptUniqueness(Entity $order)
    {
        $merchant = $order->merchant;

        if ($merchant->isFeatureEnabled(FeatureConstants::ORDER_RECEIPT_UNIQUE) === false)
        {
            return;
        }

        $receipt = $order->getReceipt();

        if ($receipt === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_REQUIRED,
                Entity::RECEIPT);
        }

        $params = [Entity::RECEIPT => $receipt];

        $duplicateOrders = $this->repo->order->fetch($params, $merchant->getId());

        if (count($duplicateOrders) > 0)
        {
            $duplicateOrderIds = $duplicateOrders->pluck(Entity::ID)->all();

            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_NOT_UNIQUE,
                ['order_ids' => $duplicateOrderIds]);
        }
    }
}
