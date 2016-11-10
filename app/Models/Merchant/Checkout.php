<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Session;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\GatewayStatus\Absence;

class Checkout
{
    const CHECKOUT_LOGO_SIZE = 'medium';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function getPreferences($merchant, $mode, $input)
    {
        $this->tracePreferencesRequest($merchant, $mode, $input);

        $this->checkAndFillAppTokenInputFromSession($merchant, $mode, $input);

        $data = $this->getMerchantPreferencesData($merchant, $mode, $input);

        $data['methods'] = (new Methods\Core)->getFormattedMethods($merchant);

        $data['gateway_status'] = (new Absence\Core)->getFormattedCheckoutData($merchant);

        $this->checkAndFillSavedTokens($input, $merchant, $data);

        $this->checkAndAddOrderForTpv($merchant, $input, $data);

        return $data;
    }

    protected function tracePreferencesRequest($merchant, $mode, $input)
    {
        $sessionData = $this->app['request']->session()->all();

        $this->app['trace']->info(
            TraceCode::CHECKOUT_PREFERENCES_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
                'session'     => $sessionData,
                'input'       => $input
            ]);
    }

    protected function fetchTPVOrderInfo($input)
    {
        $orderData = null;

        try
        {
            $orderData = (new Order\Service)->fetchOrderBankAndAccountNumberForMerchant(
                $input[Payment\Entity::ORDER_ID]);
        }
        catch(\Exception $ex)
        {
            $this->app['trace']->traceException($ex);
        }

        return $orderData ;
    }

    protected function fetchCustomerData($input, $merchant)
    {
        $custData = null;

        try
        {
            list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp($input, $merchant);

            if ($customer === null)
            {
                return null;
            }

            if((Base\Utility::isUpdatedAndroidSdk($input)) and
                    ($appToken !== null) and
                    ($appToken->getMerchantId() === $this->repo->merchant->getSharedAccount()->getId()))
            {
                return null;
            }

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
        }
        catch (\Exception $ex)
        {
            $this->app['trace']->traceException($ex);
        }

        return $custData;
    }

    protected function checkAndFillAppTokenInputFromSession($merchant, $mode, array & $input)
    {
        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            return;
        }

        if ($merchant->isFeatureEnabled('cardsaving') === false)
        {
            return;
        }

        // Check if app token is present in session
        $key = $mode . '_' . Payment\Entity::APP_TOKEN;

        $appToken = Session::get($key);

        if (empty($appToken) === false)
        {
            $input[Payment\Entity::APP_TOKEN] = $appToken;
        }
    }

    protected function checkAndAddOrderForTpv($merchant, $input, & $data)
    {
        // If merchant is TPV enabled pass details for
        // current order as part of preferences
        if (($merchant->isTPVRequired()) and
            (isset($input[Payment\Entity::ORDER_ID])))
        {
            $orderData = $this->fetchTPVOrderInfo($input);

            if ($orderData !== null)
            {
                $data['order'] = $orderData;
            }
        }
    }

    protected function checkAndFillSavedTokens($input, $merchant, & $data)
    {
        try
        {
            // fetch customer data and saved cards data
            if ((isset($input[Payment\Entity::CUSTOMER_ID])) or
                (isset($input[Payment\Entity::APP_TOKEN])))
            {
                $custData = $this->fetchCustomerData($input, $merchant);

                if ($custData !== null)
                {
                    $data['customer'] = $custData;
                }
            }
            else if(isset($input['contact']))
            {
                $response = (new Customer\Service)->fetchGlobalCustomerStatus(
                    $input['contact'],
                    $input);

                $data['customer'] = array(
                    'saved'     => $response['saved'],
                    'contact'   => $input['contact']);

                if ($response['saved'] === true)
                {
                    if (isset($response['email']))
                    {
                        $data['customer']['email'] = $response['email'];
                    }

                    if (isset($response['tokens']))
                    {
                        $data['customer']['tokens'] = $response['tokens'];
                    }
                }
            }
        }
        catch (\Exception $ex)
        {
            $this->app['trace']->traceException($ex);
        }
     }

    protected function getMerchantPreferencesData($merchant, $mode, $input)
    {
        $data['options']['theme']['color'] = $merchant->getBrandColor();

        $data['options']['image'] = $merchant->getFullLogoUrlWithSize(self::CHECKOUT_LOGO_SIZE);

        $data['options']['remember_customer'] = $this->shouldEnableCardSaving($merchant, $mode, $input);

        $data['fee_bearer'] = $merchant->isFeeBearerCustomer();

        $data['version'] = 1;

        return $data;
    }

    protected function shouldEnableCardSaving($merchant, $mode, $input)
    {
        $rememberCustomer = $merchant->isFeatureEnabled(Features::CARD_SAVING);

        // if card saving is enabled, create a session and set a key
        if ($rememberCustomer === true)
        {
            $key = $mode . '_checkcookie';

            $this->app['request']->session()->put($key, '1');
        }

        return $rememberCustomer;
    }
}
