<?php

namespace RZP\Models\CardMandate\MandateHubs\OptimizerHubs;

use RZP\Constants\Entity;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\CardMandate;
use RZP\Exception\BadRequestException;

trait OptimizerHubSelector
{

    public function GetOptimizerTerminal(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {
        // Check if card mandates is supported on gateway
        if (in_array($payment->terminal->getGateway(), CardMandate\Constants::OPTIMIZER_HUBS, true)) {
            switch ($payment->terminal->getGateway()) {
                case Payment\Gateway::PAYU:
                    // check if recurring is supported on BIN at Payu's end
                    if ($this->IsRecurringSupportedPayu($payment, $cardMandate) == true) {
                        return $payment->terminal;
                    }
                    break;
                default:
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
            }
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
    }


    // Checks if Payu supports recurring on BIN/IIN
    public function IsRecurringSupportedPayu(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {
        $input[Constants::PAYMENT][Constants::GATEWAY] = $payment->getGateway();
        $input[Constants::TERMINAL] = $payment->terminal;
        $input[Constants::CARD][Constants::IIN] = $payment->card->iinRelation->getIin();
        $input[Constants::IS_OPTIMIZER_CARD_MANDATE] = true;

        $response = $this->app['gateway']->call(Entity::MOZART,
            Payment\Action::CHECK_BIN, $input, $this->mode);

        if (empty($response[Constants::DATA][Constants::BIN_DATA][Constants::IS_SI_SUPPORTED]) == false) {
            $result = $response[Constants::DATA][Constants::BIN_DATA][Constants::IS_SI_SUPPORTED];
            if ($result == 1) {
                return true;
            }
        }

        return false;
    }
}
