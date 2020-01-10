<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Gateway\Mozart\Action;
use RZP\Gateway\Mozart\Mandate;

trait MandateTrait
{
    public function mandateCreate(array $input)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_CREATE)
            ->mandateCreate($input);
    }

    protected function getGatewayMandateObject(string $action)
    {
        $object = (new Mandate)->setMandateParams($action);

        return $object;
    }

    public function preProcessMandateCallback(array $input, string $gateway)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_CREATE_VERIFY)
                ->preProcessMandateCallback($input, $gateway);
    }

    public function getPaymentIdFromMandateCallback($response, $gateway)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_CREATE_VERIFY)
            ->getPaymentIdFromMandateCallback($response, $gateway);
    }

    public function mandateCreateCallback($input)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_CREATE_VERIFY)
                    ->callback($input);
    }

    public function mandateExecute(array $input)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_EXECUTE)
                ->mandateExecute($input);
    }

    public function mandateUpdate($input)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_UPDATE)
                ->mandateUpdate($input);
    }

    public function mandateUpdateCallback($input)
    {
        return $this->getGatewayMandateObject(Action::MANDATE_UPDATE_VERIFY)
                    ->callback($input);
    }

    protected function isMandateCreateRequest(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL));
    }

    protected function isMandateExecuteRequest(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO));
    }
}
