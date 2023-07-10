<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;


use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Consentdocumentmanager\V2\ConsentDocumentsManagerResponse;


class FetchConsentDocumentBaseResponse implements Response
{
    /**
     * @var
     */
    protected $response;

    public function __construct(ConsentDocumentsManagerResponse $response)
    {
        $this->response = $response;

        $this->validateResponse();
    }

    public function validateResponse()
    {
        if ($this->response->hasCount() === false)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR, Constant::COUNT, $this->getResponseData());
        }
        if (empty($this->response->getDocumentsDetail()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR, Constant::DOCUMENTS_DETAIL, $this->getResponseData());
        }
    }

    public function getResponseData()
    {
        $responseData = [
            'count'             => $this->response->getCount()->getValue(),
            'documents_detail'  => iterator_to_array($this->response->getDocumentsDetail()->getIterator()),
        ];

        return $responseData;
    }
}
