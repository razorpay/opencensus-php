<?php

namespace RZP\Gateway\Upi\Mozart;

use RZP\Gateway\Upi\Base;
use RZP\Gateway\Upi\Base\Entity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_mozart';

    const ACQUIRER =  'mozart';

    public function createOrUpdateUpiEntityForMozartGateways(array $input, array $response, $action)
    {
        parent::action($input, $action);

        $upiEntity = $this->repo->findByPaymentIdAndAction($input['payment']['id'], $action);

        if (($upiEntity instanceof Entity) === false)
        {
            $upiEntity = $this->createGatewayEntityForMozartGateway($response, $action);

            return $upiEntity;
        }

        $this->updateGatewayPaymentEntity($upiEntity, $response, false);

        return $upiEntity;
    }

    public function fetchByMerchantReference(string $merchantReference)
    {
        $gatewayPayment = $this->repo->fetchByMerchantReference($merchantReference);

        return $gatewayPayment;
    }
}
