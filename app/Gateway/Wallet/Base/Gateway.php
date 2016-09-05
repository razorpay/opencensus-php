<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet;
use Lib\PhoneBook;

class Gateway extends Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes, $action = null;)
    {
        $attr = $this->getMappedAttributes($attributes);

        $action = $action ? $action : $this->action;

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAction($action);

        $payment->setWallet($this->input['payment']['wallet']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
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

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

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
