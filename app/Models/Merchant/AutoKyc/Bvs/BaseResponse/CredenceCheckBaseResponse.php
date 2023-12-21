<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;


use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Credencecheck\V1\GetDetailsByAccountIdResponse;


class CredenceCheckBaseResponse implements Response
{
    /**
     * @var
     */
    protected $response;

    public function __construct(GetDetailsByAccountIdResponse $response)
    {
        $this->response = $response;

        $this->validateResponse();
    }

    public function validateResponse()
    {
        if (empty($this->response->getResults()) === true)
        {
            throw new BadRequestException(ErrorCode::SERVER_ERROR, Constant::RESULTS, $this->getResponseData());
        }
    }

    public function getResponseData()
    {
        $responseData = [
            'results'  => iterator_to_array($this->response->getResults()->getIterator()),
        ];

        return $responseData;
    }
}
