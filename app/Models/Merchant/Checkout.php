<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Session;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Emi;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime;

class Checkout
{
    const CHECKOUT_LOGO_SIZE = 'medium';
    const CHECKOUT_DEFAULT_THEME_COLOR = '#3594E2';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function getPreferences(Entity $merchant, $mode, array $input)
    {
        $this->tracePreferencesRequest($merchant, $mode, $input);

        $this->checkAndFillAppTokenInputFromSession($merchant, $mode, $input);

        $data = $this->getMerchantPreferencesData($merchant, $mode, $input);

        $data['methods'] = (new Methods\Core)->getFormattedMethods($merchant);

        $this->checkAndFillSavedTokens($input, $merchant, $data);

        $this->checkAndAddOrderForTpv($merchant, $input, $data);

        $this->checkAndAddDetailsForInvoice($input, $merchant, $data);

        $this->checkAndFillOfferDetails($merchant, $input, $data);

        $this->tracePreferencesResponse($merchant, $data);

        return $data;
    }

    protected function checkAndAddDetailsForInvoice(
        array $input, Merchant\Entity $merchant, array & $data)
    {
        if (empty($input['invoice_id']) === true)
        {
            return;
        }

        $invoiceId = $input['invoice_id'];

        $invoiceCore = new Invoice\Core;

        $invoiceData = $invoiceCore->getFormattedInvoiceData($invoiceId, $merchant);

        $data['invoice'] = $invoiceData['invoice'];

        // If invoice's customer data is set, merge it to existing data
        if (isset($invoiceData['customer']))
        {
            if (isset($data['customer']))
            {
                $data['customer'] = array_merge($data['customer'], $invoiceData['customer']);
            }
            else
            {
                $data['customer'] = $invoiceData['customer'];
            }
        }
    }

    protected function tracePreferencesRequest(Entity $merchant, $mode, array $input)
    {
        $sessionData = $this->app['request']->session()->all();

        $this->trace->info(
            TraceCode::CHECKOUT_PREFERENCES_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
                'session'     => $sessionData,
                'input'       => $input
            ]);
    }

    protected function tracePreferencesResponse(Entity $merchant, array $response)
    {
        $this->trace->info(
            TraceCode::CHECKOUT_PREFERENCES_RESPONSE,
            [
                'merchant_id' => $merchant->getId(),
                'response' => $response,
            ]);
    }

    protected function fetchTPVOrderInfo(array $input)
    {
        $orderData = null;

        try
        {
            $orderData = (new Order\Service)->fetchOrderBankAndAccountNumberForMerchant(
                $input[Payment\Entity::ORDER_ID]);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        return $orderData;
    }

    protected function fetchCustomerData(array $input, Entity $merchant)
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
            $this->trace->traceException($ex);
        }

        return $custData;
    }

    protected function checkAndFillAppTokenInputFromSession(Entity $merchant, $mode, array & $input)
    {
        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            return;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::NOFLASHCHECKOUT) === true)
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

    protected function checkAndAddOrderForTpv(Entity $merchant, array $input, array & $data)
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

    protected function checkAndFillSavedTokens(array $input, Entity $merchant, array & $data)
    {
        try
        {
            /// we don't return the customer data if request is jsonp
            if (isset($input['callback']) === true)
            {
                return;
            }

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
            $this->trace->traceException($ex);
        }
    }

    protected function getMerchantPreferencesData(Entity $merchant, $mode, array $input)
    {
        $data['options']['theme']['color'] = $merchant->getBrandColor();

        $data['options']['image'] = $merchant->getFullLogoUrlWithSize(self::CHECKOUT_LOGO_SIZE);

        $data['options']['remember_customer'] = $this->shouldEnableCardSaving($merchant, $mode);

        $data['fee_bearer'] = $merchant->isFeeBearerCustomer();

        $data['version'] = 1;

        $optionalInputConfig = $merchant->getOptionalInputConfig();

        if (empty($optionalInputConfig) === false)
        {
            $data['optional'] = $optionalInputConfig;
        }

        return $data;
    }

    protected function shouldEnableCardSaving(Entity $merchant, $mode)
    {
        $rememberCustomer = ($merchant->isFeatureEnabled(Feature\Constants::NOFLASHCHECKOUT) === false);

        // if card saving is enabled, create a session and set a key
        if ($rememberCustomer === true)
        {
            $key = $mode . '_checkcookie';

            $this->app['request']->session()->put($key, '1');
        }

        return $rememberCustomer;
    }

    public function checkAndFillOfferDetails(Merchant\Entity $merchant, array $input, array & $data)
    {
        $offerCore = new Offer\Core;

        // Temporaily commenting fetching shared offers
        // $sharedOffers = $offerCore->fetchSharedOffers();

        $orderId = $input[Payment\Entity::ORDER_ID] ?? null;

        if ($orderId === null)
        {
            return;
        }

        $directOffer = $offerCore->fetchForOrder($orderId, $merchant);

        if ($directOffer !== null)
        {
            $data['offers'] = $directOffer->toArrayCheckout();
        }
    }
}
