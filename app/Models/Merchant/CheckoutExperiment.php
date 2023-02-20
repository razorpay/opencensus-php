<?php

namespace RZP\Models\Merchant;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;

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
            'upi_ux'                                             => 'existing_variant',
            'emi_ux_revamp'                                      => false,
            'upi_qr_v2'                                          => false,
            'cb_redesign_v1_5'                                   => false,
            'recurring_redesign_v1_5'                            => false,
            'reuse_upi_paymentId'                                => false,
            'recurring_upi_intent'                               => false,
            'recurring_upi_qr'                                   => false,
            'recurring_payment_method_configuration'             => false,
            'recurring_upi_all_psp'                              => false,
            'banking_redesign_v15'                               => false,
            'remove_default_tokenization_flag'                   => false,
            'truecaller_standard_checkout_for_prefill'           => 'control',
            'truecaller_standard_checkout_for_non_prefill'       => 'control',
            'truecaller_1cc_for_prefill'                         => 'control',
            'truecaller_1cc_for_non_prefill'                     => 'control',
            'email_less_checkout'                                => false,
            'enable_rudderstack_plugin'                          => false,
            'checkout_downtime'                                  => 'control',
            'upi_number'                                         => false, 
        ];

        $this->input = $input;

        $this->merchantId = $merchantId;
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

    /**
     * This method fills experiment data for all experiments we want to send to splitz service.
     * if you want to add new experiment, call the $this->fillExperimentData with your own parameters. just make sure
     * that experimentTag is same as what you used in default experiment results array($this->experimentResults)
     * @return void
     */
    private function fillSplitzExperimentsData(): void
    {
        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_redesign_v1_5_splitz_experiment_id',
            'CheckoutRedesign',
            'checkout_redesign_v1_5',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_upi_ux_splitz_experiment_id',
            'UpiUx',
            'upi_ux',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_emi_ui_revamp_splitz_experiment_id',
            'EmiUxRevamp',
            'emi_ux_revamp',
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
            'app.checkout_cb_redesign_v1_5_splitz_experiment_id',
            'CrossBorderRedesign',
            'cb_redesign_v1_5',
            ['merchant_id' => $this->merchantId]
        );

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
            'app.checkout_banking_redesign_v1_5_splitz_experiment_id',
            'BankingRedesign',
            'banking_redesign_v15',
            ['merchant_id' => $this->merchantId]
        );

        $this->fillExperimentData(
            UniqueIdEntity::generateUniqueId(),
            'app.checkout_remove_default_tokenization_flag_splitz_experiment_id',
            'RemoveDefaultTokenizationFlag',
            'remove_default_tokenization_flag',
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

    private function handleCheckoutRedesignResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleUpiUxResponse($response): string
    {
        return $response['variant']['name'] ?? 'existing_variant';
    }

    private function handleEmiUxRevampResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleUpiQrV2Response($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }

    private function handleCrossBorderRedesignResponse($response): bool
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

    private function handleRemoveDefaultTokenizationFlagResponse($response): bool
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

    private function handleUpiNumberResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }
}
