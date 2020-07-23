<?php

namespace RZP\Models\Terminal\Auth\Card;

use App;

use RZP\Exception;
use RZP\Models\BankAccount\Generator;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Card\IIN;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN\Flow;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant\Preferences;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Processor\Netbanking;

class AuthFilter extends Terminal\Auth\Base
{
    public function isValidAuth($authType, $authenticationGateways) : bool
    {
        $payment = $this->payment;

        switch ($authType)
        {
            case Payment\AuthType::IVR:
                return $this->canRunIvrFlow($payment);
                break;

            case Payment\AuthType::OTP:
                return $this->canRunAxisExpressPay($payment);
                break;

            case Payment\AuthType::HEADLESS_OTP:
                return $this->canRunHeadlessOtpFlow($payment, $authenticationGateways);
                break;

            case Payment\AuthType::_3DS:
                return true;
                break;

            case Payment\AuthType::PIN:
                return $this->canRunPinFlow($payment);
                break;

            case Payment\AuthType::SKIP:
                return ($payment->terminal->isMoto() === true);
                break;

            default:
                return false;
                break;
        }
    }


    protected function isAuthTypeOtp(Payment\Entity $payment): bool
    {
        if (($payment->getAuthType() === Payment\AuthType::OTP) or
            (in_array(Payment\AuthType::OTP, $payment->getMetadata(Payment\Entity::PREFERRED_AUTH, []), true) === true))
        {
            return true;
        }

        return false;
    }

    protected function canRunIvrFlow(Payment\Entity $payment): bool
    {
        if (($payment->merchant->isIvrEnabled() === true) and
            (is_null($payment->card) === false) and
            ($payment->card->iinRelation !== null) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($payment->card->iinRelation->supports(IIN\Flow::IVR) === true))
        {
            return true;
        }

        return false;
    }

    protected function canRunAxisExpressPay(Payment\Entity $payment): bool
    {
        if (($payment->merchant->isAxisExpressPayEnabled() === true) and
            (is_null($payment->card) === false) and
            ($payment->card->iinRelation !== null) and
            ($payment->card->iinRelation->getIssuer() === IFSC::UTIB) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($payment->card->iinRelation->supports(IIN\Flow::OTP) === true))
        {
            return true;
        }

        return false;
    }

    protected function canRunHeadlessOtpFlow(Payment\Entity $payment, $authenticationGateways=[]): bool
    {
        if (($this->isAuthTypeOtp($payment) === true) and
            ($this->merchant->isHeadlessEnabled() === true) and
            (is_null($payment->card) === false) and
            ($payment->card->iinRelation !== null) and
            ($payment->card->iinRelation->supports(IIN\Flow::HEADLESS_OTP) === true))

        {
            if (Payment\Gateway::supportsHeadlessBrowser($payment->getGateway(), $payment->card->iinRelation->getNetworkCode()) === true)
            {
                return true;
            }

            foreach ($authenticationGateways as $authenticationGateway)
            {
                if ((isset($authenticationGateway)) and
                    (Payment\Gateway::supportsHeadlessBrowser($authenticationGateway, $payment->card->iinRelation->getNetworkCode()) === true))
                {
                    return true;
                }
            }
        }

        return false;
    }

    protected function canRunPinFlow(Payment\Entity $payment): bool
    {
        if (($this->merchant->isFeatureEnabled(Feature\Constants::ATM_PIN_AUTH) === true) and
            (is_null($payment->card) === false) and
            ($payment->card->iinRelation !== null) and
            ($payment->card->iinRelation->supports(IIN\Flow::PIN) === true) and
            ($payment->terminal->isPin() === true))
        {
            return true;
        }

        return false;
    }
}
