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

        $data = [
            'valid'   => true,
            'message' => null,
        ];

        try
        {
            $this->validator->validateInput('validate', $input);
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->traceException($e);

            $data = [
                'valid'   => false,
                'message' => $e->getMessage(),
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
