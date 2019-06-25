<?php

namespace RZP\Gateway\Mpi\Base;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;

class Gateway extends Base\Gateway
{
    protected function createGatewayPaymentEntity(array $attributes, array $input, $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setGateway($this->gateway);

        $action = $action ?: $this->action;

        $gatewayPayment->setAction($action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateGatewayPaymentFromCallbackResponse(
        Entity $gatewayPayment,
        array $response)
    {
        $attributes = $this->getCallbackResponseAttributes($response);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getAcquirerBin(array $input)
    {
        $gateway = $input['payment']['gateway'];
        $network = $input['card']['network_code'];

        switch ($network)
        {
            case Card\Network::MC:
            case Card\Network::MAES:
                $acqBin = $this->config[$gateway]['live_mastercard_acq_bin'] ?? $this->config['live_mastercard_acq_bin'];
                break;

            case Card\Network::VISA:
                $acqBin = $this->config[$gateway]['live_visa_acq_bin'] ?? $this->config['live_visa_acq_bin'];
                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);

        }

        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_acq_bin'];
        }

        return $acqBin;
    }

    protected function generateXid(array $input)
    {
        $xid = str_pad($input['payment']['id'], 20, '0', STR_PAD_LEFT);

        return base64_encode($xid);
    }

    protected function getNewGatewayPaymentEntity()
    {
        return new Entity;
    }

    protected function getRepository()
    {
        $gateway = 'mpi';

        return $this->app['repo']->$gateway;
    }
}
