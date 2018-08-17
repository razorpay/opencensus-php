<?php

namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $this->modifyOfferRequestFromOldFormat($input);

        $order = (new Core)->create($input, $merchant);

        return $order->toArrayPublic();
    }

    /**
     * Old format:
     * {
     *   "offer_id": "offer_AJDTUWZjgei84L"
     * }
     *
     * New format:
     * {
     *   "offers": [
     *     "offer_AJDTUWZjgei84L"
     *   ]
     * }
     *
     * Both formats are to be concurrently supported.
     * Here, we convert the old format to the new one, and force_offer explicitly,
     * so that the old format continues to work the way it did.
     *
     * @param  array $input
     */
    protected function modifyOfferRequestFromOldFormat(array & $input)
    {
        if ($this->isOldFormat($input) === false)
        {
            return;
        }

        if (isset($input[Entity::OFFERS]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Request should send either offer_id or offers', null, [
                    Entity::OFFER_ID => $input[Entity::OFFER_ID],
                    Entity::OFFERS   => $input[Entity::OFFERS],
                ]);
        }

        $additionalInput = [
            Entity::FORCE_OFFER => true,
            Entity::OFFERS      => [
                $input[Entity::OFFER_ID],
            ],
        ];

        $input = array_merge($input, $additionalInput);

        unset($input[Entity::OFFER_ID]);
    }

    protected function isOldFormat(array $input): bool
    {
        return isset($input[Entity::OFFER_ID]) ? true : false;
    }

    public function fetch($id)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($id, $this->merchant);

        return $order->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $orders = $this->repo->order->fetch($input, $this->merchant->getId());

        return $orders->toArrayPublic();
    }

    public function fetchPaymentsFor($id)
    {
        $options = ['order_id' => $id];

        $payments = $this->repo->payment->fetch($options, $this->merchant->getKey());

        return $payments->toArrayPublic();
    }
}
