<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Consentdocumentmanager\V2\ConsentDocumentsManagerResponse;

class ConsentDocumentBaseResponse implements Response
{
    /**
     * @var ConsentDocumentsManagerResponse
     */
    protected $response;

    public function __construct(ConsentDocumentsManagerResponse $response)
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
