<?php

namespace RZP\Models\Merchant\AutoKyc\KycService;

use App;
use Requests_Response;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;

class BaseResponse implements Response
{
    protected $allowedStatusCode = [400, 201];

    protected $successStatusCode = [201];
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

    /**
     * @var array
     */
    protected $requestInput;

    public function __construct(Requests_Response $response, array $responseMetaData = [], array $input = [])
    {
        $this->response = $response;

        $this->responseMetaData = $responseMetaData;

        $this->statusCode = $response !== null ? $response->status_code : null;

        $this->responseBody = $this->getResponseBody();

        $this->requestInput = $input;

        $this->validateResponse();
    }

    public function getResponseBody()
    {
        if ($this->response === null)
        {
            return array();
        }

        $responseBody = json_decode($this->response->body, true);

        if($this->isSuccessResponse()=== true)
        {
            return $responseBody['data'] ?? [];
        }

        return $responseBody ?? [];
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateResponse()
    {
        if (in_array($this->statusCode, $this->allowedStatusCode, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_STATUS_CODE, Constants::STATUS_CODE, $this->responseBody);
        }

        if (empty($this->responseBody) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_KYC_INTEGRATION, Constants::RESPONSE_BODY, $this->responseBody);
        }

        if ($this->isSuccessResponse())
        {
            if (empty($this->getKycId()) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_KYC_ID_MISSING, Constants::KYC_ID, $this->responseBody);
            }
        }
    }

    protected function isSuccessResponse()
    {
        return in_array($this->statusCode, $this->successStatusCode, true) === true;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function getResponseData(): array
    {
        $this->validateResponse();

        return [
            Constants::KYC_ID                 => $this->getKycId(),
            Constants::STATUS_CODE            => $this->response->status_code ?? null,
            Constants::INTERNAL_ERROR_CODE    => $this->getInternalErrorCode(),
            Constants::INTERNAL_ERROR_MESSAGE => $this->getInternalErrorMessage(),
            Constants::RESPONSE_TIME          => $this->responseMetaData[Constants::RESPONSE_TIME] ?? null,
        ];
    }

    protected function getInternalErrorCode(): ?string
    {
        if ($this->isSuccessResponse())
        {
            return '';
        }

        return $this->responseBody[Constants::INTERNAL_ERROR][Constants::CODE] ?? null;
    }

    protected function getInternalErrorMessage(): ?string
    {
        if ($this->isSuccessResponse())
        {
            return '';
        }

        return $this->responseBody[Constants::INTERNAL_ERROR][Constants::MESSAGE] ?? null;
    }

    public function getKycId(): ?string
    {
        return $this->responseBody[Constants::KYC_ID] ?? null;
    }
}
