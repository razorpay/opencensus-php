<?php

namespace RZP\Services\Mock\NbPlus;

use RZP\Models\Payment\Gateway;

use RZP\Services\NbPlus\Wallet as WalletBase;

class Wallet extends WalletBase
{
    public function sendRawRequest($request)
    {
        $action  = camel_case(explode('/', $request['url'])[1]);

        $content = $request['content'];

        $this->request($content, $action);

        $response = $this->$action($content);

        $this->content($response, $action);

        return $this->makeJsonResponse($response);
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function authorize($input)
    {
        if($input['gateway'] === 'wallet_paypal') {
            return [
                'response' => [
                    'data' => [
                        'next' => [
                            'redirect' => [
                                'url'      => 'https://api.razorpay.com/v1/gateway/mock/mozart/wallet_paypal?callbackUrl='.($this->app['api.route']->getPublicCallbackUrlWithHash(
                                    $input['input']['payment']['public_id'],
                                    'rzp_test_TheTestAuthKey')
                                ),
                                'method'  => 'get',
                                'content' => []
                            ]
                        ]
                    ]
                ],
                'error' => null
            ];
        }
        if ($input['input']['token'] === null)
        {
            $url = $this->app['api.route']->getPublicCallbackUrlWithHash(
                $input['input']['payment']['public_id'],
                'rzp_test_TheTestAuthKey'
            );

            $otpFlowWallets = [
                'wallet_freecharge',
                Gateway::WALLET_BAJAJ,
            ];

            if (in_array($input['input']['payment']['gateway'], $otpFlowWallets, true) === true)
            {
                $url = $this->app['api.route']->getPublicCallbackUrlWithHash(
                    $input['input']['payment']['public_id'],
                    $input['input']['payment']['public_key'],
                    'payment_otp_submit'
                );
            }

            return [
                'response' => [
                    'data' => [
                        'next' => [
                            'redirect' => [
                                'url'      => $url,
                                'method'  => 'post',
                                'content' => []
                            ]
                        ]
                    ]
                ],
                'error' => null
            ];
        }
        elseif ($input['input']['payment']['contact'] === "+918448720400")
        {
            return [
                'response' => [
                    'data' => [
                        'gateway_reference_number' => "9dQMF1u4Uomspn_pay_IdZOtyKHHVE8LG_1",
                        'gateway_status'=> true,
                    ],
                ],
                'error' => null
            ];
        }
        else
        {
            return [
                'response' => null,
                'error' => [
                    'error_type' => "GATEWAY",
                    'code'       => "PAYMENT_WALLET_INSUFFICIENT_BALANCE_ERROR",
                    "description" => "Wallet does not have sufficient balance",
                    "custom_message" => "",
                    "cause"=> [
                        "internal_error_code" => "BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE",
                        "description" => "Payment failed due to insufficient balance in wallet"
                    ],
                ],
            ];
        }
    }

    public function callback($input)
    {
        if (isset($input['input']['payment']['gateway']) && $input['input']['payment']['gateway'] === 'wallet_phonepe')
        {
            return [
                'response' => [
                    'data' => [
                        'gateway_reference_number'   => "gateway_ref_no",
                        'gateway_status'=> false,
                    ],
                ],
                'error' => null
            ];
        }

        if (isset($input['input']['gateway']) && isset($input['input']['gateway']['otp']) && $input['input']['gateway']['otp'] === "200000")
        {
            return [
                'response' => null,
                'error' => [
                    'error_type' => "GATEWAY",
                    "cause"=> [
                        "internal_error_code" => "BAD_REQUEST_PAYMENT_OTP_INCORRECT",
                    ]
                ]
            ];
        }
        elseif ((isset($input['input']['gateway']) and
                isset($input['input']['gateway']['type']) and
                $input['input']['gateway']['type'] === "otp") and
                $input['input']['payment']['gateway'] === Gateway::WALLET_FREECHARGE)
        {
            return [
                'response' => [
                    'data' => [
                        'token' => [
                            'method'                 => "wallet",
                            'wallet'                 => "freecharge",
                            'terminal_id'            => "100FrchrgeTmnl",
                            'gateway_token'          => "57lI6LcL9JWVgBODyUOumbD5Ig3abjQHmH-aqeTmJWQmryPG1hWs11PBIaKdmz1jtPxKhKPY2_RS0sjty1VMq1GGA16X35K9HloZGK7uSZdAWIKRK2hM3Xh8fGGEr0bp",
                            'gateway_token2'         => "cdVUIEk_X71Gh74NupywppswQ1C3zKJJLkY4xh5fVx1sVLkNGcOfi_DG9AuSXcWhbwYwGCNhFuD7T9biqy7DLw",
                            'expired_at'             => "1735705800",
                        ],
                        'gateway_reference_number'   => null,
                        'gateway_status'=> false,
                    ],
                ],
                'error' => null
            ];
        }
        else
        {
            return [
                'response' => [
                    'data' => [
                        'gateway_reference_number' => "9dQMF1u4Uomspn_pay_IdZOtyKHHVE8LG_1",
                        'gateway_status'=> true,
                    ],
                ],
                'error' => null
            ];
        }
    }

    protected function topup($input)
    {
        return [
            'response' => [
                'data' => [
                    'next' => [
                        'redirect' => [
                            'url'      => $this->app['api.route']->getPublicCallbackUrlWithHash(
                                $input['input']['payment']['public_id'],
                                'rzp_test_TheTestAuthKey',
                                'payment_callback_post'
                            ),
                            'method'  => 'post',
                            'content' => []
                        ]
                    ]
                ]
            ],
            'error' => null
        ];
    }

    protected function verify($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_status'           => true,
                    'gateway_reference_number' => '1234'
                ]
            ],
            'error' => null
        ];
    }

    protected function otpResend($input)
    {
        return [
            'response' => [
                'data' => [
                    'next' => [
                        'redirect' => [
                            'url'      => $this->app['api.route']->getPublicCallbackUrlWithHash(
                                $input['input']['payment']['public_id'],
                                'rzp_test_TheTestAuthKey',
                                'payment_callback_post'
                            ),
                            'method'  => 'post',
                            'content' => []
                        ]
                    ]
                ]
            ],
            'error' => null
        ];
    }


    protected function makeJsonResponse(array $content)
    {
        $response = new \WpOrg\Requests\Response();

        $response->headers = ['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache'];

        $response->status_code = 200;

        $response->body = json_encode($content);

        return $response;
    }

}
