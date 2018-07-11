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
     * @throws Exception\BadRequestValidationFailureException
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

        $order->generateId();

        $this->validateReceiptUniqueness($order);

        if ($partialPayment === true)
        {
            $order->allowPartialPayment();
        }

        $order->getValidator()->validateMerchantSpecificData();

        $order = $this->repo->transaction(function() use ($order, $input)
        {
            $this->associateOffers($order, $input);

            $this->repo->saveOrFail($order);

            return $order;
        });

        $this->trace->info(
            TraceCode::ORDER_CREATED,
            ['order_id' => $order->getId()]
        );

        return $order;
    }

    protected function associateOffers(Entity $order, array $input)
    {
        if (isset($input[Entity::OFFERS]) === false)
        {
            return;
        }

        foreach (array_unique($input[Entity::OFFERS]) as $offerId)
        {
            $this->validateAndAssociateOffer($order, $offerId);
        }
    }

    protected function validateAndAssociateOffer(Entity $order, string $offerId)
    {
        $offer = (new Offer\Core)->fetchAndValidateOfferForOrder($offerId, $order);

        // Creates row in entity_offers table
        $order->associateOffer($offer);

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_ORDER,
            [
                'offer_id' => $offerId,
                'order_id' => $order->getId()
            ]);
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
