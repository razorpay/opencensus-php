<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Base\Mock;
use RZP\Gateway\Netbanking\Oriental\Status;
use RZP\Gateway\Netbanking\Oriental\RequestFields;
use RZP\Gateway\Netbanking\Oriental\ResponseFields;
use RZP\Models\Currency\Currency;

/**
 * This class cannot be marked as final as it will be mocked for test cases
 * Class Server
 * @package RZP\Gateway\Netbanking\Oriental\Mock
 */
class Server extends Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $content = $this->getAuthResponse($input);

        $this->content($content);

        $request = [
            'url'     => $input[RequestFields::RETURN_URL],
            'content' => $content,
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);
    }

    private function getAuthResponse(array $input)
    {
        $queryArray = $this->getQueryArray($input[RequestFields::QUERY_STRING]);

        return [
            ResponseFields::PAID            => Status::SUCCESS,
            ResponseFields::BANK_PAYMENT_ID => 9999999999,
            ResponseFields::CURRENCY        => Currency::INR,
            ResponseFields::AMOUNT          => $queryArray[RequestFields::TXN_AMOUNT],
            ResponseFields::PAYEE_ID        => $queryArray[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $queryArray[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $queryArray[RequestFields::ITEM_CODE],
            ResponseFields::DEBIT_ACC_NUM   => 1234567890,
        ];
    }

    private function getQueryArray(string $queryString)
    {
        $querySubArray = explode('|', $queryString);

        $array = [];

        foreach ($querySubArray as $subArray)
        {
            $explodedArray = explode('~', $subArray);

            $key = $explodedArray[0];

            $value = $explodedArray[1];

            $array[$key] = $value;
        }

        return $array;
    }
}
