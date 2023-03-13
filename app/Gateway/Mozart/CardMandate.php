<?php

namespace RZP\Gateway\Mozart;

use RZP\Trace\TraceCode;
use RZP\Constants;

trait CardMandate {

    protected function cardMandateCreate($input)
    {
        parent::action($input, Action::AUTHENTICATE_INIT);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_MANDATE_CREATE_REQUEST,
            TraceCode::GATEWAY_MANDATE_CREATE_RESPONSE, true);

        return $response;
    }

    protected function cardMandatePreDebitNotify($input)
    {
        parent::action($input, Action::PAY_INIT);

        if (isset($input['gateway']) and $input['gateway'] == Constants\Table::PAYSECURE)
        {
            parent::action($input, Action::NOTIFY);
        }

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_PRE_DEBIT_NOTIFY_REQUEST,
            TraceCode::GATEWAY_PRE_DEBIT_NOTIFY_RESPONSE, true);

        return $response;
    }

    protected function reportPayment($input)
    {
        parent::action($input, Action::AUTHENTICATE_VERIFY);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_PAYMENT_REPORT_REQUEST,
            TraceCode::GATEWAY_PAYMENT_REPORT_RESPONSE, true);

        return $response;
    }

    protected function cardMandateVerify($input)
    {
        parent::action($input, Action::PAY_VERIFY);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_CARD_MANDATE_VERIFY_REQUEST,
            TraceCode::GATEWAY_CARD_MANDATE_VERIFY_RESPONSE, true);

        return $response;
    }

    protected function cardMandateUpdate($input)
    {
        parent::action($input, Action::AUTH_VERIFY);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_UPDATE_CARD_MANDATE_REQUEST,
            TraceCode::GATEWAY_UPDATE_CARD_MANDATE_RESPONSE, true);

        return $response;
    }

    protected function cardMandateCancel($input)
    {
        parent::action($input, Action::MANDATE_REVOKE);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_MANDATE_REVOKE_REQUEST,
            TraceCode::GATEWAY_MANDATE_REVOKE_RESPONSE, true);

        return $response;
    }

    protected function cardMandateUpdateToken($input)
    {
        parent::action($input, Action::UPDATE_TOKEN);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_UPDATE_TOKEN_REQUEST,
            TraceCode::GATEWAY_UPDATE_TOKEN_RESPONSE, true);

        return $response;
    }
}
