<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends \RZP\Gateway\Base\Gateway
{
    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->getMappedAttributes($attributes);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($this->input['payment']['id']);

        $gatewayPayment->setAction($this->action);

        $gatewayPayment->setBank($this->input['payment']['bank']);

        if (($this->action === Action::AUTHORIZE) and
            ($this->input['merchant']->isTPVRequired()))
        {
            $gatewayPayment->setAccountNumber($this->input['order']['account_number']);
        }

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Netbanking\Base\Entity;
    }

    protected function getRepository()
    {
        $gateway = 'netbanking';

        return $this->app['repo']->$gateway;
    }

    protected function setTpv(Entity $gatewayPayment)
    {
        $this->tpv = $gatewayPayment->isTpv();
    }

    public function isPaymentTpvEnabled(Entity $gatewayPayment, Merchant\Entity $merchant)
    {
        if (($gatewayPayment->isTpv()) or ($merchant->isTPVRequired()))
        {
            return true;
        }

        return false;
    }

    protected function returnVerifyStatusOrThrowException(Verify $verify)
    {
        //
        // In this case, there's a bug in the code
        // The payment was incorrectly marked as authorized.
        //
        if (($verify->apiSuccess === true) and
            ($verify->gatewaySuccess === false))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FALSE_AUTHORIZE);
        }

        return VerifyResult::STATUS_MISMATCH;
    }

    public function generateClaims(array $input)
    {
        $paymentIds = array_map(function($row)
        {
            return $row['payment']['id'];
        }, $input['data']);

        $gatewayPayments = $this->repo->fetchByPaymentIdsAndAction(
                                $paymentIds, Action::AUTHORIZE);

        // payment id is key and gatewayPayment entity is value
        $gatewayPayments = $gatewayPayments->getDictionaryByAttribute(Entity::PAYMENT_ID);

        // Adding relevant information to each gateway row in $input['data']
        $input['data'] = array_map(function($row) use ($gatewayPayments)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayPayments[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayPayments[$paymentId]->toArray();
            }

            return $row;
        }, $input['data']);

        $namespace = $this->getGatewayNamespace();

        $class = $namespace . '\\' . 'ClaimsFile';

        return (new $class)->generate($input);
    }
}
