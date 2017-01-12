<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Constants\Mode as RZPMode;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

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
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attrs = $this->getCallbackAttributes($content);

        $payment->fill($attrs);

        $this->repo->saveOrFail($payment);

        $this->checkResponseStatus($attrs, $content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

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

        $verify->verifyResponseBody = $response->body;
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseBody;

        $xml = $this->getResponseArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            (array) $xml);

        $status = $this->getVerifyStatus($verify, $xml);

        return $status;
    }

    protected function getVerifyStatus($verify, $xml)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->getApiSuccess($verify);

        $this->getGatewaySuccess($verify, $xml);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    protected function getApiSuccess($verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment'][Payment\Entity::STATUS] === 'failed') or
            ($input['payment'][Payment\Entity::STATUS] === 'created'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function getGatewaySuccess($verify, $xml)
    {
        $verify->gatewaySuccess = false;

        if (isset($xml[ResponseFields::STATE]) === true and
            $xml[ResponseFields::STATE] === Constants::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
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

        $this->setTpvFieldIfNeeded($additionalData, $input);

        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function getEncryptedString($input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $this->traceGatewayPaymentRequest($data, $input);

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getSecret();

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

        $this->setTpvFieldIfNeeded($data, $input);

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

    protected function setTpvFieldIfNeeded(&$additionalData, $input)
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
        $masterKey = $this->getSecret();

        $decryptedString = $this->decryptString(
            base64_decode($data['ES']), $masterKey);

        parse_str($decryptedString, $content);

        if (empty($content) === true)
        {
            $errorContent = [
                'msg' => 'decryption failure',
                'encryptedString' => $data['ES']
            ];

            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                $errorContent);

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

    public function encryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        return $aes->encrypt($string);
    }

    public function decryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        return $aes->decrypt($string);
    }

    public function getPid()
    {
        if ($this->mode === RZPMode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
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
