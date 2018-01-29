<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Csb\Constants;
use RZP\Gateway\Netbanking\Csb\RequestFields;
use RZP\Gateway\Netbanking\Csb\ResponseFields;
use RZP\Gateway\Netbanking\Csb\Status;

class Server extends Base\Mock\Server
{
    private $gatewayInstance = null;

    public function authorize($input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->verifyChecksum($request);

        $this->validateAuthorizeInput($request);

        $response = $this->getAuthorizeResponse($request);

        return $request[RequestFields::RETURN_URL] . '?' . http_build_query($response);
    }

    /**
     * Using the gatewayInstance like a singleton object
     *
     * @param null $bankingType
     * @return mixed|null
     */
    protected function getGatewayInstance($bankingType = null)
    {
        if ($this->gatewayInstance === null)
        {
            $this->gatewayInstance = parent::getGatewayInstance($bankingType);
        }

        return $this->gatewayInstance;
    }

    private function getAuthorizeResponse(array $request)
    {
        // TODO: Check if this format is correct
        $date = Carbon::now(Timezone::IST)->format('dmy');

        $content = [
            ResponseFields::PAYEE_ID     => $request[RequestFields::PAYEE_ID],
            ResponseFields::BANK_REF_NUM => $request[RequestFields::BANK_REF_NUM],
            ResponseFields::AMOUNT       => $request[RequestFields::AMOUNT],
            ResponseFields::MODE         => $request[RequestFields::MODE],
            ResponseFields::NARRATION    => Constants::NARRATION,
            ResponseFields::DATE_TIME    => $date,
            ResponseFields::TRAN_REF_NUM => 9999999999, // TODO: Check the diff b/w this and bankId
            ResponseFields::STATUS       => Status::SUCCESS,
            ResponseFields::BANKID       => 9999999999,
            ResponseFields::CHNPGCODE    => $request[RequestFields::CHNPGCODE]
        ];

        $this->content($content, $this->action);

        return $content;
    }

    private function getAuthorizeRequest(array $input)
    {
        $data = $input[RequestFields::AUTH_DATA];

        $base64DecodedRequestString = base64_decode($data);

        $requestArray = explode('|', $base64DecodedRequestString);

        return array_combine($this->getAuthorizeRequestFields(), $requestArray);
    }

    private function verifyChecksum(array $request)
    {
        $checkSum = $request[RequestFields::CHECKSUM];

        unset($request[RequestFields::CHECKSUM]);

        $generatedCheckSum = $this->getAuthCheckSum($request);

        $this->getGatewayInstance()->compareHashes($checkSum, $generatedCheckSum);
    }

    private function getAuthCheckSum(array $request)
    {
        $content = implode('|', array_values($request));

        return $this->getGatewayInstance()->getHashOfString($content);
    }

    private function getAuthorizeRequestFields()
    {
        return [
            RequestFields::CHNPGSYN,
            RequestFields::CHNPGCODE,
            RequestFields::PAYEE_ID,
            RequestFields::BANK_REF_NUM,
            RequestFields::AMOUNT,
            RequestFields::RETURN_URL,
            RequestFields::MODE,
            RequestFields::CHECKSUM
        ];
    }
}
