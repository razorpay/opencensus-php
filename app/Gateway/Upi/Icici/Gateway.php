<?php

namespace RZP\Gateway\Upi\Icici;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use ErrorException;
use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\UpiTransfer;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base as GatewayBase;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\BharatQr;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    const GATEWAY_API_RAZORX_PREFIX     = 'upi_icici_gateway_api_versions';
    const GATEWAY_API_VERSION_1         = 'v1';
    const GATEWAY_API_VERSION_2         = 'v2';
    const VPA_LENGTH                    = 20;

    use AuthorizeFailed;
    use Base\RecurringTrait;
    use Base\MandateTrait;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 30;

    protected $gateway = 'upi_icici';

    const ACQUIRER = 'icici';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@icici';

    /**
     * This is a special ifsc for TPV , which skips the TPV
     * validation on IFSC, and does the validation based on bank
     * in the gateway side
     */
    const SPECIAL_IFSC = 'IFSCXXXXXNA';

    /**
     * Main parent MID of Razorpay
     */
    const PARENT_GATEWAY_MERCHANT_ID = '116798';

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::PROVIDER                  => Entity::PROVIDER,
        Entity::BANK                      => Entity::BANK,
        Entity::TYPE                      => Entity::TYPE,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Fields::PAYER_VA                  => Entity::VPA,
        Fields::VERIFY_PAYER_VA           => Entity::VPA,
        Fields::PAYER_NAME                => Entity::NAME,
        Fields::PAYER_MOBILE              => Entity::CONTACT,
        Fields::RESPONSE                  => Entity::STATUS_CODE,
        Fields::TXN_STATUS                => Entity::STATUS_CODE,
        Fields::RESPONSE_CODE             => Entity::STATUS_CODE,
        Fields::MERCHANT_TRAN_ID          => Entity::MERCHANT_REFERENCE,
        // NOTE: The GATEWAY_PAYMENT_ID is resolved into NPCI_REFERENCE_ID for Payments
        // If trying to change its definition, Kindly find the solution for NPCI_REFERENCE_ID too
        Fields::BANK_RRN                  => Entity::GATEWAY_PAYMENT_ID,
        Fields::ORIGINAL_BANK_RRN         => Entity::GATEWAY_PAYMENT_ID,
        Fields::MERCHANT_ID               => Entity::GATEWAY_MERCHANT_ID,
        Fields::ORIGINAL_BANK_RRN_REQ     => Entity::NPCI_REFERENCE_ID,
        Entity::GATEWAY_DATA              => Entity::GATEWAY_DATA,
    ];

    protected $forceFillable = [
        Entity::VPA                       => Entity::VPA,
        Fields::BANK_RRN                  => Entity::GATEWAY_PAYMENT_ID,
    ];

    // To trace the razorx response
    protected $razorxTrace   = [];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        parent::action($input, Action::AUTHENTICATE);

        if ($this->isFirstRecurringPayment($input) === true)
        {
            return $this->authenticate($input);
        }

        if (($this->isBharatQrPayment() === true) or
            ($this->isUpiTransferPayment() === true))
        {
            //
            //Hacky fixture: When ORIGINAL_BANK_RRN_REQ is null, the entity NPCI_REFERENCE_ID method becomes
            // inaccessible for the gateway.To fix the issue, we assign it to BANK_RRN so that paymentData can
            //access NPCI_REFERENCE_ID using getNpciReferenceId() method.
            //
            $input[Fields::ORIGINAL_BANK_RRN_REQ] = $input[Fields::BANK_RRN];

            $input[Entity::TYPE] = Base\Type::PAY;

            $paymentData = $this->createGatewayPaymentEntity($input, Action::AUTHORIZE);

            return [
                'acquirer' => [
                    Payment\Entity::REFERENCE16 => $paymentData->getNpciReferenceId(),
                ],
            ];
        }

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $attributes[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];

        $payment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request =  $this->getAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        if (Utility::isXml($response->body) === true)
        {
            $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [
                'body'      => $response->body,
                'encrypted' => false,
                'gateway'   => $this->gateway
            ]);

            $this->action = 'verify';

            $verify = new Verify($this->gateway, $this->input);

            $response = $this->sendPaymentVerifyRequest($verify);

            if ($response['status'] === Status::PENDING)
            {
                $response['response'] = Status::TXN_INITIATED;
            }

            $this->action = 'authorize';
        }
        else
        {
            $response = $this->parseGatewayResponse($response->body);
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $status = (int) $response['response'];

        if ($status !== Status::TXN_INITIATED)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            $ex->markSafeRetryTrue();

            throw $ex;
        }

        $vpa = $this->terminal->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA;

        return [
            'data'   => [
                'vpa'   => $vpa
            ]
        ];
    }

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE => Base\Type::PAY,
        ];

        $payment = $this->createGatewayPaymentEntity($attributes, Action::AUTHORIZE);

        $request =  $this->getPayAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $status = (int) $response['response'];

        if ($status !== Status::TXN_SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            $ex = new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));

            $ex->markSafeRetryTrue();

            throw $ex;
        }

        return $this->getIntentRequest($input, $response);
    }

    protected function getIntentRequest($input, $response)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => $response['refId'],
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => 'INR',
            Base\IntentParams::MCC           => $this->getTerminalId($input),
        ];

        return ['data' => ['intent_url' => $this->generateIntentString($content)]];
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
            Entity::VPA => $input['payment']['vpa'],
            Entity::TYPE => Base\Type::COLLECT,
            Entity::GATEWAY_DATA => $input['upi']['gateway_data'] ?? null,
        ];
    }

    protected function getMandateCallbackResponseIfApplicable($response){
        if (($this->isMandatePauseCallback($response) === true))
        {
            return [
                'upi_mandate' => [
                    'umn'     => $response[Fields::UMN],
                    'status'  => 'pause',
                ]
            ];
        }
        else if ($this->isMandateResumeCallback($response) === true)
        {
            return [
                'upi_mandate' => [
                    'umn'     => $response[Fields::UMN],
                    'status'  => 'resume',
                ]
            ];
        }
        else if ($this->isMandateRevokeCallback($response) === true)
        {
            return [
                'upi_mandate' => [
                    'umn'     => $response[Fields::UMN],
                    'status'  => 'revoke',
                ]
            ];
        }
        return [];
    }

    /**
     * @param string $response
     * @param bool   $forceDecryption
     * @param bool   $isUpiTransfer
     *
     * @return array response as associative array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    protected function parseGatewayResponse(string $response, bool $forceDecryption = false, bool $isUpiTransfer = false): array
    {
        if (($forceDecryption === false) or
            (Reconciliate::$isReconRunning === true))
        {
            $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [
                'body'      => $response,
                'encrypted' => true,
                'gateway'   => $this->gateway
            ]);

            $decodedJson = json_decode($response, true);

            // The response is encrypted sometimes,
            // but not in all cases (usually errors are unencrypted)
            if ($decodedJson !== null)
            {
                return $decodedJson;
            }
        }

        // The gateway response is encrypted, but wrapped
        // in lines of 80-length. Decryption can't handle
        // this, so we remove any whitespace from the response
        // since this is base64, it only removes newlines
        $response = preg_replace('/\s/', '', $response);

        $response = base64_decode($response, true);

        try
        {
            $response = $this->decrypt($response, $isUpiTransfer);
        }
        catch (ErrorException $e)
        {
            $this->trace->traceException($e, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        return $this->jsonToArray($response);
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getMerchantId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->input['terminal']['gateway_merchant_id'];
    }

    /**
     * In both getPublicKey and getPrivateKey,
     * we are converting literal '\n' (single quotes)
     * to actual newlines (double quotes "\n").
     *
     * This is because we store them in environment, which
     * uses literal \n
     *
     * This is the public key used to encrypt requests
     * @return string public key
     */
    protected function getPublicKey(): string
    {
        $key = $this->config['live_public_key'];

        if ($this->mode === Mode::TEST)
        {
            $key = $this->config['test_public_key'];
        }

        return trim(str_replace('\n', "\n", $key));
    }

    /**
     * This is the private key used for
     * decrypting responses we get from the
     * gateway server
     *
     * @param bool $isUpiTransfer : For upi transfer, new private key will be used for decryption of callback responses.
     *
     * @return string Private Key
     */
    protected function getPrivateKey(bool $isUpiTransfer): string
    {
        $configKey = 'live_private_key';

        if ($this->mode === Mode::TEST)
        {
            $configKey = 'test_private_key';
        }

        if ($isUpiTransfer === true)
        {
            $configKey = 'ut_'.$configKey;
        }

        $key = $this->config[$configKey];
        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }


    /**
     * Gets the correct URL from the
     * Url class
     * @param  string $type Action String
     * @return String URL
     */
    protected function getUrl($type = null): string
    {
        $url = parent::getUrl($type);

        // We don't need to use different URLs for different merchants from now on.
        // Main parent MID can be appended instead of specific merchant MID
        return sprintf($url, self::PARENT_GATEWAY_MERCHANT_ID);
    }

    /**
     * Encrypts data before sending it to ICICI
     * @param  string $data
     * @return string
     */
    protected function encrypt(string $data): string
    {
        $rsa = $this->getCipherInstance();

        $rsa->loadKey($this->getPublicKey());

        return $rsa->encrypt($data);
    }

    /**
     * Decrypts responses from the ICICI API
     *
     * @param string $data
     * @param bool   $isUpiTransfer
     *
     * @return string
     */
    protected function decrypt(string $data, bool $isUpiTransfer = false): string
    {
        $rsa = $this->getCipherInstance();

        $key = $this->getPrivateKey($isUpiTransfer);

        $rsa->loadKey($key, RSA::PRIVATE_FORMAT_PKCS1);

        return $rsa->decrypt($data);
    }

    protected function getCipherInstance(): RSA
    {
        /**
         * We need to do this to use PCCS 1.5 instead of 1.7
         * which is the default. This is because of what the
         * bank uses on the other side.
         */
        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false)
        {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
        }

        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    protected function getAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $expiryTime = $input['upi']['expiry_time'];

        $collectByTimestamp = Carbon::now(Timezone::IST)->addMinutes($expiryTime)->format('d/m/Y h:i A');

        $data = [
            Fields::AMOUNT           => $this->formatAmount($payment['amount']),
            Fields::COLLECT_BY_DATE  => $collectByTimestamp,
            Fields::BILL_NUMBER      => '1234',
            Fields::MERCHANT_ID      => $this->getMerchantId(),
            Fields::MERCHANT_TRAN_ID => $payment['id'],
            Fields::MERCHANT_NAME    => 'Razorpay',
            Fields::NOTE             => $this->getPaymentRemark($input),
            // sub-merchant name field only supports alphanumeric
            // hence replacing all the spaces to empty string here.
            Fields::SUBMERCHANT_NAME => $this->getSubMerchantName($input),
            Fields::PAYER_VA_REQ     => $input['payment']['vpa'],
            Fields::SUBMERCHANT_ID   => $this->getSubMerchantId($input),
            Fields::TERMINAL_ID      => $this->getTerminalId($input),
        ];

        if ($input['merchant']->isTPVRequired() === true)
        {
            $data[Fields::VALIDATE_PAYER_ACCOUNT] = 'Y';
            $data[Fields::PAYER_ACCOUNT] = $input['order'][Order\Entity::ACCOUNT_NUMBER];
            $data[Fields::PAYER_IFSC] = self::SPECIAL_IFSC;
        }

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $traceData = $this->maskUpiDataForTracing($data, [
            Entity::VPA             => Fields::PAYER_VA_REQ,
            Entity::ACCOUNT_NUMBER  => Fields::PAYER_ACCOUNT,
        ]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'decrypted_content' => $traceData,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'razorx'            => $this->razorxTrace,
            ]);

        return $request;
    }

    protected function getPayAuthorizeRequestArray(array $input): array
    {
        $payment = $input['payment'];

        $data = [
            Fields::AMOUNT           => $this->formatAmount($payment['amount']),
            Fields::BILL_NUMBER      => '1234',
            Fields::MERCHANT_ID      => $this->getMerchantId(),
            Fields::MERCHANT_TRAN_ID => $payment['id'],
            Fields::TERMINAL_ID      => $this->getTerminalId($input),
        ];

        $path = 'pay';

        if ($input['merchant']->isTPVRequired() === true)
        {
            $path = 'pay_v3';

            $data[Fields::VALIDATE_PAYER_ACCOUNT2] = 'Y';
            $data[Fields::PAYER_ACCOUNT] = $input['order'][Order\Entity::ACCOUNT_NUMBER];
            $data[Fields::PAYER_IFSC] = self::SPECIAL_IFSC;
        }

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content, 'post', $path);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'decrypted_content' => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
                'razorx'            => $this->razorxTrace,
            ]);

        return $request;
    }

    protected function getTerminalId(array $input): string
    {
        $mcc = (string) $input['merchant']->getCategory();

        //Default merchant category code is 5411
        if ($mcc === '1234')
        {
            $mcc = '5411';
        }

        return $mcc;
    }

    protected function getSubMerchantName(array $input): string
    {
        $dba = preg_replace('/\s+/', '', $input['merchant']->getFilteredDba());

        return ($dba ? substr($dba, 0, 30) : 'Razorpay');
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data): string
    {
        $json = json_encode($data);

        $data = $this->encrypt($json);

        // RSA::encrypt returns false if encryption failed
        assertTrue($data !== false);

        return base64_encode($data);
    }

    protected function updateGatewayPaymentResponse(Entity $payment, array $response, bool $shouldMap = true)
    {
        $attr = $response;

        if ($shouldMap === true)
        {
            $attr = $this->getMappedAttributes($response);
        }

        // We can not save RRN timeline for recurring payments because gateway_data is too short for that
        $isRecurringAuthorize = (array_get($payment->getGatewayData(), Base\Constants::ACTION) === 'execte');

        // For payment's entities, we need NPCI REF ID to be generated.
        // Now, gateway payment entity will have authorize action in 3 scenarios - authorize, callback and verify.
        // In authorize and callback, we dont get OriginalBankRrn or originalBankRrn. So, entity wont have Npci
        // reference field set at all. In these cases, we need to get the rrn from gateway payment id only as we get
        // bankRRN field only in response. So, we can always update using gateway payment id for authorize and callback.
        // For verify, we get OriginalBankRRN, which is again mapped to gateway payment id.
        // So, in all the above cases, it is safe to overwrite the npci reference id with the value in mapped attribute
        // for gateway payment id.
        // For refund, we get originalBankRRN in response which is mapped to NPCI reference ID. But refund will have
        // entity with refund action. So, overwrite is safe as long as action is authorize.
        if (($payment->getAction() === Action::AUTHORIZE) and
            (isset($attr[Entity::GATEWAY_PAYMENT_ID]) === true) and
            ($isRecurringAuthorize === false))
        {
            // Now, The Mapper already maps the BANK_RRN to GATEWAY_PAYMENT_ID
            // Even if it is null, we can update null by null
            $attr[Entity::NPCI_REFERENCE_ID] = $attr[Entity::GATEWAY_PAYMENT_ID];

            // If we already have NPCI ref id set in database and we are getting another entity
            // We need to trace that as mismatch to get more details of payment and action
            if ((empty($payment->getNpciReferenceId()) === false) and
                ($payment->getNpciReferenceId() !== $attr[Entity::NPCI_REFERENCE_ID]))
            {
                $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [
                    'message'   => 'NpciRefId Mismatch',
                    'expected'  => $payment->getNpciReferenceId(),
                    'actual'    => $attr[Entity::NPCI_REFERENCE_ID],
                ]);
            }

            $attr[Entity::GATEWAY_DATA] = $payment->getGatewayData();

            // Not exactly needed, but just to be on safer side
            if (is_array($attr[Entity::GATEWAY_DATA]) === false)
            {
                $attr[Entity::GATEWAY_DATA] = [];
            }

            // We can also store all the different NRI(RRN) we receive from bank
            $attr[Entity::GATEWAY_DATA][Entity::NPCI_REFERENCE_ID][$attr[Entity::NPCI_REFERENCE_ID]] = [
                Entity::ACTION      => $this->getAction(),
                Entity::UPDATED_AT  => Carbon::now()->getTimestamp(),
            ];
        }

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $payment->fill($attr);

        $payment->generatePspData($attr);

        $payment->saveOrFail();
    }

    public function mandateCancel(array $input)
    {
        parent::action($input, Action::MANDATE_CANCEL);

        $this->setGatewayDataBlockForUpiRecurring($input);

        return $this->recurringMandateRevoke($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        if (($this->isFirstRecurringPayment($input) === true) or
            ($this->isSecondRecurringPayment($input) === true))
        {
            return $this->recurringPaymentVerify($input);
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest(Verify $verify): array
    {
        $input = $verify->input;

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body);

        $this->mapMigratedFields($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'raw_content' => $response->body,
                'content'     => $content,
                'gateway'     => 'upi_icici',
                'payment_id'  => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function sendRefundVerifyRequest(array $input)
    {
        $request = $this->getRefundVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'raw_content' => $response->body,
                'content'     => $content,
                'gateway'     => 'upi_icici',
                'refund_id'   => $input['refund']['id'],
            ]);

        return $content;
    }

    protected function getPaymentVerifyRequestArray(array $input)
    {
        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $data = [
            'merchantId'        => $this->getMerchantId(),
            'merchantTranId'    => $gatewayPayment['merchant_reference'] ?: $input['payment']['id'],
            'subMerchantId'     => $this->getSubMerchantId($input),
            'terminalId'        => '1234',
        ];

        $request = $this->getRequest($data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
                'razorx'            => $this->razorxTrace,
            ]);

        return $request;
    }

    protected function getRefundVerifyRequestArray(array $input)
    {
        //
        // Appending (attempt count - 1)  to refund id for verifying previous refund if that was successful.
        // For scrooge refunds, attempts are sent from scrooge which signifies the attempts which have been done on this.
        // As attempts in scrooge starts with 0, For eg. if attempts = 5,
        // that means we will be requesting refund R5 and we need to verify for R4.
        //
        $attempts = $input['refund']['attempts'] - 1;

        //
        // If this is 0th or 1st attempt, verify refund should be called for first refund (exact Refund Id)
        // Appending empty string to refund if we want to verify refund with 14 digit refund id.
        //
        if (((int) $attempts === 0) or ((int) $input['refund']['attempts'] === 0))
        {
            $attempts = '';
        }

        $data = [
            'merchantId'        => $this->getMerchantId(),
            'merchantTranId'    => $input['refund']['id'] . $attempts,
            'subMerchantId'     => $this->getSubMerchantId($input),
            'terminalId'        => '1234',
        ];

        $request = $this->getRequest($data);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
                'razorx'            => $this->razorxTrace,
            ]);

        return $request;
    }

    protected function getRequest(array $data): array
    {
        $content = $this->transformRequestArrayToContent($data);

        return $this->getStandardRequestArray($content);
    }

    protected function checkResponseAndThrowExceptionIfRequired(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        // 5006 = The payment was not created at the gateway end
        // 5000 = Invalid Request
        // 15   = Original record not found
        //        And we can safely mark this payment as failed
        if (in_array($content[Fields::RESPONSE], ['5006', '5000', '15'], true) === true)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                VerifyAction::FINISH);
        }
    }

    protected function verifyPayment(Verify $verify): string
    {
        // Removing this for now because verify becomes successful after
        // some delay on 5000 response
        //$this->checkResponseAndThrowExceptionIfRequired($verify);

        $content = $verify->verifyResponseContent;

        if (($content['success'] !== 'true') and
            ($content[Fields::RESPONSE] !== '5006'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                $content['success'],
                $content['message']);
        }

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if ($content['status'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        //
        // If gatewaySuccess is false
        // we don't need to check for amount
        // also in case the gateway says merchant trans id
        // not availble it doesn't give us amount
        //
        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

            $actualAmount  = number_format($content[Fields::VERIFY_AMOUNT], 2, '.', '');

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

        // Ensuring RNN is set if exists in $content
        if (isset($content[Fields::ORIGINAL_BANK_RRN]) === true)
        {
            $content[Entity::NPCI_REFERENCE_ID] = $content[Fields::ORIGINAL_BANK_RRN];
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->verifyResponseContent = $content;

        $this->updateGatewayPaymentResponse($verify->payment, $verify->verifyResponseContent);

        return $status;
    }

    protected function getAmountMismatchExceptionInVerify(Verify $verify)
    {
        // If gateway success is not false we can throw the generic runtime exception
        if ($verify->gatewaySuccess === false)
        {
            return parent::getAmountMismatchExceptionInVerify($verify);
        }

        $amountAuthorized = $this->getIntegerFormattedAmount($verify->verifyResponseContent[Fields::VERIFY_AMOUNT]);

        $verify->setCurrencyAndAmountAuthorized('INR', $amountAuthorized);

        return new Exception\PaymentVerificationException(
            $verify->getDataToTrace(),
            $verify);
    }

    /**
     * We need to implement alreadyRefunded
     * @see https://github.com/razorpay/api/issues/6984
     */

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new GatewayBase\ScroogeResponse();

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                   ->toArray();
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        $content = $this->sendRefundVerifyRequest($input);

        $scroogeResponse->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if (($content[Fields::STATUS] === Status::SUCCESS))
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        //
        // Checking for 8010 code specifically here as this is Internal Service Failure, its not a refund failure.
        // throwing exception so that, verify will be called in such case.
        //
        if (((int) $content[Fields::RESPONSE] === 8010) or
            ($content[Fields::MESSAGE] === 'INTERNAL_SERVICE_FAILURE-The system had an internal exception'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $content[Fields::STATUS],
                $content[Fields::MESSAGE],
                [
                    Payment\Gateway::GATEWAY_VERIFY_RESPONSE    => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS               =>
                        [
                            'gateway_status' => $content[Fields::STATUS],
                            'refund_id'      => $input['refund']['id'],
                        ],
                ]);
        }

        if (($content[Fields::STATUS] === Status::FAILURE) or
            ($content[Fields::STATUS] === Status::FAIL))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED)
                                   ->toArray();
        }

        $this->checkVerifyRefundStatus($input, $content);

        $msg = strtolower($content['message']);

        if (in_array($msg, [Status::NO_RECORDS, Status::NO_RECORDS2], true) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }

        throw new Exception\LogicException(
                'Shouldn\'t reach here',
                ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
                [
                    Payment\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS             =>
                        [
                            'gateway_status' => $content[Fields::STATUS],
                            'refund_id'      => $input['refund']['id'],
                        ],
                ]);
    }

    protected function checkVerifyRefundStatus(array $input, array $content)
    {
        $responseKey = ($this->action === Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE : Payment\Gateway::GATEWAY_RESPONSE;

        if (($content[Fields::STATUS] === Status::DEEMED))
        {
            throw new Exception\LogicException(
                PublicErrorDescription::GATEWAY_ERROR_REFUND_DEEMED,
                ErrorCode::GATEWAY_ERROR_REFUND_DEEMED,
                [
                    $responseKey                       => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS      =>
                        [
                            'gateway_status' => $content[Fields::STATUS],
                            'refund_id'      => $input['refund']['id'],
                        ],
                ]);
        }

        if (($content[Fields::STATUS] === Status::PENDING))
        {
            throw new Exception\LogicException(
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_PENDING,
                ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                [
                    $responseKey                       => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS      =>
                        [
                            'gateway_status' => $content[Fields::STATUS],
                            'refund_id'      => $input['refund']['id'],
                        ],
                ]);
        }
    }

    /**
     * subMerchantId is limited to 10 characters
     * so we send the first 10 characters
     * @return string
     */
    protected function getSubMerchantId(array $input): string
    {
        // ICICI docs say that they accept alphanumeric
        // merchant IDs, but they do not. The field is
        // also marked as optional, but it is not.
        return '1234';

        // return substr($input['merchant']['id'], 0, 10);
    }

    /**
     * Returns Payment Id
     * @param  string $body Request Body
     * @return string Payment Id
     */
    public function getPaymentIdFromServerCallback(array $response): string
    {
        $details = $this->getRecurringDetailsFromServerCallback($response);

        if (empty($details[Entity::PAYMENT_ID]) === false)
        {
            return $details[Entity::PAYMENT_ID];
        }

        return $this->getActualPaymentIdFromServerCallback($response);
    }

    protected function getActualPaymentIdFromServerCallback(array $response)
    {
        return $response[Fields::MERCHANT_TRAN_ID];
    }

    /**
     * Takes in S2S request as a body string
     * and returns the parsed response as an array
     *
     * @param String $body Request body
     *
     * @param bool   $isBharatQr
     *
     * @param bool   $isUpiTransfer
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function preProcessServerCallback($body, $isBharatQr = false, bool $isUpiTransfer = false): array
    {
        // This is a temporary check added to handle recurring callbacks. For normal payments, we get a normal string
        // in callback, whereas for recurring we get json. Now, there will be encryption for recurring, so we need
        // to check the callback.We also have to be backward compatible if encryption is enabled stepwise.
        // We are checking if we are getting json response and UMN field (which we get only for
        // recurring). If yes, then we process recurring callback, otherwise normal.

        $decoded = json_decode($body, true);

        if (($decoded !== null) and (isset($decoded[Fields::UMN]) === true))
        {
            $response = $this->parseGatewayResponse($body, false, $isUpiTransfer);
        }
        else
        {
            $response = $this->parseGatewayResponse($body, true, $isUpiTransfer);
        }

        // if we are getting a UMN field (which we get only for recurring), we will process for mandatecallbacks.

        if (isset($response[Fields::UMN]) === true)
        {
            $mandateResponse = $this->getMandateCallbackResponseIfApplicable($response);

            if (empty($mandateResponse) === false)
            {
                return $mandateResponse;
            }
        }

        $traceResponse = $this->maskUpiDataForTracing($response, [
            Entity::VPA             => Fields::PAYER_VA,
        ]);

        if ($decoded != null)
        {
            $maskedDecoded = $this->maskUpiDataForTracing($decoded, [
                Entity::VPA => Fields::PAYER_VA,
            ]);

            $maskedBody = json_encode($maskedDecoded);
        }

        $traceHeaders = $this->app['request']->header();
        unset($traceHeaders['authorization'], $traceHeaders['x-passport-jwt-v1']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'body'      => ($decoded != null) ? $maskedBody : $body,
                'headers'   => $traceHeaders,
                'gateway'   => $this->gateway,
                'data'      => $traceResponse
            ]);

        if ($isBharatQr === true)
        {
            $response = $this->getQrData($response);
        }

        return $response;
    }

    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null)
        {
            return ['success' => true];
        }

        return [
            'success' => false,
        ];
    }

    public function getParsedDataFromUnexpectedCallback(array $input)
    {
        $payment = [
            Payment\Entity::METHOD      => Payment\Method::UPI,
            Payment\Entity::AMOUNT      => (int) ($input[Fields::PAYER_AMOUNT] * 100),
            Payment\Entity::VPA         => $input[Fields::PAYER_VA],
            Payment\Entity::CURRENCY    => 'INR',
            Payment\Entity::CONTACT     => '+919999999999',
            Payment\Entity::EMAIL       => 'void@razorpay.com',
        ];

        $terminal = [
            Terminal\Entity::GATEWAY                => $this->gateway,
            Terminal\Entity::GATEWAY_MERCHANT_ID    => $input[Fields::MERCHANT_ID],
        ];

        return [
            'payment'   => $payment,
            'terminal'  => $terminal,
        ];
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        $gatewayInput = [
            'payment' => [
                'id'     => $paymentId,
                'vpa'    => $callbackData[Fields::PAYER_VA],
                'amount' => (int) ($callbackData[Fields::PAYER_AMOUNT] * 100),
            ],
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::MERCHANT_REFERENCE  => $callbackData[Fields::MERCHANT_TRAN_ID],
            Entity::GATEWAY_MERCHANT_ID => $callbackData[Fields::MERCHANT_ID],
            Entity::NPCI_REFERENCE_ID   => $callbackData[Fields::BANK_RRN],
            Entity::GATEWAY_PAYMENT_ID  => $callbackData[Fields::BANK_RRN],
            Entity::STATUS_CODE         => $callbackData[Fields::TXN_STATUS],
            Entity::VPA                 => $callbackData[Fields::PAYER_VA],
            Entity::RECEIVED            => 1,
            Entity::GATEWAY_DATA        => [],
        ];

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes, null, false);

        $this->checkCallbackResponseStatus($callbackData);

        return [
            'acquirer' => [
                Payment\Entity::VPA         => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    protected function isMandatePauseCallback($input)
    {
        if ((isset($input[Fields::TXN_STATUS]) === true) and ($input[Fields::TXN_STATUS] === Status::PAUSE_SUCCESS))
        {
            return true;
        }

        return false;
    }

    protected function isMandateResumeCallback($input)
    {
        if ((isset($input[Fields::TXN_STATUS]) === true) and ($input[Fields::TXN_STATUS] === Status::RESUME_SUCCESS))
        {
            return true;
        }

        return false;
    }

    protected function isMandateRevokeCallback($input)
    {
        if ((isset($input[Fields::TXN_STATUS]) === true) and ($input[Fields::TXN_STATUS] === Status::REVOKE_SUCCESS))
        {
            return true;
        }

        return false;
    }

    protected function getQrData(array $input)
    {
        $this->checkForBharatQrPaymentFailure($input);

        $amount = $this->getIntegerFormattedAmount($input[Fields::PAYER_AMOUNT]);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $amount,
            BharatQr\GatewayResponseParams::VPA                   => $input[Fields::PAYER_VA],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::UPI,
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[Fields::MERCHANT_ID],
            BharatQr\GatewayResponseParams::MERCHANT_REFERENCE    => substr($input[Fields::MERCHANT_TRAN_ID], 0, Entity::ID_LENGTH),
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => (string) $input[Fields::BANK_RRN],
        ];

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    protected function checkForBharatQrPaymentFailure($input)
    {
        if ($input[Fields::TXN_STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_BQR_PAYMENT_FAILED,
                 null,
                 null,
                  [
                      'notification_request' => $input,
                      'gateway'              => $this->gateway
                  ]);
        }
    }

    public function getBharatQrResponse(bool $valid, $gatewayInput = null, $exception = null)
    {
        // sending OK for time being, till it will be confirmed on how this acknowledgement
        // is treated at Upi Icici
        $xml = '<RESPONSE>OK</RESPONSE>';

        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
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

        if ($input['payment']['recurring'] === true)
        {
            return $this->processRecurringCallback($input);
        }

        $content = $input['gateway'];

        $actualPaymentId = $content[Fields::MERCHANT_TRAN_ID];

        if ($this->isSecondRecurringPayment($input) === true)
        {
            $actualPaymentId = substr($actualPaymentId, 0, 14);
        }

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // Since there is no Auth in this flow (just public key encryption)
        // and we are not revealing Bank RRN, this gives us a bit of
        // extra security for fake callbacks

        $responseReceived = empty($gatewayPayment[Entity::RECEIVED]) === false;

        $this->trace->info(TraceCode::MISC_TRACE_CODE, [
            'message'           => $responseReceived ? 'Making assertions' : 'Skipping assertions',
            'gateway'           => $this->gateway,
            'payment_id'        => $input['payment']['id'],
            'response_received' => $responseReceived
        ]);

        if ($responseReceived === true)
        {
           assertTrue($content[Fields::MERCHANT_ID] === $gatewayPayment->getMerchantId());
           assertTrue($actualPaymentId === $gatewayPayment->getPaymentId());
        }

//        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
//        $actualAmount   = number_format($content[Fields::PAYER_AMOUNT], 2, '.', '');
//
//        $this->assertAmount($expectedAmount, $actualAmount);

        // We are saving the gateway entity even if txn was failed
        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        // Process and Map fields for throwing Exception
        $this->processGatewayCallbackContentFields($content);

        $this->checkCallbackResponseStatus($content);

        $amountAuthorized = $this->getIntegerFormattedAmount($content[Fields::PAYER_AMOUNT]);

        $response  = [
            'acquirer' => [
                Payment\Entity::VPA => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ],
            'currency'          => 'INR',
            'amount_authorized' => $amountAuthorized,
        ];

        if ($this->isSecondRecurringPayment($input) === true)
        {
            $response = array_merge($response, $this->getResponseForAutoRecurring($input, null, $gatewayPayment));
        }

        return $response;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $refund = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getRefundRequest($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->parseGatewayResponse($response->body);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [
            'gateway'    => $this->gateway,
            'payment_id' => $input['payment']['id'],
            'response'   => $content
        ]);

        $this->updateGatewayPaymentResponse($refund, $content);

        if ($content[Fields::STATUS] !== Status::SUCCESS)
        {
            $code = $content[Fields::RESPONSE];

            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $content[Fields::STATUS],
                ResponseCode::getResponseMessage($code),
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($content)
                ]
            );
        }

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($content),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($content)
        ];
    }

    protected function getExternalMockUrl(string $type)
    {
        return  env('EXTERNAL_MOCK_GO_GATEWAY_DOMAIN') . $this->getRelativeUrl($type);
    }

    protected function getRefundRequest(array $input)
    {
        $payment = $input['payment'];

        $refund = $input['refund'];

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($payment['id'], Action::AUTHORIZE);

        $data = [
            Fields::MERCHANT_ID                     => $this->getMerchantId(),
            Fields::SUBMERCHANT_ID                  => $this->getSubMerchantId($input),
            Fields::TERMINAL_ID                     => $this->getTerminalId($input),
            Fields::ORIGINAL_BANK_RRN_REQ           => $gatewayPayment->getGatewayPaymentId(),
            Fields::MERCHANT_TRAN_ID                => $this->getRefundId($refund),
            Fields::ORIGINAL_MERCHANT_TRAN_ID       => $gatewayPayment['merchant_reference'] ?? $payment['id'],
            Fields::REFUND_AMOUNT                   => $this->formatAmount($refund['amount']),
            Fields::NOTE                            => 'Razorpay Refund ' . $refund['id'],
            Fields::ONLINE_REFUND                   => $this->isOnlineRefund($refund),
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request'           => $request,
                'decrypted_content' => $data,
                'gateway'           => $this->gateway,
                'razorx'            => $this->razorxTrace,
            ]);

        return $request;
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                Fields::ORIGINAL_BANK_RRN_REQ => $refundFields[Fields::ORIGINAL_BANK_RRN_REQ] ?? null,
                Fields::STATUS                => $refundFields[Fields::STATUS] ?? null,
                Fields::RESPONSE              => $refundFields[Fields::RESPONSE] ?? null,
                Fields::SUCCESS               => $refundFields[Fields::SUCCESS] ?? null,
                Fields::MESSAGE               => $refundFields[Fields::MESSAGE] ?? null,
                Fields::ORIGINAL_BANK_RRN     => $refundFields[Fields::ORIGINAL_BANK_RRN] ?? null,
            ];
        }
        return [];
    }

    /**
     * if refund attempt fails for 2 times,
     * refund is retried with offline mode in the later attempts
     *
     * @return string
     */
    protected function isOnlineRefund(array $refund)
    {
        if ($refund['attempts'] < 3)
        {
            return 'Y';
        }

        return 'N';
    }

    /**
     * This is done in order to fix duplicate merchant transaction id issue in case refund is retried multiple times
     *
     * UPI gateways do not process refund which has been failed, they process new refund everytime. And hence,
     * we send the refund id appended with attempts to generate new refund id.
     *
     * @param array $refund
     * @return string
     */
    protected function getRefundId(array $refund)
    {
        return $refund['id'] . ($refund['attempts'] ?: '');
    }

    public function generateRefunds($input)
    {
        $paymentIds = array_map(function($row)
        {
            return $row['payment']['id'];
        }, $input['data']);

        $payments = $this->repo->fetchByPaymentIdsAndAction(
            $paymentIds, Action::AUTHORIZE);

        $refunds = $this->repo->fetchByPaymentIdsAndAction(
            $paymentIds, Action::REFUND);

        $payments = $payments->getDictionaryByAttribute(Entity::PAYMENT_ID);

        $refunds = $refunds->getDictionaryByAttribute(Entity::REFUND_ID);

        $input['data'] = array_map(function($row) use ($payments, $refunds)
        {
            $paymentId = $row['payment']['id'];
            $refundId = $row['refund']['id'];

            if ((isset($refunds[$refundId]) === false) and
                (isset($payments[$paymentId]) === true))
            {
                $row['gateway'] = $payments[$paymentId]->toArray();
            }

            return $row;
        }, $input['data']);

        $ns = $this->getGatewayNamespace();

        $class = $ns . '\\' . 'RefundFile';

        return (new $class)->generate($input);
    }

    public function forceAuthorizeFailed(array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'],
            Action::AUTHORIZE);

        /**
         * We do not update the upi status code on callback, thus we are going to
         * use success as status code to make sure we do not force auth already auth txns.
         */
        if (($gatewayPayment[Entity::STATUS_CODE] === Status::SUCCESS) and
            ($gatewayPayment[Entity::RECEIVED]) === true)
        {
            return true;
        }

        $attr = array_only($input['gateway'], $this->forceFillable);

        $attr[Entity::STATUS_CODE] = Status::SUCCESS;

        $gatewayPayment->fill($attr);

        $gatewayPayment->generatePspData($attr);

        $gatewayPayment->saveOrFail();

        return true;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        // For certain actions type is different from gateway action
        $type = $type ?? $this->action;

        $version = $this->getVersionForAction($this->input, $type);

        if ($version === self::GATEWAY_API_VERSION_2)
        {
            $this->domainType =  $this->getMode() . '_v2';

            $type = $type . '_v2';
        }

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers']['Content-Type'] = 'text/plain';

        return $request;
    }

    protected function getVersionForAction($input, $action)
    {
        $accessCode = trim($input['terminal']['gateway_access_code'] ?? null);

        $this->razorxTrace = [
            'access_code' => $accessCode,
        ];

        // If the access code is set to v1
        if ($accessCode === self::GATEWAY_API_VERSION_1)
        {
            return self::GATEWAY_API_VERSION_1;
        }
        // Else if the access code if empty or v2
        else if((empty($accessCode) === true) or ($accessCode === self::GATEWAY_API_VERSION_2))
        {
            return self::GATEWAY_API_VERSION_2;
        }

        // Else go to razorx variant
        $variant = $this->getRazorXVariantForMode();

        // If enabled is set to 0 or doesn't exists we fallback to v1
        if (empty($variant['enabled']) === true)
        {
            return self::GATEWAY_API_VERSION_1;
        }

        // If enabled but action is specifically added, we give preference to type
        if (isset($variant[$action]) === true)
        {
            return $variant[$action];
        }

        return self::GATEWAY_API_VERSION_2;
    }

    protected function getRazorXVariantForMode()
    {
        $this->razorxTrace = [
            'env'           => $this->app['env'],
            'id'            => $this->request->getTaskId(),
            'feature'       => self::GATEWAY_API_RAZORX_PREFIX,
            'mode'          => $this->mode,
            'variant'       => null,
            'variant_array' => null,
            'exception'     => [
                'code'      => null,
                'message'   => null,
            ],
        ];

        try
        {
            $variant = $this->app->razorx->getTreatment(
                           $this->request->getTaskId(),
                           self::GATEWAY_API_RAZORX_PREFIX,
                           $this->getMode());

            parse_str($variant, $variantArray);

            $this->razorxTrace['variant']       = $variant;
            $this->razorxTrace['variant_array'] = $variantArray;

            // Exception will be traced
            assertTrue(isset($variantArray['enabled']), 'Enabled field is mandatory in variant array');

            $variantArray['enabled'] = (bool) $variantArray['enabled'];

            return $variantArray;
        }
        catch (\Throwable $exception)
        {
            $this->razorxTrace['exception'] = [
                'code'      => $exception->getCode(),
                'message'   => $exception->getMessage(),
            ];

            return [
                'enabled' => 0,
            ];
        }
    }

    public function getUpiTransferData(array $input)
    {
        $this->checkCallbackResponseStatus($input);

        $amount = $this->getIntegerFormattedAmount($input[Fields::PAYER_AMOUNT]);

        // icici will send merchant_tran_id in format vpatr e.g. payto000011112223333ref123
        // vpa of length 20 will be extracted, remaining part will be tr
        $vpa = substr($input[Fields::MERCHANT_TRAN_ID], 0, self::VPA_LENGTH);

        $transactionReference = substr($input[Fields::MERCHANT_TRAN_ID], self::VPA_LENGTH);

        $transactionReference = empty($transactionReference) ? null : $transactionReference;

        $upiTransferData = [
            UpiTransfer\GatewayResponseParams::AMOUNT                => $amount,
            UpiTransfer\GatewayResponseParams::GATEWAY               => $this->gateway,
            UpiTransfer\GatewayResponseParams::PAYER_VPA             => $input[Fields::PAYER_VA],
            UpiTransfer\GatewayResponseParams::PAYEE_VPA             => $this->terminal->getVirtualUpiRoot() . $vpa . '@' . $this->terminal->getVirtualUpiHandle(),
            UpiTransfer\GatewayResponseParams::TRANSACTION_TIME      => $input[Fields::TXN_COMPLETION_DATE],
            UpiTransfer\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[Fields::MERCHANT_ID],
            UpiTransfer\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[Fields::BANK_RRN],
            UpiTransfer\GatewayResponseParams::NPCI_REFERENCE_ID     => $input[Fields::BANK_RRN],
            UpiTransfer\GatewayResponseParams::PAYER_IFSC            => '',
            UpiTransfer\GatewayResponseParams::TRANSACTION_REFERENCE => $transactionReference,
        ];

        return [
            'callback_data'     => $input,
            'upi_transfer_data' => $upiTransferData
        ];
    }

    protected function checkCallbackResponseStatus($response, $successStatus = Status::SUCCESS)
    {
        if ($response[Fields::TXN_STATUS] !== $successStatus)
        {
            $errorMessage = ResponseCode::getResponseMessage($response[Fields::RESPONSE_CODE]);

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($response[Fields::RESPONSE_CODE]),
                $response[Fields::RESPONSE_CODE],
                $errorMessage);
        }
    }

    public function validateVpa(array $input)
    {
        parent::action($input, Action::VALIDATE_VPA);

        $request = [
            'terminal' =>  $this->terminal,
            'payment' => [
                //payment_id is not used, but have to send anyway else the flow breaks @sendMozartRequest
                'id' => 'NA',
                'vpa' => $input['vpa'],
            ],
        ];

        $traceData = $this->maskUpiDataForTracing($request['payment'], [
            Entity::VPA             => Entity::VPA,
        ]);

        $this->trace->info('GATEWAY_VALIDATE_VPA_REQUEST',
            [
                'encrypted'  => false,
                'vpa'        => $traceData,
                'gateway'    => $this->gateway,
            ]);

        $response = $this->sendMozartRequest($request);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $data = $response['data'];

        $traceData = $this->maskUpiDataForTracing($data, [
            Entity::VPA             => 'MobileAppData',
            Entity::VPA             => 'customer_name',
        ]);

        $this->trace->info(TraceCode::GATEWAY_VALIDATE_VPA_RESPONSE,
            [
                'encrypted'  => false,
                'response'   => $traceData,
                'gateway'    => $this->gateway,
            ]);

        $this->checkVpaResponseStatus($data['success']);

        return $this->returnValidateVpaResponse($data);
    }

    /**
     * @param string $status
     * @throws GatewayErrorException
     */
    private function checkVpaResponseStatus(string $status)
    {
        if ($status === false)
        {
            $errorCode = $status['error'];

            $errorMessage = $status['errorDesc'];

            throw new GatewayErrorException($errorCode, $status, $errorMessage);
        }
    }

    private function mapMigratedFields(& $content)
    {
        if ((isset($content[Fields::AMOUNT_NEW]) === true))
        {
            $content[Fields::AMOUNT] = $content[Fields::AMOUNT_NEW];
            unset($content[Fields::AMOUNT_NEW]);
        }
    }

    protected function returnValidateVpaResponse($data)
    {
        if ( isset($data['customer_name']))
        {
            return $data['customer_name'];
        }
        if (isset($data['MobileAppData']) === true and $data['MobileAppData'] != null)
        {
            $vpa = explode( '=', $data['MobileAppData']);
            return $vpa[1];
        }
    }

    /***
     * Process Gateway Callback Content Fields
     *
     * @param $content
     */
    private function processGatewayCallbackContentFields(& $content)
    {
        // For few merchants ICICI has started sending ResponseCode and we need to utilize that
        // If ResponseCode is already set then no need to store anything in ResponsceCode field
        // as it's already mapped to `status_code` of UPI Entity in DB
        if (isset($content[Fields::RESPONSE_CODE]) === true)
        {
            return;
        }

        // In some cases there will be a `response` field so store this to ResponceCode
        // because we are displaying error code from ResponseCode only
        // If Response Code is not there then store TxnStatus to ResponseCode
        // Since we will be using ResponseCode to throw Exception for all the cases
        if (isset($content[Fields::RESPONSE]) === true)
        {
            $content[Fields::RESPONSE_CODE] = $content[Fields::RESPONSE];
        }
        else
        {
            $content[Fields::RESPONSE_CODE] = $content[Fields::TXN_STATUS];
        }
    }
}
