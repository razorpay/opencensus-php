<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'method',
        'network',
        'international',
        'bank',
    ];

    public function methodFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false)) ;
                break;

            case Method::NETBANKING:
                return $terminal->isNetbankingEnabled();
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
                    return $terminal->isCardEnabled();
                }
                else
                {
                    return $terminal->isEmiEnabled();
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
        $method = $input['payment']->getMethod();

        if ($method !== Method::CARD)
        {
            return true;
        }

        $isMerchantInternational = $input['merchant']->isInternational();

        if (($input['mode'] === Mode::TEST) and ($isMerchantInternational))
        {
            // Allow support for cards on atom for international test
            $testTerminals = array_merge(
                                [Gateway::ATOM, Gateway::AXIS_GENIUS, Gateway::PAYTM],
                                Gateway::$internationalCardGateways);

            return in_array($terminal->getGateway(), $testTerminals);
        }
        else if ($isMerchantInternational)
        {
            return in_array($terminal->getGateway(), Gateway::$internationalCardGateways);
        }
        else if ($input['mode'] === Mode::TEST)
        {
            $testTerminals = array_merge(
                                Gateway::$domesticCardGateways,
                                Gateway::$domesticCardGatewaysInTest);

            return in_array($terminal->getGateway(), $testTerminals);
        }
        else
        {
            return in_array($terminal->getGateway(), Gateway::$domesticCardGateways);
        }
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
}
