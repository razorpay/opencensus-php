<?php

namespace RZP\Gateway\Upi\Mozart;

use RZP\Gateway\Upi\Base;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Payment\UpiMetadata;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_mozart';

    const ACQUIRER = 'mozart';

    public function createOrUpdateUpiEntityForMozartGateways(array $input, array $attributes, $action)
    {
        parent::action($input, $action);

        $upiEntity = $this->repo->findByPaymentIdAndAction($input['payment']['id'], $action);

        $this->setUpiTypeForAttributes($input, $attributes);

        if (($upiEntity instanceof Entity) === false)
        {
            $upiEntity = $this->createGatewayEntityForMozartGateway($attributes, $action);

            return $upiEntity;
        }

        $this->updateGatewayPaymentEntity($upiEntity, $attributes, false);

        return $upiEntity;
    }

    public function fetchByMerchantReference(string $merchantReference)
    {
        $gatewayPayment = $this->repo->fetchByMerchantReference($merchantReference);

        return $gatewayPayment;
    }

    protected function setUpiTypeForAttributes($input, &$attributes)
    {
        if (isset($input['upi']['flow']) === true)
        {
            switch ($input['upi']['flow'])
            {
                case UpiMetadata\Flow::COLLECT:
                    $attributes[Entity::TYPE] = Base\Type::COLLECT;
                    break;
                case UpiMetadata\Flow::INTENT:
                    $attributes[Entity::TYPE] = Base\Type::PAY;
                    break;
            }
        }
    }

}
