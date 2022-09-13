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

            $bulk_evaluate = '{"bulk_evaluate":' . $bulkEvaluateArray . '}';

            $response = $this->app['splitzService']->bulkCallsToSplitz($bulk_evaluate);

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
     * This method fills experiment data for all experiments we want to have.
     * if you want to add new experiment, add a new fill data method and
     * call it in this method to fill its data.
     * @return void
     */
    private function fillSplitzExperimentsData(): void
    {
        $this->experimentsData[] = $this->fillCheckoutRedesignExperimentData();

        $this->experimentsData[] = $this->fillUpiUxExperimentData();

        $this->experimentsData[] = $this->fillEmiRevampExperimentData();

        if ($this->shouldIncludeUpiQrV2Experiment())
        {
            $this->experimentsData[] = $this->fillUpiQrV2ExperimentData();
        }
    }

    private function fillCheckoutRedesignExperimentData(): array
    {
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.checkout_redesign_v1_5_splitz_experiment_id'),
        ];
    }

    private function fillUpiUxExperimentData(): array
    {
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.checkout_upi_ux_splitz_experiment_id'),
        ];
    }

    private function fillEmiRevampExperimentData(): array
    {
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.checkout_emi_ui_revamp_splitz_experiment_id'),
        ];
    }

    private function fillUpiQrV2ExperimentData(): array
    {
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.checkout_upi_qr_v2_splitz_experiment_id'),
            'request_data'  => json_encode(['merchant_id' => $this->merchantId]),
        ];
    }

    private function shouldIncludeUpiQrV2Experiment(): bool
    {
        return filter_var($this->input['qr_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * This method just goes through all experiments responses and allows us to handle
     * those responses the way we want for each experiment. if you are creating new
     * experiment, add new handle method for that experiment and call it here.
     *
     * @param $response
     * @return array
     */
    private function handleExperimentResponses($response): array
    {
        $checkoutRedesignExperimentId = $this->app['config']->get('app.checkout_redesign_v1_5_splitz_experiment_id');
        $upiUxExperimentId            = $this->app['config']->get('app.checkout_upi_ux_splitz_experiment_id');
        $emiRevampExperimentId        = $this->app['config']->get('app.checkout_emi_ui_revamp_splitz_experiment_id');
        $upiQrV2ExperimentId          = $this->app['config']->get('app.checkout_upi_qr_v2_splitz_experiment_id');

        foreach ($response['response']['bulk_evaluate_response'] as $experimentResponse)
        {
            $experimentId = $experimentResponse['experiment']['id'];

            if ($experimentId === $checkoutRedesignExperimentId)
            {
                $this->experimentResults['checkout_redesign_v1_5'] = $this->handleCheckoutRedesignResponse($experimentResponse);
            }
            elseif ($experimentId === $upiUxExperimentId)
            {
                $this->experimentResults['upi_ux'] = $this->handleUpiUxResponse($experimentResponse);
            }
            elseif ($experimentId === $emiRevampExperimentId)
            {
                $this->experimentResults['emi_ux_revamp'] = $this->handleCheckoutRedesignResponse($experimentResponse);
            }
            elseif ($experimentId === $upiQrV2ExperimentId)
            {
                $this->experimentResults['upi_qr_v2'] = $this->handleUpiQrV2Response($experimentResponse);
            }
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

    private function handleUpiQrV2Response($response): bool
    {
        $variant = $response['variant']['name'] ?? '';

        return $variant === 'variant_on';
    }
}
