<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Gateway\Netbanking\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response);

        $callbackUrl = $input[RequestFields::RETURN_URL] . '?' .
                        http_build_query($response);

        return $callbackUrl;
    }

    public function verify($input)
    {
        parent::verify($input);

        $response = $this->getVerifyResponseData($request);

        $this->content($response);

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData($input)
    {

    }

    protected function getVerifyResponseData($input)
    {

    }
}
