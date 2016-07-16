<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Emi\Repository;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'method',
        'network',
        'international',
        'bank',
        'emi',
    ];

    public function methodFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->card) and ($terminal->emi === 0)) ;
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

    public function emiFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::EMI:
                // For EMI PAYMENTS In a particular case we need to use
                // the payment duration and the payment bank corresponding gateway
                $bank = $input['payment']->getBank();

                $emiBankGateway = Gateway::$emiBankToGatewayMap[$bank];

                // Extra DB Query getting added here - needs to be moved to cache
                $emiPlan = (new Repository)->findOrFail($emiPlanId);

                $emiDuration = $emiPlan->getDuration();

                $terminalGateway = $terminal->getGateway();

                $cardTerminalBanks = array(
                    IFSC::KKBK,
                    IFSC::UTIB,
                );

                if (in_array($bank, $cardTerminalBanks))
                {
                    ; // No other filtration required
                }
                else
                {
                    // The HDFC case currently where the payment has
                    // to be routed through the corresponding duration
                    // terminal and the corresponding
                    return (($terminalGateway === $emiBankGateway) and
                            ($emiDuration === $terminal->getEmiDuration()));
                }
                break;

            default:
                break;
        }

        return true;
    }
}
