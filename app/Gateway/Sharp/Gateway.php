<?php

namespace RZP\Gateway\Sharp;

use Crypt;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BharatQr;
use RZP\Models\UpiMandate;
use RZP\Models\Customer\Token;
use RZP\Gateway\GooglePay\Action;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\CardlessEmi;

class Gateway extends Base\Gateway
{
    const DEFAULT_PAYEE_VPA = 'upi@razopay';

    protected $gateway = 'sharp';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $this->wasGatewayHit = true;

        if ($this->isBharatQrPayment() === true)
        {
            return null;
        }

        if($input['payment']['method'] === Payment\Gateway::CARDLESS_EMI)
        {
            $provider = $input['payment']['wallet'];

            if(in_array($provider, CardlessEmi::getCardlessEmiDirectAquirers()) === false)
            {
                $provider = strtolower(CardlessEmi::getProviderForBank($provider));
            }

            if((in_array($provider, Payment\Gateway::$redirectFlowProvider) === false) and !($provider == CardlessEmi::ZESTMONEY and $this->mode === Mode::TEST))
            {
                return;
            }
        }

        if($input['payment']['method'] === Payment\Gateway::PAYLATER)
        {
            $provider = $input['payment']['wallet'];

            if(in_array($provider, PayLater::getPaylaterDirectAquirers()) === false)
            {
                $provider = strtolower(PayLater::getProviderForBank($provider));
            }
            if((in_array($provider, Payment\Gateway::$redirectFlowProvider) === false) and $provider !== PayLater::GETSIMPL)
            {
                return;
            }
        }

        $this->failIfRequired($input);

