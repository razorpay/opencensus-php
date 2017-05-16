<?php

namespace RZP\Gateway\Netbanking\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Rbl\Status;
use RZP\Gateway\Netbanking\Rbl\RequestFields;
use RZP\Gateway\Netbanking\Rbl\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $bank = IFSC::RATN;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $request = [
            'url'     => $input[RequestFields::RETURN_URL],
            'content' => $response,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $response = $this->getVerifyResponseData($input);

        $this->content($response, 'verify');

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::STATUS             => Status::SUCCESS,
            ResponseFields::BANK_REFERENCE     => 99999999,
            ResponseFields::MERCHANT_REFERENCE => $input[RequestFields::PAYMENT_ID],
        ];

        return $data;
    }

    protected function getVerifyResponseData(array $input)
    {
        $content = [
            $input[RequestFields::PAYMENT_ID],
            $input[RequestFields::ITEM_CODE],
            99999999,
            $input[RequestFields::AMOUNT],
            'S',
        ];

        return $this->getStringFromContent($content, '|');
    }

    protected function getStringFromContent($content, $glue = '')
    {
        return implode($glue, $content);
    }
}
