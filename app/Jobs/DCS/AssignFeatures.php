<?php

namespace RZP\Jobs\DCS;

use RZP\Error\ErrorCode;
use RZP\Jobs\Job;
use RZP\Exception;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;

class AssignFeatures extends Job
{
    protected $mode;

    public $timeout = 7200;

    const LIMIT = 500;
    protected $input;

    public function __construct($input, $mode)
    {
        parent::__construct($mode);

        $this->input = $input;
        $this->mode = $mode;
    }

    public function handle()
    {
        parent::handle();
        $this->assign();
    }

    protected function assign(): void
    {
        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::DCS_EDIT_FEATURE_SCHEDULED_JOB, [
            'input' => $this->input
        ]);

        try
        {
            $offset = 0;

            $i = 0;
            $variant = $this->getDcsEditVariant($this->input['name'], $this->mode);
            if ($variant === 'control')
            {
                $data = [
                    'variant'  => $variant,
                    'name'    => $this->input['name'],
                    'type'    => $this->input['entity_type'],
                    'mode'      => $this->mode
                ];
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_DCS_DISABLED, null, $data);
            }

            while (true)
            {
                $entityIds = $this->repoManager
                    ->feature
                    ->fetchEntityIdsWithFeatureInChunks($this->input['name'], $this->input['entity_type'], $offset, self::LIMIT);

                $i++;

                $offset = $i * self::LIMIT;

                if (empty($entityIds) === true)
                {
                    break;
                }

                $this->trace->info(TraceCode::DCS_EDIT_FEATURE_SCHEDULED_JOB_MERCHANT_IDS, [
                    "entity_ids"  =>  sizeof($entityIds),
                    'offset' => $offset,
                    'limit' => self::LIMIT
                ]);

                // Split $entityIds into chunks of 500
                $entityIdChunks = array_chunk($entityIds, 500);

                foreach ($entityIdChunks as $chunk) {
                    AssignMerchantFeatures::dispatch($this->mode, $variant, $this->input['name'], $this->input['entity_type'], $chunk);
                    $this->trace->info(TraceCode::DCS_EDIT_FEATURE_SCHEDULED_FOR_MERCHANT_JOB_DISPATCHED, [
                        "entity_ids_size" => sizeof($chunk)
                    ]);

                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DCS_EDIT_FEATURE_JOB_FAILED
            );
        }
        finally
        {
            $this->delete();
        }
    }
    public function getDcsEditVariant($featureName, $mode)
    {
        $mode = $mode ?? 'live';
        $flag = app('razorx')->getTreatment($featureName,
            RazorxTreatment::DCS_EDIT_ENABLED,
            $mode);
        $this->trace->info(TraceCode::DCS_RAZORX_EXPERIMENT, [
            'feature_name' => $featureName,
            'razorx_treatment' => RazorxTreatment::DCS_EDIT_ENABLED,
            'razorx_output' => $flag,
            'mode' => $mode,
        ]);
        return $flag;
    }
}
