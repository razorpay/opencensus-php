<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
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
        FaVpaValidation::dispatch($this->mode, $this->validation->getId());
    }

    public function setDefaultValuesForValidation()
    {
        return;
    }
}
