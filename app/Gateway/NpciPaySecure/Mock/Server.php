<?php

namespace RZP\Gateway\NpciPaySecure\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\NpciPaySecure;

class Server extends Base\Mock\Server
{
    public function getGatewayResponse($command, $params)
    {
        $response = $this->runCommandRequest($command, $params);

        return $response;
    }

    protected function runCommandRequest($command, $contentArray)
    {
        switch ($command)
        {
            case NpciPaySecure\Constants::COMMAND_CHECKBIN2:
                return $this->getCheckBin2Response($contentArray);
        }
    }

    protected function getCheckBin2Response($data)
    {
        // todo: Use constants here
        $response = [
            NpciPaySecure\Fields::STATUS                => NpciPaySecure\Constants::STATUS_SUCCESS,
            NpciPaySecure\Fields::ERROR_CODE            => '0',
            NpciPaySecure\Fields::ERROR_MESSAGE         => '',
            NpciPaySecure\Fields::QUALIFIED_INTERNETPIN => 'TRUE',
            NpciPaySecure\Fields::IMPLEMENTS_REDIRECT   => 'TRUE',
        ];

        $this->content($response, 'checkbin2');

        return $response;
    }
}
