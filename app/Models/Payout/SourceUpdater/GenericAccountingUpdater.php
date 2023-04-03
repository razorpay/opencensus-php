<?php

namespace RZP\Models\Payout\SourceUpdater;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Status;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;

class GenericAccountingUpdater extends Base
{
    const GENERIC_AI_ENABLED_EXPERIMENT             = 'app.generic_ai_enabled_experiment_id';
    const GENERIC_AI_ENABLED_EXPERIMENT_RESULT_MOCK = 'app.generic_ai_enabled_experiment_result_mock';
    const ENABLE                                    = "enable";
    const SPLITZ_EVALUATION_ID                      = "id";
    const SPLITZ_EXPERIMENT_ID                      = "experiment_id";

    public function update()
    {
        if (self::isGAIExperimentEnabled($this->payout->getMerchantId()) === false)
        {
            return null;
        }

        if (($this->payout->getStatus() != Status::PROCESSED) and
            ($this->payout->getStatus() != Status::REVERSED))
        {
            return null;
        }

        $trace = $this->app['trace'];

        $merchantId = $this->payout->getMerchantId();

        $trace->info(TraceCode::GENERIC_ACCOUNTING_PAYOUT_UPDATER_INFO, [
            "merchant_id" => $merchantId,
        ]);

        if ($this->mode != Mode::LIVE)
        {
            return null;
        }

        try
        {
            $gaiService = $this->app['accounting-integration-service'];

            $gaiService->pushPayoutStatusUpdate($this->payout, $this->mode);
        }
        catch (\Exception $e)
        {
            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::GENERIC_ACCOUNTING_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
        }
    }

    public static function isGAIExperimentEnabled($merchantId): bool
    {
        $app = App::getFacadeRoot();

        if($app['env'] === Environment::TESTING)
        {
            return $app['config']->get(self::GENERIC_AI_ENABLED_EXPERIMENT_RESULT_MOCK);
        }

        $trace = $app['trace'];

        $trace->info(TraceCode::GENERIC_ACCOUNTING_PAYOUT_UPDATER_CHECK_EXPERIMENT, [
            "merchant_id" => $merchantId,
        ]);

        $properties = [
            self::SPLITZ_EVALUATION_ID => $merchantId,
            self::SPLITZ_EXPERIMENT_ID => $app['config']->get(self::GENERIC_AI_ENABLED_EXPERIMENT),
        ];

        $response = $app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $trace = $app['trace'];

        $trace->info(TraceCode::GENERIC_ACCOUNTING_PAYOUT_CHECK_EXPERIMENT_RESP, [
            "merchant_id"   => $merchantId,
            "response"      => $response,
            'splitz_output' => $variant,
        ]);

        return ($variant === self::ENABLE);
    }
}
