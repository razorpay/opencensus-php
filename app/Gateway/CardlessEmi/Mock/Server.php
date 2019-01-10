<?php

namespace RZP\Gateway\CardlessEmi\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\CardlessEmi\RequestFields;
use RZP\Gateway\CardlessEmi\ResponseFields;
use RZP\Gateway\CardlessEmi\Repository;

class Server extends Base\Mock\Server
{
    public function checkAccount()
    {
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
            'loan_agreement'   => 'link_to_loan_agreement'
        ];

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
        $array = json_decode($this->input, true);

        $content = [
            'entity'               => ResponseFields::PAYMENT,
            'rzp_payment_id'       => $array[ResponseFields::PAYMENT_ID],
            'provider_payment_id'  => '12345678',
            'status'               => 'authorized',
            'currency'             => $array[ResponseFields::CURRENCY],
            'amount'               => $array[ResponseFields::AMOUNT],
        ];

        $this->content($content, 'authorize');

        $content = json_encode($content);

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function verify($input)
    {
        $array = json_decode($this->input, true);

        $content = [
            'entity'              => ResponseFields::PAYMENT,
            'rzp_payment_id'      => $array[ResponseFields::PAYMENT_ID],
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

        $content = $this->makeJsonResponse($content);

        return $content;
    }

    public function makeJsonResponse($json)
    {
        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
