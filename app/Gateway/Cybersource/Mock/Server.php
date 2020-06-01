<?php

namespace RZP\Gateway\Cybersource\Mock;

use Str;
use RZP\App;
use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Cybersource\Fields as F;

class Server extends Base\Mock\Server
{
    protected $repo;

    protected function getWsdlFile()
    {
        return dirname(__DIR__) . '/Wsdl/cybstest.wsdl.xml';
    }

    public function acs(array $input)
    {
        $this->validateAuthenticateInput($input);

        $response = [
            F::MD => $input[F::MD],
            F::PA_RES => 'eNpVUttygjAQfc9XMP0AkiAw',
            F::TERM_URL => $input[F::TERM_URL]
        ];

        $this->content($response, 'callback');

        return $response;
    }

    // Dummy function for mock soap client
    public function Security($header)
    {
        return null;
    }

    public function authenticateInit($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        switch ($entities['card']['number'])
        {
            case 4012001038443335:
            case 4000000000000002:
                $response = [
                    'data'  => [
                        '_raw'                  => '',
                        'commerce_indicator'    => null,
                        'eci'                   => null,
                        'enrollment_status'     => 'Y',
                        'reason_code'           => 475,
                        'status'                => 'created',
                        'xid'                   => 'aFM3NktkemM4OW1sSGNoOERXUzE=',
                        'attempt_id'            => $entities['payment']['created_at'],
                        'gateway_reference_id1' => '4661468455432' . random_int(10000000, 99999999),
                        'payment_id'            => $entities['payment']['created_at'],
                    ],
                    'next'    => [
                        'redirect'   => [
                            'content' => [
                                'MD'      => $entities['payment']['id'],
                                'PaReq'   => 'eNpVUV1vgkAQfL9fQXxuuA8Qi1kvsdVUm2qsNbH1jZ4XpQrocRTtr+8doLY87Qw7tzuzsNgqKQdvUhRKcpjIPI820onXvdbpi0S7F/U5eu+E3isLVmRDWxxm/bk8cviWKo+zlFOXuAzwBSLzhBLbKNUcInF8GE+5H4SMBoAbiCCRajzgnk99xnxG6g9wTSNIo0Tyhcy1s4u14wGuCAQiK1KtzvzeN80XgKBQe77V+tDFuCxLVxuhK7IEsP2BAN/2mRW2yo3NU7zm8ikrP5Z6IpLn8XIg2hM6PE/3Wbn6GfYA2w4E60hLzggNCSWhw0iXsa5nplc8giixW/B5HtwZB6410VAIDnZYv0ZB4/AvZ/wUSslUnLnXNulcEQJ5OmSpND0m12ttnNz2fxzZdIU2eVEatEnHTq5xJY9NMsyjtNLHVUzYanBzPNzc2VT/7v8LOren5w==',
                                'TermUrl' => $entities['gateway']['payment']['callbackUrl'],
                            ],
                            'method'    => 'post',
                            'url'       => 'https://0eafstag.cardinalcommerce.com/EAFService/jsp/v1/redirect',
                        ],
                    ],
                    'error'             => null,
                    'success'           => true,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];
                break;

            default:
                $response = [
                    'data'  => [
                        '_raw'                  => '',
                        'commerce_indicator'    => 'vbv_attempted',
                        'eci'                   => '06',
                        'enrollment_status'     => 'N',
                        'reason_code'           => 100,
                        'status'                => 'created',
                        'xid'                   => null,
                        'attempt_id'            => $entities['payment']['created_at'],
                        'gateway_reference_id1' => '4661468455432' . random_int(10000000, 99999999),
                        'payment_id'            => $entities['payment']['id'],
                    ],
                    'error'             => null,
                    'success'           => true,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];

        }

        $this->content($response, 'auth_init');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function authenticateVerify($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
            'data' =>
                [
                    '_raw' => '',
                    'attempt_id' => $entities['payment']['id'],
                    'authentication_status' => 'Y',
                    'cavv' => 'AAABAWFlmQAAAABjRWWZEEFgFz+=',
                    'commerce_indicator' => 'vbv',
                    'eci' => '05',
                    'enrollment_status' => null,
                    'gateway_reference_id1' => '4661468455432' . random_int(10000000, 99999999),
                    'payment_id' => $entities['payment']['id'],
                    'received' => true,
                    'status' => 'authenticated',
                    'xid' => 'aFM3NktkemM4OW1sSGNoOERXUzE=',
                ],
            'error' => null,
            'success' => true,
        ];

        $this->content($response, 'auth_verify');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function payInit($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        switch ($entities['card']['number'])
        {
            case 4444333322221111:
                $response = [
                    'data' =>
                    [
                        '_raw'                  => '',
                        'attempt_id'            => $entities['payment']['id'],
                        'status'                => 'authorized',
                        'received'              => true,
                        'avs_code'              => 'X',
                        'gateway_reference_id1' => '4465840340765000001541',
                        'gateway_reference_id3' => '888888',
                        'payment_id'            => 'Bi7fYtbRhkouOX',
                        'processorResponse'     => '100',
                        'reason_code'           => 100
                    ],
                    'error' => null,
                    'success' => true,
                ];
                break;
            case 4532948024710971:
                $response = [
                    'data' =>
                        [
                            '_raw'                  => '',
                            'attempt_id'            => $entities['payment']['id'],
                            'status'                => 'faileds',
                            'received'              => true,
                            'avs_code'              => 'X',
                            'payment_id'            => 'Bi7fYtbRhkouOX',
                        ],
                    'error' => null,
                    'success' => false,
                ];
                break;
            default:
                $response = [
                    'data' =>
                    [
                        '_raw'                  => '',
                        'attempt_id'            => $entities['payment']['id'],
                        'avs_code'              => 'Y',
                        'card_category'         => null,
                        'card_group'            => null,
                        'cv_code'               => 'M',
                        'gateway_reference_id1' => '5474993075916772203012',
                        'gateway_reference_id2' => '016153570198200',
                        'gateway_reference_id3' => '831000',
                        'payment_id'            => $entities['payment']['id'],
                        'processorResponse'     => '00',
                        'processor_code'        => '01',
                        'reason_code'           => 100,
                        'received'              => true,
                        'rrn'                   => '184090',
                        'status'                => 'authorized',
                    ],
                    'error' => null,
                    'success' => true,
                ];
        }

        $this->content($response, 'pay_init');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function capture($input)
    {
        $input = json_decode($input, true);
        $entities = $input['entities'];

        $response = [
             'data'  => [
                    '_raw'                  => '',
                    'received'              => true,
                    'attempt_id'            => $entities['payment']['id'],
                    'payment_id'            => '',
                    'gateway_reference_id1' => '5470653499446597903009',
                    'status'                => 'captured',
                    'decision'              => 'ACCEPT',
                    'reconciliationID'      => '',
                    'reason_code'           => 100,
                ],
                'error'             => null,
                'success'           => true,
                'mozart_id'         => '',
                'external_trace_id' => '',
            ];

        $this->content($response, 'capture');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function verify($input)
    {
        parent::verify($input);

        $response = [
             'data'              => [
                    '_raw'                  => '',
                    'received'              => true,
                    'gateway_reference_id1' => '5470653499446597903009',
                    'gateway_reference_id3' => '831001',
                    'status'                => 'authorized',
                    'reason_code'           => 100,
                    'eci'                   => null,
                    'avs_code'              => null,
                    'cavv'                  => null,
                    'xid'                   => null,
                ],
                'error'             => null,
                'success'           => true,
                'mozart_id'         => '',
                'external_trace_id' => '',
            ];

        $this->content($response, 'verify_content');

        $response = json_encode($response);

        $response = $this->makeResponseJson($response);

        return $response;
    }

    public function runTransaction($request)
    {
        $request = json_decode(json_encode($request), true);

        switch(true)
        {
            case isset($request[F::PA_ENROLL_SERVICE]):
                $action = 'auth_enroll';
                break;
            case isset($request[F::PA_VALIDATE_SERVICE]):
                $action = 'auth_validate';
                break;
            case isset($request[F::CC_AUTH_SERVICE]):
                $action = 'authorize';
                break;
            case isset($request[F::CC_CAPTURE_SERVICE]):
                $action = 'capture';
                break;
            case isset($request[F::CC_CREDIT_SERVICE]):
                $action = 'refund';
                break;
            case isset($request[F::CC_AUTH_REVERSAL_SERVICE]):
                $action = 'auth_reversal';
                break;
            default:
                assertTrue(false, 'Unrecognized request type');
        }

        $action = camel_case($action);

        return $this->{$action}($request);
    }

    public function verifyRefund($input)
    {
        if (is_array($input) === false) {
            $response = [
                'data' => [
                    '_raw' => '',
                    'received' => true,
                    'gateway_reference_id1' => '5470653499446597903009',
                    'reason_code' => '100',
                    'r_code' => '1',
                    'r_flag' => 'SOK',
                    'r_message' => 'Request was processed successfully.',
                    'status' => 'refunded'
                ],
                'error' => null,
                'success' => true,
                'mozart_id' => '',
                'external_trace_id' => '',
            ];

            $this->content($response, 'verify_refund_fail');

            $this->content($response, 'verify_reverse');

            return $this->makeResponse($response);
        }
        else
        {
            $content = $this->getVerifyContent($input);

            $this->content($content, 'verify_content');

            $xml = '';

            if ($content !== [])
            {
                $xml = require __DIR__ . '/VerifyResponseXml.php';
            }

            $this->content($xml, 'verify_xml');

            return $this->makeResponse($xml);
        }
    }

    protected function getVerifyContent(array $input)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input[F::MERCHANT_REFERENCE_NUMBER], Cybersource\Action::AUTHORIZE);

        if ($payment === null)
        {
            return [];
        }

        $amount = ($payment->getAmount() / 100);

        return [
            'ccCaptureService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount
            ],
            'ccAuthService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount,
                'authCode' => strtoupper(Str::random(6)),
                'eci' => '2',
                'RFlag' => Cybersource\ReplyFlag::SOK
            ],
            'payerAuthEnrollService' => [
                'requestId' => '4661468455432' . random_int(10000000, 99999999),
                'amount' => $amount
            ]
        ];
    }

    protected function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input);

        $this->content($input, 'validate_refund');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];

        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);

        $response[F::REQUEST_TOKEN] = Str::random(40);

        $response[F::DECISION] = 'ACCEPT';

        $response[F::REASON_CODE] = 100;

        $response[F::CC_CREDIT_REPLY] = [
            F::REASON_CODE       => 100,
            F::AMOUNT            => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            F::RECONCILIATION_ID => $response[F::REQUEST_ID],
            F::REFUND_DATETIME   => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z')
        ];

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->content($response, 'refund');

        return $response;
    }
    protected function authReversal($input)
    {
        parent::reverse($input);

        $this->validateActionInput($input);

        $this->content($input, 'validate_auth_reversal');

        $response = [];

        $response[F::MERCHANT_REFERENCE_CODE] = $input[F::MERCHANT_REFERENCE_CODE];

        $response[F::REQUEST_ID] = '4661468455432' . random_int(10000000, 99999999);

        $response[F::REQUEST_TOKEN] = Str::random(40);

        $response[F::DECISION] = 'ACCEPT';

        $response[F::REASON_CODE] = 100;

        $response[F::CC_AUTH_REVERSAL_REPLY] = [
            F::REASON_CODE        => 100,
            F::AMOUNT             => $input[F::PURCHASE_TOTALS][F::GRAND_TOTAL_AMOUNT],
            F::PROCESSOR_RESPONSE => $response[F::REQUEST_ID],
            F::REQUEST_DATETIME   => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z')
        ];

        $response[F::PURCHASE_TOTALS][F::CURRENCY] = 'INR';

        $this->content($response);

        return $response;
    }

    protected function makeResponseJson($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function makeResponse($body)
    {
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
