<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Payment;
use RZP\Models\Batch\Header;

class RecurringCharge
{
    public static function getRecurringChargeInput(array $entry): array
    {
        return [
            Payment\Entity::AMOUNT      => $entry[Header::RECURRING_CHARGE_AMOUNT],
            Payment\Entity::CURRENCY    => $entry[Header::RECURRING_CHARGE_CURRENCY],
            Payment\Entity::EMAIL       => $entry[Header::RECURRING_CHARGE_EMAIL],
            Payment\Entity::CONTACT     => $entry[Header::RECURRING_CHARGE_CONTACT],
            Payment\Entity::DESCRIPTION => $entry[Header::RECURRING_CHARGE_DESCRIPTION],
            Payment\Entity::CUSTOMER_ID => $entry[Header::RECURRING_CHARGE_CUSTOMER_ID],
            Payment\Entity::TOKEN       => $entry[Header::RECURRING_CHARGE_TOKEN],
            Payment\Entity::RECURRING   => true,
        ];
    }
}
