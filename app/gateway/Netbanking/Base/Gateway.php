<?php

namespace Gateway\Netbanking\Base;

use Gateway\Netbanking;
use Gateway\Base\Action;

class Gateway extends \Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setPaymentId($this->input['payment']['id']);

        $payment->setAction($this->action);

        $payment->setBank($this->input['payment']['bank']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
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


    public function generateRefunds($input)
    {
        foreach ($input['data'] as & $row)
        {
            $payment = $this->getRepo()->findByPaymentIdAndAction(
                                $row['payment']['id'], Action::AUTHORIZE);

            $row['gateway'] = $payment->toArray();
        }

        $ns = $this->getGatewayNamespace();
        $class = $ns . '\\' . 'RefundFile';

        return (new $class)->generate($input);
    }

    protected function getRepo()
    {
        return new Repository();
    }
}
