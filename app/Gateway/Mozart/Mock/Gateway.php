<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Mozart;
use RZP\Gateway\Base;

class Gateway extends Mozart\Gateway
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

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($request['method'] === 'post')
        {
            $request['content']['gateway'] = $this->targetGateway;
        }
        else if ($request['method'] === 'get')
        {
            $request['url'] = $request['url'] . '&gateway=' . $this->targetGateway;
        }

        return $request;
    }
}
