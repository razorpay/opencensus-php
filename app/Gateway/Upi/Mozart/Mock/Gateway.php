<?php


namespace RZP\Gateway\Upi\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Upi\Mozart as UpiMozart;

class Gateway extends UpiMozart\Gateway
{
    use Base\Mock\GatewayTrait;

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;
    }

    protected function sendGatewayRequest($request)
    {

        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        $response = $this->prepareInternalResponse($serverResponse);

        return $this->jsonToArray($response->body, true);
    }
}
