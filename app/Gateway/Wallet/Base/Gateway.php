<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet;
use Lib\PhoneBook;

class Gateway extends Base\Gateway
{
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

    /*
     * Updates the gateway payment entity
     *
     * @param wallet        Wallet\Base\Entity      Gateway Payment Entity
     * @param attributes    array
     */
    protected function updateGatewayPaymentEntity($wallet, $attributes)
    {
        // wallet is gateway payment entity
        $attr = $this->getMappedAttributes($attributes);

        $wallet->fill($attr);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    protected function createGatewayRefundEntity($attributes)
    {
        $refund = $this->getNewGatewayPaymentEntity();

        $refund->fill($attributes);

        $refund->saveOrFail();

        return $refund;
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
