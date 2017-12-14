<?php

namespace RZP\Gateway\Upi\Icici;

use Request;
use Carbon\Carbon;
use RZP\Exception;
use ErrorException;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\RSA;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Gateway\Upi\Icici\ResponseCodeMap;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 120;

    protected $gateway = 'upi_icici';

    const ACQUIRER = 'icici';

    /**
     * This is what shows up as the payee
     * on the notification to the customer
     */
    const DEFAULT_PAYEE_VPA = 'razorpay@icici';

    protected $map = [
        Entity::VPA                       => Entity::VPA,
        Entity::EXPIRY_TIME               => Entity::EXPIRY_TIME,
        Entity::PROVIDER                  => Entity::PROVIDER,
        Entity::BANK                      => Entity::BANK,
        Entity::RECEIVED                  => Entity::RECEIVED,
        Fields::PAYER_VA                  => Entity::VPA,
        Fields::PAYER_NAME                => Entity::NAME,
        Fields::PAYER_MOBILE              => Entity::CONTACT,
        Fields::RESPONSE                  => Entity::STATUS_CODE,
        Fields::BANK_RRN                  => Entity::GATEWAY_PAYMENT_ID,
        Fields::ORIGINAL_BANK_RRN         => Entity::GATEWAY_PAYMENT_ID,
        Fields::MERCHANT_ID               => Entity::GATEWAY_MERCHANT_ID,
    ];

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return boolean
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            return $this->authorizeIntent($input);
        }

        $attributes = $this->getGatewayEntityAttributes($input);

        $attributes[Entity::EXPIRY_TIME] = $input['upi']['expiry_time'];

        $payment = $this->createGatewayPaymentEntity($attributes);

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

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
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
            Entity::TYPE => Base\Type::COLLECT,
        ];

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request =  $this->getPayAuthorizeRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $status = (int) $response['response'];

        if ($status !== Status::TXN_SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($status);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $status,
                ResponseCode::getResponseMessage($status));
        }

        return $this->getIntentRequest($input, $response);
    }

    protected function getIntentRequest($input, $response)
    {
        $content = [
            IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2() ?? self::DEFAULT_PAYEE_VPA,
            IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            IntentParams::TXN_REF_ID    => $response['refId'],
            IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            IntentParams::TXN_CURRENCY  => 'INR',
            IntentParams::MCC           => '5411',
        ];

        $query = str_replace(' ', '', urldecode(http_build_query($content)));

        return ['data' => ['intent_url' => 'upi://pay?' . $query]];
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
        ];
    }

    /**
     * @param  string $response
     * @return array response as associative array
     */
    protected function parseGatewayResponse(string $response, bool $forceDecryption = false): array
    {
        if ($forceDecryption === false)
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
            $response = $this->decrypt($response);
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

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId(): string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->config['live_merchant_id'];
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
     * @see getPublicKey
     * @return string Private Key
     */
    protected function getPrivateKey(): string
    {
        $key = $this->config['live_private_key'];

        if ($this->mode === Mode::TEST)
        {
            $key = $this->config['test_private_key'];
        }

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

        return sprintf($url, $this->getMerchantId());
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
     * @param  string $data
     * @return string
     */
    protected function decrypt(string $data): string
    {
        $rsa = $this->getCipherInstance();

        $key = $this->getPrivateKey();

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
            Fields::SUBMERCHANT_NAME => preg_replace('/\s+/', '', $input['merchant']->getFilteredDba()),
            Fields::PAYER_VA_REQ     => $input['payment']['vpa'],
            Fields::SUBMERCHANT_ID   => $this->getSubMerchantId($input),
            Fields::TERMINAL_ID      => $this->getTerminalId($input),
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'decrypted_content' => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
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

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content, 'post', 'pay');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'           => $request,
                'decrypted_content' => $data,
                'gateway'           => $this->gateway,
                'payment_id'        => $input['payment']['id'],
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

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $payment->fill($attr);

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

        $request = $this->getPaymentVerifyRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->parseGatewayResponse($response->body);

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
        $data = [
            'merchantId'        => $this->getMerchantId(),
            'merchantTranId'    => $input['payment']['id'],
            'subMerchantId'     => $this->getSubMerchantId($input),
            'terminalId'        => '1234',
        ];

        $request = $this->getRequest($data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data
            ]);

        return $request;
    }

    protected function getRefundVerifyRequestArray(array $input)
    {
        $attempts = $input['refund']['attempts'] - 1;

        if ($input['refund']['attempts'] === 1)
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
                'decrypted_content' => $data
            ]);

        return $request;
    }

    protected function getRequest(array $data): array
    {
        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        return $request;
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
        $this->checkResponseAndThrowExceptionIfRequired($verify);

        $content = $verify->verifyResponseContent;

        if ($content['success'] !== 'true')
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

        $verify->verifyResponseContent = $this->getMappedAttributes($content);

        return $status;
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

        $content = $this->sendRefundVerifyRequest($input);

        if ($content['status'] === Status::SUCCESS)
        {
            return true;
        }

        if ($content['status'] === Status::FAILURE)
        {
            return false;
        }

        $msg = strtolower($content['message']);

        if (in_array($msg, [Status::NO_RECORDS, Status::NO_RECORDS2], true) === true)
        {
            return false;
        }

        throw new Exception\LogicException(
                'Shouldn\'t reach here',
                null,
                [
                    'gateway_status' => $content['status'],
                    'refund_id'      => $input['refund']['id'],
                ]);
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
        return $response[Fields::MERCHANT_TRAN_ID];
    }

    /**
     * Takes in S2S request as a body string
     * and returns the parsed response as an array
     * @param  String $body Request body
     * @return array
     */
    public function preProcessServerCallback($body): array
    {
        $response = $this->parseGatewayResponse($body, true);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'body'      => $body,
                'headers'   => $this->app['request']->header(),
                'gateway'   => $this->gateway,
                'data'      => $response
            ]);

        return $response;
    }

    /**
     * Handles the S2S callback
     * @param  array $input
     * @return null
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $status = $content[Fields::TXN_STATUS];

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // Since there is no Auth in this flow (just public key encryption)
        // and we are not revealing Bank RRN, this gives us a bit of
        // extra security for fake callbacks

        assertTrue($content[Fields::MERCHANT_ID] === $gatewayPayment->getMerchantId());
        assertTrue($content[Fields::MERCHANT_TRAN_ID] === $gatewayPayment->getPaymentId());
        assertTrue($content[Fields::BANK_RRN] === $gatewayPayment->getGatewayPaymentId());

        if ($status !== Status::SUCCESS)
        {
            $message = "Payment Failed during callback";

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $status,
                $message);
        }

        // Authorization was successful
        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        return [
            'acquirer' => [
                'vpa' => $gatewayPayment->getVpa()
            ]
        ];
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
                ResponseCode::getResponseMessage($code));
        }
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
            Fields::ORIGINAL_MERCHANT_TRAN_ID       => $payment['id'],
            Fields::REFUND_AMOUNT                   => $this->formatAmount($refund['amount']),
            Fields::NOTE                            => 'Razorpay Refund ' . $refund['id'],
            Fields::ONLINE_REFUND                   => 'Y',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
                'gateway' => $this->gateway,
            ]);

        return $request;
    }

    /**
     * This is done in order to fix duplicate
     * merchant transaction id issue in case
     * refund is retried multiple times
     *
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
}
