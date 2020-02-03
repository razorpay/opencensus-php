<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Base;

class CheckAccountData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function getsimpl($entities)
    {
        if ($entities['contact'] === '+918602579721')
        {
            return $this->redirectionFlow($entities);
        }
        elseif ($entities['contact'] === '+917602579721')
        {
            return $this->otpFlow();
        }
        else
        {
            $data = [
                'data'  => null,
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description'  => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED
                ],
                'success'           => false,
                'mozart_id'         => 'DUMMY_MOZART_ID',
                'external_trace_id' => 'DUMMY_REQUEST_ID'
            ];

            return $data;
        }
    }

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'account_number'            => '2847381947',
                    '_raw'                      => '',
                    'success'                   => true,
                    'status'                    => 'check_account_successful',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function otpFlow()
    {
        $response = [
            'data' =>
                [
                    'Http_status'               => 200,
                    'Eligibility_Status'        => 'eligible',
                    '_raw'                      => '{\"Eligibility_Status\":\"eligible\",\"error_code\":null,\"Http_status\":200,\"token\":\"227cf9d92470faba95148dd2b6c4cf5996ceba6a\",\"success\":true,\"available_credit_in_paise\":4999570,\"first_transaction\":null,\"redirection_url\":null}',
                    'available_credit_in_paise' => 4999570,
                    'error_code'                => null,
                    'simpltoken'                => '227cf9d92470faba95148dd2b6c4cf5996ceba6a',
                    'success'                   => true,
                    'status'                    => 'eligibility_successful',
                    'redirection_url'           => null,
                    'first_transaction'         => null
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function redirectionFlow($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            [
                'paymentId' => $entities['payment']['id'],
                'gateway'   => $entities['provider'],
            ]);

        $response = [
            'data' => [
                    'Http_status'               => 200,
                    'Eligibility_Status'        => 'eligible',
                    '_raw'                      => '',
                    'available_credit_in_paise' => 0,
                    'error_code'                => 'linking_required',
                    'simpltoken'                => null,
                    'success'                   => true,
                    'status'                    => 'eligibility_successful',
                    'redirection_url'           => $url,
                    'first_transaction'         => null
            ],
            'next'=> [
                'redirect'=> [
                    'url'       => $url,
                    'method'    => 'POST',
                    'content'   => [],
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }
}
