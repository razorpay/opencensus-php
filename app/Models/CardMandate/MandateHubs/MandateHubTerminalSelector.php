<?php

namespace RZP\Models\CardMandate\MandateHubs;

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

        $this->trace->info(
            TraceCode::CARD_MANDATE_TERMINAL_LOG,
            [
                'terminals'         => $selectedTerminalIds,
                'is_si_hub_enabled' => $isSIHubEnabled,
            ]
        );

        $finalTerminals = [];

        foreach ($terminals as $terminal)
        {
            if ($isSIHubEnabled === true or
                $terminal->getGateway() !== MandateHubs::BILLDESK_SIHUB)
            {
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
