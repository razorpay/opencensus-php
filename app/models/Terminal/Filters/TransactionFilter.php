<?php

namespace Models\Terminal\Filters;

use Models\Terminal;
use Models\Payment\Method;
use Models\Payment\Gateway;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'method',
        'international',
        'bank',
    ];

    public function methodFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return $terminal->card;
                break;

            case Method::NETBANKING:
                return $terminal->netbanking;
                break;

            // Check - Needs more work with respect to emi terminals of other banks
            case Method::EMI:

                $bank = $input['payment']->getBank();

                $cardTerminalBanks = array(
                    IFSC::KKBK,
                    IFSC::UTIB,
                );

                if (in_array($bank, $cardTerminalBanks))
                {
                    // for Kotak, process as normal card transaction and mail for emi
                    return $terminal->card;
                }
                else
                {
                    return ($terminal->emi === 1);
                }
                break;

            // Pick the right terminal only
            case Method::WALLET:
                $wallet = $input['payment']->getWallet();

                $gateway = Gateway::getGatewayForWallet($wallet);

                return ($gateway === $terminal->getGateway());

            default:
                break;
        }
    }

    public function internationalFilter($terminal, $input)
    {
        return true;
    }

    public function bankFilter($terminal, $input)
    {
        return true;
    }
}
