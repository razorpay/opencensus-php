<?php

namespace RZP\Models\Merchant;

use App;
use Request;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Constants\Mode;
use RZP\Models\Locale\Core as Locale;
use Session;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Emi;
use RZP\Models\Card;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Contact;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Feature;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Offer\Checker;
use RZP\Base\RepositoryManager;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Currency\Currency;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\Config as Config;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Services\DE\PersonalisationService;
use RZP\Services\Mock\DE\PersonalisationService as MockPersonalisationService;
use RZP\Models\SubscriptionRegistration\Validator as SubscriptionRegistrationValidator;
use RZP\Models\Key;
use RZP\Models\TrustedBadge;
use RZP\Models\Customer\AppToken;

class Checkout
{
    const CHECKOUT_LOGO_SIZE            = 'medium';
    const CHECKOUT_DEFAULT_THEME_COLOR  = '#3594E2';

    const SUBSCRIPTION_ID               = 'subscription_id';

    const DYNAMIC_WALLET_FLOW = 'dynamic_wallet_flow';

    protected $app;
    /**
     * @var Trace
     */
    protected $trace;
    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var Subscription\Entity
     */
    protected $subscription;

    /**
     * @var Order\Entity
     */
    protected $order;

    protected $isCardVaultUp;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }

    public function getPreferences(Entity $merchant, $mode, array $input)
    {
        Locale::setLocale($input, $merchant->getId());

        $this->tracePreferencesRequest($merchant, $mode, $input);

        $this->isCardVaultUp = $this->app['card.cardVault']->ping();

        $this->checkAndFillAppTokenInputFromSession($merchant, $mode, $input);

        $data = $this->getMerchantPreferencesData($merchant, $mode);

        $data['activated'] = $merchant->getActivated();

        $data[Entity::METHODS] = (new Methods\Core)->getFormattedMethods($merchant);

        $data[Entity::METHODS] = (new Methods\Core)->addUpiType($merchant, $data[Entity::METHODS]);

        $this->checkAndFillSavedTokens($input, $merchant, $data,$mode);

        $this->checkAndAddDetailsForOrder($input, $merchant, $data);

        $this->checkAndAddDetailsForInvoice($input, $merchant, $data, $mode);

        // This should be after `checkAndFillSavedTokens` because this expects
        // `$this->subscription` to be set.
        $this->checkAndAddDetailsForSubscription($input, $merchant, $data);

        $this->filterMethodsBasedOnAmount($data, $input);

        $this->filterMethodBasedOnRecurring($data, $input);

        $this->checkAndFillOfferDetails($merchant, $input, $data, $mode);

        $this->checkAndFillGatewayDowntime($merchant, $data);

        $this->checkAndFillPaymentDowntime($merchant, $data);

        $this->fillEnabledFeatures($merchant, $data);

        $data[Entity::METHODS] = (new Methods\Core)->enableOrDisableMethodsBasedOnTerminals($merchant, $data[Entity::METHODS], $mode);

        $this->checkAndFillPartnerUrl($merchant, $data);

        $this->checkAndFillContactDetails($input, $merchant, $data);

        $this->checkAndFillOrgDetails($merchant, $data);

        $this->checkAndFillAppDetails($input, $merchant, $data, $mode);

        if ((isset($input['personalisation']) === true) and
            (($input['personalisation'] === true) or
              ($input['personalisation'] === '1')))
        {
            $this->fillPreferredMethods($merchant, $input, $data);
        }

        $this->fillRTBDetails($merchant, $data);

        $this->fill1ccExperimentDetails($merchant, $data);

        $this->fill1ccCityAutopopulateExperimentDetails($merchant, $data);

        $this->fillCovidReliefDetails($merchant, $data, $mode);

        return $data;
    }

    protected function fillRTBDetails(Entity $merchant, array & $data): void
    {
        $data['rtb'] = (new TrustedBadge\Core())->isTrustedBadgeLiveForMerchant($merchant->getId());

        if($data['rtb'] === true)
        {
            $contact = $data['customer']['contact'] ?? '';

            $data['rtb_experiment'] = (new TrustedBadge\Core())->getRTBExperimentDetails($merchant->getId(), $contact);
        }
    }

    protected function fillCovidReliefDetails(Entity $merchant, array & $data, $mode)
    {
        $featureEnabled = $merchant->isFeatureEnabled(Feature\Constants::COVID_19_RELIEF);

        $covidRazorX = $this->app->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::COVID_19_DONATION_SHOW,
            $mode
        );

        $data['show_donation'] = $featureEnabled === true && $covidRazorX === 'on';
    }

    protected function fill1ccExperimentDetails(Entity $merchant, array &$data): void
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::ONE_CLICK_CHECKOUT) === false)
        {
            return;
        }
        try
        {
            $properties = [
                'id'            => $merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.1cc_splitz_experiment_id'),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $data['1cc_experiment'] = $response['response']['variant']['name'] ?? null;
        }
        catch (\Exception $e)
        {
            $data['1cc_experiment'] = [];
        }
    }

    protected function fill1ccCityAutopopulateExperimentDetails(Entity $merchant, array &$data): void
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::ONE_CLICK_CHECKOUT) === false)
        {
            return;
        }
        try
        {
            $properties = [
                'id'            => $merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.1cc_city_autopopulate_splitz_experiment_id'),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $data['1cc_city_autopopulate_disable'] = $response['response']['variant']['name'] ?? null;
        }
        catch (\Exception $e)
        {
            $data['1cc_city_autopopulate_disable'] = null;
        }
    }

    protected function checkAndFillAppDetails(array $input, Entity $merchant, array &$data, $mode)
    {
        $data['methods']['app_meta'] = [];

        $this->fillCredAppDetails($input, $merchant, $data, $mode);
    }

    protected function fillCredAppDetails(array $input, Entity $merchant, array &$data, $mode)
    {
        $cred_meta = [];

        if (empty($data[Entity::METHODS][Payment\Method::APP][Payment\Gateway::CRED]) === false)
        {
            unset($data['methods']['custom_text']['cred']);

            if (isset($input['cred_offer_experiment']) === true)
            {
                $experimentResult = $input['cred_offer_experiment'];
            }
            else
            {
                $experimentResult = $this->app->razorx->getTreatment(
                    $this->app['request']->getTaskId(),
                    Merchant\RazorxTreatment::CRED_OFFER_SUBTEXT,
                    $mode
                );
            }

            $cred_meta['experiment'] = $experimentResult;
        }

        if ((empty($data[Entity::METHODS][Payment\Method::APP][Payment\Gateway::CRED]) === false) and
            (empty($data['customer']['contact']) === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::CRED_MERCHANT_CONSENT) === true) and
            ($this->isCredEligibilityConfigEnabled() === true))
        {
            $hit_eligibility = true;

            try {
                list($credInput, $options) = $this->getInputAndOptionsForCred($input, $data, $merchant);

                $response = (new Payment\Validation\Cred())->processValidation($credInput, $options);

                if (($response['success'] === true) and
                    (empty($response['data']['offer']) === false))
                {
                    $cred_meta['offer'] = $response['data']['offer'];

                    // setting custom text in all cases
                    //$data['methods']['custom_text']['cred'] = $response['data']['offer']['description'];
                }

                $hit_eligibility = false;

                $cred_meta['user_eligible'] = true;
            }
            catch (Exception\GatewayTimeoutException $timeoutException)
            {
                // do nothing, just logging
                $this->trace->traceException($timeoutException, Trace::WARNING);
            }
            catch (Exception\GatewayErrorException $ex)
            {
                $cred_meta['user_eligible'] = false;

                $hit_eligibility = false;
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception, Trace::WARNING, TraceCode::CHECKOUT_PREFERENCES_EXCEPTION, $input);
            }

            $cred_meta['hit_eligibility'] = $hit_eligibility;
        }

        if (empty($cred_meta) === false)
        {
            $data['methods']['app_meta']['cred'] = $cred_meta;
        }
    }

    protected function checkAndFillOrgDetails(Entity $merchant, array &$data)
    {
        $orgId = $merchant->getOrgId();

        if (isset($orgId) === true)
        {
            $org = $this->repo->org->find($orgId);

            if (isset($org) === true)
            {

                if($orgId === "100000razorpay")
                {

                    $data['org'] = ["isOrgRazorpay" => true, "checkout_logo_url" => $org->getCheckoutLogo()];

                }
                else
                {

                    $data['org'] = ["isOrgRazorpay" => false, "checkout_logo_url" => $org->getCheckoutLogo()];

                }

            }
        }
    }

    protected function checkAndAddDetailsForOrder(
        array $input,
        Merchant\Entity $merchant,
        array & $data)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            $configId = (empty($input['checkout_config_id']) === false) ? $input['checkout_config_id'] : null;

            (new Config\Core())->getFormattedConfigForCheckout($configId, $merchant->getId(), $data);

            return;
        }

        $orderId = $input[Payment\Entity::ORDER_ID];

        $order = $this->setOrGetOrder($orderId, $merchant);

        $this->checkNachStatus($order, $merchant);

        $data['order'] = (new Order\Core)->getFormattedDataForCheckout($order, $merchant);

        $configId = (isset($order->checkout_config_id) === true) ? Payment\Config\Entity::getSignedId($order->checkout_config_id) : null;

        (new Config\Core())->getFormattedConfigForCheckout($configId, $merchant->getId(), $data);

        $this->resetMethodsIfValidBanksPresent($data, $order, $merchant);
    }

    protected function checkNachStatus(Order\Entity $order, Merchant\Entity $merchant)
    {
        if ( $order->getMethod() === Payment\Method::NACH ) {

            $invoice = $order->invoice;

            if (empty($invoice) === true) {
                return;
            }

            (new SubscriptionRegistrationValidator())->validateInvoiceCreatedForTokenRegistration($invoice);

            $subscriptionRegistration = $invoice->tokenRegistration;

            (new SubscriptionRegistrationValidator())->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);
        }
        return;
    }

    protected function filterMethodsBasedOnAmount(array & $data, $input)
    {
        if (isset($data['order']['amount']) === true)
        {
            $amount = $data['order']['amount'];
        }
        elseif (isset($input['amount']) === true)
        {
            $amount = $input['amount'];
        }
        else
        {
            return;
        }

        foreach (Payment\Gateway::$minAmountForMethodAndGateway as $method => $gatewaysWithMinimumAmount)
        {
            if (isset($data[Entity::METHODS][$method]) === false)
            {
                continue;
            }

            foreach ($gatewaysWithMinimumAmount as $gatewayKey => $minAmount)
            {

                if (in_array($gatewayKey, $data[Entity::METHODS][$method]) and ($amount < $minAmount))
                {
                    unset($data[Entity::METHODS][$method][$gatewayKey]);
                }
            }
        }
    }

    /**
     * Disable CRED as a payment method for recurring payments.
     *
     * @see https://razorpay.slack.com/archives/CB09CM13J/p1634363518234700
     *
     * @param array $data
     * @param array $input
     */
    protected function filterMethodBasedOnRecurring(array & $data, array $input)
    {
        if (isset($input['recurring']) === true and ($input['recurring'] === '1' or $input['recurring'] === 'true'))
        {
            $data[Entity::METHODS][Payment\Method::APP]['cred'] = 0;
        }
    }

    protected function setOrGetOrder(string $orderId, Merchant\Entity $merchant)
    {
        return $this->order ?? $this->repo->order->findByPublicIdAndMerchant($orderId, $merchant);
    }

    protected function resetMethodsIfValidBanksPresent(
        array & $data,
        Order\Entity $order,
        Merchant\Entity $merchant)
    {
        $bankCode = $order->getBank();

        if ($bankCode !== null)
        {
            // Order bank should be present in the list of netbanking banks.
            if (isset($data['methods'][Payment\Method::NETBANKING][$bankCode]) === true)
            {
                $bankName = $data['methods'][Payment\Method::NETBANKING][$bankCode];

                $data['methods'][Payment\Method::NETBANKING] = [
                    $bankCode => $bankName,
                ];
            }
        }

        if (($merchant->isTPVRequired() === true) and
            (empty($data['order']['method']) === true))
        {
            $methods = [
                Payment\Method::NETBANKING => $data['methods'][Payment\Method::NETBANKING],
                Payment\Method::UPI        => $data['methods'][Payment\Method::UPI],
            ];

            if (($bankCode !== null) and
                (isset($data['methods'][Payment\Method::NETBANKING][$bankCode]) === false))
            {
                unset($methods[Payment\Method::NETBANKING]);
            }

            $data['methods'] = $methods;
        }
    }

    protected function checkAndAddDetailsForInvoice(
        array $input,
        Merchant\Entity $merchant,
        array & $data,
        $mode)
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

        // Unsets Customer email, name and contact
        if (isset($data['customer']) === true)
        {
            $data['customer']['email'] = '';
            $data['customer']['contact'] = '';
            $data['customer']['name'] = '';
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

        $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

        $subscription = $this->getSubscription($input, $merchant,$data);

        //
        // If the subscription has already been authenticated, there's no reason for
        // the checkout to hit the preferences route. UNLESS it's a card change flow.
        //
        if (($cardChange === false) and
            ($subscription->hasBeenAuthenticated() === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_ALREADY_AUTHENTICATED,
                null,
                [
                    self::SUBSCRIPTION_ID   => $input[self::SUBSCRIPTION_ID],
                    'merchant_id'           => $merchant->getId(),
                    'card_change'           => $cardChange
                ]);
        }

        if (($cardChange === true) and
            ($subscription->isCardChangeStatus() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED,
                null,
                [
                    'subscription_id'       => $input[self::SUBSCRIPTION_ID],
                    'subscription_status'   => $subscription->getStatus(),
                    'merchant_id'           => $merchant->getId(),
                    'card_change'           => $cardChange
                ]);
        }

        $data['subscription'] = $subscription->formatted_subscription_data;
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

        $startTime = microtime(true);

        (new Payment\Metric())->pushCheckoutPreferenceRequestMetrics($input, $startTime);
    }

    protected function tracePersonalisationRequest(Entity $merchant, $mode, array $input)
    {
        $sessionData = $this->app['request']->session()->all();
        $this->trace->info(
            TraceCode::PERSONALISATION_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
                'session'     => $sessionData,
                'input'       => $input
            ]);
    }

    protected function fetchCustomerData(array $input, Entity $merchant, $data)
    {
        $custData = null;

        try
        {
            //
            // For the second 2FA in global flow also, we will have the
            // app_token. Hence, in this usage (preferences) of getCustomerAndApp,
            // we don't need to have the global_customer_id in the input.
            //
            list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp($input, $merchant, $data['global']);

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

            $tokenCore = (new Customer\Token\Core);

            $savedTokens = $tokenCore->fetchTokensByCustomer($customer, $merchant);

            $savedAddresses = (new Customer\Core)->fetchAddressesFor1CC($customer, $input);

            //
            // TODO: Remove this later when we start handling the below case.
            // Currently, we do not expose any recurring NB tokens to the customer.
            // We do not handle the flow where a customer can use an existing token
            // to subscribe to another product.
            //
            $savedTokens = $tokenCore->removeEmandateRecurringTokens($savedTokens);

            $savedTokens = $tokenCore->removeDisabledNetworkTokens($savedTokens, $data[Entity::METHODS][Methods\Entity::CARD_NETWORKS]);

            $savedTokens = $tokenCore->removeCardTokensWithoutName($savedTokens);

            $savedTokens = $tokenCore->addConsentFieldInTokens($savedTokens);

            $custData =  [
                'email'     => $customer->getEmail(),
                'contact'   => $customer->getContact(),
                'tokens'    => $savedTokens->toArrayPublic(),
                'addresses' => $savedAddresses,
            ];

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
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::CHECKOUT_PREFERENCES_EXCEPTION, $input);
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

    protected function checkAndFillSavedTokens(array $input, Entity $merchant, array & $data,$mode )
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
                $custData = $this->fetchCustomerData($input, $merchant, $data);

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
                    if (isset($response['email']) === true)
                    {
                        $data['customer']['email'] = $response['email'];
                    }

                    if (isset($response['tokens']) === true)
                    {
                        $tokens = $response['tokens'];

                        // TODO: Needs to be fixed later when we allow first recurring on old recurring nb token.
                        $tokensWithoutEmandate = (new Customer\Token\Core)->removeEmandateRecurringTokens($tokens);

                        $tokensWithoutDisabledCardNetwork = (new Customer\Token\Core)->removeDisabledNetworkTokens($tokensWithoutEmandate, $data[Entity::METHODS][Methods\Entity::CARD_NETWORKS]);

                        $tokensWithoutCardName = (new Customer\Token\Core)->removeCardTokensWithoutName($tokensWithoutDisabledCardNetwork);

                        $data['customer']['tokens'] = $tokensWithoutCardName;
                    }
                }
                // add saved addresses status
                if ($response['saved_address'] === true)
                {
                    $data['customer']['saved_address'] = true;
                }
            }
            $treatment = $this->app->razorx->getTreatment(
                $merchant->getId(),
                Merchant\RazorxTreatment::BLOCK_CUSTOMER_PREFILL_IN_AUTHLINK,
                $mode
            );

            if ($treatment === 'on')
            {
                // Unsets Customer email, name and contact if block_customer_prefill experiment is enabled
                if ((isset($data['customer']) === true)
                    and (isset($input[Payment\Entity::RECURRING]) === true)
                    and (($input[Payment\Entity::RECURRING]) === '1'))
                {
                    $data['customer']['email']   = '';
                    $data['customer']['contact'] = '';
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

        $subscription = $this->setSubscription($input, $merchant ,$data['methods']['upi']??false);

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
            $customerId = $subscription->getCustomerId();

            if ($this->followSubscriptionLocalFlow($merchant, $customerId) === true)
            {
                $input[Payment\Entity::CUSTOMER_ID] = $customerId;
            }
        }
    }

    protected function followSubscriptionLocalFlow(Merchant\Entity $merchant, string $customerId): bool
    {
        if ($this->subscription->hasCustomer() === false)
        {
            return false;
        }

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $merchant);

        $hasGlobalCustomer = $customer->hasGlobalCustomer();

        return ($hasGlobalCustomer === false);
    }

    protected function setSubscription(array $input, Merchant\Entity $merchant,bool $isupienabled = false)
    {
        $subscription = $this->app['module']
                             ->subscription
                             ->fetchCheckoutInfo (
                                 $input,
                                 $merchant,$isupienabled);

        $this->subscription = $subscription;

        return $subscription;
    }

    protected function getSubscription(array $input, Merchant\Entity $merchant,array $data)
    {
        if (isset($this->subscription) === true)
        {
            return $this->subscription;
        }

        return $this->setSubscription($input, $merchant,$data['methods']['upi']??false);
    }

    protected function getMerchantPreferencesData(Entity $merchant, $mode)
    {
        $data['options']['theme']['color'] = $merchant->getBrandColor();

        $data['options']['image'] = $merchant->getFullLogoUrlWithSize(self::CHECKOUT_LOGO_SIZE);

        $data['options']['remember_customer'] = $this->shouldEnableCardSaving($merchant, $mode);

        $data['fee_bearer'] = $merchant->isFeeBearerCustomerOrDynamic();

        $data['version'] = 1;

        $data['language_code'] = App::getLocale();

        $data['merchant_key'] = $this->app['basicauth']->getPublicKey();

        $data['merchant_name'] = $merchant->getName();

        if(empty($data['merchant_key']) === true)
        {
            $data['merchant_key'] = (new key\Core)->getLatestActiveKeyForMerchant($merchant->getId());
        }
        /*
        if hdfc merchant, sending redirect true. Done specificially
        for shopify merchants of HDFC.
        */
        if ($merchant->getOrgId() === ORG_ENTITY::HDFC_ORG_ID)
        {
            $data['options']['redirect'] = true;
        }

        //
        // For the merchant which doesn't want retry option in checkout, sending retry false in preferences.
        // Will be done for irctc
        //
        if ($merchant->isFeatureEnabled(Feature\Constants::CHECKOUT_DISABLE_RETRY) === true)
        {
            $data['options']['retry'] = false;
        }

        //
        // When using Keyless auth, checkout has no way to identify the request mode
        // Adding mode to the preferences response for this
        //
        $data['mode'] = $mode;

        // Magic is displayed true.
        $data['magic'] = true;

        $optionalInputConfig = $merchant->getOptionalInputConfig();

        if (empty($optionalInputConfig) === false)
        {
            $data['optional'] = $optionalInputConfig;
        }

        $data['blocked'] = false;

        if (($mode === Mode::LIVE) and
            ($merchant->isLive() === false))
        {
            $data['blocked'] = true;
        }

        // For merchants with either "RAAS" feature flag or with org_ids of banking programs,
        // we need dynamic wallet flow -> which means the checkout should decide whether a wallet follows
        // otp flow or redirect flow after payment create API response.
        // Currently, power wallets are hardcoded on front end to follow otp flow. This flag will
        // tell front end to avoid hardcoding this otp flow.
        if ($merchant->isFeatureEnabled(Feature\Constants::RAAS) === true ||
            ORG_ENTITY::isDynamicWalletFlowOrg($merchant->getOrgId())) {
            $data[self::DYNAMIC_WALLET_FLOW] = true;
        }

        return $data;
    }

    protected function shouldEnableCardSaving(Entity $merchant, $mode)
    {
        $isEmailOrContactOptional = (($merchant->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL) === true) or
                                     ($merchant->isFeatureEnabled(Feature\Constants::CONTACT_OPTIONAL) === true));

        $rememberCustomer = (($merchant->isFeatureEnabled(Feature\Constants::NOFLASHCHECKOUT) === false) and ($this->isCardVaultUp == true));

        // if card saving is enabled, create a session and set a key
        if ($rememberCustomer === true)
        {
            $key = $mode . '_checkcookie';

            $this->app['request']->session()->put($key, '1');
        }

        return $rememberCustomer;
    }

    public function checkAndFillOfferDetails(Merchant\Entity $merchant, array $input, array & $data, $mode)
    {
        $order = null;

        if (isset($input[Payment\Entity::ORDER_ID]) === true)
        {
            $order = $this->setOrGetOrder($input[Payment\Entity::ORDER_ID], $merchant);
        }

        elseif (isset($input[Payment\Entity::INVOICE_ID]) === true)
        {
            $invoiceEntity = $this->repo->invoice->findByPublicIdAndMerchant($input[Payment\Entity::INVOICE_ID], $merchant);

            if ($invoiceEntity->getOrderId() !== null)
            {
                $order = $this->setOrGetOrder('order_'.$invoiceEntity->getOrderId(), $merchant);
            }
        }

        elseif (isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
        {
            $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

            if ((isset($data['subscription']) === true) and
                ($cardChange === false) and
                ($this->isSubscriptionOffersEnabled($merchant, $mode) === true) and
                (isset($input['currency']) === false or
                (isset($input['currency'][0]) === true and $input['currency'][0] === Currency::INR)))
            {
                $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

                if (($pos = strpos($subscriptionId, "_")) !== false)
                {
                    $subscriptionId = substr($subscriptionId, $pos + 1);
                }

                $invoiceEntity = $this->repo->invoice->fetchIssuedInvoicesOfSubscriptionId($subscriptionId);

                if ($invoiceEntity !== null and $invoiceEntity->getOrderId() !== null)
                {
                    $orderId = 'order_' . $invoiceEntity->getOrderId();

                    $data['subscription']['order_id'] = $orderId;

                    $order = $this->setOrGetOrder($orderId, $merchant);
                }
            }
        }

        if (($order !== null) and
            ($order->hasOffers() === true))
        {
            $this->checkAndFillOrderOffers($order, $data);
        }
        else
        {
            $this->checkAndFillNonOrderOffers($merchant, $data);
        }
    }

    protected function isSubscriptionOffersEnabled(Merchant\Entity $merchant, $mode)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::OFFER_ON_SUBSCRIPTION) === true)
        {
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => true ]);

            return true;
        }

        $treatment = $this->app->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::OFFER_ON_SUBSCRIPTION,
            $mode
        );

        if (($treatment === null) or
            ($treatment !== 'on'))
        {
            $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => false ]);

            return false;
        }

        $this->trace->info(TraceCode::OFFER_ON_SUBSCRIPTION, [ 'enabled' => true ]);

        return true;
    }

    protected function checkAndFillOrderOffers(Order\Entity $order, array & $data)
    {
        $offers = $order->offers;

        $orderAmount = $order->getAmount();

        if ($offers->isEmpty() === true)
        {
            return;
        }

        $verbose = true;

        //
        // If there's a single forced offer, we only put those
        // methods on checkout which can be used with that offer.
        //
        if (($offers->count() === 1) and
            ($order->isOfferForced() === true))
        {
            $offer = $offers->first();

            $checker = new Checker($offer, $verbose);

            if ($checker->checkValidityOnOrder($order) === true)
            {
                $this->updateMethodsToEnableOnCheckout($offer, $data);
            }

            //
            // If offer is forced, checkout handles it by displaying it without list of choices
            //
            $data['force_offer'] = true;
        }

        $this->updateEmiOptionsUsingOffers($offers, $data, $order);

        //
        // For multiple offers, we show all methods,
        // and rely on validation during payment.
        //
        foreach ($offers as $offer)
        {
            $checker = new Checker($offer, $verbose);

            if ($checker->checkValidityOnOrder($order) === true)
            {
                $data['offers'][] = $offer->toArrayCheckout($orderAmount);
            }
        }
    }

    protected function checkAndFillNonOrderOffers(Merchant\Entity $merchant, array & $data)
    {
        $nonOrderOffers = (new Offer\Core)->fetchSharedAccOffersForCheckout($merchant);

        foreach ($nonOrderOffers as $offer)
        {
            $data['offers'][] = $offer->toArrayCheckout();
        }
    }

    protected function updateEmiOptionsUsingOffers($offers, array & $data, Order\Entity $order = null)
    {
        $emiPlansAndOptions = (new Emi\Service)->getEmiPlansAndOptions($offers, $order);

        $data['methods']['emi_options'] = $emiPlansAndOptions['options'];
    }

    protected function updateMethodsToEnableOnCheckout(Offer\Entity $offer, array & $data)
    {
        $offerMethod = $offer->getPaymentMethod();

        // If offer method is empty we do not update methods in response
        if (empty($offerMethod) === true)
        {
            return;
        }

        $enabledBanks = $data['methods'][Payment\Method::NETBANKING];

        $enabledWallets = $data['methods'][Payment\Method::WALLET];

        $recurringData = $data['methods']['recurring'] ?? null;

        $data['methods'] = [
            'entity' => 'methods'
        ];

        switch ($offerMethod)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $this->updateMethodsForCardOrEmiOffer($data, $offer);

                break;

            case Payment\Method::NETBANKING:

                // Only allow payments through supported banks
                $data['methods'][Payment\Method::NETBANKING] = $enabledBanks;

                // Only allow payment through specific bank if network is specified
                if ($offer->getIssuer() !== null)
                {
                    $bankCode = $offer->getIssuer();

                    $bankName = Netbanking::getName($bankCode);

                    $data['methods'][Payment\Method::NETBANKING] = [
                        $bankCode => $bankName
                    ];
                }

                break;

            case Payment\Method::WALLET:

                // Only allow payments through supported wallets
                $data['methods'][Payment\Method::WALLET] = $enabledWallets;

                // For wallet offers if network is specified, lock method to only that wallet
                if ($offer->getIssuer() !== null)
                {
                    $wallet = $offer->getIssuer();

                    $data['methods']['wallet'] = [
                        $wallet => true,
                    ];
                }

                break;

            // For other methods like UPI, we currently handle it here, by just
            // enabling the particular method.
            default:
                $data['methods'][$offerMethod] = true;

                break;
        }

        if (isset($recurringData) === true)
        {
            $data['methods']['recurring'] = $recurringData;
        }
    }

    protected function updateMethodsForCardOrEmiOffer(array & $data, Offer\Entity $offer)
    {
        $offerMethod = $offer->getPaymentMethod();

        $offerMethodType = $offer->getPaymentMethodType();

        $data['methods'][Payment\Method::CARD] = true;

        if ($offerMethod === Payment\Method::EMI)
        {
            $emiService = new Emi\Service;

            $data['methods'][Payment\Method::EMI] = true;

            $data['methods']['emi_plans']         = $emiService->all();
        }

        switch ($offerMethodType)
        {
            case 'credit':
                $data['methods'][Methods\Entity::CREDIT_CARD]  = true;

                break;

            case 'debit':
                $data['methods'][Methods\Entity::DEBIT_CARD]  = true;

                break;

            default:

                $data['methods'][Methods\Entity::DEBIT_CARD]  = true;
                $data['methods'][Methods\Entity::CREDIT_CARD] = true;
        }

        $offerNetwork = $offer->getPaymentNetwork();

        $data['methods'][Merchant\Methods\Entity::AMEX] = (($offerNetwork === null) or
                                                           ($offerNetwork === Card\Network::AMEX));
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

    protected function checkAndFillPaymentDowntime(Merchant\Entity $merchant, array & $data)
    {
        try
        {
            $downtimeData = (new Payment\Downtime\Service)->getMethodDowntimeDataForMerchant([]);

            if (empty($downtimeData) === false)
            {
                $data['payment_downtime'] = $downtimeData;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::WARNING, TraceCode::CHECKOUT_PREFERENCES_GET_PAYMENT_DOWNTIME_EXCEPTION);
        }
    }

    protected function checkAndFillPartnerUrl(Merchant\Entity $merchant, array & $data)
    {
        $partnershipUrl = $merchant->getPartnershipUrl();

        if (empty($partnershipUrl) === false)
        {
            $data['options']['partnership_logo'] = $partnershipUrl;
        }
    }

    protected function fillEnabledFeatures(Merchant\Entity $merchant, array & $data)
    {
        foreach (Feature\Constants::CHECKOUT_FEATURES as $feature)
        {
            // checkout feature changed to dcc in case DISABLE_NATIVE_CURRENCY is false
            if($feature === Feature\Constants::DISABLE_NATIVE_CURRENCY){
                if($merchant->isDCCEnabledInternationalMerchant() === true) {
                    $data['features']['dcc'] = true;
                }
            }

            elseif ($feature === Feature\Constants::DIRECT_SETTLEMENT and $merchant->getCategory() === '6211')
            {
                $data['features']['direct_settlement'] = true;
            }

            else if ($merchant->isFeatureEnabled($feature) === true)
            {
                $data['features'][$feature] = true;
            }
        }

        //adding this condition temp for the zestmoney reintegration will remove once we are full live so checking this with the razorx call
        if(($this->app['cache']->get(ConfigKey::REDIRECT_TO_ZESTMONEY)) == true)
        {
            $data['features'][Feature\Constants::REDIRECT_TO_ZESTMONEY] = true;
        }

    }

    protected function updateCurrencyMethodsIfApplicable($input, $merchant, array & $data)
    {
        if ((isset($data['methods']['wallet']['paypal']) === true) and
            ($data['methods']['wallet']['paypal'] === true))
        {
            try
            {
                $currency = isset($data['order']) ? $data['order']['currency'] : $input['currency'][0];

                $isPaypalTerminalPresent = (new Methods\Core)->checkPaypalTerminalForCurrency($merchant, $currency);

                if ($isPaypalTerminalPresent === false)
                {
                    unset($data['methods']['wallet']['paypal']);
                }
            }

            catch (\Throwable $exception)
            {
                $this->trace->info(TraceCode::CHECKOUT_PREFERENCES_CURRENCY_ABSENT_EXCEPTION, $data);

                unset($data['methods']['wallet']['paypal']);
            }
        }
    }

    protected function checkAndFillContactDetails($input, $merchant, array & $data)
    {
        if (isset($input['contact_id']) === false)
        {
            return;
        }

        $contact =  (new Contact\Core)->fetch($input['contact_id'], $merchant)->toArrayPublic();

        $data['contact'] = $contact;
    }

    /**
     * Method to return personalised methods for the user
     *
     * @param Entity $merchant
     * @param string $mode
     * @param array $input
     *
     * @return array
     */
    public function getPersonalisedMethods($merchant, $mode, $input)
    {
        $this->tracePersonalisationRequest($merchant, $mode, $input);

        $this->checkAndFillAppTokenInputFromSession($merchant, $mode, $input);

        $this->checkGivenContactIsDiffFromLogInContact($input, $merchant);

        $data = [];

        // The below code is required for ensuring that the disabled
        // tokens are removed for the logged in users
        if ((isset($input[Payment\Entity::CUSTOMER_ID]) === true) or
            (isset($input[Payment\Entity::APP_TOKEN]) === true))
        {
            $data[Entity::METHODS] = (new Methods\Core)->getFormattedMethods($merchant);
        }

        $this->checkAndFillSavedTokens($input, $merchant, $data,$mode);

        $this->checkAndAddDetailsForOrder($input, $merchant, $data);

        $this->checkAndAddDetailsForInvoice($input, $merchant, $data, $mode);

        $this->fillPreferredMethods($merchant, $input, $data);

        return $data;
    }

    private function checkGivenContactIsDiffFromLogInContact(array & $input, $merchant)
    {
        if (empty($input[Payment\Entity::APP_TOKEN]) or empty($input[Payment\Entity::CONTACT]))
        {
            return ;
        }

        $appTokenId = $input[Payment\Entity::APP_TOKEN];

        $appToken  = (new Customer\AppToken\Core)->getAppByAppTokenId($appTokenId, $merchant);

        $logInContact = null;

        if ($appToken !== null)
        {
            $logInContact = $appToken->customer->getContact();
        }

        if (($logInContact === null) or ($logInContact === $input[Payment\Entity::CONTACT]))
        {
            return ;
        }

        $inputForLogout = ['logout' => 'app'];

        (new AppToken\Service())->deleteAppTokensForGlobalCustomer($inputForLogout);

        unset($input[Payment\Entity::APP_TOKEN]);
    }

    protected function fillPreferredMethods($merchant, $input, array &$data)
    {
        if ((isset($data['order']) === false) and
            (isset($input['amount']) === false))
        {
            return;
        }

        $contact = $this->findContact($input, $merchant, $data);

        $mccCode = $merchant->getCategory();

        $amount = (isset($data['order']) === true) ? $data['order']['amount'] : $input['amount'];

        //call DE api with mccCode, merchant, customerId, amount

        $personalisationMock = $this->app['config']->get('services.de_personalisation.mock');

        if ($personalisationMock === true)
        {
            if ((isset( $input['upi_intent']) === true) and (isset($input['null_response']) === true))
            {
                $response = (new MockPersonalisationService())->fetchPersonalisationData($input, $data, $input['upi_intent'], $input['null_response']);
            }
            else if ((isset( $input['upi_intent']) === true))
            {
                $response = (new MockPersonalisationService())->fetchPersonalisationData($input, $data, $input['upi_intent']);
            }
            else if ((isset( $input['null_response']) === true))
            {
                $response = (new MockPersonalisationService())->fetchPersonalisationData($input, $data, false, $input['null_response']);
            }
            else
            {
                $response = (new MockPersonalisationService())->fetchPersonalisationData($input, $data);
            }
        }
        else
        {
            $content = array(
                'customer'  => $contact,
                'amount'    => (float)$amount,
                'mcc'       => $mccCode,
                'merchant'  => $merchant->getId(),
            );

            if (empty($mccCode) === true)
            {
                unset($content['mcc']);
            }
            else
            {
                $content['mcc'] = (int)$content['mcc'];
            }

            $response = (new PersonalisationService())->sendPersonalisationRequest($content);
        }

        if ($response !== null)
        {
            $this->processPersonalisationResponse($response, $merchant, $data, $contact, $input);
        }

    }

    private function processPersonalisationResponse($response, $merchant, & $data, $contact, $input)
    {
        try
        {
            $responseBody = json_decode($response->body, true);

            $preferences = $responseBody['preferences'];

            if ($preferences !== null)
            {
                $preferences = $this->sortByScore($preferences);

                $pos = 0;

                foreach ($preferences as $preference) {
                    if ((($preference['method'] === Payment\Method::CARD) or
                            ($preference['method'] === Payment\Method::EMI)) and
                        ($preference['instrument'] !== Payment\Method::CARD)) {
                        $card = (new Card\Repository())->find($preference['instrument']);

                        // adding this check to insure it doesn't break on local
                        if (is_null($card) === false) {
                            $card->overrideIINDetails();

                            $preference['issuer'] = $card->issuer;

                            $preference['type'] = $card->type;

                            $preference['network'] = $card->network;

                            $token = null;

                            //put token instead of card id in response
                            if ((isset($input[Payment\Entity::APP_TOKEN]) === true) and
                                (isset($card->global_card_id) === true)) {
                                //token for emi payments is stored with method as card
                                $token = (new Customer\Token\Repository())->fetchByMethodAndCardIdAndMerchant(
                                    Payment\Method::CARD,
                                    $card->global_card_id,
                                    $this->repo->merchant->getSharedAccount()->getId()
                                );
                            } elseif (isset($input[Payment\Entity::CUSTOMER_ID]) === true) {
                                //token for emi payments is stored with method as card

                                $token = (new Customer\Token\Repository())->getByMethodAndCustomerIdAndCardIdAndMerchantId(
                                    Payment\Method::CARD,
                                    $input[Payment\Entity::CUSTOMER_ID],
                                    $preference['instrument'],
                                    $merchant->getId()
                                );
                            }
                        }

                        if (isset($token) === true) {
                            $preference['instrument'] = $token->getPublicId();
                        } else {
                            $preference['instrument'] = null;
                            $preference['issuer'] = null;
                        }
                    }

                    if ((isset($input[Payment\Entity::APP_TOKEN]) === false) and
                        (isset($input[Payment\Entity::CUSTOMER_ID]) === false)) {

                        if ($preference['method'] === Payment\Method::NETBANKING) {
                            if (isset($preference['instrument']) === true) {
                                $preference['instrument'] = null;
                            }
                        } else if ($preference['method'] === Payment\Method::UPI) {
                            if ((isset($preference['instrument']) === true) and
                                ($this->validVpa($preference['instrument']))) {
                                $preference['instrument'] = null;
                            }
                        }
                    }

                    unset($preference['score']);

                    //replace the index value with the new value
                    $preferences[$pos] = $preference;
                    $pos = $pos + 1;
                }

                $preferences = $this->enrichPznRespForInternational($preferences, $contact);

                $contact = $contact ?: 'default';

                $data['preferred_methods'][$contact] = [
                    'instruments'               => $preferences,
                    'is_customer_identified'    => $responseBody['is_customer_identified'],
                    'user_aggregates_available' => $responseBody['user_aggregates_available'],
                    'versionID'                 => $responseBody['versionID'] ?? 'v2',
                ];
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::PERSONALISATION_EXCEPTION, [
                    'message' => 'error while processing personalised response',
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTrace(),
                ]
            );
        }
    }

    /**
     * Function to enrich personalisation preferred method response,
     * if contact dialing code is non indian add paypal in preferred methods
     * @param $preferences
     * @param $contact
     * @return mixed
     */
    private function enrichPznRespForInternational(& $preferences, $contact)
    {
        if ((empty($contact) === true) or
            (strpos($contact,'+') !== 0) or
            (strpos($contact,'+91') === 0)){
            return $preferences;
        }

        $result  = array_first($preferences, function ($preference) use (&$value){
           return ($preference["method"] === Payment\Method::WALLET) and
               ($preference["instrument"] === Merchant\Methods\Entity::PAYPAL);
        });

        //check for if paypal already added in response, skip adding it again
        if (empty($result) === true){
            $paypalPreference = [
                "method"    => Payment\Method::WALLET,
                "instrument"=> Merchant\Methods\Entity::PAYPAL,
            ];
            array_unshift($preferences, $paypalPreference);
        }

        return $preferences;
    }

    protected function sortByScore(array $preferences) : array
    {
        $score = array_column($preferences, 'score');

        array_multisort($score, SORT_DESC, $preferences);

        return $preferences;
    }

    protected function validVpa(string $vpa) : bool
    {
        $vpaRegex = '/^[a-zA-Z0-9][a-zA-Z0-9\.-]*@[a-zA-Z]+$/';
        if (((bool) preg_match($vpaRegex, $vpa) === false) or
            (strlen($vpa) > 100) or
            (strlen($vpa) < 3))
        {
            return false;
        }

        return true;
    }

    protected function findContact($input, $merchant, $data)
    {
        if ((isset($data['customer']) === true) and
             (isset($data['customer']['contact']) === true))
        {
            return $data['customer']['contact'];
        }

        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            $customer = (new Customer\Repository())->findByPublicIdAndMerchant($input[Payment\Entity::CUSTOMER_ID], $merchant);

            return $customer->contact;
        }

        if (isset($input[Payment\Entity::APP_TOKEN]) === true)
        {
            $appTokenId = $input[Payment\Entity::APP_TOKEN];

            $appToken  = (new Customer\AppToken\Core)->getAppByAppTokenId($appTokenId, $merchant);

            if ($appToken !== null)
            {
                $customer = (new Customer\Repository())->findByIdAndMerchant($appToken->getCustomerId(), $merchant);

                return $customer->contact;
            }
        }

        if (isset($input['contact']) === true)
        {
            return $input['contact'];
        }

        return;
    }

    protected function isCredEligibilityConfigEnabled() : bool
    {
        $credEligibilityEnabled = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::ENABLE_CRED_ELIGIBILITY_CALL
            ]
        );

        return ((empty($credEligibilityEnabled) === false) and (((bool) $credEligibilityEnabled) === true));
    }

    protected function getInputAndOptionsForCred(array $input, array $data, Entity $merchant)
    {
        $credInput = [
            'value' => $data['customer']['contact'],
            '_'     => $input['_'] ?? null,
        ];

        $credOptions = [
            'override_timeout' => true,
        ];

        if (empty($input[Payment\Entity::ORDER_ID]) === false)
        {
            $order = $this->setOrGetOrder($input[Payment\Entity::ORDER_ID], $merchant);

            if (is_null($order) === false)
            {
                $credOptions['order'] = [
                    Order\Entity::ID        => $order->getId(),
                    Order\Entity::CURRENCY  => $order->getCurrency() ?? 'INR',
                    Order\Entity::APP_OFFER => $order->getAppOffer() ?? false,
                    Order\Entity::AMOUNT    => (int) ($order->getAmount() / 100),
                ];
            }
        }

        return [$credInput, $credOptions];
    }
}
