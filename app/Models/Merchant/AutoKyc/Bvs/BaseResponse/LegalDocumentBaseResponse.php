<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;


use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Response;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Trace\TraceCode;

class LegalDocumentBaseResponse implements Response
{
    /**
     * @var LegalDocumentsManagerResponse
     */
    protected $response;

    public function __construct(LegalDocumentsManagerResponse $response)
    {
        $this->response = $response;

        $this->validateResponse();
    }

    public function validateResponse()
    {
        if (empty($this->response->getId()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_VALIDATION_ID_MISSING, Constant::ID, $this->getResponseData());
        }
        if (empty($this->response->getStatus()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR_VALIDATION_STATUS_MISSING, Constant::STATUS, $this->getResponseData());
        }
    }

    public function getResponseData()
    {
        $responseData = [
            'id'     => $this->response->getId(),
            'status' => $this->response->getStatus(),
        ];

        return $responseData;
    }
}
