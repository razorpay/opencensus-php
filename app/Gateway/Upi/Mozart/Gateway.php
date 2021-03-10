<?php

namespace RZP\Gateway\Upi\Mozart;

use RZP\Gateway\Mozart;
use RZP\Gateway\Upi\Base;
use RZP\Models\Payment\UpiMetadata;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

class Gateway extends Mozart\Gateway
{
    protected $gateway = 'upi_mozart';

    const ACQUIRER = 'mozart';

    public function createOrUpdateUpiEntityForMozartGateways(array $input, array $attributes, $action)
    {
        parent::action($input, $action);

        $upi = $this->getUpiRepository()->findByPaymentIdAndAction($input['payment']['id'], $action);

        $this->setUpiTypeForAttributes($input, $attributes);

        if (($upi instanceof UpiEntity) === false)
        {
            $upi = $this->createUpiEntityForMozartGateway($attributes, $action);

            return $upi;
        }

        $this->updateUpiEntityForMozartGateway($upi, $attributes);

        return $upi;
    }

    public function fetchByMerchantReference(string $merchantReference)
    {
        $gatewayPayment = $this->getUpiRepository()->fetchByMerchantReference($merchantReference);

        return $gatewayPayment;
    }

    /**
     * @param array $input Gateway Input
     * @param string $requestTraceCode TraceCode for request
     * @param string $responseTraceCode TraceCode for response
     * @param string $action Action to hit on mozart
     * @param bool $handleException Throws mozart exception if set
     * @return array Mozart response
     * @throws \RZP\Exception\GatewayErrorException
     */
    public function sendUpiMozartRequest(
        array $input,
        string $requestTraceCode,
        string $action)
    {
        parent::action($input, $action);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, $requestTraceCode);

        $response =  $this->sendGatewayRequest($request);

        return $response;
    }

    /*
     * TODO: Remove this functions post dependency of mozart entity
     */
    public function createMozartEntity($attributes, $input, $action)
    {
        return $this->createGatewayPaymentEntity($attributes, $input, $action);
    }

    public function updateMozartEntity(Mozart\Entity $gatewayPayment, $attributes, bool $mapped, $action)
    {
        return $this->updateGatewayPaymentEntityWithAction($gatewayPayment, $attributes, $mapped, $action);
    }

    public function findEntityByPaymentIdAndActionOrFail(string $paymentId, string $action)
    {
        return $this->repo->findByPaymentIdAndActionOrFail($paymentId, $action);
    }

    protected function getUpiRepository()
    {
        return app('repo')->upi;
    }

    protected function setUpiTypeForAttributes($input, &$attributes)
    {
        if (isset($input['upi']['flow']) === true)
        {
            switch ($input['upi']['flow'])
            {
                case UpiMetadata\Flow::COLLECT:
                    $attributes[UpiEntity::TYPE] = Base\Type::COLLECT;
                    break;
                case UpiMetadata\Flow::INTENT:
                    $attributes[UpiEntity::TYPE] = Base\Type::PAY;
                    break;
            }
        }
    }

    /**
     * function to create upi entity for mozart gateways
     * We are keeping it here in order to not break createOrUpdateUpiEntityForMozartGateways
     * TODO: Remove this function after all gateways moved from mozart to upi entity
     * @param $attributes
     * @param $action
     * @return UpiEntity
     */
    protected function createUpiEntityForMozartGateway($attributes, $action)
    {
        $entity = new UpiEntity();

        $action = $action ?? $this->action;

        $entity->setAmount($this->input['payment']['amount']);

        $entity->setPaymentId($this->input['payment']['id']);

        $entity->setAction($action);

        $entity->setAcquirer(static::ACQUIRER);

        $entity->setGateway($this->input['payment']['gateway']);

        $entity->generate($attributes);

        $entity->fill($attributes);

        $this->getUpiRepository()->saveOrFail($entity);

        return $entity;
    }

    /**
     * function to update upi entity for mozart gateways
     * We are keeping it here in order to not break createOrUpdateUpiEntityForMozartGateways
     * @param $upiEntity
     * @param $attributes
     * @return mixed
     */
    protected function updateUpiEntityForMozartGateway($upiEntity, $attributes)
    {
        $upiEntity->fill($attributes);

        $this->getUpiRepository()->saveOrFail($upiEntity);

        return $upiEntity;
    }

    protected function getPreviousStepName($gateway)
    {
        return null;
    }

    protected function getPreviousStepForDB($gateway)
    {
        return null;
    }
}
