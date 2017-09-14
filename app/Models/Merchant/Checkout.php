<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Session;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Emi;
use RZP\Models\Feature;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Plan\Subscription;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class Checkout
{
    const CHECKOUT_LOGO_SIZE = 'medium';
    const CHECKOUT_DEFAULT_THEME_COLOR = '#3594E2';

    const SUBSCRIPTION_ID    = 'subscription_id';

    protected $app;
    /**
     * @var Trace
     */
    protected $trace;
    protected $repo;

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

        $this->checkAndAddDetailsForOrder($input, $merchant, $data);

        $this->checkAndAddDetailsForInvoice($input, $merchant, $data);

        $this->checkAndAddDetailsForSubscription($input, $merchant, $data);

        $this->checkAndFillOfferDetails($merchant, $input, $data);

        $this->checkAndFillGatewayDowntime($merchant, $data);

        $this->tracePreferencesResponse($merchant, $data);

        return $data;
    }

    protected function checkAndAddDetailsForOrder(
        array $input,
        Merchant\Entity $merchant,
        array & $data)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            return;
        }

        $orderId = $input[Payment\Entity::ORDER_ID];

        $data['order'] = (new Order\Core)->getFormattedDataForCheckout($orderId, $merchant);
    }

    protected function checkAndAddDetailsForInvoice(
        array $input,
        Merchant\Entity $merchant,
        array & $data)
    {
        if (empty($input[Payment\Entity::INVOICE_ID]) === true)
        {
            return;
        }

        $invoiceId = $input[Payment\Entity::INVOICE_ID];

        // Gets formatted invoice data which includes invoice and customer details.

        $invoiceData = (new Invoice\Core)->getFormattedInvoiceData($invoiceId, $merchant);

        $data['invoice'] = $invoiceData['invoice'];

        // - Use invoice's customer data if no customer data exists already
        // - Override existing customer data with invoice's customer details if exists.

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

        // Add invoice's order details

        $data['order'] = $invoiceData['order'];
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

    protected function fetchCustomerData(array $input, Entity $merchant)
    {
        $custData = null;

        try
        {
            //
            // For the second 2FA in global flow also, we will have the app_token. Hence,
            // in this usage (preferences) of getCustomerAndApp, we don't need to have
            // the global_customer_id in the input.
            //
            list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp($input, $merchant);

            if ($customer === null)
            {
                return null;
            }

            if ((Base\Utility::isUpdatedAndroidSdk($input)) and
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
                // TODO: Figure out a way to tell the checkout whether it's local/global flow.
                $custData[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex, Trace::WARNING, TraceCode::CHECKOUT_PREFERENCES_EXCEPTION, $input);
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

    protected function checkAndFillSavedTokens(array $input, Entity $merchant, array & $data)
    {
        // we don't return the customer data if request is jsonp
        if (isset($input['callback']) === true)
        {
            return;
        }

        if (isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
        {
            $this->doCustomerProcessingForSubscription($input, $data, $merchant);
        }

        //
        // To recognize the flow as local, the only way is, to check
        // if `customer_id` is present in the input.
        // If it's not, we consider it as global by default.
        //
        // In case of subscriptions, the customer_id is added to the input
        // if subscription has a customer associated with it and the customer
        // associated does not have any global customer associated.
        //

        $data['global'] = true;

        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            $data['global'] = false;
        }

        try
        {
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
            else if (empty($input['contact']) === false)
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
            $this->trace->traceException(
                $ex, Trace::WARNING, TraceCode::CHECKOUT_PREFERENCES_EXCEPTION, $input);
        }
    }

    /**
     * If a customer is associated with the subscription, we assume
     * that the merchant wants the local cards, and go ahead with
     * that flow. We add customer_id to the input to force the local flow.
     * If no customer is associated with the subscription, we go
     * ahead with the global flow. (It involves more logic, though)
     *
     * @param array  $input
     * @param array  $data
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    protected function doCustomerProcessingForSubscription(array & $input, array $data, Merchant\Entity $merchant)
    {
        //
        // Since customer_id will always be associated with the subscription,
        // it should not be sent in the input. If it is sent, we would
        // not know whether to use the customer associated with the subscription
        // or the one sent in the input.
        // If the customer is not present in subscription AND not sent in
        // the input, we use/create global customer.
        //
        if (empty($input[Payment\Entity::CUSTOMER_ID]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT,
                null,
                $input);
        }

        $subscription = $this->repo->subscription->findByPublicIdAndMerchant(
            $input[Payment\Entity::SUBSCRIPTION_ID], $merchant);

        //
        // If a customer is not associated with the subscription already,
        // we go ahead with the global flow. But, for global flow, we need
        // to ensure that noflashcheckout is not enabled for the merchant.
        //
        // For the first 2FA txn, subscription.customer_id will always be null
        // for a global flow.
        // For the next non-2FA txn, this will NOT be null and the global flow
        // is handled in the ELSE condition.
        //
        if ($subscription->hasCustomer() === false)
        {
            //
            // Card saving is not enabled for the merchant.
            //
            if ($data['options']['remember_customer'] === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED,
                    null,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'input'             => $input
                    ]);
            }
        }
        //
        // If a customer is associated with the subscription, it can mean
        // local OR global.
        //
        else
        {
            if ($subscription->followLocalFlow() === true)
            {
                $input[Payment\Entity::CUSTOMER_ID] = Customer\Entity::getSignedId($subscription->getCustomerId());
            }
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
        $isEmailOrContactOptional = (($merchant->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL) === true) or
                                     ($merchant->isFeatureEnabled(Feature\Constants::CONTACT_OPTIONAL) === true));

        $rememberCustomer = (($merchant->isFeatureEnabled(Feature\Constants::NOFLASHCHECKOUT) === false) and
                            ($isEmailOrContactOptional === false));

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

        // If offer method is empty we do not update methods in response
        if (empty($method) === true)
        {
            return ;
        }

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
                if ($offer->getIssuer() !== null)
                {
                    $bankCode = $offer->getIssuer();

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
                if ($offer->getIssuer() !== null)
                {
                    $wallet = $offer->getIssuer();

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
            if ($merchant->isFeatureEnabled(Feature\Constants::HIDE_DOWNTIMES) === false)
            {
                $downtimeData = (new Downtime\Service)->getPublicGatewayDowntimeData();

                if (empty($downtimeData) === false)
                {
                    $data['downtime'] = $downtimeData;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::WARNING, TraceCode::CHECKOUT_PREFERENCES_EXCEPTION);
        }
    }
}
