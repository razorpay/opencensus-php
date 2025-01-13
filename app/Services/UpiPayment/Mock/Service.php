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

        if ($this->action === Payment\Action::AUTHORIZE_FAILED or
            $this->action === Payment\Action::VERIFY_RECURRING)
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
        $this->request($content, __FUNCTION__);

        $description = $content['payment']['description'];

        $remark = 'Test Merchant ' . preg_replace('/[^a-zA-Z0-9 ]+/', '', $description);

        assertTrue($content['metadata']['remark'] === $remark);

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
            case 'mozart_validation_failure':
                $response['error'] = [
                    'internal' => [
                        'code'          => 'BAD_REQUEST_VALIDATION_FAILURE',
                        'description'   => 'BAD_REQUEST_VALIDATION_ERROR: received false
                                            response with status 400 from mozart',
                        'metadata'      => [
                            'description'               => 'INPUT_VALIDATION_FAILED',
                            'gateway_error_code'        => '',
                            'gateway_error_description' => '',
                            'internal_error_code'       => 'BAD_REQUEST_VALIDATION_FAILURE',
                            'http_code'                 => 400
                        ]
                    ]
                ];
                $code = 200;

                    break;
            case 'tpv_order_success':
                assertTrue($content['metadata']['tpv'] === true);
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
        if ($this->isTerminalRequiredForPreProcess($content['gateway']) === true)
        {
            assertTrue($content['data']['terminal'], $content['gateway']);
        }

        $payload = $content['data']['gateway']['payload'];

        if(isJson($payload) === true)
        {
            $payload = json_decode($payload, true);
        }

        if ($content['gateway'] == Payment\Gateway::UPI_RZPAXIS)
        {
            $amount = $payload['amount'];

            if ($payload['description'] === 'amount_mismatch')
            {
                $amount = $payload['amount'] + 100;
            }

            $data['data'] = [
                'version' => 'v2',
                'upi' => [
                    'vpa' => $payload['vpa'] ?? '',
                    'npci_reference_id' => '002002002002',
                    'merchant_reference' => $payload['id'],
                ],
                'payment' => [
                    'currency' => 'INR',
                    'amount_authorized' => (string) $amount
                ],
                'terminal' => [
                    'gateway'              => Payment\Gateway::UPI_RZPAXIS,
                ],
            ];
        }
        else
        {
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
                    'amount_authorized' => (string) ($payload['amount'] * 100)
                ],
                'terminal' => [
                    'gateway_merchant_id2' => $content['data']['terminal']['gateway_merchant_id2'],
                    'gateway'              => $content['data']['terminal']['gateway'],
                ],
            ];
        }

        $recurringPayload = $content['body'];

        if(!is_string($recurringPayload) and $recurringPayload['requestInfo']['pgMerchantid'] !== null)
        {
            $data['data'] = [
                'version' => 'v2',
                'upi' => [
                    'vpa' => $recurringPayload['mandateDtls']['0']['payerVpa'] ?? '',
                    'status_code' => $recurringPayload['mandateDtls']['0']['respCode'],
                    'npci_reference_id' => $recurringPayload['mandateDtls']['0']['custRefNo'],
                    'merchant_reference' => $recurringPayload['requestInfo']['pspRefNo'],
                ],
                'payment' => [
                    'id' => substr($recurringPayload['requestInfo']['pspRefNo'], 0,14),
                    'currency' => 'INR',
                    'amount_authorized' => (string) ($recurringPayload['mandateDtls']['0']['amount'] * 100),
                    'recurring' => true
                ]
            ];

            if($recurringPayload['mandateDtls']['0']['status'] === 'PAUSE')
            {
                $data['data']['upi_mandate']['status'] = 'pause';
                $data['data']['upi_mandate']['umn'] = $recurringPayload['mandateDtls']['0']['UMN'];
            }

            if($recurringPayload['mandateDtls']['0']['status'] === 'UNPAUSE')
            {
                $data['data']['upi_mandate']['status'] = 'resume';
                $data['data']['upi_mandate']['umn'] = $recurringPayload['mandateDtls']['0']['UMN'];
            }

            if($recurringPayload['mandateDtls']['0']['status'] === 'REVOKED')
            {
                $data['data']['upi_mandate']['status'] = 'revoke';
                $data['data']['upi_mandate']['umn'] = $recurringPayload['mandateDtls']['0']['UMN'];
            }

            $recurringPayload['code'] = '0';
        }

        $recurringApbPayload = [];
        if($content['gateway'] === 'upi_rzpapb')
        {
            $recurringApbPayload = json_decode($content['body'], true);

            if ($recurringApbPayload['mandate'] === true) {

                $data['data'] = [
                    'version' => 'v2',
                    'upi' => [
                        'vpa' => $recurringApbPayload['payer']['vpa'] ?? '',
                        'status_code' => $recurringApbPayload['upi_response_code'],
                        'npci_reference_id' => $recurringApbPayload['upi_customer_reference_number'],
                        'merchant_reference' => $recurringApbPayload['reference_id'],
                    ],
                    'payment' => [
                        'id' => substr($recurringApbPayload['reference_id'], 0,14),
                        'currency' => 'INR',
                        'amount_authorized' => (string) ($recurringApbPayload['amount'] * 100),
                        'recurring' => true
                    ]
                ];

                if($recurringApbPayload['status'] === 'paused')
                {
                    $data['data']['upi_mandate']['status'] = 'pause';
                    $data['data']['upi_mandate']['umn'] = $recurringApbPayload['umn'];
                }

                if($recurringApbPayload['status'] === 'unpaused')
                {
                    $data['data']['upi_mandate']['status'] = 'resume';
                    $data['data']['upi_mandate']['umn'] = $recurringApbPayload['umn'];
                }

                if($recurringApbPayload['status'] === 'revoked')
                {
                    $data['data']['upi_mandate']['status'] = 'revoke';
                    $data['data']['upi_mandate']['umn'] = $recurringApbPayload['umn'];
                }
            }
        }

        $data['success'] = true;
        $data['error'] = null;
        $data['next'] = null;

        if (($payload['code'] !== '0' && $content['gateway'] == Payment\Gateway::UPI_AIRTEL) or
            ($payload['description'] === 'payment_failed' && $content['gateway'] == Payment\Gateway::UPI_RZPAXIS) or
            (!is_string($recurringPayload) and ($recurringPayload['mandateDtls']['0']['respCode'] !== '00')) or
            ($recurringApbPayload['mandate'] === true and $recurringApbPayload['upi_response_code'] !== '00'))
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
        if (Payment\Gateway::isUpiPaymentServiceFullyRamped($content['gateway']) === true)
        {
            return $this->callbackFullyRamped($content);
        }
        assertTrue($content['data']['data'] != null);
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
            'amount_authorized' => (string) $payment['amount_authorized'],
            'currency'          => $payment['currency'],
            'payer_account_type'=> 'credit_card',
        ];

        if ($gateway === 'upi_axis')
        {
            $responseData['acquirer']['reference1'] = 'IBL3aa942ae75214480b73704d09b3c1f69';
        }

        $responseError = $this->content($error, $this->action);

        $response = [
            'data'      => $responseData,
            'gateway'   => $gateway,
            'error'     => $responseError,
        ];

        return [$response, $statusCode];
    }

    protected function callbackFullyRamped(array $content): array
    {
        $gateway = $content['gateway'];
        $upi = $content['data']['data']['upi'];
        $payment = $content['data']['data']['payment'];
        $responseData = [
            'acquirer' => [
                'vpa'         => $upi['vpa'],
                'reference16' => $upi['npci_reference_id'],
            ],
            'amount_authorized' => (string) $payment['amount_authorized'],
            'currency'          => $payment['currency'],
            'payer_account_type'=> 'credit_card',
        ];

        if ($gateway === 'upi_axis')
        {
            $responseData['acquirer']['reference1'] = 'IBL3aa942ae75214480b73704d09b3c1f69';
        }

        $statusCode = 200;
        $responseError = [];
        // If mozart pre-process has returned an error block,
        // We need to convert that to UPS error block
        if (!empty($content['data']['error']) === true)
        {
            $error = $content['data']['error'];
            $responseError = [
                'internal' => [
                    'code'          => $error['internal_error_code'],
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
            ];
        }

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

        $responseError = null;

        $responseData['data'] = [
            'upi' => [
                'vpa' => $data['payment']['vpa'] ?? 'forceauth@upi',
                'status_code' => '000',
                'npci_reference_id' => '22712135190',
                'merchant_reference' => $data['payment']['id'],
            ],
            'payment' => [
                'currency' => 'INR',
                'amount_authorized' => (string) $data['payment']['amount']
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

        switch ($data['payment']['description']) {
            case 'verify_amount_mismatch':
                $responseData['data']['payment']['amount_authorized'] = $data['payment']['amount'] + 100;
                break;
            case 'mozart_failure':
                $responseError = [
                    'internal' => [
                        'code' => 'GATEWAY_ERROR_REQUEST_ERROR',
                        'description' => 'GATEWAY_ERROR: received false response with status
                                                        200 from mozart',
                        'metadata' => [
                            'description' => 'Debit has been failed',
                            'gateway_error_code' => 'U30',
                            'gateway_error_description' => 'Debit has been failed',
                            'internal_error_code' => 'GATEWAY_ERROR_DEBIT_FAILED'
                        ]
                    ]
                ];
                $responseData['error'] = [
                    'description' => 'Debit has been failed',
                    'gateway_error_code' => 'U30',
                    'gateway_error_description' => 'Debit has been failed',
                    'gateway_status_code' => 200,
                    'internal_error_code' => 'GATEWAY_ERROR_DEBIT_FAILED',
                ];
                $responseData['success'] = false;
                break;
        }

        $response = [
            'data'      => $responseData,
            'gateway'   => $content['gateway'],
            'error'     => $responseError,
        ];

        $this->content($response, $this->action);

        return [$response, 200];
    }

    public function validateVpa($input): array
    {
        $response = [];

        if($input['vpa'] == '9815225341')
        {
            $response = [
                'vpa' => 'test.cust@icici',
                'customer_name' => 'Test Customer',
                'success' => true,
            ];
            return [$response, 200];
        }

        if($input['vpa'] == '77777777')
        {
            $response = [
                'error' => [
                    'internal' => [
                        'code' => 'GATEWAY_ERROR_REQUEST_ERROR',
                        'identifier_code' => 'PGUP000045',
                        'description' => 'GATEWAY_ERROR: received false response with status 200 from mozart',
                        'metadata' => [
                            'description' => 'Invalid UPI number',
                            'gateway_error_code' => '1038',
                            'gateway_error_description' => 'Invalid UPI number',
                            'http_code' => '200',
                            'internal_error_code' => 'BAD_REQUEST_PAYMENT_UPI_INVALID_UPI_NUMBER'
                        ]
                    ]
                ]
            ];

        }

        return [$response, 200];
    }

    protected function entityFetch(array $content)
    {
        $response['entity'] = [];

        if ($this->app['config']->get('applications.upi_payment_service.enabled') === false)
        {
            return [[], 200];
        }

        $this->request($content, $this->action);

        //mock ups entity to return only one field
        if  ((isset($content['entity_fetch_failure']) === true) and
            ($content['entity_fetch_failure'] === true))
        {
            $response['entity']['customer_reference'] = '227121351902';
            return [$response, 200];
        }

        // mock ups entity to empty ,as this is to identity the request is from api payment
        if ($content['column_name'] === 'customer_reference')
        {
            if ($content['value'] !== '123456789013')
            {
                $response['entity']['customer_reference'] = $content['value'];
            }
            else
            {
               if (empty($content['payment_id']) === false)
               {
                   $response['entity']['payment_id'] = $content['payment_id'];
                   $response['entity']['merchant_reference'] = $content['payment_id'];
               }

                return [$response, 200];
            }
        }

        if ($content['column_name'] === 'payment_id')
        {
            if ($content['value'] === 'YESB12WE34RDSQ187')
            {
                return [$response, 200];
            }

            $response['entity']['payment_id'] = $content['value'];
        }

        $response['entity']['customer_reference']   = '227121351902';
        $response['entity']['npci_txn_id']          = 'FT2022712537204137';
        $response['entity']['gateway_reference']    = '';
        $response['entity']['reconciled_at']        = 0;
        $response['entity']['gateway']              = $content['gateway'];
        $response['entity']['gateway_payment_id']   = 227121351902;

        if (in_array('flow', $content['required_fields'], true) === true)
        {
            $response['entity']['flow'] = 'intent';
        }

        if ((isset($content['required_fields']) === true) and
            (in_array('payment_id',$content['required_fields']) === true))
        {
            $response['entity']['payment_id']   = 'KqyBFV0Cu0yEgG';
        }

        if (empty($content['reconciled_at']) === false)
        {
            $response['entity']['reconciled_at'] = $content['reconciled_at'];
        }

        $this->content($response, $this->action);

        return [$response, 200];
    }

    protected function multipleEntityFetch(array $content)
    {
        $this->request($content, $this->action);

        $response['success'] = 200;

        $entities = [];

        for($i = 0; $i < count($content['values']); $i++)
        {
            $entities[] = [
                'npci_txn_id'           => 'FT202271253720413' . $i,
                'customer_reference'    => '22712135190' . $i,
                'gateway_reference'     => '',
            ];
        }

        if (empty($content['payment_id']) === false)
        {
            $entities[0]['payment_id'] = $content['payment_id'];
        }

        $response['entities'] = $entities;

        $this->content($response, $this->action);

        return [$response, 200];
    }

    protected function forceAuthorizeFailed(array $content)
    {
        $response = [
            'data' => true
        ];

        return [$response, 200];
    }

    protected function transactionUpsert(array $content)
    {
        $response = [
            'data' => true
        ];

        return [$response, 200];
    }

    protected function reconEntityUpdate(array $data)
    {
        $response = [
            'data' => true
        ];

        return [$response, 200];
    }

    public function content(&$content, $action)
    {
        return $content;
    }

    public function request(&$content, $action)
    {
        return $content;
    }

    public function isTerminalRequiredForPreProcess(string $gateway): bool
    {
        $gateways = [
            Payment\Gateway::UPI_AIRTEL,
        ];

        return (in_array($gateway, $gateways, true) === true);
    }

    protected function validateAccountProxy($input)
    {
        $result = [];
        if (is_numeric($input['value']))
        {
            $result['vpa_token'] = 'RandomGarbledVpaThatHasBeenEncrypted|RandomGarbledTokenForDecryption';
            $result['masked_vpa'] = 'r*********@rzp';
        }
        else
        {
            $result['vpa'] = $input['value'];
        }

        $result['success'] = true;
        $result['customer_name'] = 'R******************';
        $result['error'] = null;

        return [$result, 200];
    }

    protected function validateVpaProxy($input)
    {
        $result = [];
        if (is_numeric($input['vpa']))
        {
            $result['vpa_token'] = 'RandomGarbledVpaThatHasBeenEncrypted|RandomGarbledTokenForDecryption';
            $result['masked_vpa'] = 'r*********@rzp';
        }
        else
        {
            $result['vpa'] = $input['vpa'];
        }

        $result['success'] = true;
        $result['customer_name'] = 'R******************';
        $result['error'] = null;

        return [$result, 200];
    }

    protected function authenticate($input)
    {
        $description = $input['payment']['description'];

        $response = [];

        $error = null;

        $code = 500;

        switch ($description)
        {
            case 'authenticate_success':
                $response = [
                    'data' => [
                        'data' => [
                            'vpa' => 'test@okhdfcbank',
                            'rrn' => '32131429',
                            'npci_txn_id' => '32131429'
                        ],
                        'upi_mandate' => [
                            'umn' => 'HDFebdef01e51b340448dd0caf01fffc@hdfc',
                        ],
                        'upi' => [
                            'merchant_reference' => 'OCfR6llPPsglMc0create1',
                            'flow' => 'collect',
                            'action' => 'authenticate'
                        ]
                    ],
                    'gateway' => 'upi_mindgate',
                    'error' => null
                ];
                $code = 200;
                break;
            case 'authenticate_failure':
                $response['data'] = [
                    'upi' => [
                        'merchant_reference' => 'OCfR6llPPsglMc0create1',
                        'flow' => 'collect',
                        'action' => 'authenticate'
                    ]
                ];
                $response['error'] = [
                    'code' => '01',
                    'internal' => [
                        'code'          => 'BAD_REQUEST_VALIDATION_FAILURE',
                        'description'   => 'Vpa is required for UPI collect request',
                        'metadata'      => [
                            'description'               => 'INPUT_VALIDATION_FAILED',
                            'gateway_error_code'        => '',
                            'gateway_error_description' => '',
                            'internal_error_code'       => 'BAD_REQUEST_VALIDATION_FAILURE',
                            'http_code'                 => 400
                        ]
                    ]
                ];
                $code = 200;
                break;
        }

        return [$response, $code];
    }

    public function revoke()
    {
        $response = [
            'data' => [
                'data' => [
                    'vpa' => 'test@okhdfcbank',
                    'rrn' => '32131429',
                    'npci_txn_id' => '32131429'
                ],
                'upi_mandate' => [
                    'umn' => 'HDFebdef01e51b340448dd0caf01fffc@hdfc',
                    'status' => 'revoked'
                ],
                'upi' => [
                    'merchant_reference' => 'OCfR6llPPsglMc0create1',
                    'flow' => 'collect',
                    'action' => 'revoke'
                ],
            ],
            'gateway' => 'upi_mindgate',
            'error' => null
        ];
        $code = 200;

        return [$response, $code];
    }

    public function recurringCallback($input)
    {
        $result = [];
        if(str_contains($input['data']['data']['upi']['merchant_reference'], 'create'))
        {
            $result = [
                'data' => [
                    'data' => [
                        'vpa' => 'test@okhdfcbank',
                        'rrn' => '32131429',
                        'npci_txn_id' => '32131429'
                    ],
                    'upi_mandate' => [
                        'umn' => 'HDFebdef01e51b340448dd0caf01fffc@hdfc',
                    ],
                    'upi' => [
                        'merchant_reference' => 'OCfR6llPPsglMc0create1',
                        'flow' => 'collect',
                        'action' => 'authenticate',
                        'gateway_data' => [
                            'id' => 'OCfR6llPPsglMc0create1'
                        ]
                    ]
                ],
                'gateway' => 'upi_mindgate',
                'error' => null
            ];
        }
        else if(str_contains($input['data']['data']['upi']['merchant_reference'], 'execte')) {
            $result = [
                'data' => [
                    'data' => [
                        'vpa' => 'test@okhdfcbank',
                        'rrn' => '32131429',
                        'npci_txn_id' => '32131429'
                    ],
                    'upi_mandate' => [
                        'umn' => 'HDFebdef01e51b340448dd0caf01fffc@hdfc',
                    ],
                    'upi' => [
                        'merchant_reference' => 'OCfR6llPPsglMc0execte1',
                        'flow' => 'collect',
                        'action' => 'debit',
                        'gateway_data' => [
                            'id' => 'OCfR6llPPsglMc0execte1'
                        ]
                    ]
                ],
                'gateway' => 'upi_mindgate',
                'error' => null
            ];
        }

        return [$result, 200];
    }

    public function debit($input)
    {
        $result = [
            'data' => [
                'data' => [
                    'vpa' => 'test@okhdfcbank',
                    'rrn' => '32131429',
                    'npci_txn_id' => '32131429'
                ],
                'upi_mandate' => [
                    'umn' => 'HDFebdef01e51b340448dd0caf01fffc@hdfc',
                ],
                'upi' => [
                    'merchant_reference' => 'OCfR6llPPsglMc0execte1',
                    'flow' => 'collect',
                    'action' => 'debit'
                ]
            ],
            'gateway' => 'upi_mindgate',
            'error' => null
        ];

        return [$result, 200];
    }

    protected function notify($input)
    {
        $description = $input['payment']['description'];

        $response = [];

        $error = null;

        $code = 500;

        switch ($description)
        {
            case 'notify_success':
                $response = [
                    'data' => [
                        'data' => [
                            'vpa' => 'test@okhdfcbank',
                            'rrn' => '615519221388',
                            'npci_txn_id' => '615519221388'
                        ],
                        'upi' => [
                            'merchant_reference' => $input['payment']['id'].'0create1',
                            'flow' => 'collect',
                            'action' => 'notify',
                            'npci_txn_id' => '615519221388',
                            'npci_reference_id'   => '615519221388'
                        ],
                        'upi_mandate' => [
                            'umn' => 'FirstUpiRecPayment@razorpay'
                        ]
                    ],
                    'gateway' => 'upi_mindgate',
                    'error' => null
                ];
                $code = 200;
                break;
            case 'notify_failure':
                $response['data'] = [
                    'upi' => [
                        'merchant_reference' => 'OCfR6llPPsglMc0create1',
                        'flow' => 'collect',
                        'action' => 'notify'
                    ]
                ];
                $response['error'] = [
                    'code' => '01',
                    'internal' => [
                        'code'          => 'BAD_REQUEST_VALIDATION_FAILURE',
                        'description'   => 'Vpa is required for UPI collect request',
                        'metadata'      => [
                            'description'               => 'INPUT_VALIDATION_FAILED',
                            'gateway_error_code'        => '',
                            'gateway_error_description' => '',
                            'internal_error_code'       => 'BAD_REQUEST_VALIDATION_FAILURE',
                            'http_code'                 => 400
                        ]
                    ]
                ];
                $code = 200;
                break;
        }

        return [$response, $code];
    }

    protected function getMozartGatewayWithModeSet()
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }

    public function getMode()
    {
        return $this->mode;
    }
}
