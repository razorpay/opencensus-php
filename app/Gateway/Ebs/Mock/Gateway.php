<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Gateway\Ebs;
use Requests_Response;

class Gateway extends Ebs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function sendGatewayRequestForEbsAuthorize($request)
    {
        $response = new Requests_Response();

        $txt = $this->getHtmlText($request);

        $response->body = $txt;
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }
}
