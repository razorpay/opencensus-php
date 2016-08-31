<?php

namespace RZP\Gateway\UPI\ICICI;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use Request;
use Requests_Response;
use RZP\Constants\Mode;
use RZP\Gateway\UPI\Base;
use RZP\Gateway\UPI\Base\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';

    const BANK = 'icici';

    protected $map = array(
        Entity::VPA                     => Entity::VPA,
        Entity::CONTACT                 => Entity::CONTACT,
        ResponseFields::PAYER_NAME      => Entity::NAME,
        ResponseFields::RESPONSE        => Entity::STATUS_CODE,
        ResponseFields::PAYER_AMOUNT    => Entity::AMOUNT,
        Entity::RECEIVED                => Entity::RECEIVED,
        ResponseFields::BANK_RRN        => Entity::GATEWAY_PAYMENT_ID,
        ResponseFields::MERCHANT_ID     => Entity::GATEWAY_MERCHANT_ID,
    );

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
        $key = $this->config['public_key'];
        return str_replace('\n', "\n", $key);
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
        $key = $this->config['private_key'];
        return str_replace('\n', "\n", $key);
    }

    /**
     * Authorizes a payment using UPI Gateway
     * @param  array  $input
     * @return null
     */
    public function authorize(array $input)
    {
        $this->input = $input;

        $this->action = Action::AUTHORIZE;

        $attributes = $this->getGatewayEntityAttributes($input);

        $payment = $this->createGatewayPaymentEntity($attributes);

        $content =  $this->getAuthorizeRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $response);

        $this->updateGatewayPaymentResponse($payment, $response);

        $status = $this->getStatusCode($response);

        if (ResponseMap::isInitiated($status) === false)
        {
            $errorCode = ResponseMap::getApiErrorCode($status);

            throw new GatewayErrorException(
                $errorCode,
                $status,
                ResponseMap::getResponseMessage($status));
        }
    }

    /**
     * We only store the VPA because the rest of the fields
     * are filled by the callback
     * @param  array  $input
     * @return Array
     */
    protected function getGatewayEntityAttributes(array $input)
    {
        return [
            Entity::VPA         =>  $input['vpa'],
        ];
    }

    /**
     * @param  string $response
     * @return array response as associative array
     */
    protected function parseGatewayResponse($response)
    {
        $res = preg_replace('/\s/', '', $response);
        $res = base64_decode($res, true);
        $res = $this->decrypt($res);

        return json_decode($res, true);
    }

    /**
     * Returns the status code from the gateway response
     * @param  array  $response Gateway Response Array
     * @return String Response Code (integer, but casted as string)
     */
    protected function getStatusCode(array $response)
    {
        return isset($response['response']) ? $response['response'] : '9999';
    }

    /**
     * Formats amount to 2 decimal places
     * @param  int $amount amount in paise (100)
     * @return string amount formatted to 2 decimal places in INR (1.00)
     */
    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2);
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
        else
        {
            return $this->config['live_merchant_id'];
        }
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

        $rsa->loadKey($this->getPrivateKey());

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

    protected function getAuthorizeRequestContent($input)
    {
        $payment = $input['payment'];

        $collectByTimestamp = time() + 15 * 60;
        $collect = Carbon::now('Asia/Kolkata')->addMinutes(15)->format('d/m/Y h:i A');

        $data = [
            // Amount and note are lowercase
            // despite being uppercase in docs
            'amount'            =>  $this->formatAmount($payment['amount']),
            'collectByDate'     =>  $collect,
            'billNumber'        =>  '1234',
            'merchantId'        =>  $this->getMerchantId(),
            // 'merchantName'  =>  null,//$input['merchant']['billing_label'],
            'merchantTranId'    =>  $payment['id'],
            'note'              =>  'collect-pay-request',
            // TODO: talk to icici and ask what all is allowed here
            'payerVa'           =>  $input['vpa'],
            'subMerchantId'     =>  $this->getSubMerchantId($input),
            'subMerchantName'   =>  $input['merchant']->getBillingLabel(),
            'terminalId'        =>  '1234',
        ];

        // We trace it here, because it gets encrypted later
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $data);

        return $this->transformRequestArrayToContent($data);
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
        assert($data !== false);

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

        $verify = new \RZP\Gateway\Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyRequestContent($verify);

        $request = $this->getStandardRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $response = $this->parseGatewayResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $response);

        $verify->verifyResponse = $this->response;
        $verify->verifyResponseBody = $this->response->body;
        $verify->verifyResponseContent = $response;

        return $response;
    }

    protected function getPaymentVerifyRequestContent($verify)
    {
        $data = [
            'merchantId'        =>  $this->getMerchantId(),
            'merchantTranId'    =>  $verify->input['payment']['id'],
            'subMerchantId'     =>  $this->getSubMerchantId($verify->input),
            'terminalId'        =>  '1234',
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $data);

        return $this->transformRequestArrayToContent($data);
    }

    /**
     * subMerchantId is limited to 10 characters
     * so we send the first 10 characters
     * @return string
     */
    protected function getSubMerchantId(array $input)
    {
        return substr($input['merchant']['id'], 0, 10);
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
    public function parseS2SResponse($body)
    {
        return $this->parseGatewayResponse($body);
    }
}
