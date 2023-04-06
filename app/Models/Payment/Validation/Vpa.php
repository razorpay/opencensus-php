<?php

namespace RZP\Models\Payment\Validation;

use RZP\Models\Payment\Processor\Vpa as VpaTrait;
use RZP\Models\Payment;


class Vpa extends Base
{
    use VpaTrait;

    public function processValidation($input)
    {
        $methodInput = [$input['entity'] => $input['value']];

        if(isset($input['_'][Payment\Analytics\Entity::LIBRARY]))
        {
            $methodInput[Payment\Analytics\Entity::LIBRARY] = $input['_'][Payment\Analytics\Entity::LIBRARY];
        }

        return $this->validateVpa($methodInput);
    }
}
