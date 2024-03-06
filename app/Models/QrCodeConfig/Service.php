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


    public function fetchStaticQrCodeConfig($terminal)
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCH_REQUEST);

        $terminalId = $terminal->getId();
        $merchantId = $terminal->getMerchantId();

        $configs = $this->fetchStaticQrCodeConfigsWithPreProcess($merchantId);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCHED, $configs);
        $staticQRs= $configs[Keys::STATIC_QR];

        if($staticQRs !== null)
        {
            $dataArray = json_decode($staticQRs,true);
            return $dataArray[$terminalId];
        }
        return null;
    }

    private function preProcessStaticQRInput($input)
    {
        $processedInput = [
            Entity::KEY   => Keys::STATIC_QR,
            Entity::VALUE => $input[Keys::STATIC_QR],
        ];

        return $processedInput;
    }

    public function createOrUpdateStaticQRCodeConfig($terminal, $qrCode)
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_CREATE_REQUEST,
                           [
                               'merchantId' => $this->merchant->getId(),
                               'terminalId' => $terminal->getId(),
                               'qrCodeId'   => $qrCode->getId()
                           ]);

        $staticQRs = $this->fetchStaticQrCodeConfigs();

        $staticQRsValue = $staticQRs[Keys::STATIC_QR] ?? null;

        $dataArray = $staticQRsValue !== null ? json_decode($staticQRsValue, true) : [];

        $dataArray[$terminal->getId()] = $qrCode->getId();

        $jsonData = json_encode($dataArray);

        $qrCodeConfigInput = [
            Keys::STATIC_QR => $jsonData,
        ];

        $input = $this->preProcessStaticQRInput($qrCodeConfigInput);

        if ($staticQRsValue !== null)
        {
            $configs = $this->core()->updateStaticQrCodeConfig($input);
        }
        else
        {
            $configs = $this->core()->createQrCodeConfigs($input);
        }

        $configs = $this->preProcessOutput($configs);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_CREATED, $configs);

        return $configs;
    }

    public function fetchStaticQrCodeConfigs()
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCH_REQUEST);

        $configs = $this->fetchStaticQrCodeConfigsWithPreProcess($this->merchant->getId());

        $this->trace->info(TraceCode::QR_CODE_CONFIG_FETCHED, $configs);

        return $configs;
    }

    private function fetchStaticQrCodeConfigsWithPreProcess($merchantId)
    {

        $configs = $this->core()->fetchStaticQrCodeConfig($merchantId);

        return $this->preProcessOutput($configs);
    }
}
