<?php

namespace RZP\Gateway\Mozart;

use RZP\Trace\TraceCode;
use RZP\Constants;

trait OptimizerCardMandate{

    protected function optimizerCardMandatePreDebitNotify($input)
    {
        parent::action($input, Action::NOTIFY);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_PRE_DEBIT_NOTIFY_REQUEST,
            TraceCode::GATEWAY_PRE_DEBIT_NOTIFY_RESPONSE, true);

        return $response;
    }

    protected function optimizerCardMandateCancel($input)
    {
        parent::action($input, Action::MANDATE_REVOKE);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_MANDATE_REVOKE_REQUEST,
            TraceCode::GATEWAY_MANDATE_REVOKE_RESPONSE, true);

        return $response;
    }

    // Gets BIN information from gateway. Requires, payment, terminal and card entity.
    // Takes gateway from payment.gateway, IIN from card.iin, and terminal secrets
    protected function optimizerCheckBin($input): array
    {
        parent::action($input, Action::CHECK_BIN);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_CHECK_BIN_REQUEST,
            TraceCode::GATEWAY_CHECK_BIN_RESPONSE, true);

        return $response;
    }


    protected function optimizerCardMandateUpdateToken($input)
    {
        parent::action($input, Action::UPDATE_TOKEN);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_UPDATE_TOKEN_REQUEST,
            TraceCode::GATEWAY_UPDATE_TOKEN_RESPONSE, true);

        return $response;
    }

    protected function optimzierCardMandateVerify($input)
    {
        parent::action($input, Action::MANDATE_VERIFY);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_CARD_MANDATE_VERIFY_REQUEST,
            TraceCode::GATEWAY_CARD_MANDATE_VERIFY_RESPONSE, true);

        return $response;
    }
}
