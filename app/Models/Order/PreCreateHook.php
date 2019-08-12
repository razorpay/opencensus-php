<?php


namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\SubscriptionRegistration\Core as TokenRegistrationCore;

class PreCreateHook extends Hook
{
    protected $hooks = [
        ExtraParams::TOKEN => 'validateTokenParams'
    ];

    public function validateTokenParams(array $paramInput)
    {
        (new TokenRegistrationCore())->validateTokenInput($paramInput);

        $this->validateCustomerIdNonEmpty();
    }

    public function validateCustomerIdNonEmpty()
    {
        $customerIdPresent = false;

        if (array_key_exists(Entity::CUSTOMER_ID, $this->orderInput) === true)
        {
            $customerId  = $this->orderInput[Entity::CUSTOMER_ID];

            if (empty($customerId) === false)
            {
                $customerIdPresent = true;
            }
        }

        if ($customerIdPresent === false)
        {
            throw new Exception\BadRequestValidationFailureException('Customer Id is required with token field');
        }
    }
}
