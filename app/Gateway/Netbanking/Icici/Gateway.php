<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Lib\AesTrait;
use RZP\Constants\Mode as RZPMode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

class Gateway extends Base\Gateway
{
    use GatewayBase\AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    const MODE_ECB = 1;

    protected $map = [
        RequestFields::AMOUNT  => 'amount'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = $this->createPaymentArray($input);

        $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], GatewayBase\Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $payment->fill($attrs);

        $this->repo->saveOrFail($payment);

        $this->checkResponseStatus($attrs, $content);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new GatewayBase\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
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

        $status = GatewayBase\VerifyResult::STATUS_MATCH;

        $xml = $this->getResponseArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            (array) $xml);

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if (isset($xml[ResponseFields::STATE]) === true and
            $xml[ResponseFields::STATE] === Constants::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment'][Payment\Entity::STATUS] === 'failed') or
            ($input['payment'][Payment\Entity::STATUS] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = GatewayBase\VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === GatewayBase\VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::ENCRYPTED_STRING] = $encryptedString;

        return $data;
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $data = $this->createDefaultRequestData($input);

        $paymentDate = $this->getPaymentDate($payment);

        $data[RequestFields::PAYMENT_DATE] = $paymentDate;

        $data[RequestFields::MODE]  = Mode::VERIFY;

        $additionalData = $this->getPaymentReferenceData($input);

        $this->getTpvData($additionalData, $input);

        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function getEncryptedString($input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $this->traceGatewayPaymentRequest($data, $input);

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getMasterKey();

        return base64_encode($this->encryptString($queryString, $masterKey));
    }

    protected function getAuthorizeRequestData($input)
    {
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22';

        $data = [
            RequestFields::RETURN_URL              => $callbackUrl,
            RequestFields::CONFIRMATION            => Confirmation::YES,
        ];

        $additionalData = $this->getPaymentReferenceData($input);

        $data = array_merge($data, $additionalData);

        $this->getTpvData($data, $input);

        return $data;
    }

    protected function getPaymentReferenceData($input)
    {
        $prn = $input['payment'][Payment\Entity::ID];

        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        return [
            RequestFields::PAYMENT_REFERENCE_NUBER => $prn ,
            RequestFields::ITEM_CODE               => strtoupper($prn),
            RequestFields::AMOUNT                  => $amount,
            RequestFields::CURRENCY_CODE           => 'INR',
        ];
    }

    protected function createDefaultRequestData($input)
    {
        $pid = $this->getPid();

        $spid = $this->getSpid();

        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        $data = [
            RequestFields::OBJ_NAME   => Constants::LOGIN,
            RequestFields::BAY_BANKID => Constants::BANKID,
            RequestFields::MODE       => Mode::PAY,
            RequestFields::PAYEE_ID   => $pid,
            RequestFields::SPID       => $spid,
        ];

        return $data;
    }

    protected function getTpvData(&$additionalData, $input)
    {
        if ($input['merchant']->isTPVRequired())
        {
            $additionalData[RequestFields::ACCOUNT_NO] = $input['order']['account_number'];
        }
    }

    protected function createQueryString($data)
    {
        $urlArray = [];

        foreach ($data as $key => $value)
        {
            $urlArray[] = $key . '=' . $value;
        }

        $url = implode('&', $urlArray);

        return $url;
    }

    protected function createPaymentArray($input)
    {
        $amount = $input['payment'][Payment\Entity::AMOUNT] / 100;

        return [
            RequestFields::AMOUNT => $amount
        ];
    }

    protected function getPaymentDate($payment)
    {
        $timestamp = $payment['original']['created_at'];

        return date('Y-m-d', $timestamp);
    }

    protected function getDataFromResponse($data)
    {
        $masterKey = $this->getMasterKey();

        $decryptedString = $this->decryptString(base64_decode($data['ES']), $masterKey);

        parse_str($decryptedString, $content);

        if (empty($content) === true)
        {
            // Decryption fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR);
        }

        return $content;
    }

    protected function getCallbackAttributes($content)
    {
        return [
            'received'          => true,
            'status'            => $content[ResponseFields::STATUS],
            'bank_payment_id'   => $content[ResponseFields::BANK_PAYMENT_ID]
        ];
    }

    protected function checkResponseStatus($attrs, $content)
    {
        if ((isset($attrs[Constants::STATUS]) === false) or
            ($attrs[Constants::STATUS] !== Confirmation::YES))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function getResponseArray($content)
    {
        $xml = (array) simplexml_load_string($content);

        return $xml['@attributes'];
    }

    public function getMasterKey()
    {
        $masterKey = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === RZPMode::TEST)
        {
            $masterKey = $this->config['test_master_key'];
        }

        return $masterKey;
    }

    public function getPid()
    {
        $pid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === RZPMode::TEST)
        {
            $pid = $this->config['test_pid'];
        }

        return $pid;
    }

    public function getSpid()
    {
        $spid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];

        if ($this->mode === RZPMode::TEST)
        {
            $spid = $this->config['test_spid'];
        }

        return $spid;
    }
}
