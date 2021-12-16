<?php

namespace RZP\Models\QrCodeConfig; 

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    private function preProcessInput($input)
    {
        $processedInput = [
                Entity::KEY   => Keys::CUT_OFF_TIME,
                Entity::VALUE => $input[Keys::CUT_OFF_TIME],
        ];

        return $processedInput;
    }

    private function preProcessOutput($output)
    {
        $processedOutput[$output[Entity::KEY]] = $output[Entity::VALUE];

        return $processedOutput;
    }

    public function checkIfExperimentEnabled()
    {
        $variant = $this->app->razorx->getTreatment($this->merchant->getId(), RazorxTreatment::QR_CODE_CUTOFF_CONFIG, $this->mode);

        if ($variant !== 'on')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_EXPERIMENT_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    public function create($input)
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_CREATE_REQUEST, $input);

        $input = $this->preProcessInput($input);

        $this->checkIfExperimentEnabled();

        $configs = $this->core()->createQrCodeConfigs($input);

        $configs = $this->preProcessOutput($configs);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_CREATED, $configs);

        return $configs;
    }

    public function update($input)
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_UPDATE_REQUEST, $input);

        $input = $this->preProcessInput($input);

        $this->checkIfExperimentEnabled();

        $configs = $this->core()->updateQrCodeConfigs($input);

        $configs = $this->preProcessOutput($configs);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_UPDATE, $configs);

        return $configs;
    }

    public function fetchQrCodeConfigs()
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCH_REQUEST);

        $this->checkIfExperimentEnabled();

        $configs = $this->core()->fetchQrCodeConfigs();

        $configs = $this->preProcessOutput($configs);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCHED, $configs);

        return $configs;
    }

    public function delete()
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_DELETE_REQUEST);

        $this->checkIfExperimentEnabled();

        $response = $this->core()->deletePreviousConfigs();

        $this->trace->info(TraceCode::QR_CODE_CONFIG_DELETED, ['success' => $response]);

        return ['success' => $response];
    }
}
