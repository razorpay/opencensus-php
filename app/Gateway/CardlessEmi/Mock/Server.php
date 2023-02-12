<?php

namespace RZP\Gateway\CardlessEmi\Mock;

use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Gateway\CardlessEmi\Action;
use RZP\Gateway\CardlessEmi\RequestFields;
use RZP\Gateway\CardlessEmi\ResponseFields;

class Server extends Base\Mock\Server
{
    public function checkAccount($input)
    {
        $jsonRequest = json_decode($input, true);

        $content = [
            'account_exists'  => true,
            'emi_plans'       => [
                [
                    'entity'           => 'emi_plan',
                    'duration'         => 3,
                    'interest'         => 13,
                    'currency'         => 'INR',
                    'amount_per_month' => '1000.20'
                ],
                [
                    'entity'           => 'emi_plan',
                    'duration'         => 6,
                    'interest'         => 19,
                    'currency'         => 'INR',
                    'amount_per_month' => '1000.20'
                ]
            ],
            'loan_agreement'   => 'link_to_loan_agreement',
            'redirection_url'  => 'dummy_redirect_url',
            'extra'            => 'lender_brand',
        ];
        if(isset($jsonRequest['redirect_url']))
        {
            $content[ResponseFields::REDIRECT_URL_EARLYSALARY]='dummy_redirect_url'."?callback_url=".$jsonRequest['redirect_url']."&rzp_payment_id=".$jsonRequest['rzp_payment_id']."&amount=".$jsonRequest['amount']."&currency=INR";
        }


        if (isset($jsonRequest[RequestFields::TRANSACTION_TYPE]) and $jsonRequest[RequestFields::TRANSACTION_TYPE] === 'PAY_LATER')
        {
            $content = [
                'account_exists'   => true,
                'redirection_url'  => 'dummy_redirect_url',
                'extra'            => 'lender_brand',
            ];
        }

        if ($jsonRequest['mobile_number'] === "+919918899021")
        {
            $content = [
                'account_exists'   => false,
                'error_code'       => 'USER_DNE',
                'status_code'      => 404,
            ];
        }
        $this->content($content, 'check_account');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function fetchToken()
    {
        $content = [
            'token'  => '123456',
            'expiry' => 1539867543,
        ];

        $this->content($content, 'fetch_token');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function authorize($input)
    {
        parent::capture($input);

        $array = $this->getInputData($input);

        $content = [
            'entity'               => ResponseFields::PAYMENT,
            'rzp_payment_id'       => $array[ResponseFields::PAYMENT_ID],
            'provider_payment_id'  => '12345678',
            'status'               => 'authorized',
            'currency'             => $array[ResponseFields::CURRENCY],
            'amount'               => $array[ResponseFields::AMOUNT]
        ];

        $checksum = $this->generateCheckSum($content);

        $content['checksum'] = $checksum;

        $this->content($content, 'authorize');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function getInputData($input)
    {
        switch (is_array($input))
        {
            case true:
                return $input;
            default:
                return json_decode($this->input, true);
        }
    }

    public function verify($input)
    {
        parent::verify($input);

        $array = $this->getInputData($input);

        $id = $array[ResponseFields::PAYMENT_ID] ?? null;

        list($isEpayLater, $paymentId) = $this->getEpayLaterIdIfApplicable();

        if ($isEpayLater === true)
        {
            $id = $paymentId;
        }

        $content = [
            'entity'              => ResponseFields::PAYMENT,
            'rzp_payment_id'      => $id,
            'provider_payment_id' => '12345678',
            'status'              => 'authorized',
            'currency'            => 'INR',
            'amount'              => 50000.0,
        ];

        $this->content($content, 'verify');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function capture($input)
    {
        parent::capture($input);

        $array = json_decode($this->input, true);

        $content = [
            'entity'              => ResponseFields::PAYMENT,
            'rzp_payment_id'      => $array[ResponseFields::PAYMENT_ID],
            'provider_payment_id' => '987654',
            'status'              => 'captured',
            'currency'            => $array[ResponseFields::CURRENCY],
            'amount'              => $array[ResponseFields::AMOUNT]
        ];

        $this->content($content, 'capture');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function refund($input)
    {
        $array = json_decode($this->input, true);

        $content = [
            'entity'              => ResponseFields::REFUND,
            'rzp_payment_id'      => $array['rzp_payment_id'],
            'rzp_refund_id'       => $array['rzp_refund_id'],
            'provider_payment_id' => 9876543,
            'provider_refund_id'  => 1234567,
            'amount'              => $array['amount'],
            'status'              => 'success'
        ];

        $this->content($content, 'refund');

        $content = json_encode($content);

        return $this->makeJsonResponse($content);
    }

    public function verifyRefund($input)
    {
        $array = json_decode($this->input, true);

        $content = [
            'entity'              => ResponseFields::REFUND,
            'rzp_refund_id'       => $array['rzp_refund_id'],
            'provider_payment_id' => 9876543,
            'provider_refund_id'  => 1234567,
            'status'              => 'success'
        ];

        $this->content($content, 'verify_refund');

        $content = json_encode($content);

        return $this->makeJsonResponse($content);
    }

    public function makeJsonResponse($json)
    {
        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    public function getCallbackResponseForPayment($input)
    {
        $content = [
            'entity'                => 'payment',
            'rzp_payment_id'        => 'rzp_payment_id',
            'provider_payment_id'   => 'provider_payment_id',
            'status'                => 'authorized',
            'currency'              => 'INR',
            'amount'                => 1800,
            'checksum'              => 'T+rsyMw/9mdWHhv1QjXU5uZtOSKHBraRoaI4arns3Go='
        ];

        return $content;
    }

    public function generateCheckSum($input)
    {
        $str = '';

        ksort($input);

        foreach ($input as $key => $value)
        {
            $str .= $key . '=' . $value . '|';
        }

        $str = rtrim($str, '|');

        $secret = '23MTPU209562JTP28T';

        return base64_encode(hash_hmac(HashAlgo::SHA256, $str, $secret, true));
    }

    protected function getEpayLaterIdIfApplicable()
    {
        $url = $this->mockRequest['url'];

        if ($this->action === Action::VERIFY)
        {
            $array = explode('payments/', $url);

            if (count($array) === 2)
            {
                return [true, $array[1]];
            }
        }
        return [false, null];
    }
}
