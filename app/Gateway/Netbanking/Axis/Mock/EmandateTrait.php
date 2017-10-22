<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Axis\Emandate\Constants;
use RZP\Gateway\Netbanking\Axis\Emandate\RequestFields;
use RZP\Gateway\Netbanking\Axis\Emandate\ResponseFields;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;
use RZP\Models\Currency\Currency;

use Carbon\Carbon;

trait EmandateTrait
{
    protected function handleEmandateAuthFlow(array $input)
    {
        $secondPayment = false;

        $this->content($secondPayment, 'second_payment');

        $this->validateActionInput($input, 'emandaterequest');

        $data = $this->getGatewayInstance()->getEmandateDecryptedData($input[RequestFields::DATA]);

        $this->validateActionInput($data, 'emandateauth');

        $response = $this->createEmandateAuthResponse($data);

        if ($secondPayment === true)
        {
            // Debit steps are handled in the method below
            $response = http_build_query($response);

            return $this->makeResponse($response);
        }

        $callbackUrl = $data[RequestFields::RETURN_URL] . '?' . http_build_query($response);

        return $callbackUrl;
    }

    protected function createEmandateAuthResponse(array $input) : array
    {
        $data = [
            ResponseFields::VERSION         => $input[RequestFields::VERSION],
            ResponseFields::CORP_ID         => $input[RequestFields::CORP_ID],
            ResponseFields::TYPE            => $input[RequestFields::TYPE],
            ResponseFields::CUSTOMER_REF_NO => $input[RequestFields::CUSTOMER_REF_NO],
            ResponseFields::CURRENCY        => $input[RequestFields::CURRENCY],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
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

        $this->content($data, 'emandateauth');

        $content = [
            ResponseFields::DATA => $this->getGatewayInstance()->getEmandateEncryptedData($data)
        ];

        return $content;
    }

    protected function handleEmandateVerifyFlow($input)
    {
        $this->validateActionInput($input, 'emandaterequest');

        $data = $this->getGatewayInstance()->getEmandateDecryptedData($input[RequestFields::DATA]);

        $response = $this->createEmandateVerifyResponse($data);

        return $this->makeResponse($response);
    }

    protected function createEmandateVerifyResponse($input)
    {
        $this->validateActionInput($input, 'emandateverify');

        $date = Carbon::now(Timezone::IST)->format('d-M-y');

        $gatewayEntity = $this->repo->netbanking->findByPaymentIdAndActionOrFail(
            $input[RequestFields::REQUEST_ID],
            Action::AUTHORIZE
        );

        $data = [
            ResponseFields::VERSION         => $input[RequestFields::VERSION],
            ResponseFields::REQUEST_ID      => $input[RequestFields::REQUEST_ID],
            ResponseFields::CORP_ID         => $input[RequestFields::CORP_ID],
            ResponseFields::TYPE            => $input[RequestFields::TYPE],
            ResponseFields::CUSTOMER_REF_NO => $input[RequestFields::CUSTOMER_REF_NO],
            ResponseFields::BANK_REF_NO     => 9999999999,
            ResponseFields::CURRENCY        => Currency::INR,
            ResponseFields::AMOUNT          => $gatewayEntity['amount'],
            ResponseFields::STATUS_CODE     => StatusCode::SUCCESS,
            ResponseFields::REMARKS         => 'Success',
            ResponseFields::TRANS_REF_NO    => 101714472,
            ResponseFields::TRANS_EXEC_TIME => $date,
            ResponseFields::PAYMENT_MODE    => Constants::PMD,
            ResponseFields::CHECKSUM        => $input[RequestFields::CHECKSUM]
        ];

        $this->content($data, 'verify_emandate');

        return $this->getGatewayInstance()->getEmandateEncryptedData($data);
    }
}
