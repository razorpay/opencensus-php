<?php

namespace RZP\Services\Mock\NbPlus;

use App;
use Razorpay\IFSC\Bank;
use \WpOrg\Requests\Response;

use RZP\Models\Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Services\NbPlus\Netbanking as NetbankingBase;

class Netbanking extends NetbankingBase
{
    use DbEntityFetchTrait;

    static $staticCallbackRouteMap = [
        Payment\Gateway::NETBANKING_KVB    => 'gateway_payment_static_callback_post',
        Payment\Gateway::NETBANKING_CANARA => 'gateway_payment_callback_canara_post',
        Payment\Gateway::NETBANKING_KOTAK  => 'gateway_payment_callback_kotak_corp_post',
        Payment\Gateway::NETBANKING_RBL    => 'gateway_payment_static_callback_post',
        Payment\Gateway::NETBANKING_UCO    => 'gateway_payment_static_callback_post',
        Payment\Gateway::NETBANKING_HDFC   => 'gateway_payment_static_callback_post',
    ];

    public function sendRawRequest($request)
    {
        $action  = camel_case(explode('/', $request['url'])[1]);

        $content = $request['content'];

        $this->request($content, $action);

        $response = $this->$action($content);

        $this->content($response, $action);

        return $this->makeJsonResponse($response);
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }

    public function fetchNbplusData(array $request, string $entity)
    {
        $response = [
          'count'  => 1,
          'entity' => 'collection',
          'items'  => []
        ];

        if(array_key_exists('payment_ids', $request))
        {
            foreach ($request['payment_ids'] as $paymentId)
            {
                $response['items'][$paymentId] = [
                    'gateway_transaction_id' => str_random(),
                    'bank_transaction_id'    => str_random(),
                    'bank_account_number'    => str_random(),
                    'additional_data'        => [
                        'customer_id'            => str_random(),
                        'credit_account_number'  => str_random()
                    ],
                    'gateway_status'         => 'SUC',
                    'verification_id'        => str_random(),
                    'payment_id'             => $paymentId,
                ];
            }
        }
        else if (array_key_exists('verification_ids', $request))
        {
            foreach ($request['verification_ids'] as $verificationId)
            {
                $paymentEntity = $this->getDbLastPayment();

                $response['items'][$verificationId] = [
                    'gateway_transaction_id' => str_random(),
                    'bank_transaction_id'    => str_random(),
                    'bank_account_number'    => str_random(),
                    'additional_data'        => [
                        'customer_id'            => str_random(),
                        'credit_account_number'  => str_random()
                    ],
                    'gateway_status'         => 'SUC',
                    'verification_id'        => $verificationId,
                    'payment_id'             => $paymentEntity['id'],
                ];
            }
        }
        else
        {
            foreach ($request['bank_transaction_ids'] as $bankTransactionId)
            {
                $paymentEntity = $this->getDbLastPayment();

                $response['items'][$bankTransactionId] = [
                    'gateway_transaction_id' => str_random(),
                    'bank_transaction_id'    => str_random(),
                    'bank_account_number'    => str_random(),
                    'additional_data'        => [
                        'customer_id'            => str_random(),
                        'credit_account_number'  => str_random()
                    ],
                    'gateway_status'         => 'SUC',
                    'payment_id'             => $paymentEntity['id'],
                ];
            }
        }
        return $response;
    }

    protected function authorize($input)
    {
        $gateway = $this->gateway;

        if ($gateway === Payment\Gateway::NETBANKING_IBK)
        {
            return $this->getcallbackForIbk($input);
        }

        if ((Payment\Gateway::isStaticCallbackGateway($gateway) === true) and
            (Payment\Gateway::isWebhookEnabledGateway($gateway) === false) and
            (in_array($input['input']['payment']['bank'], [Bank::HDFC]) === false))
        {
            return $this->staticGatewayAuthorize($input, $gateway);
        }

        return [
            'response' => [
                'data' => [
                    'next' => [
                        'redirect' => [
                            'url' => $this->app['api.route']->getPublicCallbackUrlWithHash(
                                $input['input']['payment']['public_id'],
                                'rzp_test_TheTestAuthKey',
                                'payment_callback_post'
                            ),
                            'method'  => 'post',
                            'content' => [
                                'encdata' => 'dummy_response_data',
                            ]
                        ]
                    ]
                ]
            ],
            'error' => null
        ];
    }

    protected function callback($input)
    {
        return [
            'response' => [
                'data' => [
                    'gateway_reference_number' => '1234'
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

    protected function preprocessCallback($input)
    {
        return [
            'response' => [
                'payment_id' => $input['input']['gateway_data']['paymentId'],
            ],
            'error' => null
        ];
    }

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function request(& $content, $action = '')
    {
        return $content;
    }

    protected function staticGatewayAuthorize($input, $gateway): array
    {
        return [
            'response' => [
                'data' => [
                    'next' => [
                        'redirect' => [
                            'url' => $this->app['api.route']->getUrlWithPublicAuth(
                                self::$staticCallbackRouteMap[$gateway],
                                [
                                    'method'    => $input['input']['payment']['method'],
                                    'gateway'   => $input['gateway'],
                                    'mode'      => 'test',
                                    'paymentId' => $input['input']['payment']['id'],
                                    'amount'    => number_format($input['input']['payment']['amount'] / 100, 2, '.', ''),
                                ]),
                            'method' => 'get',
                            'content' => [
                                'encdata' => 'dummy_response_data',
                            ]
                        ]
                    ]
                ]
            ],
            'error' => null
        ];
    }

    protected function getcallbackForIbk($input): array
    {
        $bank = $input['input']['payment']['bank'];

        if (in_array($bank, ['ALLA', 'IDIB']) === true)
        {
            return [
                'response' => [
                    'data' => [
                        'next' => [
                            'redirect' => [
                                'url' => $this->app['api.route']->getPublicCallbackUrlWithHash(
                                    $input['input']['payment']['public_id'],
                                    'rzp_test_TheTestAuthKey',
                                    'payment_callback_post'
                                ),
                                'method'  => 'post',
                                'content' => [
                                    'encdata' => 'dummy_response_data',
                                ]
                            ]
                        ]
                    ]
                ],
                'error' => null
            ];
        }
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
