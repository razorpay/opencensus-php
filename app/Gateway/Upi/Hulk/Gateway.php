<?php

namespace RZP\Gateway\Upi\Hulk;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\BharatQr;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Encryption\PGPEncryption;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\BharatQr\GatewayResponseParams;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 20;

    const ACQUIRER = 'hdfc';

    const HASH_ALGO = 'sha256';

    protected $gateway = 'upi_hulk';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    //
    // @todo: Fix the mapping
    //
    protected $map = [
        Fields::ID                        => Entity::GATEWAY_PAYMENT_ID,
        Fields::STATUS                    => Entity::STATUS_CODE,
        Fields::RRN                       => Entity::NPCI_REFERENCE_ID,
        Fields::TXN_ID                    => Entity::NPCI_TXN_ID,
        Entity::VPA                       => Entity::VPA,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::PROVIDER                  => Entity::PROVIDER,
        Entity::BANK                      => Entity::BANK,
        Entity::TYPE                      => Entity::TYPE,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Fields::CALLER_ACCOUNT_NUMBER     => Entity::ACCOUNT_NUMBER,
        Fields::CALLER_IFSC_CODE          => Entity::IFSC,
        Fields::MERCHANT_REFERENCE_ID     => Entity::MERCHANT_REFERENCE,
        Fields::RESPONSE_CODE             => Entity::STATUS_CODE,
        Fields::BANK_RRN                  => Entity::NPCI_REFERENCE_ID,
    ];

    protected $forceFillable = [
        Entity::VPA                       => Entity::VPA,
        Fields::CALLER_IFSC_CODE          => Entity::IFSC,
        Fields::CALLER_ACCOUNT_NUMBER     => Entity::ACCOUNT_NUMBER,
        Fields::RRN                       => Entity::NPCI_REFERENCE_ID,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isBharatQrPayment() === true)
        {
            $gatewayInput = $input[Fields::CONTENT][Fields::DATA];

            $gatewayInput[Entity::RECEIVED] = true;

            $gatewayInput[Fields::TYPE] = Type::getMappedTyped($gatewayInput[Fields::TYPE]);

            $gatewayInput[Entity::VPA] = $gatewayInput[Fields::SENDER][Fields::ADDRESS];

            $this->createGatewayPaymentEntity($gatewayInput);

            return null;
        }

        // @todo Enable intent
        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

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
            // @todo: Fetch internal error code on proxy auth from hulk and
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

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE => Base\Type::PAY,
        ];

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getPayAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $this->checkResponseStatus($response, Status::CREATED);

        return $this->getIntentRequest($input, $response);
    }

    protected function getIntentRequest($input, $response)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $response[Fields::RECEIVER][Fields::ADDRESS],
            Base\IntentParams::PAYEE_NAME    => $this->getFormattedDba($input),
            Base\IntentParams::TXN_REF_ID    => $this->getFormattedRefId($response),
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => (string) ($input['merchant']['category'] ?? 5411),
        ];

        return ['data' => ['intent_url' => $this->generateIntentString($content)]];
    }

    protected function getFormattedDba($input)
    {
        return preg_replace('/\s+/', '', $input['merchant']->getFilteredDba());
    }

    protected function getFormattedRefId(array $p2p)
    {
        // Ref Id is not sent right now, will be sent later
        if (isset($p2p[Fields::REF_ID]) === true)
        {
            return $p2p[Fields::REF_ID];
        }

        return str_replace('p2p_', '', $p2p[Fields::ID]);
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

        $this->updateGatewayPaymentResponse($gatewayPayment, $p2p);

        $this->checkResponseStatus($p2p, Status::COMPLETED);

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
        $input[Fields::SIGNATURE] = array_get($input, 'headers.x-hulk-signature.0');

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

    protected function sendMgGatewayRequest($request)
    {
        $request['options'] = $this->getRequestOptions();

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
     * app auth, Hulk needs receiver id to resolve merchant
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

    protected function getPayAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $content = [
            Fields::TYPE                    => Type::EXPECTED_PUSH,
            Fields::AMOUNT                  => $payment['amount'],
            Fields::CURRENCY                => $payment['currency'],
            Fields::DESCRIPTION             => $this->getPaymentRemark($input),
            Fields::NOTES                   => [
                'razorpay_payment_id' => $payment['id']
            ],
            Fields::MERCHANT_REFERENCE_ID   => $payment['id'],
            Fields::CATEGORY_CODE           => (string) ($input['merchant']['category'] ?? 5411),
        ];

        $this->setReceiverIdIfApplicable($content);

        if ($input['merchant']->isTPVRequired() === true)
        {
            $content[Fields::CALLER_ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $request = $this->getStandardRequestArray($content, 'post');

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

    protected function getSubMerchantName(array $input): string
    {
        $dba = preg_replace('/\s+/', '', $input['merchant']->getFilteredDba());

        return ($dba ? substr($dba, 0, 30) : 'Razorpay');
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

    public function forceAuthorizeFailed(array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'],
                                                                      Action::AUTHORIZE);

        if (($gatewayPayment[Entity::STATUS_CODE] === Status::COMPLETED) and
            ($gatewayPayment[Entity::RECEIVED]) === true)
        {
            return true;
        }

        $attr = array_only($input['gateway'], $this->forceFillable);

        $attr[Entity::STATUS_CODE] = Status::COMPLETED;

        $gatewayPayment->fill($attr);

        $gatewayPayment->generatePspData($attr);

        $gatewayPayment->saveOrFail();

        return true;
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

        $token = $this->fetchMindgateOAuthToken($input);

        $decryptedContent = [
            'mobile_number'           => $this->config['mindgate']['mobile'],
            'order_number'            => $input['refund']['id'],
        ];

        $content = $this->getMgEncryptedContent($decryptedContent);

        $content = json_encode($content, JSON_UNESCAPED_SLASHES);

        $traceRequest = $request = $this->getStandardRequestArray($content, 'POST', 'mg_verify_refund');

        $traceRequest['decrypted_content'] = $decryptedContent;
        $traceRequest['headers'] = $request['headers'] = [
            'Content-Type' => 'application/json'
        ];

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::REFUND_VERIFY_REQUEST);

        $request['url'] = $request['url'] . '?access_token=' . $token;

        $response = $this->sendMgGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        $this->traceGatewayPaymentResponse($responseArray, $input, TraceCode::REFUND_VERIFY_RESPONSE);

        if (isset($responseArray['data']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $responseArray[Fields::ERROR_CODE],
                $responseArray['message']);
        }

        $decryptedResp = $this->getMgDecryptedContent($responseArray['data']);

        $this->traceGatewayPaymentResponse($decryptedResp, $input, TraceCode::REFUND_VERIFY_RESPONSE);

        if ($decryptedResp[Fields::TRANSACTION_STATUS] === 'F')
        {
            return false;
        }
        else if ($decryptedResp[Fields::TRANSACTION_STATUS] === 'S')
        {
            return true;
        }

        throw new Exception\LogicException(
            'Shouldn\'t reach here',
            null,
            [
                'gateway_status' => $decryptedResp['transaction_status'],
                'refund_id'      => $input['refund']['id'],
            ]);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED, null,
            'Refund Blocked');

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

    // @codingStandardsIgnoreLine
    protected function fetchMindgateOAuthToken($input)
    {
        $content = [
            'grant_type'    => 'password',
            'client_id'     => $this->config['mindgate']['client_id'],
            'client_secret' => $this->config['mindgate']['client_secret'],
            'username'      => $this->config['mindgate']['username'],
            'password'      => $this->config['mindgate']['password'],
        ];

        $this->domainType = $this->mode . '_mindgate';

        $request = $traceRequest = $this->getStandardRequestArray($content, 'get', 'mg_oauth_token');

        unset($traceRequest['content']);

        $this->trace->info(TraceCode::GATEWAY_SUPPORT_REQUEST, $traceRequest);

        $response = $this->sendMgGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_SUPPORT_RESPONSE,
                            [
                                'action'        => 'fetch_mg_outh_token',
                                'token_type'    => $responseArray['token_type'] ?? ' ',
                                'expires_in'    => $responseArray['expires_in'] ?? ' ',
                            ]);

        return $responseArray['access_token'];
    }

    protected function getMgEncryptedContent($content)
    {
        $plainText = json_encode($content);

        $pgp = $this->getPgpInstance();

        $encrypted = $pgp->encryptSign($plainText);

        return [
            'pgmerchant_Id' => $this->config['mindgate']['mid'],
            'data'          => $encrypted,
            'key_id'        => $this->config['mindgate']['key_id'],
            'seq_number'    => UniqueIdEntity::generateUniqueId(),
        ];
    }

    protected function getMgDecryptedContent($encrypted)
    {
        $pgp = $this->getPgpInstance();

        $encrypted = trim(str_replace('\n', "\n", $encrypted));

        $plainText = $pgp->decryptVerify($encrypted);

        $decryptedData = $this->jsonToArray($plainText);

        return $decryptedData;
    }

    public function getPgpInstance()
    {
        $pgpConfig = [
            'public_key'  => trim(str_replace('\n', "\n", $this->config['mindgate']['public_key'])),
            'private_key' => trim(str_replace('\n', "\n", $this->config['mindgate']['private_key'])),
            'passphrase'  => $this->config['mindgate']['passphrase'],
        ];

        $pgp = new PGPEncryption($pgpConfig);

        return $pgp;
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

    /**
     * Only called for func environment.
     *
     * @param string $type
     * @return string
     */
    protected function getExternalMockUrl(string $type)
    {
        return env('UPI_HULK_URL') . '/' . $this->getRelativeUrl($type);
    }

    protected function getBharatQrData(array $bharatQrInput)
    {
        $input = $bharatQrInput[Fields::CONTENT][Fields::DATA];

        $attributes = [
            GatewayResponseParams::AMOUNT                => $input[Fields::AMOUNT],
            GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[Fields::RECEIVER][Fields::ID],
            GatewayResponseParams::VPA                   => $input[Fields::RECEIVER][Fields::ADDRESS],
            GatewayResponseParams::MERCHANT_REFERENCE    => substr($input[Fields::MERCHANT_REFERENCE_ID], 3, 17),
            GatewayResponseParams::METHOD                => Payment\Method::UPI,
            GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[Fields::TXN_ID],
        ];

        return $attributes;
    }
}
