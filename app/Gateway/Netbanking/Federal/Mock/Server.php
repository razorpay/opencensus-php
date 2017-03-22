<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Federal\Constants;
use RZP\Gateway\Netbanking\Federal\RequestFields;
use RZP\Gateway\Netbanking\Federal\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $request = [
            'url' => $input[RequestFields::RETURN_URL],
            'content' => $response,
            'method' => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, 'verify');

        $response = $this->getVerifyResponseData($input);

        $this->content($response, 'verify');

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        return [
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_PAYMENT_ID => 99999999,
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
            ResponseFields::STATE_FLAG      => $input[RequestFields::STATE_FLAG],
            ResponseFields::PAYEE_ID        => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAID            => Constants::CONFIRMATION,
        ];
    }

    protected function getVerifyResponseData(array $input)
    {
        if (isset($input[RequestFields::BANK_PAYMENT_ID]) === false)
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

        return
        '<HTML>
            <BODY> Y </BODY>
        </HTML>';
    }

    protected function getStringFromContent($content, $glue = '')
    {
        return implode($glue, $content);
    }
}
