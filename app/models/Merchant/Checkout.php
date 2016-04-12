<?php

namespace Models\Merchant;

use Constants\Mode;
use Models\Base;
use Models\Customer;
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
    public function getPreferences($merchant, $mode, $input)
    {
        $methodsArray = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false
        );

        $methods = (new Methods\Core)->getMethods($merchant);

        if ($methods !== null)
        {
            $methodsArray['card'] = $methods->isCardEnabled();
            $methodsArray['netbanking'] = $methods->toArrayWithBankNames();
            $methodsArray['wallet'] = $methods->getEnabledWallets();
            $methodsArray['emi'] = $methods->isEmiEnabled();
        }

        if ($mode === Mode::TEST)
        {
            $methods['card'] = true;
        }

        $data['methods'] = $methodsArray;
        $data['options']['theme']['color'] = $merchant->getBrandColor();
        $data['fee_bearer'] = false;
        $data['version'] = 1;

        if ($merchant->isFeeBearerCustomer())
        {
            $data['fee_bearer'] = true;
        }

        //fetch saved cards data if app_id or customer_id is set

        $savedTokens = null;

        if (isset($input['customer_id']))
        {
            $savedTokens = (new Customer\Token\Core)->fetchTokensbyCustomerId($merchant->getId(), $input['customer_id']);
        }
        else if (isset($input['app_id']))
        {
            $savedTokens = (new Customer\Token\Core)->fetchTokensByAppId($merchant->getId(), $input['app_id']);
        }

        if (($savedTokens !== null) and ($savedTokens->count() !== 0))
        {
            $data['tokens'] = $savedTokens->toArrayPublic();
        }

        return $data;
    }
}