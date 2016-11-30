<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use phpseclib\Crypt\AES;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    const MODE_ECB = 1;

    protected $map = array(
        RequestFields::AMOUNT  => 'amount'
    );

    /**
     * @param  array $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        // Create payment entity before passing it to gateway payment entity
        $entity = $this->createPaymentEntity($content);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getRequestArray($content);

        // sd($request);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    /**
     * @param  array $input
     * @return void
     */
    public function callback(array $input)
    {
        parent::callback($input);

        // Ask ICICI about this -----> Could run into errors? Sometimes wrong ecrypted string returned.
        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        // Use maps - Response Fields
        $attrs = $this->getAuthorizeResponseAttributes($content);

        $payment->fill($attrs);

        $payment->saveOrFail();

        if ($attrs['status'] !== 'Y')
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }


    /**
     * @param  array $input
     * @return void
     */
    public function verify(array $input)
    {
        // Similar to the code above.... Essentially the same thing
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        // Calling Parent class's method - need to write 2 classes on my own
        // sendPaymentVerifyRequest and verifyPayment
        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $content = $this->getPaymentVerifyData($input);

        $paymentDate = $this->getPaymentDate($payment);

        // Getting payment date in the specified format
        $content[ResponseFields::PAYMENT_DATE] = $paymentDate;

        $request = $this->getRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponse = $response;
        // Why is this body empty?? It definitely shouldn't be empty
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        // Not sure how the response object is going to be passed in here
        return $response;
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseBody;  // Body gets the XML string

        $status = VerifyResult::STATUS_MATCH;

        // Converting response string to XML format. ----- Make sure you verify this with ICICI once again
        $xml = $this->getResponseXml($content);

        // Should probably trace this
        /*$this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $xml);*/

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        // Verify this with ICICI
        if ($xml['STATUS'] === 'SUCCESS')
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

        // we want the status to match - in either authorize or failure
        return $status;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $pid = $this->getPid();

        $data = array(
            RequestFields::MODE               => ModeFields::AUTHORIZE,
            RequestFields::PAYEE_ID           => $pid,  // Hardcoding it for now
            RequestFields::AMOUNT             => (float) $input['payment']['amount'] / 100 ,
            RequestFields::ENCRYPTED_STRING   => $encryptedString,
        );

        return $data;
    }

    protected function getPaymentVerifyData($input)
    {
        $pid = $this->getPid();

        $data = array(
            RequestFields::MODE                     => ModeFields::VERIFY,
            RequestFields::PAYEE_ID                 => $pid,
            RequestFields::AMOUNT                   => (float) $input['payment']['amount'] / 100 ,
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input['payment']['id'], // payment_id
            RequestFields::ITEM_CODE                => strtoupper($input['payment']['id']),
            RequestFields::CURRENCY_CODE            => 'INR',
        );

        return $data;
    }

    protected function getEncryptedString($input)
    {
        // Adding & so that URL creation is simple
        $data = $this->getAuthorizeRequestData($input);

        $queryString = $this->createUrl($data);

        $masterKey = $this->getMasterKey();

        return $this->encryptString($queryString, $masterKey);
    }

    protected function getAuthorizeRequestData($input)
    {
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22'; // ICICI integration docs

        $data = array(
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input['payment']['id'] . '&', // payment_id
            RequestFields::ITEM_CODE                => strtoupper($input['payment']['id'] . '&'), // upper case
            RequestFields::AMOUNT                   => (float) $input['payment']['amount'] / 100 . '&',
            RequestFields::CURRENCY_CODE            => 'INR' . '&',
            RequestFields::RETURN_URL               => $callbackUrl . '&',
            RequestFields::CONFIRMATION             => Confirmation::YES,
        );

        return $data;
    }

    protected function createUrl($data)
    {
        $url = '';

        foreach ($data as $key => $value)
        {
            $url .= $key . '=' . $value;
        }

        return $url;
    }

    protected function getRequestArray($content)
    {
        // Amount not needed for the Purchase Request, but needed for verify
        if ($content['MD'] === 'P')
        {
            unset($content[RequestFields::AMOUNT]);
        }

        return array(
            'url' => Url::LIVE_DOMAIN,
            'method' => 'post',
            'content' => $content
        );
    }

    protected function createPaymentEntity($content)
    {
        return array(
            RequestFields::AMOUNT => $content[RequestFields::AMOUNT]
        );
    }

    protected function getPaymentDate($payment)
    {
        $timestamp = $payment['original']['created_at'];

        return date('Y-m-d', $timestamp);
    }


    protected function getDataFromResponse($data)
    {
        $masterKey = $this->getMasterKey();

        $decryptedString = $this->decryptString($data['ES'], $masterKey);

        parse_str($decryptedString, $content);

        return $content;
    }

    protected function getAuthorizeResponseAttributes($content)
    {
        return array(
            'received' => true,
            'status'   => $content[ResponseFields::STATUS],
            'bank_payment_id' => $content[ResponseFields::BANK_PAYMENT_ID]
        );
    }

    protected function getResponseXml($content)
    {
        $xml = (array) simplexml_load_string($content);

        return $xml['@attributes'];
    }

    public function getMasterKey()
    {
        return $this->config['test_master_key'];
    }

    public function getPid()
    {
        return $this->config['test_pid'];
    }
}
