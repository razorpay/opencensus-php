<?php

namespace RZP\Gateway\Billdesk\Mock;

use Requests_Response;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Billdesk;

class Gateway extends Billdesk\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function sendGatewayRequestForBilldeskAuthorize($request, $error=false)
    {
        $response = new Requests_Response();

        $txt = $this->getHtmlText($request, $error);

        $response->body = $txt;
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    protected function getHtmlText($request, $error)
    {
        if ($error === true)
        {
            $txt = '<HTML><HEAD><TITLE>Error</TITLE></HEAD><BODY>An error occurred while processing your request.<p>Reference 123456</BODY></HTML>' . PHP_EOL;

            return $txt;
        }

        $txt = '<form action="' . $request['url'] . '" method="' . $request['method'] . '">' . PHP_EOL;

        foreach ($request['content'] as $key => $value)
        {
            $txt .= "<input type='text' name='$key' value='$value'>" . PHP_EOL;
        }

        $txt .= '</form>';

        return $txt;
    }
}
