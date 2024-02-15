<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Jobs\FaVpaValidation;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\Payment\Processor\Vpa as VpaTrait;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class Vpa extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    public function preProcessValidation()
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_VPA_VALIDATE_JOB_REQUEST,
            [
                'fav_id' => $this->validation->getId(),
            ]
        );

        FaVpaValidation::dispatch($this->mode, $this->validation->getId());

        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_VPA_VALIDATE_JOB_REQUEST_DISPATCHED,
            [
                'fav_id' => $this->validation->getId(),
            ]
        );
    }

    public function setDefaultValuesForValidation()
    {
        return;
    }
}
