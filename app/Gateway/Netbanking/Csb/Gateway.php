<?php

namespace RZP\Gateway\Netbanking\Csb;

use RZP\Gateway\Netbanking\Base;

class Gateway extends Base\Gateway
{
    protected $map = [

    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $gatewayEntityAttributes = $this->getNetbankingEntityAttributes($input);

        $this->createGatewayPaymentEntity($gatewayEntityAttributes);

        $request = $this->getAuthorizeRequest($input);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        sd('Reached callback function');
    }

    public function verify(array $input)
    {
        parent::verify($input);

        sd('Reached verify function');
    }

    private function getNetbankingEntityAttributes(array $input): array
    {
        // TODO: Work on this
        return [];
    }

    private function getAuthorizeRequest(array $input): array
    {
        return [];
    }
}
