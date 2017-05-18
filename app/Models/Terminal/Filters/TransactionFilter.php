<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Card\Network;
use RZP\Models\Card\Issuer;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Currency\Currency;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Terminal\Shared;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    const DISALLOW_EDUCATION_IFSC = [
        IFSC::ICIC,
        IFSC::ALLA,
        IFSC::DBSS,
        IFSC::IDFB,
        IFSC::SVCB,
        IFSC::UTIB,
    ];

    protected $properties = [
        'method',
        'network',
        'currency',
        'international',
        'bank',
        'education_bank',
        'amount',
        'maestro',
        'recurring',
    ];

    public function methodFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));

            case Method::NETBANKING:
                return $terminal->isNetbankingEnabled();

            case Method::EMI:
                return $this->isValidEmiTerminal($terminal, $input);

            // Pick the right terminal only
            case Method::WALLET:
                $wallet = $input['payment']->getWallet();

                $gateway = Gateway::getGatewayForWallet($wallet);

                return ($gateway === $terminal->getGateway());

            case Method::UPI:
                return $terminal->isUpiEnabled();

            case Method::AEPS:
                return $terminal->isAepsEnabled();

            default:
                throw new Exception\LogicException('Unknown payment method passed.', null, ['method' => $method]);
        }
    }

    // Applicable only for card and emi
    public function networkFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi())
        {
            $network = $input['payment']->card->getNetworkCode();

            return Gateway::isCardNetworkSupported($network, $terminal->getGateway());
        }

        return true;
    }

    public function currencyFilter($terminal, $input)
    {
        $payment = $input['payment'];

        $paymentCurrency = $payment->getCurrency();

        if ($payment->getConvertCurrency() === true)
        {
            $paymentCurrency = Currency::INR;
        }

        $terminalCurrency = $terminal->getCurrency();

        return ($paymentCurrency === $terminalCurrency);
    }

    public function internationalFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi() === false)
        {
            return true;
        }

        $isPaymentInternational = $input['payment']->isInternational();

        if ($isPaymentInternational === true)
        {
            return $terminal->isInternational();
        }

        return $terminal->isDomestic();
    }

    public function bankFilter($terminal, $input)
    {
        if ($input['payment']->isNetbanking())
        {
            $bank = $input['payment']->getBank();

            $terminalGateway = $terminal->getGateway();

            $isTPV = $input['merchant']->isTPVRequired();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank, $isTPV);

            return in_array($terminalGateway, $gateways);
        }
        else if ($input['payment']->isCard())
        {
            $issuer = $input['payment']->card->getIssuer();

            if (($issuer === Issuer::ICIC) and
                ($terminal->getGateway() === Gateway::FIRST_DATA) and
                ($input['merchant']->getId() !== '5ubLZpACTmD8D4'))
            {
                // ICICI cards currently don't work on FirstData
                // This allows transactions only on test merchant
                return false;
            }
        }

        return true;
    }

    public function educationBankFilter($terminal, $input)
    {
        if (($input['payment']->isNetbanking() === true) and
            ($terminal->getGateway() === Gateway::BILLDESK))
        {
            $bank = $input['payment']->getBank();

            // 7KORSqVp2oR0GH is shared billdesk PVT education terminal
            if (($terminal->getId() === '7KORSqVp2oR0GH') and
                (in_array($bank, self::DISALLOW_EDUCATION_IFSC, true) === true))
            {
                return false;
            }
        }

        return true;
    }

    public function maestroFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi())
        {
            $network = $input['payment']->card->getNetworkCode();

            // For HDFC, only shared terminals support
            // Maestro cards on Live mode.
            if (($network === Network::MAES) and
                ($input['mode'] === Mode::LIVE) and
                ($terminal->getGateway() === Gateway::HDFC))
            {
                return Shared::isSharedTerminal($terminal);
            }
        }

        return true;
    }


    public function recurringFilter($terminal, $input)
    {
        $payment = $input['payment'];

        // for cybersource, check get the terminal based on recurring type
        if ($payment->isRecurring() === true)
        {
            if (Gateway::isRecurringGateway($terminal->getGateway()) === false)
            {
                return false;
            }

            if ($terminal->getGateway() === Gateway::CYBERSOURCE)
            {
                // for cybersource recurring payment, terminal must be hdfc acquired
                if ($terminal->getGatewayAcquirer() !== 'hdfc')
                {
                    return false;
                }
            }

            $ba = app('basicauth');

            // Check if this is the second recurring payment
            if (($payment->getTokenId() !== null) and
                ($payment->localToken->isRecurring() === true) and
                (($ba->isPrivateAuth() === true) or
                 ($ba->isPrivilegeAuth() === true)))
            {
                // For second recurring payment, ensure that we select a terminal
                // of the same gateway as for the first recurring payment.
                $previousGateway = $payment->localToken->terminal->getGateway();

                $currentGateway = $terminal->getGateway();

                return (($terminal->isNon3DSRecurring() === true) and
                        ($previousGateway === $currentGateway));
            }
            else
            {
                return ($terminal->is3DSRecurring() === true);
            }
        }

        return ($terminal->isNonRecurring() === true);
    }

    protected function isValidEmiTerminal($terminal, $input)
    {
        $bank = $input['payment']->getBank();

        // check if banks emi transactions can be processed from any card terminal
        if ((empty($bank) === false) and
            (in_array($bank, Gateway::$emiBanksUsingCardTerminals)))
        {
            return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));
        }

        // validate terminal using the gateway and emi duration
        $network = $input['payment']->card->getNetworkCode();

        if ($network === Network::AMEX)
        {
            $gateway = Gateway::AMEX;
        }
        else
        {
            $gateway = Gateway::$emiBankToGatewayMap[$bank];
        }

        $emiDuration = $input['payment']->emiPlan->getDuration();

        return $terminal->isValidEmiTerminal($gateway, $emiDuration);
    }

    public function amountFilter(Terminal\Entity $terminal, array $input)
    {
        $method = $input['payment']->getMethod();

        $gateway = $terminal->getGateway();

        $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

        $category = $terminal->getNetworkCategory();

        $minAmount = Terminal\MinAmount::getMinAmount($method, $gateway, $network, $category);

        $amount = $input['payment']->getAmount();

        return ($amount >= $minAmount);
    }
}
