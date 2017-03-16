<?php

namespace RZP\Models\Ecollect;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
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

        $data = $this->validateReceiver($input);

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

    protected function validateReceiver($input)
    {
        if ($this->mode === Mode::TEST)
        {
            $data = $this->validateTestMode($input);
        }
        else
        {
            $data = $this->validateLiveMode($input);
        }

        return $data;
    }

    protected function validateLiveMode($input)
    {
        $paymentInput = $this->ecollectPaymentArray($input);

        // TODO: Id the merchant here using the input payee_account
        $merchant = $this->repo->merchant->find('10000000000000');

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        $paymentProcessor->process($paymentInput);
    }

    protected function validateTestMode($input)
    {
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

    protected function ecollectPaymentArray(array $input)
    {
        $paymentArray = $this->defaultEcollectPaymentArray();

        $paymentArray['amount'] = $input['amount'];

        $paymentArray['bank_transfer'] = $input;

        return $paymentArray;
    }

    protected function defaultEcollectPaymentArray()
    {
        return [
            'contact'  => '9999009999',
            'currency' => 'INR',
            'email'    => 'ecollect@razorpay.com',
            'method'   => 'bank_transfer',
        ];
    }
}
