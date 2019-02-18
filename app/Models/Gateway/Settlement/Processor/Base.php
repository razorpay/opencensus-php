<?php

namespace RZP\Models\Gateway\Settlement\Processor;

use RZP\Models\Payment;
use RZP\Models\Base as BaseModel;
use RZP\Models\Base\PublicCollection;

abstract class Base extends BaseModel\Core
{
    /**
     * Generates a list of payments for which we need to initiate settlements
     * @param array $input
     * @return PublicCollection
     */
    public abstract function getPayments(array $input): PublicCollection;

    public abstract function sendGatewayRequest(array $input);

    public function process(Payment\Entity $payment)
    {
        $gatewayInput = [
            'payment'  => $payment->toArray(),
            'merchant' => $payment->merchant,
            'card'     => $payment->card->toArray(),
            'terminal' => $payment->terminal,
        ];

        $this->preProcessGatewayInput($gatewayInput);

        $this->sendGatewayRequest($gatewayInput);
    }

    protected function preProcessGatewayInput(&$input)
    {
        return;
    }
}
