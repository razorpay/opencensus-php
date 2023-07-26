<?php

namespace RZP\Jobs\DCS;

use Cache;
use RZP\Jobs\Job;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Dcs\Features\Utility;


class EnableDisableReadFromDCS extends Job
{

    protected $mode;

    public $timeout = 12000;

    const LIMIT = 500;
    protected $input;

    public function __construct($input, $mode)
    {
        parent::__construct($mode);
        $this->input = $input;
        $this->mode = $mode;
    }

    public function handle(): void
    {
        parent::handle();
        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB, [
            'input' => $this->input
        ]);

        try
        {
            if(isset($this->input['operation']) === true and $this->input['operation'] === 'add')
            {
                $this->addFeatureInTheCache();
            }
            elseif(isset($this->input['operation']) === true and $this->input['operation'] === 'remove')
            {
                $this->removeFeatureFromTheCache();
            }
            else
            {
                $this->trace->info(TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB_FAILED, ["message" => "Wrong operation provided"]);
            }

        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB_FAILED
            );
        }
        finally
        {
            $this->delete();
        }
    }

    /**
     * @return void
     */
    public function addFeatureInTheCache(): void
    {
        if ((isset($this->input['key']) === true) and (isset($this->input['feature_name']) === true)
            and (str_contains($this->input['key'], ConfigKey::DCS_READ_WHITELISTED_FEATURES) === true))
        {
            $oldValue = Cache::get($this->input['key']);

            $featureNames = $this->input['feature_name'];

            if ($oldValue === null or $oldValue === "null")
            {
                $updatedValue = $featureNames;
            }
            else
            {
                $updatedValue = $oldValue;

                if (isset($featureNames['merchant']) === true)
                {
                    foreach ($featureNames['merchant'] as $merchantFeature => $service)
                    {
                        if ((isset($updatedValue['merchant'][$merchantFeature]) === false) or
                            $updatedValue['merchant'][$merchantFeature] !== $service)
                        {
                            $updatedValue['merchant'][$merchantFeature] = $service;
                        }
                    }
                }

                if (isset($featureNames['org']) === true)
                {
                    foreach ($featureNames['org'] as $merchantFeature => $service)
                    {
                        if ((isset($updatedValue['org'][$merchantFeature]) === false) or
                            ($updatedValue['merchant'][$merchantFeature] !== $service))
                        {
                            $updatedValue['org'][$merchantFeature] = $service;
                        }
                    }
                }
            }

            $adminService = new Service();

            $outputData = $adminService->setDCSConfigKey($this->input['key'], $updatedValue);

            $this->trace->info(TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB_RESULT, [
                "output_data" => $outputData
            ]);
        }
        else
        {
            $this->trace->info(TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB_FAILED, ["message" => "key is not valid"]);
        }
    }

    /**
     * @return void
     */
    public function removeFeatureFromTheCache(): void
    {
        if ((isset($this->input['key']) === true) and (isset($this->input['feature_name']) === true)
            and (str_contains($this->input['key'], ConfigKey::DCS_READ_WHITELISTED_FEATURES) === true))
        {
            $oldValue = Cache::get($this->input['key']);

            $featureNames = $this->input['feature_name'];

            if ($oldValue === null or $oldValue === "null")
            {
                return;
            }
            $updatedValue = $oldValue;

            if ((isset($featureNames['merchant']) === true))
            {
                foreach ($featureNames['merchant'] as $merchantFeature)
                {
                    if (isset($updatedValue['merchant'][$merchantFeature]) === true)
                    {
                        unset($updatedValue[['merchant'][$merchantFeature]]);
                    }
                }
                if (empty($updatedValue['merchant']) === true)
                {
                    unset($updatedValue['merchant']);
                }
            }

            if ((isset($featureNames['org']) === true))
            {
                foreach ($featureNames['org'] as $merchantFeature)
                {
                    if (isset($updatedValue['org'][$merchantFeature]) === true)
                    {
                        unset($updatedValue['org'][$merchantFeature]);
                    }
                }
                if (empty($updatedValue['org']) === true)
                {
                    unset($updatedValue['org']);
                }
            }

            $adminService = new Service();

            $outputData = $adminService->setDCSConfigKey($this->input['key'], $updatedValue);

            $this->trace->info(TraceCode::READ_ENABLE_FEATURES_VIA_DCS_JOB_RESULT, [
                "output_data" => $outputData
            ]);
        }
    }
}
