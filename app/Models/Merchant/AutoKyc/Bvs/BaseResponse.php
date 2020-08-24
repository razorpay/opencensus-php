<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use Rzp\Bvs\Validation\V1\ValidationResponse;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\BvsValidation\Entity;

class BaseResponse implements Response
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
        // validate response here if any specific validation is required
    }

    public function getResponseData()
    {
        $responseData = [
            Entity::VALIDATION_ID => $this->response->getValidationId(),
            Entity::STATUS        => $this->response->getStatus(),
        ];

        if ($this->response->getError() !== null)
        {
            $errorData    = [
                Entity::ERROR_CODE        => $this->response->getError()->getCode(),
                Entity::ERROR_DESCRIPTION => $this->response->getError()->getDescription()
            ];
            $responseData = array_merge($responseData, $errorData);
        }

        return $responseData;
    }
}
