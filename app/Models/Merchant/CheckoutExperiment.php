<?php

namespace RZP\Models\Merchant;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;

class CheckoutExperiment
{
    /**
     * @var App
     */
    protected $app;
    /**
     * @var Trace
     */
    protected $trace;

    /** @var array */
    protected $input;

    /** @var string */
    protected $merchantId;

    protected $merchant;

    protected $experimentsData;

    protected $experimentResults;

    /*** @var array This is used to map your experiment id to (experiment name and experiment tag)
     * Sample : ['expID1' => [ 'name' => 'UpiQrV2', 'tag' => 'upi_qr_v2' ] */
    private $experimentToResponseHandlerMapping;

    public function __construct(array $input, string $merchantId)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        // initialise with default values which we want to see if experiment fails for some reason
        $this->experimentResults = [
            'checkout_redesign_v1_5'                             => false,
            'eligibility_on_std_checkout'                        => false,
            'upi_ux'                                             => 'variant_1',
            'emi_ux_revamp'                                      => true,
            'upi_qr_v2'                                          => false,
            'cb_redesign_v1_5'                                   => true,
            'recurring_redesign_v1_5'                            => false,
            'reuse_upi_paymentId'                                => false,
            'recurring_upi_intent'                               => false,
            'recurring_intl_verify_phone'                        => false,
            'recurring_upi_qr'                                   => false,
            'recurring_payment_method_configuration'             => false,
            'recurring_upi_all_psp'                              => false,
            'banking_redesign_v15'                               => false,
            'remove_default_tokenization_flag'                   => true,
            'truecaller_standard_checkout_for_prefill'           => 'control',
            'truecaller_standard_checkout_for_non_prefill'       => 'control',
            'truecaller_1cc_for_prefill'                         => 'control',
            'truecaller_1cc_for_non_prefill'                     => 'control',
            'email_less_checkout'                                => false,
            'enable_rudderstack_plugin'                          => false,
            'checkout_downtime'                                  => 'control',
            'upi_number'                                         => 'control',
            'cvv_less'                                           => false,
            'cvv_less_rupay'                                     => false,
            'enable_auto_submit'                                 => 'control',
            'dcc_vas_merchants'                                  => false,
            'emi_via_cards_revamp'                               => false,
            'upi_turbo'                                          => false,
            'checkout_offers_ux'                                 => false,
            'enable_otp_auto_read_and_auto_submit'               => 'control',
        ];

        $this->input = $input;

