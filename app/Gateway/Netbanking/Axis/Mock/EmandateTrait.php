<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Axis\Emandate\Constants;
use RZP\Gateway\Netbanking\Axis\Emandate\RequestFields;
use RZP\Gateway\Netbanking\Axis\Emandate\ResponseFields;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;

use Carbon\Carbon;

trait EmandateTrait
{
    protected function handleEmandateFlow(array $input) : string
    {
        $this->validateActionInput($input, 'emandateauthrequest');

        $data = $this->getGatewayInstance()->getDecryptedData($input[RequestFields::DATA]);

        $this->validateActionInput($data, 'emandateauth');

        $response = $this->createEmandateResponse($data);

        $callbackUrl = $data[RequestFields::RETURN_URL] . '?' . http_build_query($response);

        return $callbackUrl;
    }

    protected function createEmandateResponse(array $input) : array
    {
        $data = [
            ResponseFields::VERSION         => $input[RequestFields::VERSION],
            ResponseFields::CORP_ID         => $input[RequestFields::CORP_ID],
            ResponseFields::TYPE            => $input[RequestFields::TYPE],
            ResponseFields::CUSTOMER_REF_NO => $input[RequestFields::CUSTOMER_REF_NO],
            ResponseFields::CURRENCY        => $input[RequestFields::CURRENCY],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            // TODO: Docs say this is not needed, but docs checksum says it is needed
            ResponseFields::REQUEST_ID      => $input[RequestFields::REQUEST_ID],
            ResponseFields::BANK_REF_NO     => 9999999999,
            ResponseFields::STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::REMARKS         => 'Recurring payment successful',
            ResponseFields::TRANS_REF_NO    => $input[RequestFields::REQUEST_ID], // TODO: Confirm this
            ResponseFields::TRANS_EXEC_TIME => Carbon::now(Timezone::IST)->toDateTimeString(), // TODO: Confirm this
            ResponseFields::PAYMENT_MODE    => Constants::PMD,
            ResponseFields::CHECKSUM        => $input[RequestFields::CHECKSUM],
            ResponseFields::MANDATE_NUMBER  => 8888888888,
        ];

        // TODO: Encrypt this

        // for test cases
        $this->content($data, 'emandateauth');

        $content = [
            ResponseFields::DATA => $this->getGatewayInstance()->getEncryptedData($data)
        ];

        return $content;
    }
}
