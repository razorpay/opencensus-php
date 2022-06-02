<?php

namespace RZP\Models\CardMandate\MandateHubs;

use App;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\CardMandate;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\TerminalProcessor;

class MandateHubTerminalSelector extends Base\Core
{

    public function GetTerminalForPayment(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {
        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment, null, $cardMandate);

        $selectedTerminalIds = array_pluck($terminals, 'id');

        $isSIHubEnabled = $payment->merchant->isBilldeskSIHubEnabled();

        $iin = $payment->card->iinRelation->getIin();

        $app = App::getFacadeRoot();

        $isMandateHQIINEnabled = $app->mandateHQ->isBinSupported($iin);

        $this->trace->info(
            TraceCode::CARD_MANDATE_TERMINAL_LOG,
            [
                'terminals'                => $selectedTerminalIds,
                'is_si_hub_enabled'        => $isSIHubEnabled,
                'is_mandatehq_iin_enabled' => $isMandateHQIINEnabled,
            ]
        );

        $finalTerminals = [];

        foreach ($terminals as $terminal)
        {
            switch ($terminal->getGateway())
            {
                case MandateHubs::BILLDESK_SIHUB:
                    if ($isSIHubEnabled === true)
                    {
                        array_push($finalTerminals, $terminal);
                    }
                    break;

                case MandateHubs::MANDATE_HQ:
                    if ($isMandateHQIINEnabled === true)
                    {
                        array_push($finalTerminals, $terminal);
                    }
                    break;

                default:
                    array_push($finalTerminals, $terminal);
            }
        }

        $finalTerminalIds = array_pluck($finalTerminals, 'id');

        $this->trace->info(
            TraceCode::CARD_MANDATE_TERMINAL_LOG_AFTER_FILTER,
            [
                'final_terminals'     => $finalTerminalIds,
            ]
        );

        if (empty($finalTerminals))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
        }

        usort(
            $finalTerminals,
            function ($terminal1, $terminal2)
            {
                return $terminal1->getGateway() === MandateHubs::MANDATE_HQ ? -1 : 1;
            }
        );

        $finalTerminalIds = array_pluck($finalTerminals, 'id');

        $this->trace->info(
            TraceCode::CARD_MANDATE_TERMINAL_LOG_AFTER_SORT,
            [
                'sorted_terminals'     => $finalTerminalIds,
            ]
        );

        return $finalTerminals[0];
    }
}
