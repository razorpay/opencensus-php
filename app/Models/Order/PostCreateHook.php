<?php


namespace RZP\Models\Order;

use RZP\Constants;
use RZP\Models\SubscriptionRegistration\Core as TokenRegistrationCore;


class PostCreateHook extends Hook
{

    protected $hooks = [
        ExtraParams::TOKEN => 'createTokenRegistration'
    ];
    /**
     * @var Entity
     */
    protected $order;

    public function __construct(array $input, Entity $order)
    {
        parent::__construct($input);

        $this->order = $order;
    }

    public function createTokenRegistration(array $tokenParams)
    {
        $customerId = $this->orderInput[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicId($customerId);

        $tokenRegistrationInput = [
            Constants\Entity::SUBSCRIPTION_REGISTRATION => $tokenParams
        ];

        (new TokenRegistrationCore())->createAuthLinkForOrder($tokenRegistrationInput, $this->order, $customer);
    }

}
