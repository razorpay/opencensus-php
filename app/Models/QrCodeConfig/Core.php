<?php

namespace RZP\Models\QrCodeConfig;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\QrCodeConfig\Entity as Entity;


class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = (new Repository());

        $this->entity = (new Entity());
    }

    public function deletePreviousConfigs()
    {
        $this->trace->info(TraceCode::QR_CODE_CONFIG_DELETE_PREVIOUS_CONFIG);

        $entity = $this->repo->findQrCodeConfigsByMerchantIdAndKey($this->merchant->getId(),
                                                                   Keys::CUT_OFF_TIME);

        if ($entity == null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_NO_PREVIOUS_CONFIG,
                null,
                [
                ]);
        }

        $entity->setDeletedAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($entity);

        $this->trace->info(TraceCode::QR_CODE_CONFIG_DELETE_PREVIOUS_COMPLETED);

        return true;
    }

    public function createQrCodeConfigs($input)
    {
        $configs = $this->entity->build($input);

        $configs->merchant()->associate($this->merchant);

        $this->repo->saveOrFail($configs);

        return $configs;
    }

    public function updateQrCodeConfigs($input)
    {
        $configs = $this->transaction(function() use ($input) {

            $this->deletePreviousConfigs();

            $configs = $this->createQrCodeConfigs($input);

            return $configs;
        });

        return $configs;
    }

    public function fetchQrCodeConfigs()
    {
        $config = $this->repo->findQrCodeConfigsByMerchantIdAndKey($this->merchant->getId(),
                                                                   Keys::CUT_OFF_TIME);

        return $config;
    }

}
