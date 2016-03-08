<?php

namespace Models\Merchant;

use Constants\Mode;
use Models\Base;
use Models\Merchant;
use Models\Card;
use Models\Key;
use Models\Payment;
use Models\Pricing;
use Models\Terminal;
use Models\Merchant\Webhook;
use EE\Exception;
use EE\Error\ErrorCode;

use Trace\TraceCode;

class Checkout
{
    public function getPreferences($merchant)
    {
        $methods = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false
        );

        $methods = (new Methods\Core)->getMethods($this->merchant);

        if ($methods !== null)
        {
            $methods['card'] = $methods->isCardEnabled();
            $methods['netbanking'] = $methods->toArrayWithBankNames();
            $methods['wallet'] = $methods->getEnabledWallets();
            $methods['emi'] = $methods->isEmiEnabled();
        }

        if ($this->mode === Mode::TEST)
        {
            $methods['card'] = true;
        }

        $data['methods'] = $methods;
        $data['brand_color'] = $merchant->getBrandColor();
        $data['fee_bearer'] = false;

        return $data;
    }
}
