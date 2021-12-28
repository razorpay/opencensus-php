<?php

namespace RZP\Services\UpiPayment\Mock;

use RZP\Models\Payment;
use Psr\Http\Message\RequestInterface;
use RZP\Services\UpiPayment\Service as UpiPaymentService;

/**
 * Service implements all the UPS actions
 */
class Service extends UpiPaymentService
{
    /**
     * Mocks the request to the UPS server
     *
     * @param  RequestInterface $request
     * @return array
     */
    protected function sendRequest(RequestInterface $request): array
    {
        $content = json_decode($request->getBody()->getContents(), true);

        $action = $this->action;

        if ($this->action === Payment\Action::AUTHORIZE_FAILED)
        {
            $action = Payment\Action::VERIFY;
        }

        $action = camel_case($action);

        list($response, $code) = $this->$action($content);

        return [$response, $code];
    }

    /**
     * Authorize returns the authorize response
     *
     * @param  array $content
     * @return array
     */
    protected function authorize(array $content): array
    {
        $description = $content['payment']['description'];

        $response = [];

        $error = null;

        $code = 500;

        switch ($description)
        {
            case 'create_collect_success':
            case 'verify_amount_mismatch':
                $response['data'] = [
                    'data' => [
                        'vpa' => 'razorpay@airtel'
                    ],
                    'gateway' => $content['payment']['gateway'],
                ];
                $code = 200;

                break;
            case 'create_intent_success':
                $response['data'] = [
                    'data' => [
                        'intent_url' => 'upi://pay?am=100.00&cu=INR&mc=5411&pa=upi@razorpay
                                                &pn=merchantname&tn=PayviaRazorpay&tr=pay_someid'
                    ],
                    'gateway' => $content['payment']['gateway'],
                ];
                $code = 200;

                break;
            case 'validation_failure_collect_vpa':
                $error = [
                    'details' => [[
                        'internal' => [
                            'code'          => 'BAD_REQUEST_INPUT_VALIDATION_FAILURE',
                            'description'   => 'Vpa is required for UPI collect request'
                        ]
                    ]]
                ];
                $code = 400;

                break;
            case 'service_failure':
                $error = [
                    'error' => 'internal server error',
                ];
                $code = 500;

                break;
            case 'mozart_failure':
                $response['error'] = [
                    'internal' => [
                        'code'          => 'GATEWAY_ERROR_REQUEST_ERROR',
                        'description'   => 'GATEWAY_ERROR: received false response with status
                                                        200 from mozart',
                        'metadata'      => [
                            'description'               => 'Encryption error',
                            'gateway_error_code'        => 'U14',
                            'gateway_error_description' => 'Encryption error',
                            'internal_error_code'       => 'GATEWAY_ERROR_ENCRYPTION_ERROR'
                        ]
                    ]
                ];
                $code = 200;

                break;
            default:
                $response['data'] = [
                    'data' => [
                        'vpa' => 'razorpay@airtel'
                    ],
                    'gateway' => $content['payment']['gateway'],
                ];
                $code = 200;
        }

        if ($error != null)
        {
            return [$error, $code];
        }

        return [$response, $code];
    }

    /**
     * Pre process returns pre-process response.
     */
    protected function preProcess(array $content): array
    {
        $payload = json_decode($content['payload'], true);

        $data['data'] = [
            'version' => 'v2',
            'upi' => [
                'vpa' => $payload['payerVPA'] ?? '',
                'status_code' => $payload['errorCode'],
                'npci_reference_id' => $payload['rrn'],
                'merchant_reference' => $payload['hdnOrderID'],
            ],
            'payment' => [
                'currency' => 'INR',
                'amount_authorized' => $payload['amount'] * 100
            ],
            'terminal' => [
                'gateway_merchant_id' => 'MER0000000548542'
            ],
        ];

        $data['success'] = true;
        $data['error'] = null;
        $data['next'] = null;

        if ($payload['code'] !== '0')
        {
            $data['error'] = [
                'description'               => 'Debit has been failed',
                'gateway_error_code'        => 'U30',
                'gateway_error_description' => 'Debit has been failed',
                'gateway_status_code'       => 200,
                'internal_error_code'       => 'GATEWAY_ERROR_DEBIT_FAILED',
            ];

            $data['success'] = false;
        }

        $response = [
            'data'      => $data,
            'gateway'   => $content['gateway'],
            'error'     => null,
        ];

        return [$response, 200];
    }

    protected function callback(array $content): array
    {
        $data = $content['data']['data'];
        $error = $content['data']['error'] ?? null;
        $gateway = $content['gateway'];
        $statusCode = 200;

        $upi = $data['upi'];
        $payment = $data['payment'];

        $responseData = [
            'acquirer' => [
                'vpa'         => $upi['vpa'],
                'reference16' => $upi['npci_reference_id'],
            ],
            'amount_authorized' => $payment['amount_authorized'],
            'currency'          => $payment['currency'],
        ];

        $responseError = $this->content($error);

        $response = [
            'data'      => $responseData,
            'gateway'   => $gateway,
            'error'     => $responseError,
        ];

        return [$response, $statusCode];
    }

    protected function verify(array $content): array
    {
        $data = $content['data'];

        $responseData['data'] = [
            'upi' => [
                'vpa' => $data['payment']['vpa'] ?? '',
                'status_code' => '000',
                'npci_reference_id' => '987654321',
                'merchant_reference' => $data['payment']['id'],
            ],
            'payment' => [
                'currency' => 'INR',
                'amount_authorized' => $data['payment']['amount']
            ],
            'terminal' => [
                'gateway_merchant_id' => 'MER0000000548542'
            ],
        ];

        if ($data['payment']['description'] === 'verify_amount_mismatch')
        {
            $responseData['data']['payment']['amount_authorized'] = $data['payment']['amount'] + 100;
        }

        $responseData['success'] = true;

        $response = [
            'data'      => $responseData,
            'gateway'   => $content['gateway'],
            'error'     => null,
        ];

        return [$response, 200];
    }

    public function content(&$content)
    {
        return $content;
    }

    public function request(&$content)
    {
        return $content;
    }
}
