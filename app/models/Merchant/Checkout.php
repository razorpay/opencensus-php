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

        $savedMethods = null;

        if (isset($input['customer_id']))
        {
            $savedMethods = (new Customer\Methods\Core)->fetchMethodsbyCustomerId($merchant->getId(), $input['customer_id']);
        }
        else if (isset($input['app_id']))
        {
            $savedMethods = (new Customer\Methods\Core)->fetchMethodsByAppId($merchant->getId(), $input['app_id']);
        }

        if ($savedMethods !== null)
        {
            $data['customer_methods'] = $this->formatSavedMethods($savedMethods);
        }

        return $data;
    }

    protected function formatSavedMethods($input)
    {        
        $methods = array();

        foreach ($input as $method)
        {
            if($method->getMethod() === 'card')
            {
                $methods[] = $method->getFormattedMethod();                
            }
        }

        return $methods;
    }
}