        if ($this->isSecondRecurringPaymentRequest($input))
        {
            if (($input['payment']['method'] === 'card') and
                ($input['card']['iin'] === '400666') and
                ($input['card']['last4'] === '0007'))
            {

                //Soft Decline for recurring payments
                if ($input['payment']['amount'] === 4444)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
                }

                //Hard Decline for recurring payments
                if ($input['payment']['amount'] === 5555)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST );
                }
            }
            else if ($input['payment']['method'] === 'upi')
            {
                throw new Exception\LogicException('Authorize should not be called for UPI auto recurring');
            }

            return;
        }

        if ($input['payment'][Payment\Entity::AUTH_TYPE] == Payment\AuthType::SKIP)
        {
            return;
        }

        $content = [
            'action'            => 'authorize',
            'amount'            => $input['payment']['amount'],
            'method'            => $input['payment']['method'],
            'payment_id'        => $input['payment']['id'],
            'callback_url'      => $input['callbackUrl'],
            // This need to be 0 because if it's `false`, frontend converts
            // to "false" and Sharp server treats "false" as `true`.
            'recurring'         => 0,
        ];

        if (isset($input['payment']['auth_type']) === true)
        {
            $content['auth_type'] = $input['payment']['auth_type'];
        }

        if (isset($input['payment']['recurring']) === true)
        {
            $content['recurring'] = boolval($input['payment']['recurring']) ? 1 : 0;
        }

        if ($input['payment']['authentication_gateway'] === Payment\Gateway::GOOGLE_PAY)
        {
            $this->action = Action::AUTHENTICATE;

            $authResponse = $this->callAuthenticationGateway($input, Payment\Gateway::GOOGLE_PAY);

            return $authResponse;
        }

        if ($input['payment']['authentication_gateway'] === Payment\Gateway::VISA_SAFE_CLICK)
        {
            $resp = [
                'acquirer' => [
                    'reference2' => 'test',
                    'reference17' => '{"product_enrollment_id": "831eyJlbmMiOiJBMjU2R0NNIiwiYWxnIjoiUlNBLU9BRVAifQ.WwA2xBjK-sqL-hHIeCZ1nLRghkr-tOTVxWToFU5rH3aWlAnxsoVmvaBfBpYRPYsDBGDuAU0aQiXuJkB2ClECD07BrEcJ2eJ4hpsrYT2uF3ac_MTlLWvx8tz978DTvYPnD70-hoAVMPr6aDVLnz68-0fdx1oY0Iqum1W9Mwvr_dg8wvd_0oPpy_stPpclLCgwVTdcotcnyOfUxiiOF9CpQEoTkPzENh7QyBbNhLGri_HhUryPJN1FFFtdbCxq-NSRgKOQq__kXxv6RiY8RCKEop0a6iy7LkK6mynvf63kK1000"}',
                ]
            ];

            return $resp;
        }

        if ($content['method'] === 'card')
        {
            $content['card_number'] = $input['card']['number'];
        }

        if ($this->isEnrolled($content) === false)
        {
            $data = [];

            $this->addAvsResponseIfApplicable($input, $data);

            if (empty($data) === false)
            {
                return $data;
            }

            return;
        }

        $request = $this->getRequestArray($content, $input);

        if ($input['payment']['method'] === Payment\Method::UPI)
        {
            if ($this->isUpiRecurringCreateRequest($input) === true)
            {
                // For live gateways we actually wait for callback to mark the upi_mandate status confirmed
                // But for sharp we are forcing the mandate status directly.
                // And for metadata we are suggesting the authorization(first debit) is initiated
                $response = [
                    'data' => [
                        'vpa' => $input['terminal']['vpa'],
                    ],
                    'upi_mandate' => [
                        'status'        => 'created',
                        'umn'           => sprintf('%s@razorpay', $input['payment']['id']),
                        'npci_txn_id'   => 'RZP12345678910111213141516',
                        'rrn'           => '001000100001',
                        'gateway_data'  => [
                            'id'        => 'ID001000100001',
                        ],
                    ],
                    'upi' => [
                        'umn'               => sprintf('%s@razorpay', $input['payment']['id']),
                        'npci_txn_id'       => 'RZP12345678910111213141516',
                        'rrn'               => '001000100001',
                        'internal_status'   => 'authenticate_initiated',
                    ],
                ];

                $case = explode('@', $input['upi']['vpa'])[0];

                if ($case === 'failure')
                {
                    $exception = new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_BANK_OFFLINE);

                    $response['upi_mandate']['status'] = 'created';

                    $exception->setData($response);

                    throw $exception;
                }

                $this->processTestUpiPaymentCallback($input, $case);

                return $response;
            }

            $this->processTestUpiPayment($input['payment']);

            if ((isset($input['upi']['flow']) === true) and
                ($input['upi']['flow'] === 'intent'))
            {
                return $this->getIntentRequest($input);
            }

            $request = true;
        }

        return $request;
    }

    protected function processTestUpiPaymentCallback($input, $case)
    {
        // This is a hack which force callback when current request is processed
        // Better way would have been adding a middleware and maintaining a stack

        // Once this even is fired, we need to ignore all the queries post that
        $eventFired = false;

        \Event::listen('Illuminate\Database\Events\QueryExecuted',
            function ($query) use ($input, $case, & $eventFired)
            {
                // Now this code will be called when the UPI Metadata will be successfully marked
                // This will not get called if we throw exception from authorize function
                if ((in_array('authenticate_initiated', $query->bindings, true) === true) and
                    ($eventFired === false))
                {
                    $eventFired = true;

                    $payment = $input['payment'];

                    try
                    {
                        (new Payment\Service)->s2scallback($payment['public_id'], [
                            'case'          => $case,
                            'status'        => 'authorized',
                            'rrn'           => '001000100002',
                            'npci_txn_id'   => 'npci_txn_id_for_' . $payment['id'],
                            'vpa'           => 'testuser@razorpay',
                        ]);
                    }
                    catch (Exception\GatewayErrorException $exception)
                    {
                        // No need to throw callback exceptions
                    }
                }
            });
    }

    /**
     * Takes in S2S request as a body string
     * and returns the parsed response as an array
     *
     * @param  array $body Request body
     *
     * @param bool    $isBharatQr
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function preProcessServerCallback($body, $isBharatQr = false): array
    {
        $response = $body;

        if ($isBharatQr === true)
        {
            $response = $this->getQrData($body);
        }

        return $response;
    }

    protected function isUpiRecurringCreateRequest(array $input): bool
    {
        return ((isset($input['payment']) === true) and
                ($input['payment'][Payment\Entity::METHOD] === Payment\Method::UPI) and
                ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL));
    }

    public function getQrData(array $input)
    {
        (new Validator)->validateInput('test_bharatqr_payment', $input);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $input[Fields::AMOUNT],
            BharatQr\GatewayResponseParams::METHOD                => $input[Fields::METHOD],
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => Constants::SHARP_MERCHANT_ID,
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => $input[Fields::REFERENCE],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => random_alphanum_string(10),
            BharatQr\GatewayResponseParams::SENDER_NAME           => 'Razorpay',
        ];

        switch ($input[Fields::METHOD])
        {
            case Payment\Method::CARD:
                $qrData[BharatQr\GatewayResponseParams::CARD_FIRST6] = Constants::CARD_FIRST_SIX;
                $qrData[BharatQr\GatewayResponseParams::CARD_LAST4]  = Constants::CARD_LAST_FOUR;
                break;

            case Payment\Method::UPI:
                $qrData[BharatQr\GatewayResponseParams::VPA] = Constants::BQR_VPA;
                break;

            default:
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD);
        }

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    public function getBharatQrResponse(bool $valid, $gatewayInput = null, $exception = null)
    {
        if ($exception !== null)
        {
            if ($exception instanceof \Exception)
            {
                throw $exception;
            }
            throw new \Exception('Not valid bharat qr');
        }

        //
        // This is a fairly useless response. But it's better for the
        // merchant than the one in base gateway, and keeping in empty
        // means we can add stuff later without breaking compatibility.
        //
        return [];
    }

    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
    }

    public function mandateUpdate(array $input)
    {
        $token = $input['token'];

        if (isset($input['start_time']) === true)
        {
            $token->setStartTime($input['start_time']);
        }

        if (isset($input['max_amount']) === true)
        {
            $token->setMaxAmountAttribute($input['max_amount']);
        }

        $this->repo->saveOrFail($token);

        return true;
    }

    public function mandateCancel(array $input)
    {
        return ['success' => true];
    }

    public function getIntentUrl($input)
    {
        return $this->getIntentRequest($input);
    }

    protected function getIntentRequest($input)
    {
        $content = [
            UpiBase\IntentParams::PAYEE_ADDRESS => self::DEFAULT_PAYEE_VPA,
            UpiBase\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            UpiBase\IntentParams::TXN_REF_ID    => str_random(15),
            UpiBase\IntentParams::TXN_NOTE      => 'razorpay',
            UpiBase\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            UpiBase\IntentParams::TXN_CURRENCY  => 'INR',
            UpiBase\IntentParams::MCC           => '5411',
        ];

        $query = str_replace(' ', '', urldecode(http_build_query($content)));

        return ['data' => ['intent_url' => 'upi://pay?' . $query]];
    }

    protected function processTestUpiPayment($payment)
    {
        $server = $this->app['gateway']->server('sharp');

        $input = $server->s2sRequestContent($payment);

        $paymentId = Payment\Entity::getSignedId($payment['id']);

        try
        {
            (new Payment\Service)->s2scallback($paymentId, $input);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED,
                [
                    'payment_id'        => $paymentId,
                    'message'           => $ex->getMessage(),
                ]
            );
        }
    }

    public function checkExistingUser(array $input)
    {
    }

    public function otpGenerate(array $input)
    {
        return $this->getOtpSubmitRequest($input);
    }

    public function topup(array $input)
    {
        return $this->authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->wasGatewayHit = true;

        if ($input['payment']['authentication_gateway'] === 'google_pay')
        {
            return [];
        }

        if (($input['payment']['method'] === 'card') and
            ($input['card']['iin'] === '501010') and
            ($input['card']['last4'] === '1015'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
        }

        if ((isset($input['payment']['recurring']) === true) and
            ($input['payment']['recurring'] === true) and
            ($input['payment']['method'] === 'card') and
            ($input['card']['iin'] === '400666') and
            ($input['card']['last4'] === '0007'))
        {

            //Soft Decline for recurring payments
            if ($input['payment']['amount'] === 4444)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE);
            }

            //Hard Decline for recurring payments
            if ($input['payment']['amount'] === 5555)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST );
            }
        }

        if (isset($input['gateway']['PaRes']) === true)
        {
            $input['gateway']['status'] = 'authorized';
        }

        $this->verifyPaymentCreateResponse($input);

        $acquirerData = $this->getAcquirerData($input, null);

        $this->addRecurringDataIfApplicable($input, $acquirerData);

        $this->addAvsResponseIfApplicable($input, $acquirerData);

        if ($this->isUpiRecurringCreateRequest($input) === true)
        {
            if ($input['gateway']['case'] === 'rejected')
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED);
            }

            $acquirerData = [
                'acquirer' => [
                    'vpa'               => $input['payment']['vpa'] ?? 'testuser@razorpay',
                    'reference16'       => $input['gateway']['rrn'],
                    'reference1'        => $input['gateway']['npci_txn_id']
                ],
                'upi_mandate' => [
                    'status'        => 'confirmed',
                    'umn'           => sprintf('%s@razorpay', $input['payment']['id']),
                    'npci_txn_id'   => 'RZP12345678910111213141516',
                    'rrn'           => '001000100001',
                    'gateway_data'  => [
                        'id'        => 'ID001000100001',
                    ]
                ],
                'upi'   => [
                    'rrn'               => $input['gateway']['rrn'],
                    'npci_txn_id'       => $input['gateway']['npci_txn_id'],
                    'internal_status'   => 'authorized',
                    'vpa'               => 'testuser@razorpay',
                ],
            ];
        }

        if (($this->isSecondRecurringPaymentRequest($input) === true) and
            ($input['payment']['method'] === Payment\Method::UPI))
        {
            $acquirerData = [
                'acquirer' => $acquirerData['acquirer'],
                'upi_mandate' => [
                    'order_id'      => $input['payment']['order_id'],
                    'status'        => 'confirmed',
                    'umn'           => sprintf('%s@razorpay', $input['payment']['id']),
                    'npci_txn_id'   => 'RZP12345678910111213141516',
                    'rrn'           => '001000100001',
                    'gateway_data'  => [
                        'id'        => 'ID001000100001',
                    ]
                ],
                'upi'      => [
                    'rrn'               => $input['gateway']['rrn'],
                    'npci_txn_id'       => $input['gateway']['npci_txn_id'],
                    'internal_status'   => 'authorized',
                ],
            ];
        }

        $response = $this->getCallbackResponseData($input, $acquirerData);

        if(($input['payment'][Payment\Entity::METHOD] === Payment\Method::CARDLESS_EMI) || $input['payment'][Payment\Entity::METHOD] === Payment\Method::PAYLATER)
        {
            $response[Payment\Entity::TWO_FACTOR_AUTH] = null;
        }

        return $response;
    }

    public function callbackOtpSubmit(array $input)
    {
        switch ($input['gateway']['otp'])
        {
            case '100000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);

            case '200000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT);

            case '300000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED);

            case '400000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED);

            case '500000':
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST);
        }

        return [];
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer = [];

        if (($input['payment']['method'] === Payment\Method::NETBANKING) or
            ($input['payment']['method'] === Payment\Method::EMANDATE))
        {
            $acquirer = [
                'reference1' => (string) random_integer(7)
            ];
        }

        if ($input['payment']['method'] === Payment\Method::UPI)
        {
            $bankNamePrefix = strtoupper(substr($input['terminal']['gateway_acquirer'], 0, 3));
            $randomStr = strtoupper(substr(md5(time()), 0, 32)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto

            $acquirer = [
                Payment\Entity::VPA => $input['payment']['vpa'] ?? $input['gateway']['vpa'],
                ///REFERENCE16 refers to RRN field
                Payment\Entity::REFERENCE16 => (string) random_integer(12),
                ///REFERENCE1 refers to upi_gateway_txn_id
                Payment\Entity::REFERENCE1 => ($bankNamePrefix . $randomStr),
            ];
        }

        if (($input['payment']['method'] === Payment\Method::CARD) or
            ($input['payment']['method'] === Payment\Method::EMI))
        {
            $acquirer = [
                'reference2' => (string) random_integer(6)
            ];
        }

        return [
            'acquirer' => $acquirer
        ];
    }

    public function capture(array $input)
    {
        parent::capture($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        if ((isset($input['refund'][Payment\Refund\Entity::IS_SCROOGE]) === true) and
            ($input['refund'][Payment\Refund\Entity::IS_SCROOGE] === true))
        {
            return $this->getScroogeResponse($input, 'refund');
        }
    }

    public function validateVpa(array $input)
    {
        $vpa = $input['vpa'];

        if ($vpa === 'withname@razorpay')
        {
            return "Razorpay Customer";
        }

        if ($vpa === 'invalidvpa@razorpay')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA);
        }

        if ($vpa === 'invalidhandle@razor')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA);
        }

        if ($vpa === 'vpagatewayerror@razorpay')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
        }

    }

    public function preDebit(array $input): array
    {
        (new Validator)->validateInput('pre_debit', $input);

        $metadata    = $input['upi'];
        $attempt     = (int)(substr($metadata['reference'], 18)) + 1;
        $remindAt    = Carbon::now()->addSeconds(90)->getTimestamp();
        $descripion  = $input['payment']['description'];
        $errorCode   = null;
        $mandate     = $input['upi_mandate'];
        $shouldSkip  = UpiMandate\Frequency::shouldSkipNotify($this->gateway, $mandate['frequency']);

        switch ($descripion)
        {
            case 'notify_fails_twice':
                if ($attempt < 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    $remindAt  = Carbon::now()->addSeconds(3600)->getTimestamp();
                }
                break;

            case 'notify_fails':
                if ($attempt < 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    $remindAt  = Carbon::now()->addSeconds(3600)->getTimestamp();
                }
                else if ($attempt === 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    // Just fail the retries completely
                    $remindAt  = null;
                }
                break;

            case 'notify_skips':
                $shouldSkip = true;
                break;
        }

        $response = [
            // Data which is needed for mandate
            'upi_mandate'   => [],
            // Data which is needed for UPI Metadata
            'upi'           => [
                'vpa'               => $input['payment']['vpa'],
                'reference'         => 'preDebitReference:' . $attempt,
                'umn'               => $mandate['umn'],
                // For Sharp Gateway, webhook will be almost instantaneous
                'remind_at'         => $shouldSkip ? null : $remindAt,
            ],
        ];

        if (($shouldSkip === true) or ($errorCode === null))
        {
            return $response;
        }

        $exception = new Exception\GatewayErrorException($errorCode, null, null, $response);

        $exception->setAction('pre_debit');

        throw $exception;
    }

    public function debit(array $input)
    {
        parent::action($input, 'debit');

        if (($input['payment']['recurring'] === false) or
            ($input['payment']['method'] !== Payment\Method::UPI))
        {
            return parent::debit($input);
        }

        $descripion  = $input['payment']['description'];
        $attempt     = (int)(substr($input['upi']['reference'], 15)) + 1;
        $errorCode   = null;
        $remindAt    = null;
        $status      = $input['upi']['internal_status'];

        if (in_array($status, ['authorize_initiated', 'reminder_in_progress_for_authorize']) === false)
        {
            throw new Exception\LogicException('UPI recurring metadata status not applicable for debit');
        }

        switch ($descripion)
        {
            case 'authorize_fails_twice':
                if ($attempt < 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    $remindAt  = Carbon::now()->addSeconds(3600)->getTimestamp();
                }
                break;

            case 'authorize_fails':
                if ($attempt < 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    $remindAt  = Carbon::now()->addSeconds(3600)->getTimestamp();
                }
                else if ($attempt === 3)
                {
                    $errorCode = ErrorCode::GATEWAY_ERROR_BANK_OFFLINE;
                    // Just fail the retries completely
                    $remindAt  = null;
                }
                break;
        }

        // Handle failure here
        $response = [
            'acquirer' => [
                'vpa'           => $input['payment']['vpa'],
                'reference16'   => '001000100001',
            ],
            'upi'   => [
                'rrn'               => '001000100001',
                'npci_txn_id'       => 'npci_txn_id_for_' . $input['payment']['id'],
                'internal_status'   => 'authorized',
                'reference'         => 'DebitReference:' . $attempt,
                'remind_at'         => $remindAt,
            ],
        ];

        // This is the scenario where the gateway callback is needed to authorize the auto recurring
        // payments, On live gateways it will default behavior of UPI AutoPay
        if ($input['payment']['description'] === 'authorize_on_callback')
        {
            $response['upi'] = [
                'rrn'               => '001000100000',
                'npci_txn_id'       => 'expecting_from_callback',
            ];
            $response['acquirer'] = [];
        }

        if (is_null($errorCode) === false)
        {
            $exception = new Exception\GatewayErrorException($errorCode);

            $exception->setData($response);

            throw $exception;
        }

        return $response;
    }

    protected function verifyPaymentCreateResponse($input)
    {
        if ((isset($input['gateway']['status']) === false) or
            ($input['gateway']['status'] !== 'authorized'))
        {
            if ((isset($input['gateway']['status']) === true) and
                ($input['gateway']['status'] === 'gateway_down'))
            {
                throw new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function isEnrolled($content)
    {
        $content['action'] = 'enroll';

        $server = new Server;

        $content = $server->action($content);

        return ($content !== 'N');
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['TxnRefNo']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    public function setMode($mode)
    {
        assertTrue ($mode === Mode::TEST);

        parent::setMode($mode);
    }

    protected function getRequestArray($content, $input)
    {
        $url = $this->route->getUrlWithPublicAuth('mock_sharp_payment_post');

        $method = 'post';

        if ($input['payment']['method'] === 'card')
        {
            $content['card_number'] = $this->encryptCardNumber($input['card']['number']);
            $content['encrypt'] = '1';
        }

        // Adding for 3ds 2.0 first authentication call
        if (($input['payment']['method'] === 'card') and (isset($input['payment']['notes']) === true)
            and (isset($input['payment']['notes']['protocol']) === true) and (isset($input['browser']) === false) and
            ($input['payment']['notes']['protocol'] === '3ds2'))
        {
            $request = [
                'url' => $url,
                'method' => $method,
                'content' => $content,
                'auth_step'=> '3ds2Auth',
                'notificationUrl' => $this->route->getUrl('payment_redirect_to_authenticate_get', ['id' => $input['payment']['id']]),
            ];

            return $request;
        }

        if ((($input['payment']['method'] === 'card') and
            ($input['card']['number'] === '4111111111111111')) or  ( ($input['payment']['method'] === 'wallet') and ($input['payment']['wallet'] === 'amazonpay')))
        {
            $method = 'get';
            $url = $url . '&' . http_build_query($content);
            $content = [];
        }

        $request = [
            'url' => $url,
            'method' => $method,
            'content' => $content,
        ];

        return $request;
    }

    protected function encryptCardNumber($number)
    {
        return Crypt::encrypt($number);
    }

    protected function decryptCardNumber($encryptedCard)
    {
        return Crypt::decrypt($encryptedCard);
    }

    protected function isSecondRecurringPaymentRequest($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['recurring_type'] === 'auto'))
        {
            return true;
        }

        return false;
    }

    protected function addRecurringDataIfApplicable(array $input, array & $acquirerData)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['method'] === Payment\Method::EMANDATE))
        {
            $recurringData = $this->getRecurringData($input['gateway']);

            $acquirerData = array_merge($acquirerData, $recurringData);
        }
    }

    protected function getRecurringData($gatewayInput)
    {
        $recurringStatus = Token\RecurringStatus::CONFIRMED;

        if ((isset($gatewayInput['token_recurring_status']) === false) or
            ($gatewayInput['token_recurring_status'] !== Token\RecurringStatus::CONFIRMED))
        {
            $recurringStatus = Token\RecurringStatus::REJECTED;
        }

        $recurringData[Token\Entity::RECURRING_STATUS] = $recurringStatus;

        if ($recurringStatus === Token\RecurringStatus::REJECTED)
        {
            $recurringData[Token\Entity::RECURRING_FAILURE_REASON] = 'Rejected by bank';
        }

        return $recurringData;
    }

    protected function addAvsResponseIfApplicable(array $input, array & $acquirerData)
    {
        if (isset($input['payment']['billing_address']))
        {
            $validAddresses = [$this->getBillingAddressArray(), $this->getBillingAddressArray(true)];

            if(in_array($billingAddress = $input['payment']['billing_address'], $validAddresses))
            {
                $avsResponse['avs_result'] = 'B';
            }

            else
            {
                $avsResponse['avs_result'] = 'A';
            }

            $acquirerData = array_merge($acquirerData, $avsResponse);
        }
    }

    protected function getBillingAddressArray($international = false)
    {
        $address = [];

        if ($international === false)
        {
            $address = [
                'line1' => 'Razorpay Software, 1st Floor, 22, SJR Cyber',
                'line2' => 'Hosur Main Road, Adugodi',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'in',
                'postal_code' => '560030',
            ];
        }

        else{
            $address = [
                'line1' => '21 Applegate Appartment',
                'line2' => 'Rockledge Street',
                'city' => 'New York',
                'state' => 'New York',
                'country' => 'us',
                'postal_code' => '11561',
            ];
        }

        return $address;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        if ((isset($input['refund'][Payment\Refund\Entity::IS_SCROOGE]) === true) and
            ($input['refund'][Payment\Refund\Entity::IS_SCROOGE] === true))
        {
            return $this->getScroogeResponse($input, 'verify');
        }

        return false;
    }

    protected function getGatewayResponse(int $amount, int $attempts)
    {
        $response = [
            'amount'                => $amount,
            'action'                => 'refund',
            'received'              => true,
            'response_code'         => 300,
            'gateway_refund_id'     => '',
            'gateway_merchant_id'   => '10000000000000'
        ];

        switch ($amount)
        {
            // Validation failure
            case ($amount === 8888):

                $response['result']         = 'Your account does not have enough credits '.
                                                'to carry out the refund operation.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS;
                break;

            // Hard failure
            case (($amount === 4444) or ($amount === 4442)):

                $response['result']         = 'Payment failed because of risk score.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK;
                break;

             // Soft failure
            case (($amount === 5555) or ($amount === 6666)):

                $response['result']         = 'Your account does not have enough balance to '.
                                                'carry out the refund operation.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;
                break;

            // Soft failure
            case ($amount === 2111):

                $response['result']         = 'Refund failed';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_FAILED;
                break;

            // Soft failure
            case ($amount === 3111):

                $response['result']         = 'Gateway response code mapping not found.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;
                break;

            // Soft failure
            case ($amount === 4111):

                $response['result']         = 'Gateway system is busy, please retry.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_SYSTEM_BUSY;
                break;

             // Request failure
            case ((($amount === 7777) or ($amount === 9999)) and ((int) $attempts === 0)):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;
                break;

            // Request failure
            case ($amount === 3456):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS;
                break;

            // Hard failure + FTA retry
            case ($amount === 1357):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::BAD_REQUEST_FORBIDDEN;
                break;

            // Hard failure
            case ($amount === 2468):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::SERVER_ERROR_LOGICAL_ERROR;
                break;

            // Soft failure
            case ($amount === 7531):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_FATAL_ERROR;
                break;

            default:
                $response = [
                    'result'                => 'REFUND SUCCESSFUL',
                    'action'                => 'refund',
                    'amount'                => $amount,
                    'received'              => true,
                    'response_code'         => 200,
                    'status_code'           => 'REFUND_SUCCESSFUL',
                    'gateway_refund_id'     => '224343435454',
                    'gateway_merchant_id'   => '10000000000000'
                ];
        }

        return $response;
    }

    protected function getVerifyGatewayResponse(int $amount, int $amountRefunded, int $attempts)
    {
        $response = [
            'amount'                => $amount,
            'action'                => 'verify',
            'received'              => true,
            'response_code'         => 200,
            'gateway_refund_id'     => '',
            'gateway_merchant_id'   => '10000000000000'
        ];

        if (empty($amount) === true)
        {
            $response['result']         = 'Refund Failed';
            $response['status_code']    = ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT;

            return $response;
        }

        switch ($amount)
        {
            // intermediate failure - no retry - waiting for recon
            case ($amount === 1111):
                $response['result']         = 'Gateway verify refund unexpected response';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS;

                break;

            // request failure
            case ($amount === 2222):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

                break;

            case (($amount === 8888) and ((int) $attempts === 0) and ($amount === $amountRefunded)):
                $response['result']         = 'Request Timeout. Please try again.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

                break;

            // Hard failure. Added amount check to mock the case when refund for amount 5000 of payment 5000
            // will be failed at first time but if full 5000 is refunded, it will be successful
            case (($amount === 4444) and ($amount === $amountRefunded)):
                $response['result']         = 'Payment failed because of risk score.';
                $response['status_code']    = ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK;

                break;

            case (($amount === 4444) and ($amount !== $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            case (($amount === 5555) and ($amount == $amountRefunded)):
                $response['result']         = 'Refund Failed';
                $response['status_code']    = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;

                break;

            case (($amount === 5555) and ($amount != $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            // Soft failure. Added amount check to mock the case when verify for refund of 6666 of payment 7000
            // will be failed at first time but if full 7000 is refunded, it will be successful
            case (($amount === 6666) and ($amount !== $amountRefunded)):
                $response['result']         = 'REFUND_SUCCESSFUL';
                $response['status_code']    = 'REFUND_SUCCESSFUL';

                break;

            default:
                $response['result']         = 'Refund Failed';
                $response['status_code']    = ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT;
        }

        return $response;
    }

    protected function getScroogeResponse(array $input, string $action)
    {
        if ($action === 'refund')
        {
            $gatewayResponse['gateway_response'] = $this->getGatewayResponse($input['refund']['amount'], $input['refund']['attempts']);
        }
        else
        {
            $gatewayResponse['gateway_verify_response'] = $this->getVerifyGatewayResponse($input['refund']['amount'],
                                                                $input['payment']['amount_refunded'], $input['refund']['attempts']);
        }

        if (($action === 'refund') and ($gatewayResponse['gateway_response']['status_code'] !== 'REFUND_SUCCESSFUL'))
        {
            throw new Exception\GatewayErrorException($gatewayResponse['gateway_response']['status_code'],
                $gatewayResponse['gateway_response']['status_code'],
                $gatewayResponse['gateway_response']['result'],
                [
                    'gateway_response'      => json_encode($gatewayResponse['gateway_response']),
                    'gateway_keys'          => [
                        'gateway_refund_id'     => $gatewayResponse['gateway_response']['gateway_refund_id'],
                        'gateway_merchant_id'   => $gatewayResponse['gateway_response']['gateway_merchant_id']
                    ],
                    'refund_gateway'        => 'sharp',
                ]);
        }

        $gatewayResponseFinal = (($action === 'verify') ?
                            $gatewayResponse['gateway_verify_response'] :
                            $gatewayResponse['gateway_response']);


        $response = [
            'gateway_response'          => json_encode($gatewayResponse['gateway_response'] ?? ''),
            'gateway_verify_response'   => json_encode($gatewayResponse['gateway_verify_response'] ?? ''),
            'gateway_keys'              => [
                    'gateway_refund_id'     => $gatewayResponseFinal['gateway_refund_id'],
                    'gateway_merchant_id'   => $gatewayResponseFinal['gateway_merchant_id']
            ],
            'refund_gateway'            => 'sharp',
        ];

        if ($action === 'verify')
        {
            $response['success']        = ($gatewayResponseFinal['status_code'] === 'REFUND_SUCCESSFUL');
            $response['status_code']    = $gatewayResponseFinal['status_code'];
        }

        return $response;
    }
}
