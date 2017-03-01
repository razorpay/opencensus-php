<?php

namespace RZP\Models\Ecollect;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;
    }

    public function validate(array $input)
    {
        $this->trace->info(
            TraceCode::ECOLLECT_VALIDATION_REQUEST,
            $input
        );

        $this->validator->validateInput('validate', $input);

        $data = [
            'valid'   => true,
            'message' => null,
        ];

        if ((substr($input['payee_account'], 0, 3) !== 'RZP') and
            (substr($input['payee_account'], 0, 6) !== 'RAZORP'))
        {
            $data = [
                'valid'   => false,
                'message' => 'Invalid account number',
            ];
        }

        return $data;
    }

    public function pay(array $input)
    {
        $this->app['trace']->info(
            TraceCode::ECOLLECT_PAY_REQUEST,
            $input
        );

        $this->validator->validateInput('pay', $input);

        return [];
    }
}
