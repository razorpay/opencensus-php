<?php

namespace RZP\Models\CardMandate\MandateHubs;

use App;

use RZP\Models\Base;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\CardMandate;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\Feature;

class MandateHubTerminalSelector extends Base\Core
{

    Use OptimizerHubSelector;

    public function GetTerminalForPayment(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::RAAS)) {

            // We are enabling for select merchants based oon razorx
            $variant = $this->app['razorx']->getTreatment(
                $payment->getMerchantId(),
                RazorxTreatment::ALLOW_OPTIMIZER_CARD_MANDATE_HUB,
                $this->mode
            );

            if (strtolower($variant) !== 'on')
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
            }

            // since Optimizer payment can go through RZP or external gateway, it is imperative to first select the
            // payment authorization terminal, and then choose the corresponding mandate-hub
            // As of Mar 2023, payment authorization terminal is selected before card mandate creation.
            if ($payment->terminal == null) {

                // TODO : explore if payment authorization terminal selection can be invoked here, if it is null
                throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_CARD_NOT_SUPPORTED);
            }

            // For external gateways return optimizer specific mandate hub terminal. For RZP gateway optimizer payment,
            // continue to regular mandate hub selection
            if (in_array($payment->terminal->getGateway(), Payment\Gateway::OPTIMIZER_CARD_GATEWAYS, true)) {
                return $this->GetOptimizerTerminal($payment, $cardMandate);
            }

        }

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
