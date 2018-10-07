<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use Requests_Hooks;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Encryption\PGPEncryption;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\BharatQr\GatewayResponseParams;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 20;

    const ACQUIRER = 'yesbank';

    const HASH_ALGO = 'sha256';

    protected $gateway = 'upi_yesbank';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    //
    // @todo: Fix the mapping
    //
    protected $map = [
    ];

    protected $forceFillable = [
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        if ((isset($response['error']) === true) or
            ($response['status'] !== Status::INITIATED))
        {
            // @todo: Fetch internal error code on proxy auth from Yesbank and
            // pass it as error code to API
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $response['error']['code'] ?? null,
                $response['error']['description'] ?? null
            );
        }

        return [
            'data'   => [
                'vpa'   => ''
            ]
        ];
    }

    /**
     * Handles the S2S callback
     *
     * @param  array $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->validateCallbackSignature($content);

        $p2p = $content[Fields::DATA];

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $expectedAmount = $input['payment']['amount'];
        $actualAmount   = $p2p[Fields::AMOUNT];

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->checkResponseStatus($p2p, Status::COMPLETED);

        // Authorization was successful
        $this->updateGatewayPaymentResponse($gatewayPayment, $p2p);

        return [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa()
            ]
        ];
    }

    protected function validateCallbackSignature(array $input)
    {
        $signature = $input[Fields::SIGNATURE];

        $content = $input[Fields::RAW];

        $password = $this->getGatewayPassword();

        $hashed = hash_hmac(self::HASH_ALGO, $content, $password);

        if (hash_equals($hashed, $signature) !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
                null,
                null,
                [
                    'expected'  => $hashed,
                    'actual'    => $signature,
                ]);
        }
    }

    /**
     * Will inject Signature in input from request
     *
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        $input[Fields::SIGNATURE] = array_get($input, 'headers.x-Yesbank-signature.0');

        // To make sure, we do not use it later in code.
        unset($input['headers']);

        if ($isBharatQr === true)
        {
            $qrData = $this->getBharatQrData($input);

            return [
                Fields::QR_DATA       => $qrData,
                Fields::CALLBACK_DATA => $input,
            ];
        }

        return $input;
    }

    public function getPaymentIdFromServerCallback($input): string
    {
        return $input['data'][Fields::MERCHANT_REFERENCE_ID];
    }

    /**
     * We only store the VPA, bank and provider because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input): array
    {
        return [
            Entity::VPA         => $input['payment']['vpa'],
            Entity::TYPE        => Base\Type::COLLECT,
            Entity::EXPIRY_TIME => $input['upi']['expiry_time'],
        ];
    }

    protected function sendGatewayRequest($request)
    {
        $username = $this->getGatewayUsername();
        $password = $this->getGatewayPassword();

        if ($this->shouldUseAppAuth() === true)
        {
            // Url must be appended with app
            $request['url'] .= '/app';
        }
        else
        {
            // Otherwise we will use proxy auth
            $username .= '_' . $this->input['merchant']['id'];
        }

        $request['options']['auth'] = [$username, $password];

        return parent::sendGatewayRequest($request);
    }

    protected function getRequestOptions()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $options = [
            'hooks' => $hooks
        ];

        return $options;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientSslKey());
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $cert = $this->config['mindgate']['live_client_cert'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'gateway'        => $this->gateway,
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getClientSslKey()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
                          $this->getClientSslKeyName();

        if (file_exists($clientCertPath) === false)
        {
            $cert = $this->config['mindgate']['live_cert_key'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'gateway'        => $this->gateway,
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    public function getClientCertificateName()
    {
        return 'client_cert_v2.crt';
    }

    public function getClientSslKeyName()
    {
        return 'client_cert_v1.key';
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function getGatewayUsername(): string
    {
        if ($this->isTestMode() === true)
        {
            return 'rzp_test';
        }

        return 'rzp_live';
    }

    protected function getGatewayPassword(): string
    {
        if ($this->isTestMode() === true)
        {
            return $this->config['test_terminal_password'];
        }

        // This is set on all environments, we will be using this regardless of auth
        return $this->config['gateway_terminal_password'];
    }

    public function verifyBharatQrNotification($gatewayResponse)
    {
        $this->validateCallbackSignature($gatewayResponse[Fields::CALLBACK_DATA]);
    }

     /**
     * If gateway_access_code is empty, we will still be using proxy auth.
     * This way we can switch between proxy and app auth from terminal itself.
     *
     * @return bool
     */
    protected function shouldUseAppAuth()
    {
        return ($this->input['terminal']['gateway_access_code'] === 'app');
    }

    /**
     * Sets receiver id in request's content if terminal is for
     * app auth, Yesbank needs receiver id to resolve merchant
     *
     * @param $content
     */
    protected function setReceiverIdIfApplicable(& $content)
    {
        if ($this->shouldUseAppAuth() === true)
        {
            $content[Fields::RECEIVER_ID] = $this->input['terminal']['gateway_merchant_id'];
        }
    }

    protected function getAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $expiryTime = $input['upi']['expiry_time'];

        $collectByTimestamp = Carbon::now(Timezone::IST)->addMinutes($expiryTime)->getTimestamp();

        $content = [
            Fields::TYPE                    => Type::PULL,
            Fields::AMOUNT                  => $payment['amount'],
            Fields::CURRENCY                => $input['payment']['currency'],
            Fields::EXPIRE_AT               => $collectByTimestamp,
            Fields::SENDER                  => [
                Fields::ADDRESS => $input['payment']['vpa'],
            ],
            Fields::DESCRIPTION             => $this->getPaymentRemark($input),
            Fields::NOTES                   => [
                'razorpay_payment_id'       => $payment['id'],
            ],
            Fields::MERCHANT_REFERENCE_ID   => $payment['id'],
            Fields::CATEGORY_CODE           => (string) ($input['merchant']['category'] ?? 5411),
        ];

        $this->setReceiverIdIfApplicable($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRemark(array $input): string
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attr = $this->getMappedResponseToUpdate($response);

        $payment->fill($attr);

        $payment->generatePspData($attr);

        $payment->saveOrFail();
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest(Verify $verify): array
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestArray($verify);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content'     => $content,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestArray($verify)
    {
        $input = $verify->input;
        $gatewayEntity = $verify->payment;

        $request = $this->getStandardRequestArray(
            [
                Fields::ID  => $gatewayEntity['gateway_payment_id'],
            ],
            'get');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'           => $request,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
            ]);

        return $request;
    }

    protected function verifyPayment(Verify $verify): string
    {
        $content = $verify->verifyResponseContent;

        if (isset($content['error']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                $content['error']['code'],
                $content['error']['description']);
        }

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if ($content['status'] === Status::COMPLETED)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = $input['payment']['amount'];

            $actualAmount  = $content[Fields::AMOUNT];

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $attr = $this->getMappedResponseToUpdate($content);

        // We have to call this method explicitly as AuthorizeFailed does not
        $verify->payment->generatePspData($attr);

        $verify->verifyResponseContent = $attr;

        return $status;
    }

    protected function getMappedResponseToUpdate(array $response)
    {
        // Unsetting as we don't want to override it
        unset($response[Entity::TYPE]);

        $attr = $this->getMappedAttributes($response);

        $attr[Entity::VPA] = array_get($response, Fields::SENDER.'.'.Fields::ADDRESS);
        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        return $attr;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return false;
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return true;
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $rrn = $gatewayPayment[Entity::NPCI_REFERENCE_ID];

        $token = $this->fetchMindgateOAuthToken($input);

        $decryptedContent = [
            'device_id'               => '551897080946357',
            'mobile_number'           => $this->config['mindgate']['mobile'],
            'sim_id'                  => '89918740400029188800',
            'os'                      => 'Android6.0',
            'app_name'                => 'org.razorpay',
            'location'                => 'Bangalore',
            'ip'                      => '172.21.14.99',
            'geocode'                 => '19.0911,72.9208',
            'type'                    => 'TYPE',
            'account_provider_ref_id' => $this->config['mindgate']['account_id'],
            'sender_vpa'              => $this->config['mindgate']['vpa'],
            'receiver_vpa'            => $input['payment']['vpa'],
            'receiver_name'           => 'Receiver',
            'txn_note'                => 'Refund for ' . $input['payment']['id'] . ' RRN ' . $rrn,
            'amount'                  => (string) ($input['refund']['amount'] / 100),
            'order_number'            => $input['refund']['id'],
        ];

        $content = $this->getMgEncryptedContent($decryptedContent);

        $content = json_encode($content, JSON_UNESCAPED_SLASHES);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'mg_refund');

        $traceRequest['decrypted_content'] = $decryptedContent;
        $traceRequest['headers'] = $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $request['url'] = $request['url'] . '?access_token=' . $token;

        $response = $this->sendMgGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($responseArray, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        if (isset($responseArray['data']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $responseArray[Fields::ERROR_CODE],
                $responseArray['message']);
        }

        $decryptedResp = $this->getMgDecryptedContent($responseArray['data']);

        $this->traceGatewayPaymentResponse($decryptedResp, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        if ((isset($decryptedResp[Fields::RESPONSE_CODE]) === false) or
            ($decryptedResp[Fields::RESPONSE_CODE] !== '00'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $decryptedResp[Fields::ERROR_CODE] ?? '',
                $decryptedResp['message'] ?? '');
        }

        $decryptedResp['received'] = true;

        $this->createGatewayPaymentEntity($decryptedResp, Action::REFUND);
    }

    protected function checkResponseStatus(array $p2p, string $successStatus)
    {
        if ($p2p[Fields::STATUS] !== $successStatus)
        {
            $errorCode = ResponseErrorCode::getMappedErrorCode($p2p[Fields::INTERNAL_ERROR_CODE]);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $p2p[Fields::INTERNAL_ERROR_CODE],
                $p2p[Fields::ERROR_DESCRIPTION]);
        }
    }
}
