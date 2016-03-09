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
    public function getPreferences($merchant, $mode)
    {
        $methods = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false
        );

        $methods = (new Methods\Core)->getMethods($merchant);

        if ($methods !== null)
        {
            $methods['card'] = $methods->isCardEnabled();
            $methods['netbanking'] = $methods->toArrayWithBankNames();
            $methods['wallet'] = $methods->getEnabledWallets();
            $methods['emi'] = $methods->isEmiEnabled();
        }

        if ($mode === Mode::TEST)
        {
            $methods['card'] = true;
        }

        $data['methods'] = $methods;
        $data['options']['theme']['color'] = $merchant->getBrandColor();
        $data['fee_bearer'] = false;
        $data['version'] = 1;

        if ($merchant->isFeeBearerCustomer())
        {
            $data['fee_bearer'] = true;
        }

        return $data;
    }
}
