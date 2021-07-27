<?php

namespace RZP\Services\Bbps\Impl;

use RZP\Exception;
use RZP\Http\Response;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Setu
{
    const CONTENT_TYPE_JSON      = 'application/json';

    const IMPERSONATE_IFRAME_URL = 'impersonate_iframe_url';

    const CONFIG_CLIENT_ID       = 'client_id';

    const CONFIG_CLIENT_SECRET   = 'client_secret';

    const IFRAME_EMBED_URL       = 'iframe_embed_url';

    protected $bbpsConfig;

    protected $trace;

    public function __construct($bbpsConfig, $trace)
    {
        $this->bbpsConfig = $bbpsConfig;

        $this->trace = $trace;
    }

    public function getIframeForDashboard(Merchant\Entity $merchant, string $mode)
    {
        $payload = $this->getPayloadForSetuIframeUrl($merchant);

        $response = $this->sendRequest($payload, 'POST');

        $this->appendMode($response, $mode);

        return $response;
    }

    protected function sendRequest(array $input, string $method)
    {
        $headers = [
            'Content-Type'      => self::CONTENT_TYPE_JSON,
        ];

        try {
            $response = \Requests::request(
                $this->bbpsConfig[self::IMPERSONATE_IFRAME_URL],
                $headers,
                json_encode($input),
                $method,
                []
            );

            return $this->parseAndReturnResponse($response);
        }
        catch(\Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error on setu service',
                ErrorCode::SERVER_ERROR_BBPS_SERVICE_FAILURE,
                null,
                $e
            );
        }
    }

    protected function getPayloadForSetuIframeUrl(Merchant\Entity $merchant): array
    {
        return [
            'clientID' => $this->bbpsConfig[self::CONFIG_CLIENT_ID],
            'secret'   => $this->bbpsConfig[self::CONFIG_CLIENT_SECRET],
            'entityID' => $merchant->getId(),
        ];
    }

    protected function parseAndReturnResponse($res): array
    {
        $code = $res->status_code;

        if ($code === Response\StatusCode::SUCCESS)
        {
            $res = json_decode($res->body, true);

            $iframeUrl = $res['data']['url'];

            return [self::IFRAME_EMBED_URL => $iframeUrl];
        }

        throw new Exception\ServerErrorException(
            $res->body,
            ErrorCode::SERVER_ERROR_SERVICE_UNAVAILABLE,
            null,
            null
        );
    }

    protected function appendMode(array & $response, string $mode)
    {
        $response[self::IFRAME_EMBED_URL] = $response[self::IFRAME_EMBED_URL] . '&env=' . $mode;
    }
}
