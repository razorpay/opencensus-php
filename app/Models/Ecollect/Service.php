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

        $this->cache = $this->app['redis'];
    }

    public function validate(array $input)
    {
        $this->trace->info(
            TraceCode::ECOLLECT_VALIDATION_REQUEST,
            $input
        );

        $this->validator->validateInput('validate', $input);

        $data = [
            'valid'          => true,
            'message'        => null,
        ];

        if ((substr($input['payee_account'], 0, 3) !== 'RZP') and
            (substr($input['payee_account'], 0, 6) !== 'RAZORP'))
        {
            $data = [
                'valid'          => false,
                'message'        => 'Invalid account number',
            ];
        }

        $data['transaction_id'] = $input['transaction_id'];

        $this->uniqueUtrCheck($input, $data);

        return $data;
    }

    public function pay(array $input)
    {
        $this->app['trace']->info(
            TraceCode::ECOLLECT_PAY_REQUEST,
            $input
        );

        $this->validator->validateInput('pay', $input);

        return [
            'success' => true,
        ];
    }

    protected function uniqueUtrCheck(array $input, array & $data)
    {
        if($data['valid'] === true)
        {
            $key = 'ecollect' . $this->mode . $input['transaction_id'];

            $cachedData = $this->cache->get($key);

            if (is_null($cachedData) === false)
            {
                $this->app['trace']->warning(
                    TraceCode::ECOLLECT_VALIDATION_DUPLICATE_UTR,
                    [
                        'cached_data'   => json_decode($cachedData, true),
                        'received_data' => $input,
                    ]
                );

                $data['valid']   = false;
                $data['message'] = 'Duplicate UTR received';
            }
            else
            {
                $this->cache->set($key, json_encode($input));
            }
        }
    }
}
