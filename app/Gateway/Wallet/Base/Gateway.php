<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet;
use Lib\PhoneBook;

class Gateway extends Base\Gateway
{
    protected function otpResend(array $input)
    {
        $this->input = $input;
        $this->action = Action::OTP_RESEND;
    }

    protected function createGatewayPaymentEntity($attributes, $action = null)
    {
        $attr = $this->getMappedAttributes($attributes);

        $action = $action ? $action : $this->action;

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($action);

        $gatewayPayment->setWallet($this->input['payment']['wallet']);

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes)
    {
        $refund = $this->getNewGatewayPaymentEntity();

        $refund->fill($attributes);

        $refund->saveOrFail();

        return $refund;
    }

    /*
     * Updates the gateway payment entity
     *
     * @param gatewayPayment Wallet\Base\Entity      Gateway Payment Entity
     * @param attributes     array
     */
    protected function updateGatewayPaymentEntity($gatewayPayment, $attributes)
    {
        // gatewayPayment is gateway payment entity
        $attr = $this->getMappedAttributes($attributes);

        $gatewayPayment->fill($attr);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Wallet\Base\Entity;
    }

    protected function getReverseMappedAttributes($attributes)
    {
        $attr = [];

        $map = array_flip($this->map);

        foreach ($attributes as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function getRepository()
    {
        $gateway = 'wallet';

        return $this->app['repo']->$gateway;
    }

    protected function getFormattedContact($contact)
    {
        // Constructor does the basic validation
        $phoneBook = new PhoneBook($contact, true);

        return $phoneBook->format(PhoneBook::DOMESTIC);
    }
}
