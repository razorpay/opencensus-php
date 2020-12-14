<?php

namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\Transfer;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant\Methods;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\UpiMandate\Core as UpiMandateCore;
use RZP\Models\SubscriptionRegistration\Core as TokenRegistrationCore;

class PreCreateHook extends Hook
{
    protected $hooks = [
        ExtraParams::TOKEN     => 'validateTokenParams',
        ExtraParams::TRANSFERS => 'validateTransferParams'
    ];

    public function process()
    {
        (new Validator())->validateInput('preCreateHook', array_only($this->orderInput, array_keys($this->hooks)));

        parent::process();
    }

    public function validateTokenParams(array $paramInput)
    {
        if ((isset($this->orderInput[Entity::METHOD]) === true) and
            ($this->orderInput[Entity::METHOD] === Methods\Entity::UPI))
        {
            $this->validateTokenParamsForUpiMandate($paramInput, $this->orderInput);
        }
        else
        {
            $this->validateTokenParamsForSubscriptionRegistration($paramInput);
        }

        $this->validateCustomerIdNonEmpty();
    }

    protected function validateTokenParamsForUpiMandate($input, $orderInput)
    {
        (new UpiMandateCore())->validateTokenInput($input, $orderInput);
    }

    protected function validateTokenParamsForSubscriptionRegistration($input)
    {
        $input[SubscriptionRegistration\Entity::METHOD] = $this->orderInput[Entity::METHOD] ?? null;

        (new TokenRegistrationCore())->validateTokenInput($input);
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

    public function validateTransferParams(array $input)
    {
        try
        {
            $partialPayment = boolval($this->orderInput[Entity::PARTIAL_PAYMENT] ?? false);

            if ($partialPayment === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Partial payment not allowed for transfers');
            }

            if ($this->orderInput[Entity::CURRENCY] !== Currency::INR)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The currency should be INR for transfers');
            }

            (new Transfer\Core())->validateTransfersInput($this->orderInput[Entity::AMOUNT], $input);
        }
        catch (\Exception $e)
        {
            (new Transfer\Metric())->pushCreateFailedMetrics($e);

            throw $e;
        }
    }
}
