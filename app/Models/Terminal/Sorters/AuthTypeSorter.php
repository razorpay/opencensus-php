<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Feature;
use RZP\Models\Card\Network;

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

                if (($terminal->isAuthTypeEnabled($authType, $networkCode) === true) and
                    ($this->filterOtpAuthType($payment, $terminal, $authType) === true))
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
            return ($terminal->getGateway() === Payment\Gateway::HITACHI);
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
