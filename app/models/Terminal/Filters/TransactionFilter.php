<?php

namespace Models\Terminal\Filters;

use Constants\Mode;

use EE\Exception;
use EE\Error\ErrorCode;

use Models\Terminal;
use Models\Bank\IFSC;
use Models\Payment\Method;
use Models\Payment\Gateway;
use Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'method',
        'network',
        'international',
        'bank',
        'corporate',
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

    public function networkFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                $network = $input['payment']->card->getNetworkCode();

                if(Gateway::isCardNetworkSupported($network, $terminal->getGateway()))
                {
                    return true;
                }
                else
                {
                    // Check for partially supported networks on live
                    $networks = Gateway::$partiallySupportedCardNetworks;

                    if ((in_array($network, $networks)) and
                        ($input['mode'] === Mode::LIVE))
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
                    }

                    return false;
                }

                break;

            default:
                break;
        }

        return true;
    }

    public function internationalFilter($terminal, $input)
    {
        return true;
    }

    public function bankFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::NETBANKING:
                $bank = $input['payment']->getBank();

                $terminalGateway = $terminal->getGateway();

                $gateways = Gateway::getGatewaysForNetbankingBank($bank);

                return in_array($terminalGateway, $gateways);
                break;

            default:
                break;
        }

        return true;
    }

    public function corporateFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::NETBANKING:
                $bank = $input['payment']->getBank();

                $corporateNetbankingBanks = Netbanking::getCorporateNetbankingBanks();

                if (in_array($bank, $corporateNetbankingBanks))
                {
                    return $terminal->isCorporate();
                }
                break;

            default:
                break;
        }

        return true;
    }
}
