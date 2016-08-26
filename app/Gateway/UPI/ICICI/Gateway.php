<?php

namespace RZP\Gateway\UPI\ICICI;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use Request;
use Requests_Response;
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
        // ResponseFields::PAYER_VA        => Entity::VPA,
        ResponseFields::PAYER_NAME      => Entity::NAME,
        ResponseFields::RESPONSE        => Entity::STATUS_CODE,
        ResponseFields::PAYER_AMOUNT    => Entity::AMOUNT,
        Entity::RECEIVED                => Entity::RECEIVED,
        ResponseFields::BANK_RRN        => Entity::GATEWAY_PAYMENT_ID,
    );

    /**
     * In both getPublicKey and getPrivateKey,
     * we are converting literal '\n' (single quotes)
     * to actual newlines (double quotes "\n").
     *
     * This is because we store them in environment, which
     * uses literal \n
     * @return string public key
     */
    protected function getPublicKey()
    {
        $key = $this->config['public_key'];
        return str_replace('\n', "\n", $key);
    }

    /**
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

        $response = $this->parseGatewayResponse($response);

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

    protected function getGatewayEntityAttributes(array $input)
    {
        return [
            Entity::VPA         =>  $input['vpa'],
            Entity::CONTACT     =>  $input['payment'][Entity::CONTACT],
        ];
    }

    /**
     * @param  Requests_Response $response
     * @return array response as associative array
     */
    protected function parseGatewayResponse(Requests_Response $response)
    {
        $res = preg_replace('/\s/', '', $response->body);
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

    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2);
    }

    protected function getMerchantId()
    {
        // TODO: Decide between LIVE/TEST
        return $this->config['test_merchant_id'];
    }

    /**
     * Generates request object for the status call
     * @return array
     */
    protected function statusData()
    {
        return [
            'merchantId'        =>  'merchantId',
            'subMerchantId'     =>  '12234',
            'terminalId'        =>  '2342342',
            'merchantTranId'    =>  '612413726581'
        ];
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
        $collect = Carbon::now('Asia/Kolkata')->addMinutes(15)->format('d/m/y h:i a');

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
            'subMerchantId'     =>  substr($input['merchant']['id'], 0, 10),
            'subMerchantName'   =>  $input['merchant']->getBillingLabel(),
            'terminalId'        =>  '1234',
        ];


        // We trace it here, because it gets encrypted later
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $data);

        $json = json_encode($data);

        $data = $this->encrypt($json);
        assert($data !== false);

        $content = base64_encode($data);

        return $content;
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attr = $this->getMappedAttributes($response);

        $payment->fill($attr);

        $payment->save();
    }
}
