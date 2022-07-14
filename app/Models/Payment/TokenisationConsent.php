<?php

namespace RZP\Models\Payment;

use App;
use RZP\Diag\EventCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Customer\Token\Core as TokenCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use View;

class TokenisationConsent
{
    protected $app;
    protected $trace;
    protected $config;
    protected $route;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->config = $this->app['config'];
        $this->route = $this->app['api.route'];
    }

    public function logTokenisationConsentViewRequest($input): void
    {
        $this->trace->info(TraceCode::TOKENISATION_CONSENT_VIEW_REQUEST, [
            'library'     => $input['_']['library'] ?? '',
            'checkout_id' => $input['_']['checkout_id'] ?? '',
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::TOKENISATION_CONSENT_SCREEN_REQUEST,
            [
                'library'      => $input['_']['library'] ?? '',
                'checkout_id'  => $input['_']['checkout_id'] ?? '',
                'is_recurring' => false,
            ]
        );
    }

    public function logRecurringTokenisationConsentViewRequest($input): void
    {
        $this->trace->info(TraceCode::TOKENISATION_CONSENT_VIEW_REQUEST_RECURRING, [
            'library'     => $input['_']['library'] ?? '',
            'checkout_id' => $input['_']['checkout_id'] ?? '',
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::TOKENISATION_CONSENT_SCREEN_REQUEST,
            [
                'library'      => $input['_']['library'] ?? '',
                'checkout_id'  => $input['_']['checkout_id'] ?? '',
                'is_recurring' => true,
            ]
        );
    }

    /**
     * @param array          $input
     * @param MerchantEntity $merchant
     *
     * @return bool
     */
    public function showRecurringTokenisationConsentView(array $input, $merchant): bool
    {
        /**
         * Following checks to be passed if consent page to be shown
         * 1. Library - custom checkout => razorpayjs / custom(comes from sdk)
         * 2. Payment method - card, emi
         * 3. Card CVV should be present
         * 4. NO_CUSTOM_CHECKOUT_RECURRING_CONSENT Feature Flag should not be enabled
         * 5. Customer id or subscription id should be present
         * 6. For existing saved card token, check for token consent if consent not taken, trigger this
         * 7. for new card, if recurring and subscription id is present, trigger this
         */

        try
        {
            $paymentMethod            = $input[Payment\Entity::METHOD] ?? '';
            $paymentMethodsUsingCards = [Payment\Entity::CARD, Payment\Entity::EMI];
            $library                  = $input['_']['library'] ?? '';
            $allowedLibraries         = [Payment\Analytics\Metadata::RAZORPAYJS, Payment\Analytics\Metadata::CUSTOM];
            $collectConsentEnabled    = $merchant->isCollectConsentEnabledForMerchantRecurring();

            if ((in_array($paymentMethod, $paymentMethodsUsingCards, true) === false) or
                (in_array($library, $allowedLibraries, true) === false) or
                (empty($input['card']['cvv']) === true) or
                ($collectConsentEnabled === false) or
                ((empty($input[Payment\Entity::CUSTOMER_ID]) === true) and
                    (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === true)))
            {
                return false;
            }

            $isNewRecurringCard = $this->checkIsNewRecurringCard($input);

            if ($isNewRecurringCard === true)
            {
                return true;
            }

            if ((($isNewRecurringCard === false) or
                (empty($input[Payment\Entity::TOKEN]) === false)) and
                (empty($input[Payment\Entity::CUSTOMER_ID]) === false))
            {

                $showConsentView = (new TokenCore())
                    ->showTokenisationConsentViewForExistingSavedCard($input[Payment\Entity::TOKEN],
                        $input[Payment\Entity::CUSTOMER_ID]);

                return $showConsentView;

            } elseif ((($isNewRecurringCard === false) or
                    (empty($input[Payment\Entity::TOKEN]) === false)) and
                (empty($input[Payment\Entity::CUSTOMER_ID]) === true))
            {
                $showConsentView = (new TokenCore())->showTokenisationConsentViewForExistingSavedCardwithoutCustomer(
                    $input[Payment\Entity::TOKEN]
                );

                return $showConsentView;
            }

            return true;
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::SHOW_TOKENISATION_CONSENT_VIEW_ERROR_RECURRING, []);

            return true;
        }
    }

    /**
     * Returns a decision whether to show intermediate consent screen to end customers
     * on custom checkout merchants.
     *
     * @param array          $input
     * @param MerchantEntity $merchant
     *
     * @return bool
     */
    public function showTokenisationConsentView(array $input, $merchant): bool
    {
        /**
         * Following checks to be passed if consent page to be shown
         * 1. Library - custom checkout => razorpayjs / custom(comes from sdk)
         * 2. Payment method - card, emi
         * 3. Card CVV should be present
         * 4. Collect Consent Feature flag - on
         * 5. Custom checkout consent screen feature flag - on
         * 6. Customer id should be present
         * 7. consent_to_save_card should not be present
         * 8. For existing saved card token, check if consent already taken
         * 9. for new card, if save=1, trigger this, for existing card no check
         */

        try
        {
            $paymentMethod           = $input[Payment\Entity::METHOD] ?? '';
            $paymentMethodsUsingCards = [Payment\Entity::CARD, Payment\Entity::EMI];
            $library                  = $input['_']['library'] ?? '';
            $allowedLibraries         = [Payment\Analytics\Metadata::RAZORPAYJS, Payment\Analytics\Metadata::CUSTOM];
            $collectConsentEnabled    = (
                $merchant->isCustomCheckoutConsentScreenEnabledForMerchant() &&
                $merchant->isCollectConsentEnabledForMerchant()
            );

            if ((in_array($paymentMethod, $paymentMethodsUsingCards, true) === false) or
                (in_array($library, $allowedLibraries, true) === false) or
                (empty($input['card']['cvv']) === true) or
                ($collectConsentEnabled === false) or
                (empty($input[Payment\Entity::CUSTOMER_ID]) === true) or
                (isset($input['consent_to_save_card']) === true))
            {
                return false;
            }

            $isNewCard = $this->checkIsNewCard($input);

            if (($isNewCard === true) and (empty($input['save']) === false))
            {
                return true;
            }

            if (($isNewCard === false) and
                (empty($input[Payment\Entity::TOKEN]) === false))
            {
                $showConsentView = (new TokenCore())
                    ->showTokenisationConsentViewForExistingSavedCard($input[Payment\Entity::TOKEN],
                                                                      $input[Payment\Entity::CUSTOMER_ID]);

                return $showConsentView;
            }

            return false;
        }
        catch(\Throwable $e)
        {
            $this->trace->info(TraceCode::SHOW_TOKENISATION_CONSENT_VIEW_ERROR, []);

            return false;
        }
    }

    /**
     * @param  array  $input
     * @return bool
     */
    public function checkIfRequestIsFromTokenisationConsentView(array $input): bool
    {
        /**
         * We would be receiving input['consent_to_save_card'] and encrypted string in input['card] from consent page
         * Hence we are checking for type string for input['card'] which would otherwise be an array containing number, expiry, cvv, etc
         * and the presence of consent_to_save_card field in input
         * we are checking for the custom checkout libraries since the consent page is applicable only for those merchants
         */
        $library                  = $input['_']['library'] ?? '';
        $allowedLibraries         = [Payment\Analytics\Metadata::RAZORPAYJS, Payment\Analytics\Metadata::CUSTOM];

        if((isset($input['consent_to_save_card']) === true) and
            (in_array($library, $allowedLibraries, true) === true) and
            (isset($input['card']) === true) and
            (is_string($input['card']) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function returnTokenisationConsentView(array $input)
    {
        $renderRecurringConsentPage = false;

        if ((isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true) or
                    (isset($input[Payment\Entity::RECURRING]) === true))
        {
            $renderRecurringConsentPage = true;
        }

        $input = $this->encryptCardDetails($input);

        $url = $this->route->getUrlWithPublicAuth('payment_create_checkout');

        if ($renderRecurringConsentPage == true) {

            $merchant = $this->app['basicauth']->getMerchant();

            return View::make('tokenisation.recurringTokenisationConsentForm')
                ->with('input', array_assoc_flatten($input, "%s[%s]"))
                ->with('url', $url)
                ->with('merchantName', $merchant['name']);
        }

        return View::make('tokenisation.tokenisationConsentForm')
            ->with('input', array_assoc_flatten($input, "%s[%s]"))
            ->with('url', $url);
    }

    /**
     * @param  array  $input
     * @return array
     */
    public function decryptCardDetails(array $input): array
    {
        try
        {
            $isRecurring = ((isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
                or (isset($input[Payment\Entity::RECURRING]) === true));

            $this->trace->info(TraceCode::CONSENT_VIEW_DECRYPT_LOG, [
                'library'     => $input['_']['library'] ?? '',
                'checkout_id' => $input['_']['checkout_id'] ?? '',
                'consent_to_save_card' => $input['consent_to_save_card'] ?? '',
            ]);

            app('diag')->trackTokenisationEvent(
                EVENTCODE::TOKENISATION_CONSENT_SCREEN_USER_RESPONSE,
                [
                    'library' => $input['_']['library'] ?? '',
                    'checkout_id' => $input['_']['checkout_id'] ?? '',
                    'is_recurring' => $isRecurring,
                    'consent_to_save_card' => $input['consent_to_save_card'] ?? '',
                ]
            );

            if(isset($input['card']) === true)
            {
                $input['card'] = $this->app['encrypter']->decrypt($input['card']);
            }

            if(empty($input['token']) === false)
            {
                $input['token'] = $this->app['encrypter']->decrypt($input['token']);
            }
        }
        catch (\Throwable $e)
        {
            // Not logging the error since it may contain sensitive information
            $this->trace->info(TraceCode::CARD_DETAILS_DECRYPTION_ERROR, []);
        }

        return $input;
    }

    /**
     * @param $merchant
     * @param $library
     * @return bool
     */
    protected function showConsentViewExperimentResult($merchant, $library): bool
    {
        try
        {
            $mode = $this->app['rzp.mode'] ?? '';

            if($mode === 'test')
            {
                return true;
            }

            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->config->get('app.consent_view_splitz_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchant->getId(),
                        'library'     => $library,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            if($variant === 'show_consent')
            {
                return true;
            }

            return false;
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::CONSENT_VIEW_SPLITZ_ERROR);

            return false;
        }
    }

    /**
     * @param $input
     *
     * @return bool
     */
    protected function checkIsNewCard($input): bool
    {
        if (empty($input['card']['number']) === false)
        {
            return true;
        }

        return false;
    }

    protected function checkIsNewRecurringCard($input): bool
    {
        if ((empty($input['card']['number']) === false) and
            ($input[Payment\Entity::RECURRING]) === "1" or (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false))
        {
            return true;
        }

        return false;
    }

    /**
     * @param  array  $input
     * @return array
     */
    protected function encryptCardDetails(array $input): array
    {
        try
        {
            if(isset($input['card']) === true)
            {
                $input['card'] = $this->app['encrypter']->encrypt($input['card']);
            }

            if(empty($input['token']) === false)
            {
                $input['token'] = $this->app['encrypter']->encrypt($input['token']);
            }
        }
        catch (\Throwable $e)
        {
            // Not logging the error since it may contain sensitive information
            $this->trace->info(TraceCode::CARD_DETAILS_ENCRYPTION_ERROR, []);
        }

        return $input;
    }
}
