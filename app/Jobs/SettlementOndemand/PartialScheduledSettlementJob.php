<?php

namespace RZP\Jobs\SettlementOndemand;

use App;
use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Ondemand;
use Razorpay\Trace\Logger as Trace;

class PartialScheduledSettlementJob extends Job
{
    protected $mode;

    public $timeout = 7200;

    const LIMIT = 400;

    protected $metricsEnabled = true;

    public function __construct($mode)
    {
        parent::__construct($mode);

        $this->mode = $mode;
    }

    public function handle()
    {
        parent::handle();

        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB);

        try
        {
            $merchantIDsToExclude = $this->getMerchantIDsToExclude();

            $offset = 0;

            $i = 0;

            while (true)
            {
                $merchantIds = $this->repoManager
                                    ->feature
                                    ->fetchMerchantIdsWithFeatureInChunks(Feature\Constants::ES_AUTOMATIC_RESTRICTED, $offset, self::LIMIT);

                $i++;

                $offset = $i * self::LIMIT;

                if (empty($merchantIds) === true)
                {
                    break;
                }

                $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB_MERCHANT_IDS, [
                    "merchant_ids"  =>  $merchantIds
                ]);

                foreach ($merchantIds as $merchantId)
                {
                    if (in_array($merchantId, $merchantIDsToExclude, true))
                    {
                        // Exclude this merchant as it will be handled by the microservice as part of API Decomp.
                        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULE_MERCHANT_EXCLUDED_DECOMP, [
                            "merchant_id"   =>  $merchantId
                        ]);

                        continue;
                    }

                    PartialScheduledSettlementForMerchantJob::dispatch($this->mode, $merchantId);

                    $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_FOR_MERCHANT_JOB_DISPATCHED, [
                        "merchant_id"   =>  $merchantId
                    ]);
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_JOB_ERROR
            );
        }
        finally
        {
            $this->delete();
        }
    }

    private function getMerchantIDsToExclude(): array
    {
        $app = App::getFacadeRoot();

        $experimentId = $app['config']->get('app.restricted_scheduled_es_migration_experiment_id');
        $request = ['experiment_id' => $experimentId];
        $response = $app['splitzService']->evaluateRequest($request);

        $variables = $response['response']['variant']['variables'] ?? [];
        if (!is_array($variables)) {
            return [];
        }

        foreach ($variables as $variable) {
            if (is_array($variable) && $variable['key'] === 'mids') {
                return explode(',', $variable['value']);
            }
        }

        return [];
    }
}
