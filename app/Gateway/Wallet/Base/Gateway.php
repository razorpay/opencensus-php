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

        $action = $action ?: $this->action;

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($action);

        $gatewayPayment->setWallet($this->input['payment']['wallet']);

        $gatewayPayment->setEmail($this->input['payment']['email']);

        $gatewayPayment->setContact($this->input['payment']['contact']);

        $gatewayPayment->fill($attr);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $action = null)
    {
        $action = $action ? $action : $this->action;

        $refund = $this->getNewGatewayPaymentEntity();

        $refund->fill($attributes);

        $refund->setAction($action);

        $refund->saveOrFail();

        return $refund;
    }

    private function updateWalletEntity($wallet, $attributes)
    {
        $wallet->fill($attributes);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    /*
     * Updates the gateway payment entity
     *
     * @param gatewayPayment Wallet\Base\Entity      Gateway Payment Entity
     * @param attributes     array
     * @param mapped         boolean                 If the attrs are mapped to gateway codes
     */
    protected function updateGatewayPaymentEntity(
        Base\Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        return $this->updateWalletEntity($gatewayPayment, $attributes);
    }

    /**
     * Updates the gateway refund entity
     *
     * @param      $gatewayPayment
     * @param      $attributes
     * @param bool $mapped
     *
     * @return
     */
    protected function updateGatewayRefundEntity(
        $gatewayPayment,
        $attributes,
        $mapped = true)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        return $this->updateWalletEntity($gatewayPayment, $attributes);
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
