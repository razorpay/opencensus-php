<?php

namespace Gateway\Billdesk\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Billdesk;
use Requests_Response;

class Gateway extends Billdesk\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function sendGatewayRequestForBilldeskAuthorize($request)
    {
        $response = new Requests_Response();

        $txt = $this->getHtmlText($request);

        $response->body = $txt;
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    protected function getHtmlText($request)
    {
        $txt = '<form action="'.$request['url'].'" method="'.$request['method'].'">' .PHP_EOL;

        foreach ($request['content'] as $key => $value)
        {
            $txt .= "<input type='text' name='$key' value='$value'>".PHP_EOL;
        }

        $txt .= '</form>';

        return $txt;
    }
}
