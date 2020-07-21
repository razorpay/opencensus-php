<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base\Action;
use RZP\Models\Payment;
use RZP\Constants\Entity;

trait RecurringTrait
{
    protected static $gatewayDataIdToActionMap = [
        Action::AUTHENTICATE    => 'create',
        Action::AUTHORIZE       => 'execte',
        Action::DEBIT           => 'execte',
        Action::MANDATE_CANCEL  => 'revoke',
        Action::PRE_DEBIT       => 'notify',
    ];

    protected function getGatewayDataBlockForUpiRecurring($input, $action)
    {
        $attempt = 0;

        $action = self::$gatewayDataIdToActionMap[$action];

        $id = $input['payment']['id'] . $action . $attempt;

        $gatewayData = [
            'id'     => $id,
            'act'    => $action,
            'ano'    => $attempt,
        ];

        return $gatewayData;
    }

    protected function setGatewayDataBlockForUpiRecurring(array & $input)
    {
        $input['upi']['gateway_data'] = $this->getGatewayDataBlockForUpiRecurring($input, $this->action);
    }

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
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->authorizeRecurring($input);
    }

    protected function recurringMandateCreateCallback(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->callback($input);
    }

    protected function recurringMandateRevoke(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->mandateRevoke($input);
    }

    protected function firstDebit(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->debit($input);
    }

    protected function isFirstUpiRecurringPayment($payment): bool
    {
        return ($payment['method'] === 'upi' and $payment['recurring_type'] === 'initial');
    }
}
