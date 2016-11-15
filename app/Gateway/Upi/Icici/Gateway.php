<?php

namespace RZP\Gateway\Upi\Icici;

use Carbon\Carbon;
use ErrorException;
use phpseclib\Crypt\RSA;
use Request;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Gateway\Utility;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'upi_icici';

    const BANK = 'icici';

    protected $map = array(
        Entity::VPA                       => Entity::VPA,
        Entity::PROVIDER                  => Entity::PROVIDER,
        Entity::RECEIVED                  => Entity::RECEIVED,
        ResponseFields::PAYER_VA          => Entity::VPA,
        ResponseFields::PAYER_NAME        => Entity::NAME,
        ResponseFields::PAYER_MOBILE      => Entity::CONTACT,
        ResponseFields::RESPONSE          => Entity::STATUS_CODE,
        ResponseFields::BANK_RRN          => Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::ORIGINAL_BANK_RRN => Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::MERCHANT_ID       => Entity::GATEWAY_MERCHANT_ID,
    );

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return null
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getGatewayEntityAttributes($input);

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

        return true;
    }

    /**
     * We only store the VPA and provider because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input)
    {
        $vpa = $input['payment']['vpa'];

        $vpaParts = explode('@', $vpa);

        $pspCode = ProviderCode::getBankCode($vpaParts[1]);

        return [
            Entity::VPA         => $vpa,
            Entity::PROVIDER    => $pspCode
        ];
    }

    /**
     * @param  string $response
     * @return array response as associative array
     */
    protected function parseGatewayResponse($response, $forceDecryption = false)
    {
        if ($forceDecryption === false)
        {
            $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [
                'body'      =>  $response,
                'encrypted' =>  true,
                'gateway'   =>  $this->gateway
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
    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2, '.', '');
    }

    /**
     * The Merchant ID doesn't change for different
     * merchants since this is the master merchant Id
     * @return string (numeric merchant id)
     */
    protected function getMerchantId()
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
    protected function getPublicKey()
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
    protected function getPrivateKey()
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
    protected function getUrl($type = null)
    {
        if ($type === null)
        {
            $type = $this->action;
        }

        $type = "{$this->mode}_{$type}";

        return parent::getUrl($type);
    }

    /**
     * Encrypts data before sending it to ICICI
     * @param  string $data
     * @return string
     */
    protected function encrypt($data)
    {
        $rsa = $this->getRSAInstance();

        $rsa->loadKey($this->getPublicKey());

        return $rsa->encrypt($data);
    }

    /**
     * Decrypts responses from the ICICI API
     * @param  string $data
     * @return string
     */
    protected function decrypt($data)
    {
        $rsa = $this->getRSAInstance();

        $key = $this->getPrivateKey();

        $rsa->loadKey($key, RSA::PRIVATE_FORMAT_PKCS1);

        return $rsa->decrypt($data);
    }

    protected function getRSAInstance()
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

    protected function getAuthorizeRequestArray($input)
    {
        $payment = $input['payment'];

        $collectByTimestamp = Carbon::now('Asia/Kolkata')->addMinutes(5)->format('d/m/Y h:i A');

        $data = [
            // Amount and note are lowercase
            // despite being uppercase in docs
            'amount'            => $this->formatAmount($payment['amount']),
            'collectByDate'     => $collectByTimestamp,
            'billNumber'        => '1234',
            'merchantId'        => $this->getMerchantId(),
            'merchantTranId'    => $payment['id'],
            'merchantName'      => 'Razorpay',
            'note'              => $this->getPaymentRemark($input),
            'payerVa'           => $input['payment']['vpa'],
            'subMerchantId'     => $this->getSubMerchantId($input),
            'subMerchantName'   => $input['merchant']->getBillingLabelElseName(),
            'terminalId'        => '1234',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data,
                'gateway' => 'upi_icici',
                'payment_id' => $input['payment']['id'],
            ]);

        return $request;
    }

    /**
     * This is same as the payment description, capped
     * to 50 characters
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $description = $input['merchant']->getBillingLabelElseName();

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
    }

    /**
     * Formats a request content array to a proper string
     * that is sent to the server in POST body
     * @param  array  $data request array
     * @return string post body
     */
    protected function transformRequestArrayToContent(array $data)
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

    protected function sendPaymentVerifyRequest($verify)
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
                'content' => $content,
                'gateway' => 'upi_icici',
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestArray($input)
    {
        $data = [
            'merchantId'        => $this->getMerchantId(),
            'merchantTranId'    => $input['payment']['id'],
            'subMerchantId'     => $this->getSubMerchantId($input),
            'terminalId'        => '1234',
        ];

        $content = $this->transformRequestArrayToContent($data);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = [
            'Content-Type' => 'text/plain'
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request' => $request,
                'decrypted_content' => $data
            ]);

        return $request;
    }

    protected function verifyPayment($verify)
    {
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

    /**
     * subMerchantId is limited to 10 characters
     * so we send the first 10 characters
     * @return string
     */
    protected function getSubMerchantId(array $input)
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
    public function getPaymentIdFromServerCallback(array $response)
    {
        return $response[ResponseFields::MERCHANT_TRAN_ID];
    }

    /**
     * Takes in S2S request as a body string
     * and returns the parsed response as an array
     * @param  String $body Request body
     * @return array
     */
    public function preProcessS2SResponse($body)
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
     * @return boolean
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $status = $content[ResponseFields::TXN_STATUS];

        $repo = $this->getRepository();

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        // Since there is no Auth in this flow (just public key encryption)
        // and we are not revealing Bank RRN, this gives us a bit of
        // extra security for fake callbacks

        assertTrue($content[ResponseFields::MERCHANT_ID] === $gatewayPayment->getMerchantId());
        assertTrue($content[ResponseFields::MERCHANT_TRAN_ID] === $gatewayPayment->getPaymentId());
        assertTrue($content[ResponseFields::BANK_RRN] === $gatewayPayment->getGatewayPaymentId());

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
    }
}
