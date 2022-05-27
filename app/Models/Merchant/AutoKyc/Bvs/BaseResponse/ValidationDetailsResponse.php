<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;


use Rzp\Bvs\Validation\V1\ValidationResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\BvsValidation\Entity;

class ValidationDetailsResponse implements Response
{
    protected $response;

    public function __construct(ValidationResponse $response)
    {
        $this->response = $response;

        $this->validateResponse();
    }

    public function validateResponse()
    {
        // TODO: Implement validateResponse() method.
    }

    public function getResponseData()
    {
        $enrichmentDetails = json_decode($this->response->getEnrichmentDetails()->serializeToJsonString());

        return [
            Constant::VALIDATION_ID            => $this->response->getValidationId(),
            Constant::ENRICHMENT_DETAIL_FIELDS => $enrichmentDetails,
        ];
    }

    public function getResponse()
    {
        return [
            Constant::VALIDATION_ID   => $this->response->getValidationId(),
            Entity::VALIDATION_STATUS => $this->response->getStatus(),
            Entity::ERROR_CODE        => $this->response->getErrorCode(),
            Entity::ERROR_DESCRIPTION => $this->response->getErrorDescription()
        ];
    }

    public function getStatus()
    {
        return empty($this->response) ? null : $this->response->getStatus();
    }
}
