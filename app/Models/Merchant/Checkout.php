<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Session;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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

        $data = $this->getMerchantPreferencesData($merchant, $input);

        $data['methods'] = $this->getMethods($merchant, $input);

        $this->checkAndFillSavedTokens($input, $merchant, $data);

        $this->checkAndAddOrderForTpv($merchant, $input, $data);

        return $data;
    }

    protected function tracePreferencesRequest($merchant, $mode, $input)
    {
        $this->app['trace']->info(
            TraceCode::CHECKOUT_PREFERENCES_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
                'cookie'      => Session::getId(),
                'input'       => $input
            ]);
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

            if ($customer->isLocal() === true)
            {
                $custData[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            }
            else if((Base\Utility::isUpdatedAndroidSdk($input)) and
                    ($appToken !== null) and
                    ($appToken->getMerchantId() === $this->repo->merchant->getSharedAccount()->getId()))
            {
                return;
            }

            $savedTokens = (new Customer\Token\Core)->fetchTokensByCustomer($customer);

            $custData =  array(
                'email'     => $customer->getEmail(),
                'contact'   => $customer->getContact(),
                'tokens'    => $savedTokens->toArrayPublic()
            );
        }
        catch (\Exception $ex)
        {
            $this->app['trace']->traceException($ex);
        }

        return $custData;
    }

    protected function checkAndFillAppTokenInputFromSession($merchant, $mode, array & $input)
    {
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

    protected function getMethods($merchant, $input)
    {
        $methodsArray = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false,
            'upi'           => false,
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
            $methodsArray['upi'] = $methods->isUpiEnabled();
        }

        return $methodsArray;
    }

    protected function checkAndAddOrderForTpv($merchant, $input, & $data)
    {
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

    protected function getMerchantPreferencesData($merchant, $input)
    {
        $data['options']['theme']['color'] = $merchant->getBrandColor();
        $data['options']['image'] = $merchant->getFullLogoUrlWithSize(self::CHECKOUT_LOGO_SIZE);
        $data['options']['remember_customer'] = $this->shouldEnableCardSaving($merchant, $input);
        $data['fee_bearer'] = $merchant->isFeeBearerCustomer();
        $data['version'] = 1;

        return $data;
    }

    protected function shouldEnableCardSaving($merchant, $input)
    {
        $key = 'checkcookie';

        $rememberCustomer = $merchant->isFeatureEnabled(Features::CARD_SAVING);

        // On few devices where browser is blocking cookies, disable card saving
        if (($rememberCustomer === true) and (isset($input[$key]) === true))
        {
            $expectedValue = $input[$key];

            $cookie = Request::cookie($key);

            if (($expectedValue === '1') and ($expectedValue !== $cookie))
            {
                $this->app['trace']->info(
                    TraceCode::CHECKOUT_PREFERENCES_COOKIE_CHECK,
                    [
                        'actual'           => $cookie,
                        'expected'         => $expectedValue,
                        'merchant_id'      => $merchant->getId(),
                        'rememberCustomer' => $rememberCustomer,
                    ]);

                $this->app['request']->session()->put($key, $expectedValue);
            }
        }

        return $rememberCustomer;
    }
}
