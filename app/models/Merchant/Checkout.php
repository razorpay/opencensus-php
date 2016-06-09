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
use Models\Order;
use Models\Merchant\Webhook;
use EE\Exception;
use EE\Error;
use EE\Error\ErrorCode;
use Trace\Trace;
use Trace\TraceCode;
use Session;

class Checkout
{
    const CHECKOUT_LOGO_SIZE = 'medium';

    public function getPreferences($merchant, $mode, $input)
    {
        // check if appToken or device token is present in session
        $appToken = Session::get(Payment\Entity::APP_TOKEN);
        $deviceToken = Session::get(Payment\Entity::DEVICE_TOKEN);

        if (isset($input[Payment\Entity::APP_TOKEN]) === false)
        {
            $input[Payment\Entity::APP_TOKEN] = $appToken;
        }

        if (isset($input[Payment\Entity::DEVICE_TOKEN]) === false)
        {
            $input[Payment\Entity::DEVICE_TOKEN] = $deviceToken;
        }

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
            $netbankingEnabled = $methods->isNetbankingEnabled();
            if ($netbankingEnabled === true)
            {
                $methodsArray['netbanking'] = $methods->toArrayWithBankNames();
            }
            $methodsArray['wallet'] = $methods->getEnabledWallets();
            $methodsArray['emi'] = $methods->isEmiEnabled();
        }

        if ($mode === Mode::TEST)
        {
            $methods['card'] = true;
        }

        $data['methods'] = $methodsArray;
        $data['options']['theme']['color'] = $merchant->getBrandColor();
        $data['options']['image'] = $merchant->getLogoUrl(self::CHECKOUT_LOGO_SIZE);
        $data['fee_bearer'] = false;
        $data['version'] = 1;

        if ($merchant->isFeeBearerCustomer())
        {
            $data['fee_bearer'] = true;
        }

        //fetch customer data and saved cards data
        if ((isset($input[Payment\Entity::CUSTOMER_ID])) or
            (isset($input[Payment\Entity:Payment\Entity::APP_TOKEN)))
        {
            $custData = $this->fetchCustomerData($input, $merchant);

            if ($custData !== null)
            {
                $data['customer'] = $custData;
            }
        }
        elseif ((isset($input[Payment\Entity::DEVICE_TOKEN])) and
                (isset($input['contact'])))
        {
            $response = (new Customer\Service)->validateDeviceToken(
                $input[Payment\Entity::DEVICE_TOKEN],
                $input);

            $data['customer'] = array(
                'contact'   => $input['contact'],
                'valid'     => $response['valid']);

            if ($response['valid'] === true)
            {
                $data['customer'][Payment\Entity::APP_TOKEN] = $response[Payment\Entity::APP_TOKEN];
            }
        }
        elseif (isset($input['contact']))
        {
            $response = (new Customer\Service)->fetchCustomerStatus($input['contact']);

            $data['customer'] = array(
                'contact'   => $input['contact'],
                'saved'     => $response['saved']);
        }

        // If merchant is TPV enabled pass details for
        // current order as part of preferences
        if (($merchant->isTPVRequired()) and
            (isset($input[Payment\Entity::ORDER_ID])))
        {
            $orderData = $this->fetchTPVOrderInfo($input, $merchant);

            if ($orderData !== null)
            {
                $data['order'] = $orderData;
            }
        }

        return $data;
    }

    protected function fetchTPVOrderInfo($input, $merchant)
    {
        $orderData = null;

        try
        {
            $orderData = (new Order\Service)->fetchOrderBankAndAccountNumberForMerchant(
                                    $input[Payment\Entity::ORDER_ID], $merchant->getId());
        }
        catch(\Exception $ex)
        {
            //;
        }

        return $orderData ;
    }

    protected function fetchCustomerData($input, $merchant)
    {
        $custData = null;

        try
        {
            list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp($input, $merchant);

            assert($customer !== null);

            $savedTokens = (new Customer\Token\Core)->fetchTokensByCustomer($customer);

            $custData =  array(
                'email'     => $customer->getEmail(),
                'contact'   => $customer->getContact(),
                'tokens'    => $savedTokens->toArrayPublic()
            );

            if ($customer->isLocal() === true)
            {
                $custData[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            }
            else
            {
                $custData[Payment\Entity::APP_TOKEN] = $customerApp->getPublicId();
            }
        }
        catch (\Exception $e)
        {
            //log error and ignore
            //s($e);
        }

        return $custData;
    }
}
