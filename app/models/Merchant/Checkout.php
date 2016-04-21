<?php

namespace Models\Merchant;

use Constants\Mode;
use Models\Base;
use Models\Customer;
use Models\Customer\Token;
use Models\Merchant;
use Models\Card;
use Models\Key;
use Models\Payment;
use Models\Pricing;
use Models\Terminal;
use Models\Merchant\Webhook;
use EE\Exception;
use EE\Error;
use EE\Error\ErrorCode;
use Trace\Trace;
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

        //fetch customer data and saved cards data
        if ((isset($input[Payment\Entity::CUSTOMER_ID])) or
            (isset($input[Payment\Entity::APP_ID])))
        {
            $custData = $this->fetchCustomerData($input, $merchant);

            if ($custData !== null)
            {
                $data['customer'] = $custData;
            }
        }

        return $data;
    }

    protected function fetchCustomerData($input, $merchant)
    {
        $custData = null;

        try
        {
            list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp($input, $merchant);

            assert($customer !== null);

            $savedTokens = (new Customer\Token\Service)->fetchMultiple($customer->getPublicId());

            $custData =  array(
                'email'     => $customer->getEmail(),
                'contact'   => $customer->getContact(),
                'tokens'    => $savedTokens
            );
        }
        catch (\Exception $e)
        {
            //log error and ignore
            //s($e);
        }

        return $custData;
    }
}