        $this->merchantId = $merchantId;

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->fillDefaultValuesForOneCc();
    }

    /**
     * This method is used to gather data for all experiments, make bulk evaluate call,
     * Compile all the experiment's results in array and return it.
     *
     * sample output: ['checkout_redesign_v1_5' => true, 'upi_ux' => 'variant_2', 'emi_ux_revamp' => true]
     * @return array
     */
    public function getCheckoutExperimentsResults(): array
    {
        try
        {
            $this->fillSplitzExperimentsData();

            // at max, each bulk evaluate call can have upto 10 experiments.
            $chunkedExperimentsData = array_chunk($this->experimentsData, 10);

            $chunkedResponses = [];

            foreach ($chunkedExperimentsData as $experimentData)
            {
                $chunkedResponses [] = $this->app['splitzService']->bulkCallsToSplitz($experimentData);
            }

            $response = array_merge([], ...$chunkedResponses);

            return $this->handleExperimentResponses($response);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CHECKOUT_SPLITZ_ERROR
            );
        }

        return $this->experimentResults;
    }

    public function shouldRoutePreferencesTrafficThroughCheckoutService(
        string $experimentId,
    ): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $experimentId,
                'request_data'  => json_encode(['merchant_id' => $this->merchantId]),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            return $variant === 'variant_on';
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CHECKOUT_SERVICE_PREFERENCES_ROUTING_SPLITZ_ERROR
            );
        }

        return false;
    }

    /**
     * This method fills experiment data for all experiments we want to send to splitz service.
     * if you want to add new experiment, call the $this->fillExperimentData with your own parameters. just make sure
     * that experimentTag is same as what you used in default experiment results array($this->experimentResults)
     * Adding 1cc Experiment for magic
     * @return void
     */
    private function fillSplitzExperimentsData(): void
    {
        $this->fill1CcExperimentData();

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_redesign_v1_5_splitz_experiment_id',
            'CheckoutRedesign',
            'checkout_redesign_v1_5',
            ['merchant_id' => $this->merchantId]
        );

        if ($this->shouldIncludeUpiQrV2Experiment())
        {
            $this->fillExperimentData(
                UniqueIdEntity::generateUniqueId(),
                'app.checkout_upi_qr_v2_splitz_experiment_id',
                'UpiQrV2',
                'upi_qr_v2',
                ['merchant_id' => $this->merchantId]
            );
        }

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_redesign_v1_5_splitz_experiment_id',
            'RecurringRedesign',
            'recurring_redesign_v1_5',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_reuse_upi_payment_id_splitz_experiment_id',
            'ReuseUpiPaymentId',
            'reuse_upi_paymentId',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_upi_intent_splitz_experiment_id',
            'RecurringUpiIntent',
            'recurring_upi_intent',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_intl_verify_phone_splitz_experiment_id',
            'RecurringIntlVerifyPhone',
            'recurring_intl_verify_phone',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_upi_qr_splitz_experiment_id',
            'RecurringUpiQr',
            'recurring_upi_qr',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_payment_method_configuration_splitz_experiment_id',
            'RecurringUpiPaymentMethodConfiguration',
            'recurring_payment_method_configuration',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_recurring_upi_autopay_psp_splitz_experiment_id',
            'RecurringUpiPsp',
            'recurring_upi_all_psp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.eligibility_on_std_checkout_splitz_experiment_id',
            'EligibilityOnStdCheckout',
            'eligibility_on_std_checkout',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_banking_redesign_v1_5_splitz_experiment_id',
            'BankingRedesign',
            'banking_redesign_v15',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.truecaller_standard_checkout_for_prefill_splitz_experiment_id',
            'TruecallerStandardCheckoutForPrefill',
            'truecaller_standard_checkout_for_prefill',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.truecaller_standard_checkout_for_non_prefill_splitz_experiment_id',
            'TruecallerStandardCheckoutForNonPrefill',
            'truecaller_standard_checkout_for_non_prefill',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.truecaller_1cc_for_prefill_splitz_experiment_id',
            'TruecallerOneCCForPrefill',
            'truecaller_1cc_for_prefill',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.truecaller_1cc_for_non_prefill_splitz_experiment_id',
            'TruecallerOneCCForNonPrefill',
            'truecaller_1cc_for_non_prefill',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.email_less_checkout_experiment_id',
            'EmailLessCheckout',
            'email_less_checkout',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_enable_rudderstack_plugin_splitz_experiment_id',
            'EnableRudderstackPlugin',
            'enable_rudderstack_plugin',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_downtime_splitz_experiment_id',
            'CheckoutDowntime',
            'checkout_downtime',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_upi_number_splitz_experiment_id',
            'UpiNumber',
            'upi_number',
            [
                'merchant_id' => $this->merchantId,
            ]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_cvv_less_splitz_experiment_id',
            'CvvLess',
            'cvv_less',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_cvv_less_rupay_splitz_experiment_id',
            'CvvLessRupay',
            'cvv_less_rupay',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_enable_auto_submit_splitz_experiment_id',
            'EnableAutoSubmit',
            'enable_auto_submit',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_dcc_vas_merchants_splitz_experiment_id',
            'DccVasMerchants',
            'dcc_vas_merchants',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.emi_via_card_screen_splitz_experiment_id',
            'EmiViaCardRevamp',
            'emi_via_cards_revamp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_upi_turbo_splitz_experiment_id',
            'UpiTurbo',
            'upi_turbo',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_offers_ux_splitz_experiment_id',
            'CheckoutOffersUx',
            'checkout_offers_ux',
            ['merchant_id' => $this->merchantId]
        );
      
        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_enable_otp_auto_read_and_auto_submit_splitz_experiment_id',
            'EnableOtpAutoReadAndAutoSubmit',
            'enable_otp_auto_read_and_auto_submit',
            ['merchant_id' => $this->merchantId]
        );
    }

    private function fill1CcExperimentData(): void
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ONE_CLICK_CHECKOUT) === false)
        {
            return;
        }

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.1cc_multiple_shipping_splitz_experiment_id',
            'MagicGeneralExperiment',
            '1cc_multiple_shipping',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.1cc_address_flow_exp_splitz_experiment_id',
            'MagicGeneralExperiment',
            '1cc_address_flow_exp',
            ['merchant_id' => $this->merchantId]
        );


        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.magic_offers_fix_splitz_experiment_id',
            'MagicGeneralExperiment',
            '1cc_offers_fix_exp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.1cc_enable_v165_splitz_experiment_id',
            'MagicGeneralExperiment',
            '1cc_enable_v165_exp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.magic_show_coupon_callout_experiment_id',
            'MagicGeneralExperiment',
            '1cc_show_coupon_callout_exp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.1cc_coupons_with_se_splitz_experiment_id',
            'MagicGeneralExperiment',
            '1cc_coupons_with_se_exp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.magic_enable_shopify_taxes_experiment_id',
            'MagicGeneralExperiment',
            '1cc_enable_shopify_taxes',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.magic_qr_v2_experiment_id',
            'MagicGeneralExperiment',
            '1cc_upi_qr_v2',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.one_cc_auto_submit_otp_experiment_id',
            'MagicGeneralExperiment',
            'one_cc_auto_submit_otp',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.one_cc_email_optional_on_checkout_experiment_id',
            'MagicGeneralExperiment',
            'one_cc_email_optional_on_checkout',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.one_cc_email_hidden_on_checkout_experiment_id',
            'MagicGeneralExperiment',
            'one_cc_email_hidden_on_checkout',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.one_cc_conversion_address_improvements_experiment_id',
            'MagicGeneralExperiment',
            'one_cc_conversion_address_improvements',
            ['merchant_id' => $this->merchantId]
        );
    }

    private function fillExperimentData(
        string $experimentEntityId,
        string $experimentIdVariable,
        string $experimentName,
        string $experimentTag,
        array $requestData
    ): void {
        $experimentId = $this->app['config']->get($experimentIdVariable);

        $this->experimentToResponseHandlerMapping[$experimentId]['name'] = $experimentName;
        $this->experimentToResponseHandlerMapping[$experimentId]['tag'] = $experimentTag;

        $this->experimentsData[] = array(
            'id'            => $experimentEntityId,
            'experiment_id' => $experimentId,
            'request_data'  => json_encode($requestData),
        );
    }

    private function shouldIncludeUpiQrV2Experiment(): bool
    {
        return filter_var($this->input['qr_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * This method just goes through all experiments responses and allows us to handle those responses
     * the way we want for each experiment. if you are creating new experiment, add new handle response method for
     * that experiment. your handle method must be named like: <'handle' prefix>, <your exp name>, <'Response' suffix>.
     * sample handle response method name: handleEmiUxRevampResponse
     * @param $response
     * @return array
     */
    private function handleExperimentResponses($response): array
    {
        foreach ($response as $experimentResponse)
        {
            $experimentId = $experimentResponse['experiment']['id'];

            $experimentName = $this->experimentToResponseHandlerMapping[$experimentId]['name'];

            $experimentTag = $this->experimentToResponseHandlerMapping[$experimentId]['tag'];

            $responseHandlerMethod = 'handle' . $experimentName . 'Response';

            $this->experimentResults[$experimentTag] = $this->$responseHandlerMethod($experimentResponse);
        }

        return $this->experimentResults;
    }

    /**
     *  initialise one cc values which we want to see if experiment fails for some reason
     */
    private function fillDefaultValuesForOneCc()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::ONE_CLICK_CHECKOUT) === false)
        {
            return;
        }

        $oneCcExperiments = [
            '1cc_offers_fix_exp'                                 => 'control',
            '1cc_enable_v165_exp'                                => 'control',
            '1cc_address_flow_exp'                               => 'control',
            '1cc_multiple_shipping'                              => 'control',
            '1cc_show_coupon_callout_exp'                        => 'control',
            '1cc_coupons_with_se_exp'                            => 'control',
            '1cc_enable_shopify_taxes'                           => 'control',
            '1cc_upi_qr_v2'                                      => 'control',
        ];

        $this->experimentResults = array_merge($this->experimentResults, $oneCcExperiments);
    }

    private function handleEligibilityOnStdCheckoutResponse($response):bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleCheckoutRedesignResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleUpiQrV2Response($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringRedesignResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleReuseUpiPaymentIdResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringUpiIntentResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringIntlVerifyPhoneResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringUpiQrResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringUpiPaymentMethodConfigurationResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleRecurringUpiPspResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleBankingRedesignResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleEmailLessCheckoutResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleEnableRudderstackPluginResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleTruecallerStandardCheckoutForPrefillResponse($response): string
    {
        $variant = $response['variant']['name'] ?? 'control';

        return match ($variant)
        {
            'variant_1' => 'home_and_add_card',
            'variant_2' => 'access_saved_cards_and_add_card',
            'variant_3' => 'home_and_access_saved_cards_and_add_card',
            default => 'control',
        };
    }

    private function handleTruecallerStandardCheckoutForNonPrefillResponse($response): string
    {
        $variant = $response['variant']['name'] ?? 'control';

        return match ($variant)
        {
            'variant_1' => 'contact',
            default => 'control',
        };
    }

    private function handleTruecallerOneCCForPrefillResponse($response): string
    {
        $variant = $response['variant']['name'] ?? 'control';

        return match ($variant)
        {
            'variant_1' => 'test',
            default => 'control',
        };
    }

    private function handleTruecallerOneCCForNonPrefillResponse($response): string
    {
        $variant = $response['variant']['name'] ?? 'control';

        return match ($variant)
        {
            'variant_1' => 'test',
            default => 'control',
        };
    }

    private function handleCheckoutDowntimeResponse($response): string
    {
        return $response['variant']['name'] ?? 'control';
    }

    private function handleUpiNumberResponse($response): string
    {
        return $response['variant']['name'] ?? 'control';
    }

    private function handleCvvLessResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleCvvLessRupayResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleMagicGeneralExperimentResponse($response): string
    {
        return $response['variant']['name'] ?? 'control';
    }

    private function handleEnableAutoSubmitResponse($response): string
    {
        return $response['variant']['name'] ?? 'control';
    }

    private function handleDccVasMerchantsResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleCheckoutOffersUxResponse($response): bool
    {
        return $response['variant']['name'] === 'variant_on';
    }

    private function handleEmiViaCardRevampResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleUpiTurboResponse($response): bool
    {
        return $response['variant']['name'] === 'variant_on';
    }
  
    private function handleEnableOtpAutoReadAndAutoSubmitResponse($response): string
    {
        return $response['variant']['name'] ?? 'control';
    }
}
