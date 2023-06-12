<?php

namespace RZP\Models\Terminal\Filters;

use App;

use RZP\Exception;
use RZP\Models\BankAccount\Generator;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature;
use RZP\Models\Merchant\Methods\EmiType;
use RZP\Models\Merchant\Methods\Entity as MerchantMethod;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN\Flow;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant\Preferences;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Gateway\Hitachi\Gateway as HitachiGateway;
use RZP\Models\VirtualAccount\Receiver;

class TransactionFilter extends Terminal\Filter
{
    const PREPAID_IIN = '457392';

    protected $properties = [
        'method',
        'network',
        'bank',
        'emandate',
        'recurring',
        'gateway',
        'subscription',
        'tpv',
        'upi',
        'pharma',
        'corporate',
        'auth_type',
        'bharat_qr',
        'pos',
        'bank_account_type',
        'capability',
        'blacklisted_mcc',
        'direct_settlement',
        'fee_bearer',
        'shared_terminal',
        'mcc',
        'application',
        'provider',
        'acquirer',
        'card_mandate'
    ];

    public function methodFilter($terminal)
    {
        $method  = $this->input['payment']->getMethod();
        $payment = $this->input['payment'];

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));

            case Method::NETBANKING:
                return $terminal->isNetbankingEnabled();

            case Method::EMI:
                return $this->isValidEmiTerminal($terminal);

            // Pick the right terminal only
            case Method::WALLET:
                $wallet = $this->input['payment']->getWallet();

                $gateway = Gateway::getGatewayForWallet($wallet);

                $enabled_wallets = $terminal->getEnabledWallets();

                if (empty($enabled_wallets)) {
                    return ($gateway === $terminal->getGateway());
                }

                // Select the terminals whose gateway matches with wallet gateway
                // For some gateways like payu and ccavenue, gateway will not match, in that case,
                // we check for enabled wallets field of terminals.
                return (($gateway === $terminal->getGateway()) || (in_array($wallet, $enabled_wallets)));

            case Method::UPI:
                return $terminal->isUpiEnabled();

            case Method::AEPS:
                return $terminal->isAepsEnabled();

            case Method::EMANDATE:
                return $terminal->isEmandateEnabled();

            case Method::BANK_TRANSFER:
                return $terminal->isBankTransferEnabled();

            case Method::OFFLINE:
                return $terminal->isOfflineEnabled();

            case Method::CARDLESS_EMI:
                // @todo: fix the getWallet() for cardless emi and move it to a separate function
                return ($terminal->isCardlessEmiEnabled() === true);

            case Method::PAYLATER:
                return ($terminal->isPayLaterEnabled() === true);

            case Method::NACH:
                return $terminal->isNachEnabled();

            case Method::COD:
                // no terminal should be applicable to cod as it doesnt deal with gateways
                return false;

            case Method::APP:
                if ($payment->isAppCred() === true) {
                    return $terminal->isCredEnabled();
                }

                if($terminal->isAppEnabled())
                {
                    $app = $payment->getWallet();

                    $enabledApps = (array) $terminal->getEnabledApps();

                    return (in_array(strtolower($app), $enabledApps, true));
                }

                return false;

            case Method::FPX:
                return $terminal->isFpxEnabled();

            default:
                throw new Exception\LogicException(
                    'Unknown payment method passed.',
                    null,
                    [
                        'terminal_id'   => $terminal->getId(),
                        'method'        => $method
                    ]);
        }
    }

    // Applicable only for card and emi
    public function networkFilter($terminal)
    {
        $payment = $this->input['payment'];

        if (($payment->isMethodCardOrEmi() === true) and
            ($payment->isGooglePayCard() === false))
        {
            $network = $payment->card->getNetworkCode();
            $gateway = $terminal->getGateway();
            $cardMandate = $this->input['card_mandate'];

            if ($payment->isBharatQr() === true)
            {
                $supported = ((Gateway::isBharatQrCardNetworkSupported($network, $gateway)) and
                              (empty($terminal[strtolower($network) . '_mpan']) === false));
            }
            else if (($terminal->getId() === 'CmRSEGymhC3lae') and ($network === Network::RUPAY))
            {
                return true;
            }
            else if ((is_null($cardMandate) === false) &&
                (Gateway::isCardMandateGateways($terminal->getGateway()) === true)) {
                return true;
            }
            else
            {
                $issuer = $payment->card->getIssuer();

                $supported = Gateway::isCardNetworkSupported($network, $gateway, $issuer, $payment->isRecurring());
            }

            return $supported;
        }

        return true;
    }

    public function bankFilter($terminal)
    {
        if ($this->input['payment']->isNetbanking() === true)
        {
            $bank = $this->input['payment']->getBank();

            $terminalGateway = $terminal->getGateway();

            $isTPV = $this->input['merchant']->isTPVRequired();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank, $isTPV);

            return in_array($terminalGateway, $gateways);
        }

        return true;
    }

    public function emandateFilter($terminal)
    {
        if ($this->input['payment']->isEmandate() === false)
        {
            return true;
        }

        $gateways = [];

        $paymentBank = $this->input['payment']->getBank();

        $authType = $this->input['payment']->getAuthType();

        $terminalGateway = $terminal->getGateway();

        $authTypeGateways = ($authType !== null) ? Gateway::getEmandateGatewaysForAuthType($authType) : [];

        // @todo: Can be more cleaner
        foreach (Gateway::$gatewaysEmandateBanksMap as $gateway => $authTypes)
        {
            foreach ($authTypes as $gatewaysupportedAuthType => $gatewaySupportedBanks)
            {
                    if (in_array($paymentBank, $gatewaySupportedBanks, true) === true) {
                        if (($authType !== null) and
                            (in_array($gateway, $authTypeGateways, true) === false)) {
                            continue;
                        }

                        $gateways[] = $gateway;
                    }
            }
        }

        if (($this->input['payment']->getAmount() > 0) and
            ($this->input['payment']->getRecurringType() === Payment\RecurringType::INITIAL))
        {
            return (in_array($terminalGateway, $gateways) and
                   (Gateway::isDirectDebitEmandateGateway($terminalGateway) === true));
        }

        return in_array($terminalGateway, $gateways);
    }

    /**
     * Filter to remove cybersource shared terminals for non recurring payments
     *
     * @param  Terminal\Entity $terminal
     * @return bool
     */
    public function gatewayFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        $merchant = $this->input['merchant'];

        // This filter should run only in production environment, else tests for
        // cybersource would fail.
        if (($this->isLiveMode() === true) and ($payment->isMethodCardOrEmi() === true)
            and ($payment->isGooglePayCard() === false))
        {
            if ($terminal->getGateway() === Gateway::CYBERSOURCE)
            {
                //
                // For some merchants, due to business reasons we want payments
                // to go through cybersource terminal
                //
                $merchantWhitelisted = (in_array($merchant->getId(),
                                            Preferences::CYBERSOURCE_MERCHANT_WHITELIST,
                                            true) === true);

                $iin = $payment->card->getIin();

                if (($merchantWhitelisted === false) and
                    ($payment->isRecurring() === false) and
                    ($payment->isInternational() === false) and
                    ($iin !== self::PREPAID_IIN) and
                    ($terminal->isDirectForMerchant() === false))
                {
                    return false;
                }
            }
            else if (($terminal->getGateway() === Gateway::AXIS_MIGS) and
                     ($merchant->getId() === Preferences::MID_ZOMATO))
            {
                $iin = $payment->card->getIin();

                if ($iin !== self::PREPAID_IIN)
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function recurringFilter($terminal)
    {
        $payment = $this->input['payment'];
        $merchant = $this->input['merchant'];
        $cardMandate = $this->input['card_mandate'];

        //TODO need to remove this, add type non_recurring in offline terminal
        if ($payment->isOffline() === true)
        {
            return true;
        }
        if ($payment->isRecurring() === false)
        {
            return ($terminal->isNonRecurring() === true);
        }

        if ((is_null($cardMandate) === false) && (Gateway::isCardMandateGateways($terminal->getGateway()) === true)) {
            return true;
        }

        $recurringGateway = Gateway::isRecurringGateway($terminal->getGateway());

        if ($recurringGateway === false)
        {
            return false;
        }

        if ($payment->isCard() === true)
        {
            if(($payment->card->isDebit() !== true) and
               ($payment->isSecondRecurring() === true) and
               ($terminal->getGateway() === Gateway::HDFC) and
               ($terminal->isDebitRecurring() === true))
            {
                return false;
            }
        }

        $payment = $this->input['payment'];

        $gatewayTokens = $this->input['gateway_tokens'];

        //
        // All first recurring payments or payments made via public
        // auth need to go via 3DS Recurring terminals only.
        //
        if ($payment->isSecondRecurring() === false)
        {
            return ($terminal->is3DSRecurring() === true);
        }

        //
        // From here onwards, the terminal selection
        // logic is for second recurring.
        //
        if ($terminal->isNon3DSRecurring() === false)
        {
            return false;
        }

        $applicableTypes = [
            Terminal\Type::RECURRING_3DS,
            Terminal\Type::RECURRING_NON_3DS,
        ];

        //
        // If the terminal supports both [recurring 3ds and recurring non-3ds] or [no-2fa],
        // we don't care about gateway tokens. We care about gateway tokens
        // only because of 2fa. But if the terminal supports both [3ds and
        // non-3ds] or [no-2fa], it means that the terminal does not care about 2fa and
        // hence, we don't need to too. We can just use this terminal without
        // worrying about whether we have a gateway token for this or not.
        //
        // Also, we would be doing this only for direct terminals and for card
        // payments. Though, it would be applicable for shared terminals also,
        // we don't want to fallback on that just yet.
        //
        if ((empty(array_diff($applicableTypes, $terminal->getType())) === true) or
            ($terminal->isNo2Fa() === true))
        {
            if (($terminal->isFallbackApplicable($this->input['merchant']) === true) and
                ($payment->isCard() === true))
            {
                return true;
            }

            if ($payment->isNach() === true)
            {
                if ($gatewayTokens->isEmpty() === false)
                {
                    return (new Terminal\Core)->hasApplicableGatewayTokens($terminal, $payment, $gatewayTokens);
                }

                if ($terminal->getId() !== $payment['localToken']->getTerminalId())
                {
                    return false;
                }
                return true;
            }

            if ($payment->isUpiAutoRecurring() === true)
            {
                // Its UPI auto recurring payment, does not have anything to do with gateway token(As of now)
                return true;
            }
        }

        return (new Terminal\Core)->hasApplicableGatewayTokens($terminal, $payment, $gatewayTokens);
    }

    protected function upiFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isUpi() === true)
        {
            if ($payment->isUpiTransfer() !== $terminal->isUpiTransfer())
            {
                return false;
            }

            $upiMetadata = $payment->getMetadata(Payment\UpiMetadata\Entity::UPI_METADATA);

            if ($upiMetadata instanceof Payment\UpiMetadata\Entity)
            {
                if ($upiMetadata->isOtmCollect() === true)
                {
                    return ($terminal->isOtmCollect() === true);
                }

                if ($upiMetadata->isOtmIntent() === true)
                {
                    return ($terminal->isOtmPay() === true);
                }
            }

            $flow = $payment->getMetadata('flow', 'collect');

            if (($payment->isBharatQr() === true) and
                ($payment->isFlowIntent() === false))
            {
                if (((new \RZP\Models\QrCode\NonVirtualAccountQrCode\Generator())->checkIfDedicatedTerminalSplitzExperimentEnabled(
                            $this->input['merchant']->getId()) === true) and ($terminal->isOnline() === true))
                {
                    return true;
                }

                if (empty($terminal->getVpa()) === true)
                {
                    return false;
                }
            }
            // Flow is intent for UPI QR on VA and direct payments
            // also for Gpay Upi flow will be intent
            else if ($flow === 'intent' or $payment->isGooglePayMethodSupported(Method::UPI))
            {
                $gateway = $terminal->getGateway();

                if ((Gateway::isUpiIntentFlowSupported($gateway) === true) and
                    ($terminal->isPay() === true))
                {
                    $upiProvider = $payment->getMetadata(Payment\Entity::UPI_PROVIDER);

                    // if $upiprovider is set, it's omnichannel flow. We need to select only those terminal for which
                    // corresponsing omnichannel terminal exist otherwise it's normal intent flow and we return true.
                    if (empty($upiProvider))
                    {
                        // For UPI QR Terminal has to be intent enabled, yet we are putting
                        // extra check only to make sure implementation is there for gateway
                        if ($payment->isUpiQr())
                        {
                            return in_array($gateway, Gateway::$upiQrGateways, true);
                        }

                        return true;
                    }

                    $upiProviderGateway = Payment\UpiProvider::$upiProvidersToGatewayMap[$upiProvider];

                    $vpa = $terminal->getVpaForTerminal();

                    $terminal = $this->repo->terminal->findByGatewayAndTerminalData($upiProviderGateway, ['vpa' => $vpa]);

                    if (($terminal !== null) and
                        ($terminal->isEnabled() === true))
                    {
                        return true;
                    }
                }

                return false;
            }
        }

        return true;
    }

    protected function corporateFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isNetbanking() === true)
        {
            // If terminal supports both corporate and retail,
            // we can directly pass this filter
            if ($terminal->isBankingTypeBoth() === true)
            {
                return true;
            }

            $bank = $payment->getBank();

            $terminalBankingTypes = $terminal->getBankingTypes();

            // For corporate bank, the terminal should support corporate type
            if ((Netbanking::isCorporateBank($bank) === true) and
                (in_array(Terminal\BankingType::CORPORATE, $terminalBankingTypes) === true))
            {
                return true;
            }
            else if ((Netbanking::isCorporateBank($bank) === false) and
                     (in_array(Terminal\BankingType::RETAIL, $terminalBankingTypes) === true))
            {
                return true;
            }

            // If the banking type in payment and terminal does not match
            return false;
        }

        return true;
    }

    protected function subscriptionFilter(Terminal\Entity $terminal)
    {
        //
        // For now, not filtering based on gateway.
        // Assuming that all gateways work without
        // one year limitation. /cc @shk
        //
        return true;

        $payment = $this->input['payment'];

        //
        // If it's NOT a subscription payment,
        // don't do any filtering.
        //
        if ($payment->hasSubscription() === false)
        {
            return true;
        }

        $subscription = $payment->subscription;

        if ($subscription->isMoreThanOneYear() === false)
        {
            //
            // If subscription is not for more than a year,
            // there's no filtering required.
            //
            return true;
        }

        $currentGateway = $terminal->getGateway();
        $allowedGateways = Gateway::$subscriptionOverOneYearGateways;

        return (in_array($currentGateway, $allowedGateways, true) === true);
    }

    protected function isValidEmiTerminal($terminal)
    {
        $payment = $this->input['payment'];

        $bank = $payment->getBank();

        if ($this->isMerchantEmiTypeEnabled() === false || $terminal->supportsCurrency(Currency::INR) === false) {
            return false;
        }

        // check for Debit Emi Gateways

        if (empty($bank) === false and (array_key_exists($bank,GATEWAY::$debitEmiGateways)) and ($payment->card->isDebit()))
        {
            if(GATEWAY::$debitEmiGateways[$bank] === $terminal->getGateway())
            {
                return true;
            }

            return false;
        }

        if ((empty($bank) === false) and
            (in_array($bank, Gateway::$emiBanksUsingCardAndEmiTerminals) === true)) {
            return ($terminal->isEmiEnabled() || $terminal->isCardEnabled());
        }

        // check if banks emi transactions can be processed from any card terminal
        if ((empty($bank) === false) and
            (in_array($bank, Gateway::$emiBanksUsingCardTerminals)))
        {
            return $terminal->isCardEnabled();
        }

        // validate terminal using the gateway and emi duration
        $network = $this->input['payment']->card->getNetworkCode();

        if ($network === Network::AMEX)
        {
            $gateway = Gateway::AMEX;
        }
        else if ($network === Network::BAJAJ)
        {
            $gateway = Gateway::BAJAJ;
        }
        else
        {
            $cardType = $payment->card->getType();

            $gateway = Gateway::$emiBankToGatewayMap[$bank][$cardType];
        }

        $emiDuration = $this->input['payment']->emiPlan->getDuration();

        return ($this->isMerchantEmiTypeEnabled() and $terminal->isValidEmiTerminal($gateway, $emiDuration));
    }

    protected function isMerchantEmiTypeEnabled()
    {
        $payment = $this->input['payment'];

        $methods = $this->input['merchant']->getMethods();

        $emi = $methods[MerchantMethod::EMI];

        if (empty($emi) === false)
        {
            return (($payment->card->isCredit() &&  $methods->isCreditEmiEnabled()) || ($payment->card->isDebit() && $methods->isDebitEmiEnabled()));
        }

        return true;
    }

    public function pharmaFilter(Terminal\Entity $terminal)
    {
        $category2 = $this->input['merchant']->getCategory2();

        $acquirer = $terminal->getGatewayAcquirer();

        if (($category2 === Category::PHARMA) and
            ($this->input['payment']->isMethodCardOrEmi() === true))
        {
            if (($terminal->isShared() === true) and
                ($acquirer === Gateway::ACQUIRER_HDFC))
            {
                // This check is for all the card networks which are
                // supported by gateways from other acquirers that also
                // have a shared terminal.
                // Currently, we don't have a shared terminal RuPay and
                // Maestro. We are doing a workaround using the
                // merchant descriptor feature of FirstData
                return false;
            }
            // Terminal ID for Aala first data terminal is 76lEBqibDvhOzY
            else if ($terminal->getId() === '76lEBqibDvhOzY')
            {
                 $network = $this->input['payment']->card->getNetworkCode();
                 if ($network !== Network::RUPAY)
                 {
                    return false;
                 }
            }
        }
        return true;
    }

    /**
     * For netbanking payments, if a merchant has tpv feature enabled, checks
     * if the terminal supports tpv or not
     *
     * @param  Terminal\Entity      $terminal
     *
     * @return bool
     */
    public function tpvFilter($terminal)
    {
        if ($this->input['payment']->isTpvMethod() === true)
        {
            if ($this->input['merchant']->isFeatureEnabled(Feature\Constants::TPV))
            {
                return ($terminal->isTpvAllowed() === true);
            }

            return ($terminal->isNonTpvAllowed() === true);
        }

        return true;
    }

    /**
     * For card / emi payments, selects terminal with gateway not hitachi
     * and merchant mcc not in blacklist mcc array
     *
     * @param  Terminal\Entity $terminal
     *
     * @return bool
     */
    public function blacklistedMccFilter(Terminal\Entity $terminal)
    {
        $merchant = $this->input['merchant'];

        $merchantMcc = $merchant->getCategory();

        // These MCCs are blacklisted by RBL and Hitachi. Hence, should not go via hitachi.
        // We already have gateway rules enabled for this, but this is a fallback in case
        // the gateway rules fails.
        // Violation of the agreement with Hitachi and RBL, which results in getting fined by the bank.
        if (($terminal->getGateway() === Gateway::HITACHI) and
            ((in_array($merchantMcc, HitachiGateway::BLACKLISTED_MCC) === true) and
             ($merchant->isFeatureEnabled(Feature\Constants::OVERRIDE_HITACHI_BLACKLIST) === false)))
        {
            return false;
        }

        return true;
    }

    /**
     * For card / emi payments, selects terminals with null mcc or with mcc
     * matching that of the merchant
     *
     * @param  Terminal\Entity $terminal
     * @param array            $applicableTerminals
     *
     * @return bool
     */
    public function mccFilter(Terminal\Entity $terminal, array $applicableTerminals)
    {
        $merchant = $this->input['merchant'];

        $merchantMcc = $merchant->getCategory();

        if (($this->input['payment']->isMethodCardOrEmi() === true) and
            (in_array($terminal->getGateway(), Gateway::MCC_FILTER_GATEWAYS, true) === true))
        {
            //
            // If terminal is direct for the merchant, we always select it.
            //
            if ($terminal->isDirectForMerchant() === true)
            {
                return true;
            }

            if ($terminal->getCategory() !== null)
            {
                //
                // If terminal category is not null, then we reject the terminal
                // if it's category is not the same as merchant mcc.
                //
                return ($terminal->getCategory() === $merchantMcc);

            }
            else
            {
                //
                // If the terminal is a shared terminal with category null, then
                // we select it, if there are no terminals with the merchant mcc
                // present in the set of all terminals.
                //
                return ($this->isTerminalWithMerchantMccAbsent(
                            $applicableTerminals,
                            $merchantMcc) === true);
            }
        }

        return true;
    }

    // filter rejects all shared terminal if there is a atleast one direct terminal present on same gateway
    // filter selects all shared terminal if there is no direct terminal on same gateway
    public function sharedTerminalFilter(Terminal\Entity $terminal, array $applicableTerminals)
    {
        $payment  = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === false)
        {
            return true;
        }

        //
        // If terminal is direct for the merchant, we always select it.
        //
        if ($terminal->isDirectForMerchant() === true)
        {
            return true;
        }

        $currentGateway = $terminal->getGateway();

        $directTerminalsOnSameGateway = false;

        foreach ($applicableTerminals as $applicableTerminal)
        {
            if (($applicableTerminal->isDirectForMerchant() === true) and
                ($applicableTerminal->getGateway() === $currentGateway))
            {
                // breaking once we get direct terminal on the same gateway as of current terminal
                $directTerminalsOnSameGateway = true;
                break;
            }
        }

        if ($directTerminalsOnSameGateway === true)
        {
            return false;
        }

        return true;
    }

    public function authTypeFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if (($payment->isMethodCardOrEmi() === false) or ($payment->isGooglePayCard() === true))
        {
            return true;
        }

        //
        // We use preferred_auth only if it's available else to fallback to
        // $authType attribute
        //
        $authType = (array) $payment->getAuthType();

        $authTypes = $payment->getMetadata(Payment\Entity::PREFERRED_AUTH, $authType);

        //
        // We fallback to the default flow if the preferred authentication or authType
        // is empty. Normal flow chooses all the 3ds terminals.
        //
        if (empty($authTypes) === false)
        {
            foreach ($authTypes as $authType)
            {
                switch ($authType)
                {
                    case Payment\AuthType::PIN:
                        $gateway = $terminal->getGateway();
                        $acquirer = $terminal->getGatewayAcquirer();

                        $issuer = $payment->card->getIssuer();

                        //
                        // Pin auth terminal is only selected when the terminal issuer
                        // supports pin auth and card iin also supports the flow
                        //
                        if (($terminal->isPin() === true) and
                            (Gateway::isIssuerSupportedForPinAuthType($issuer, $gateway, $acquirer) === true))
                        {
                            if (($payment->card->iinRelation !== null) and
                                ($payment->card->iinRelation->supports(Flow::PIN) === true))
                            {
                                return true;
                            }
                        }

                        break;

                    case Payment\AuthType::OTP:
                        $iin = $payment->card->iinRelation;
                        // We should select the terminal only if iin is set and flows are supported
                        // by the IIN
                        if ($iin !== null)
                        {
                            if (($terminal->isIvr() === true) and
                                (($iin->supports(Flow::IVR) === true) or
                                 ($iin->supports(Flow::OTP) === true)))
                            {
                                return true;
                            }

                            $gateway = $terminal->getGateway();

                            //
                            // IVR is supported only on on Hitachi.
                            // Hence, it should be enabled only for all the IVR enabled iins.
                            //
                            if ((Payment\Gateway::isOnlyAuthorizationGateway($gateway) === true) and
                                (($iin->supports(Flow::IVR) === true) or
                                 ($iin->supports(Flow::OTP) === true)))
                            {
                                return true;
                            }

                            if ((Gateway::supportsHeadlessBrowser($gateway, $iin->getNetworkCode()) === true) and
                                ($iin->supports(Flow::HEADLESS_OTP) === true))
                            {
                                return true;
                            }
                        }

                        break;

                    case Payment\AuthType::_3DS:
                        if ($this->is3DSTerminal($terminal) === true)
                        {
                            return true;
                        }

                        break;

                    case Payment\AuthType::SKIP:
                        // Moto terminal is selected only on skip auth
                        if ($terminal->isMoto() === true)
                        {
                            return true;
                        }

                        break;
                }
            }

            //
            // If the terminal doesn't match the given condition then
            // we filter that terminal.
            //
            return false;
        }

        // Default terminals should always be the one which supports 3DS
        // Any other auth type terminals should be filtered out if `auth_type`
        // is empty or null.
        // In case, we have plan to add new auth in the filter, we will have to
        // add a condition here to remove terminals of that auth type while
        // ensuring that all other gateways are selected.
        return ($this->is3DSTerminal($terminal) === true);
    }

    // @codingStandardsIgnoreLine
    protected function is3DSTerminal($terminal)
    {
        return (($terminal->isPin() === false) and ($terminal->isIvr() === false));
    }

    protected function isTerminalWithMerchantMccAbsent(
        array $applicableTerminals,
        $merchantMcc = null): bool
    {
        foreach ($applicableTerminals as $terminal)
        {
            //
            // Currently this checks only for HDFC and hitachi gateway terminals
            //

            if ((in_array($terminal->getGateway(), Gateway::MCC_FILTER_GATEWAYS, true) === true) and
                ($terminal->getCategory() === $merchantMcc))
            {
                return false;
            }
        }

        return true;
    }

    public function bharatQrFilter($terminal)
    {
        if (($this->input['payment']->isFlowIntent()) === true)
        {
            // We have already verified that terminal is intent enabled in UPI Filter
            return true;
        }
        if ($this->input['payment']->isBharatQr() === true)
        {
            if (((new \RZP\Models\QrCode\NonVirtualAccountQrCode\Generator())->checkIfDedicatedTerminalSplitzExperimentEnabled(
                        $this->input['merchant']->getId()) === true) and ($terminal->isOnline() === true))
            {
                return true;
            }

            return ($terminal->isBharatQr() === true);
        }
        else
        {
            return ($terminal->isBharatQr() === false);
        }
    }

    public function posFilter($terminal)
    {
        if ($this->input['payment']->isPos() === true)
        {
            return ($terminal->isPos() === true);
        }
        return true;
    }

    public function directSettlementFilter($terminal, $applicableTerminals)
    {
       $directSettlementTerminals = array_filter(
                                    $applicableTerminals,
                                    function ($terminal)
                                    {
                                        return ($terminal->isDirectSettlement() === true);
                                    });

        // if no direct settlement terminals found, return true.
        if (empty($directSettlementTerminals) === true)
        {
            return true;
        }

        if (in_array($terminal, $directSettlementTerminals, true) === true)
        {
            return true;
        }

        return false;
    }

    public function feeBearerFilter($terminal, $applicableTerminals)
    {
        return true;
    }

    public function hitachiSharedTerminalFilter($terminal, $applicableTerminals)
    {
        $payment  = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === false)
        {
            return true;
        }

        if (($terminal->getGateway() !== Gateway::HITACHI) or
            ($terminal->isDirectForMerchant() === true))
        {
            return true;
        }

        $directTerminals = array_filter(
                                    $applicableTerminals,
                                    function ($terminal)
                                    {
                                        return (($terminal->getGateway() === Gateway::HITACHI) and
                                                ($terminal->isDirectForMerchant() === true));
                                    });

        // if there is a direct hitachi terminal we reject shared hitachi terminal
        if (empty($directTerminals) === true)
        {
            return true;
        }

        return false;
    }

    public function bankAccountTypeFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isBankTransfer() === false)
        {
            return true;
        }

        $metadata = $payment->getMetadata();

        $bankingTypeApplicable = $terminal->isTypeApplicable(Terminal\Type::BUSINESS_BANKING);

        // If a bank account is requested specifically for banking, only terminals with that type set can be selected.
        if (($metadata[Generator::BANKING] === true) and ($bankingTypeApplicable === false))
        {
            return false;
        }

        // If metadata.banking is not set, we must not select the terminal with business_banking type.
        if (($metadata[Generator::BANKING] === false) and ($bankingTypeApplicable === true))
        {
            return false;
        }

        if ($metadata[Generator::NUMERIC] === true)
        {
            return $terminal->isTypeApplicable(Terminal\Type::NUMERIC_ACCOUNT);
        }

        return $terminal->isTypeApplicable(Terminal\Type::ALPHA_NUMERIC_ACCOUNT);
    }

    public function capabilityFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isGooglePayCard() === true)
        {
            return true;
        }

        if ((Payment\Gateway::isOnlyAuthorizationGateway($terminal->getGateway()) === true) or
            ($terminal->getCapability() === Terminal\Capability::AUTHORIZE))
        {
            switch ($payment->getMethod())
            {
                case Method::CARD:
                case Method::EMI:
                    $allowedNetworks = [Network::MAES, Network::VISA, Network::MC];
                    $network = $payment->card->getNetworkCode();

                    if (in_array($network, $allowedNetworks, true) === true)
                    {
                        return true;
                    }

                    // Special case handling for Hitachi Rupay
                    if (($terminal->getGateway() === Gateway::HITACHI) and
                        ($network === Network::RUPAY))
                    {
                        return true;
                    }
                    break;

                case Method::EMANDATE:
                    // Only Enach RBL gateway supports only authorization
                    if ($terminal->getGateway() === Gateway::ENACH_RBL)
                    {
                        return true;
                    }
                    break;
            }

            return false;
        }

        return true;
    }

    public function applicationFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];
        $application = $payment->getApplication();

        switch ($application)
        {
            case 'google_pay':
                if ($payment['method'] === Method::CARD)
                {
                    return ($terminal->isTokenizationSupported() === true);
                }
                return true;
            case 'visasafeclick_stepup':
                return ($terminal->isGateway(Gateway::CYBERSOURCE) === true);
            default:
                return true;
        }

        return true;
    }

    public function providerFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if (($payment->isPayLater() === false) and ($payment->isCardlessEmi() === false))
        {
            return true;
        }

        $wallet = $this->input['payment']->getWallet();

        if (((in_array($wallet, Payment\Processor\PayLater::getPaylaterDirectAquirers()) === true) and ($payment->isPayLater())) or
            ((in_array($wallet, Payment\Processor\CardlessEmi::getCardlessEmiDirectAquirers()) === true) and ($payment->isCardlessEmi())))
        {
            return ($wallet === $terminal->getGatewayAcquirer());
        }
        else
        {
            $enabledBanks = (array) $terminal->getEnabledBanks();

            return (in_array(strtoupper($wallet), $enabledBanks, true));
        }
    }

    public function acquirerFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if (($payment->card() === true) and
            ($terminal->getGateway() === Gateway::PAYSECURE) and
            ($terminal->getGatewayAcquirer() === Gateway::ACQUIRER_AXIS))
        {
            $orgId = $this->input['merchant']->getOrgId();

            if (($orgId !== 'CLTnQqDj9Si8bx') or
                ($payment->card->getNetworkCode() !== Network::RUPAY))
            {
                return false;
            }
        }

        return true;
    }

    public function cardMandateFilter($terminal) {
        $method  = $this->input['payment']->getMethod();
        $cardMandate = $this->input['card_mandate'];

        if ($method === Method::CARD && is_null($cardMandate) === false) {
            return Gateway::isCardMandateGateways($terminal->getGateway());
        }

        return true;
    }
}
