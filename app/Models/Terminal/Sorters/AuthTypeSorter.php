<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Feature;
use RZP\Models\Card\Network;
use RZP\Trace\TraceCode;

class AuthTypeSorter extends Terminal\Sorter
{
    protected $properties = [
        'auth_type',
    ];

    // Arrange card terminals in order of preferred auth type
    // @codingStandardsIgnoreLine
    public function authTypeSorter($terminals)
    {
        $payment = $this->input['payment'];

        $preferredAuthentications = $payment->getMetadata(Payment\Entity::PREFERRED_AUTH);

        $this->trace->info(
            TraceCode::SMART_ROUTING_AUTH_FILTER,
            [
                'payment_id'         => $payment['id'],
                'preferred_auth'     => $preferredAuthentications
            ]);

        // No need to sort unless the method is either card or EMI.
        // or preferredAuthentications is empty.
        if (($payment->isMethodCardOrEmi() === false) or
            (empty($preferredAuthentications) === true))
        {
            return $terminals;
        }

        $orderedTerminals = [];
        $unorderedTerminals = $terminals;
        $iin = $payment->card->iinRelation;

        $networkCode = Network::UNKNOWN;

        if ($iin !== null)
        {
            $networkCode = $iin->getNetworkCode();
        }

        foreach ($preferredAuthentications as $authType)
        {
            //
            // As the terminals are from the priority list
            // append to the terminal
            //
            $terminals = $unorderedTerminals;

            foreach ($terminals as $key => $terminal)
            {
                $authTypeEnabled = $terminal->isAuthTypeEnabled($authType, $networkCode);

                $filterOtpAuthTypeBool = $this->filterOtpAuthType($payment, $terminal, $authType);

                $this->trace->info(
                    TraceCode::SMART_ROUTING_AUTH_FILTER,
                    [
                        'payment_id'           => $payment['id'],
                        'terminal_id'          => $terminal['id'],
                        'auth_type_enabled'    => $authTypeEnabled,
                        'filter_otp_auth_type' => $filterOtpAuthTypeBool
                    ]);

                if (($authTypeEnabled === true) and
                    ($filterOtpAuthTypeBool === true))
                {
                    $orderedTerminals[] = $terminal;

                    unset($unorderedTerminals[$key]);
                }
            }
        }

        return $orderedTerminals;
    }

    /**
     * We are doing this because terminal selection for
     * Axis OTP is kinda tricky where hitachi and HDFC both
     * get selected but we don't want both of them to get
     * selected
     */
    protected function filterOtpAuthType($payment, $terminal, $authType)
    {
        if (($authType === Payment\AuthType::OTP) and
            ($payment->card->iinRelation !== null) and
            (($payment->card->iinRelation->supports(Card\IIN\Flow::OTP) === true) or
             ($payment->card->iinRelation->supports(Card\IIN\Flow::IVR) === true)))
        {
            $onlyAuthGateway = (Payment\Gateway::isOnlyAuthorizationGateway($terminal->getGateway()) === true);

            $this->trace->info(
                TraceCode::SMART_ROUTING_AUTH_FILTER,
                [
                    'payment_id'        => $payment['id'],
                    'terminal_id'       => $terminal['id'],
                    'only_auth_gateway' => $onlyAuthGateway
                ]);
            return $onlyAuthGateway;
        }

        // headless check
        if (($authType === Payment\AuthType::OTP) and
            ($payment->card->iinRelation !== null) and
            ($payment->card->iinRelation->supports(Card\IIN\Flow::HEADLESS_OTP) === false))
        {
            return false;
        }

        return true;
    }
}
