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
            'checkout_redesign_v1_5' => false,
            'upi_ux'                 => 'existing_variant',
            'emi_ux_revamp'          => false,
            'upi_qr_v2'              => false,
            'cb_redesign_v1_5'       => false,
            'recurring_redesign_v1_5' => false,
            'reuse_upi_paymentId'     => false,
            'recurring_upi_intent_qr'=> false,
            'recurring_upi_all_psp'   => false,
            'banking_redesign_v15'    => false,
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

            $bulkEvaluateArray = json_encode($this->experimentsData, JSON_UNESCAPED_SLASHES);

            $bulkEvaluate = '{"bulk_evaluate":' . $bulkEvaluateArray . '}';

            $response = $this->app['splitzService']->bulkCallsToSplitz($bulkEvaluate);

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
            'app.checkout_recurring_upi_intent_qr_splitz_experiment_id',
            'RecurringUpiIntentQr',
            'recurring_upi_intent_qr',
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
        foreach ($response['response']['bulk_evaluate_response'] as $experimentResponse)
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

    private function handleRecurringUpiIntentQrResponse($response): bool
    {
        $variant = $response['variant'] ?? '';

        if($variant)
        {
            $variantArray =  $variant["variables"] ?? [];

            foreach ($variantArray as $eachVariant)
            {
                if($eachVariant["value"] === "on")
                {
                    return true;
                }
            }
        }

        return false;
    }

    private function handleRecurringUpiPspResponse($response): bool
    {
        $variant = $response['variant'] ?? '';

        if($variant)
        {
            $variantArray =  $variant["variables"] ?? [];

            foreach ($variantArray as $eachVariant)
            {
                if($eachVariant["value"] === "on")
                {
                    return true;
                }
            }
        }

        return false;
    }

    private function handleBankingRedesignResponse($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }
}
