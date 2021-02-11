<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Rzp\Bvs\Validation\V1\ValidationResponse;

class ValidationBaseResponse implements Response
{
    /**
     * @var ValidationResponse
     */
    protected $response;

    public function __construct(ValidationResponse $response)
    {
        $this->response = $response;

        $this->validateResponse();
    }

    public function validateResponse()
    {
        if (empty($this->response->getValidationId()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_VALIDATION_ID_MISSING, Constant::VALIDATION_ID, $this->getResponseData());
        }
        if (empty($this->response->getStatus()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_VALIDATION_STATUS_MISSING, Constant::STATUS, $this->getResponseData());
        }
    }

    public function getResponseData()
    {
        $responseData = [
            Entity::VALIDATION_ID     => $this->response->getValidationId(),
            Entity::VALIDATION_STATUS => $this->response->getStatus(),
        ];

        if ($this->response->getErrorCode() !== null)
        {
            $errorData    = [
                Entity::ERROR_CODE        => $this->response->getErrorCode(),
                Entity::ERROR_DESCRIPTION => $this->response->getErrorDescription()
            ];
            $responseData = array_merge($responseData, $errorData);
        }

        return $responseData;
    }
}
