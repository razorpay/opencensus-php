<?php

namespace Gateway\Wallet\Base;

use Gateway\Base;
use Gateway\Wallet;

class Gateway extends Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAction($this->action);

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

    public function generateRefunds($input)
    {
        foreach ($input['data'] as & $row)
        {
            $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $row['payment']['id'], Base\Action::AUTHORIZE);

            $row['gateway'] = $payment->toArray();
        }

        $ns = $this->getGatewayNamespace();
        $class = $ns . '\\' . 'RefundFile';

        return (new $class)->generate($input);
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

    protected function getRepo()
    {
        return new Repository();
    }
}
