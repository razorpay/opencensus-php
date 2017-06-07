<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Session;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Emi;
use RZP\Models\Plan\Subscription;
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

    const SUBSCRIPTION_ID    = 'subscription_id';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }

    public function getPreferences(Entity $merchant, $mode, array $input)
    {
        $this->tracePreferencesRequest($merchant, $mode, $input);

        $this->checkAndFillAppTokenInputFromSession($merchant, $mode, $input);

        $data = $this->getMerchantPreferencesData($merchant, $mode);

        $data['methods'] = (new Methods\Core)->getFormattedMethods($merchant);

        $this->checkAndFillSavedTokens($input, $merchant, $data);

        $this->checkAndAddOrderForTpv($merchant, $input, $data);

        $this->checkAndAddDetailsForInvoice($input, $merchant, $data);

        $this->checkAndAddDetailsForSubscription($input, $merchant, $data);

        $this->checkAndFillOfferDetails($merchant, $input, $data);

        $this->checkAndFillGatewayDowntime($merchant, $data);

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

    protected function checkAndAddDetailsForSubscription(array $input, Merchant\Entity $merchant, array & $data)
    {
        if (empty($input[self::SUBSCRIPTION_ID]) === true)
        {
            return;
        }

        $subscriptionId = $input[self::SUBSCRIPTION_ID];

        $data['subscription'] = (new Subscription\Core)->getFormattedSubscriptionData($merchant, $subscriptionId);
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

            //
            // This case comes when customer_id is sent in the input (always local customer).
            //
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

            //
            // Since customer_id will always be associated with the subscription,
            // it should not be sent in the input. If it is sent, we would
            // not know whether to use the customer associated with the subscription
            // or the one sent in the input.
            // If the customer is not present in subscription AND not sent in
            // the input, we use/create global customer.
            //
            if (isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
            {
                if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
                {
                    // TODO: Throw an exception
                }

                $subscription = $this->repo->subscription->findByPublicIdAndMerchant(
                    $input[Payment\Entity::SUBSCRIPTION_ID], $merchant);

                //
                // If a customer is associated with the subscription, we assume
                // that the merchant wants the local cards, and go ahead with
                // that flow. We add customer_id to the input to force the local flow.
                // If no customer is associated with the subscription, we go
                // ahead with the global flow.
                //
                $customerId = $subscription->getCustomerId();

                if ($customerId === null)
                {
                    //
                    // Card saving is not enabled for the merchant.
                    //
                    if ($data['options']['remember_customer'] === false)
                    {
                        // TODO: Throw an exception
                    }
                    else
                    {
                        $input[Payment\Entity::CUSTOMER_ID] = $customerId;
                    }
                }
            }

            //
            // We get the customer using either the customer_id or the app_token.
            // If customer_id is present in the input, it means that it's a local customer.
            // Since the merchant will not have customer_id of a global customer.
            // If app_token is present in the input, it means that it's a global customer.
            // It also means that the user is already logged in.
            // If customer_id AND app_token both are present, we give preference to the customer_id.
            //
            if ((isset($input[Payment\Entity::CUSTOMER_ID])) or
                (isset($input[Payment\Entity::APP_TOKEN])))
            {
                $custData = $this->fetchCustomerData($input, $merchant);

                if ($custData !== null)
                {
                    $data['customer'] = $custData;
                }
            }
            //
            // In cases where neither app_token is present nor any customer_id,
            // we use the contact number + device_token to search for an existing
            // global customer. We receive device token only in case of
            // mobile (sometimes). (contact will always be there anyway).
            // If device token is valid, we fetch the tokens and send across.
            // Otherwise, we don't send any tokens. (Refer the checkout flow to
            // find out what happens at checkout when we don't send any tokens).
            //
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

    protected function getMerchantPreferencesData(Entity $merchant, $mode)
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

        $orderId = $input[Payment\Entity::ORDER_ID] ?? null;

        if ($orderId !== null)
        {
            $orderOffer = $offerCore->fetchForOrder($orderId, $merchant);

            if ($orderOffer !== null)
            {
                // For offer applied on a particular order only enable methods eligible for the
                // offer. Customer won't be able to select other payment methods
                $this->updateMethodsToEnableOnCheckout($orderOffer, $data);

                $data['offers'] = [
                    $orderOffer->toArrayCheckout()
                ];

                return;
            }
        }

        $this->checkAndFillNonOrderOffers($merchant, $data);
    }

    protected function checkAndFillNonOrderOffers(Merchant\Entity $merchant, array & $data)
    {
        $nonOrderOffers = (new Offer\Core)->fetchMerchantOffersForCheckout($merchant);

        foreach ($nonOrderOffers as $offer)
        {
            $data['offers'][] = $offer->toArrayCheckout();
        }
    }

    protected function updateMethodsToEnableOnCheckout(Offer\Entity $offer, array & $data)
    {
        $method = $offer->getPaymentMethod();

        $enabledBanks = $data['methods']['netbanking'];

        $enabledWallets = $data['methods']['wallet'];

        $data['methods'] = [
            'entity' => 'methods'
        ];

        switch ($method)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:

                // For card offers only set card method to true
                $data['methods']['card'] = true;

                break;

            case Payment\Method::NETBANKING:

                // Only allow payments through supported banks
                $data['methods']['netbanking'] = $enabledBanks;

                // Only allow payment through specific bank if network is specified
                if ($offer->getPaymentNetwork() !== null)
                {
                    $bankCode = $offer->getPaymentNetwork();

                    $bankName = Netbanking::getName($bankCode);

                    $data['methods']['netbanking'] = [
                        $bankCode => $bankName
                    ];
                }

                break;

            case Payment\Method::WALLET:

                // Only allow payments through supported wallets
                $data['methods']['wallet'] = $enabledWallets;

                // For wallet offers if network is specified, lock method to only that wallet
                if ($offer->getPaymentNetwork() !== null)
                {
                    $wallet = $offer->getPaymentNetwork();

                    $data['methods']['wallet'] = [
                        $wallet
                    ];
                }

                break;

            // For other methods like UPI, we currently handle it here, by just
            // enabling the particular method.
            default:
                $data['methods'][$method] = true;

                break;
        }
    }

    public function checkAndFillGatewayDowntime(Merchant\Entity $merchant, array & $data)
    {
        try
        {
            if ($merchant->isFeatureEnabled(Feature\Constants::EXPOSE_DOWNTIMES) === true)
            {
                $downtimeData = (new Downtime\Core)->getFormattedGatewayDowntimeCheckoutData($merchant);

                if (empty($downtimeData) === false)
                {
                    $data['downtime'] = $downtimeData;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);
        }
    }
}
