<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Payment;
use RZP\Constants\Entity;

trait RecurringTrait
{
    protected function isFirstRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL));
    }

    protected function isSecondRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO));
    }

    protected function authorizeRecurring(array $input)
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        return $gateway->authorize($input);
    }
}
