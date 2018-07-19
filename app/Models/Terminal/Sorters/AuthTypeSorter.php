<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;

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

        foreach ($preferredAuthentications as $authType)
        {
            //
            // As the terminals are from the priority list
            // append to the terminal
            //
            $terminals = $unorderedTerminals;

            foreach ($terminals as $key => $terminal)
            {
                if (($terminal->isAuthTypeEnabled($authType) === true) and
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
            ($payment->card->iinRelation->getIssuer() === Bank\IFSC::UTIB) and
            ($payment->card->iinRelation->supports(Card\IIN\Flow::OTP) === true) and
            ($payment->merchant->isAxisExpressPayEnabled() === true))
        {
            return ($terminal->getGateway() === Payment\Gateway::HITACHI);
        }

        return true;
    }
}
