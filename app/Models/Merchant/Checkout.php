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

    const SUBSCRIPTION_ID    = 'subscription_id';

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

        $this->checkAndAddDetailsForSubscription($input, $merchant, $data);

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
}
