<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use App;
use Requests_Response;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class BaseResponse implements Response
{
    /**
     * @var Requests_Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $responseBody;

    /**
     * @var array
     */
    protected $responseMetaData;

    /**
     * @var int
     */
    protected $statusCode;

    public function __construct(Requests_Response $response,  array $responseMetaData = [])
    {
        $this->response = $response;

        $this->responseMetaData = $responseMetaData;

        $this->statusCode = $response !== null ? $response->status_code : null;

        $this->responseBody = $this->response != null ? json_decode($response->body, true) : array();
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateResponse()
    {
        if ($this->response->status_code !== 200)
        {
            $payload['body'] = $this->responseBody;

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CAPITAL_INTEGRATION, null, $payload);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function getResponseData(): array
    {
        $this->validateResponse();

        return [
            Constants::RESPONSE_TIME => $this->responseMetaData[Constants::RESPONSE_TIME] ?? null,
            Constants::STATUS_CODE   => $this->getInternalStatusCode(),
        ];
    }

    protected function getInternalStatusCode(): ?string
    {
        $body = $this->responseBody;

        $response = $body['data']['content']['response'] ?? [];

        $statusCode = $response['status-code'] ??
                      ($response['statusCode'] ??
                       ($response['status'] ??
                        $this->statusCode));

        return $statusCode;
    }
}
