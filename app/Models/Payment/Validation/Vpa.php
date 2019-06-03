<?php

namespace RZP\Models\Payment\Validation;

use RZP\Models\Payment\Processor\Vpa as VpaTrait;


class Vpa extends Base
{
    use VpaTrait;

    public function processValidation($input)
    {
        $methodInput = [$input['entity'] => $input['value']];

        return $this->validateVpa($methodInput);
    }
}
