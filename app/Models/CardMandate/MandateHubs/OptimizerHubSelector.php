<?php

namespace RZP\Models\CardMandate\MandateHubs;

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
        $input['payment']['gateway'] = $payment->getGateway();
        $input['terminal'] = $payment->terminal;
        $input['card']['iin'] = $payment->card->iinRelation->getIin();

        $response = $this->app['gateway']->call(Entity::MOZART,
            Payment\Action::CHECK_BIN, $input, $this->mode);

        if (empty($response['data']) == false && empty($response['data']['bin_data']) == false &&
            empty($response['data']['bin_data']['is_si_supported']) == false) {
            $result = $response['data']['bin_data']['is_si_supported'];
            if ($result == 1) {
                return true;
            }
        }

        return false;
    }
}
