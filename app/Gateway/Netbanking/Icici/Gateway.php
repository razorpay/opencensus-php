<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Mode as RZPMode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Entity;

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

        // Ask ICICI about this ? Sometimes wrong ecrypted string returned.
        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $content);

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
        $content[RequestFields::PAYMENT_DATE] = $paymentDate;

        $request = $this->getRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $response;
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseBody;

        $status = VerifyResult::STATUS_MATCH;

        // Converting response string to XML format.
        $xml = $this->getResponseXml($content);

        // Should probably trace this
        $this->trace->info(
            TraceCode::VERIFY_CAPTURE_RESPONSE,
            (array)$xml);

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

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

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::ENCRYPTED_STRING] = $encryptedString;

        return $data;
    }

    protected function getPaymentVerifyData($input)
    {
        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::MODE]  = Mode::VERIFY;

        $prn = $input['payment']['id'];

        $data += array(
            RequestFields::PAYMENT_REFERENCE_NUBER  => $prn,
            RequestFields::ITEM_CODE                => strtoupper($prn),
            RequestFields::CURRENCY_CODE            => 'INR',
        );

        return $data;
    }

    protected function getEncryptedString($input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $queryString = $this->createUrl($data);

        $masterKey = $this->getMasterKey();

        return $this->encryptString($queryString, $masterKey);
    }

    protected function getAuthorizeRequestData($input)
    {
        // Formatted for ICICI
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22';

        $prn = $input['payment']['id'];

        $amount = $input['payment']['amount'] / 100;

        $data = array(
            RequestFields::PAYMENT_REFERENCE_NUBER => $prn ,
            RequestFields::ITEM_CODE               => strtoupper($prn),
            RequestFields::AMOUNT                  => $amount,
            RequestFields::CURRENCY_CODE           => 'INR',
            RequestFields::RETURN_URL              => $callbackUrl,
            RequestFields::CONFIRMATION            => Confirmation::YES,
        );

        return $data;
    }

    protected function createDefaultRequestData($input)
    {
        $pid = $this->getPid();

        $spid = $this->getSpid();

        $data = array(
            RequestFields::OBJ_NAME   => Constants::LOGIN,
            RequestFields::BAY_BANKID => Constants::BANKID,
            RequestFields::MODE       => Mode::PAY,
            RequestFields::PAYEE_ID   => $pid,  // Hardcoding it for now
            RequestFields::SPID       => $spid,
            RequestFields::AMOUNT     => $input['payment']['amount'] / 100
        );

        return $data;
    }

    protected function createUrl($data)
    {
        $url = '';

        foreach ($data as $key => $value)
        {
            $url .= $key . '=' . $value . '&';
        }

        // Removing the trailing &
        return rtrim($url, '&');
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
        // Terminal Entitiy
        $masterKey = $this->terminal[Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === RZPMode::TEST)
        {
            $masterKey = $this->config['test_master_key'];
        }

        return $masterKey;
    }

    public function getPid()
    {
        $pid = $this->terminal[Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === RZPMode::TEST)
        {
            $pid = $this->config['test_pid'];
        }

        return $pid;
    }

    public function getSpid()
    {
        $spid = $this->terminal[Entity::GATEWAY_MERCHANT_ID2];

        if ($this->mode === RZPMode::TEST)
        {
            $spid = $this->config['test_spid'];
        }

        return $spid;
    }
}
