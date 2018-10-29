<?php

namespace RZP\Gateway\Netbanking\Sbi;

use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base\Entity as GatewayEntity;

class Gateway extends Base\Gateway
{
    const TEST_IV = '1234567890123456'; // TODO: fix this value

    const LIVE_IV = ''; // TODO: fill in this after getting live creds

    protected $gateway = Payment\Gateway::NETBANKING_SBI;

    /**
     * @var $aesCrypto AESCrypto
     */
    protected $aesCrypto;

    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::AMOUNT    => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_CODE => Base\Entity::MERCHANT_CODE,

        /**
         * Fields from the authorize response
         */
        Base\Entity::RECEIVED           => Base\Entity::RECEIVED,
        ResponseFields::BANK_REF_NO     => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS          => Base\Entity::STATUS,
    ];

    /**
     * This function initiates a transaction on sbi.
     * Request params are appended one after other with | in between
     * md5 checksum for request is appended at the end of request string again with | in between
     * this string is encrypted with AES 128
     * then it is url encoded with merchant code and posted at sbi url.
     *
     * user is redirected to netbanking login portal at this point.
     *
     * @param array $input
     * @return $url
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getAuthorizeRequestArray($input);

        $this->createGatewayPaymentEntity($attributes);

        $request = $this->getAuthorizeRequest($input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $gatewayInput = $this->preProcessServerCallback($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $gatewayInput[ResponseFields::REF_NO]);

        $this->assertAmount($input['payment']['amount'] / 100, (int) $gatewayInput[ResponseFields::AMOUNT]);

        /**
         * @var $gatewayPayment GatewayEntity
         */
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackStatus($gatewayInput, $gatewayPayment);

        $this->verifyCallback($gatewayPayment, $input);

        $gatewayInput[ResponseFields::AMOUNT] = $gatewayInput[ResponseFields::AMOUNT] * 100;

        $gatewayInput[Base\Entity::RECEIVED] = true;

        $this->updateGatewayPaymentEntity($gatewayPayment, $gatewayInput);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    //------------------- Verify --------------------------------------------//

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyCallback(Base\Entity $gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If the status in callback and verify does not match
        //
        if ($verify->gatewaySuccess !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                $verify->verifyResponseContent[ResponseFields::STATUS],
                $verify->verifyResponseContent[ResponseFields::STATUS_DESC],
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);

        if ($verify->amountMismatch === true)
        {
            throw new Exception\LogicException(
                'Amount tampering found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    //-------------------------- Authorize helper ---------------------------//

    // returns all the params for creating gateway payment entity
    // does not actually create the authorize request
    protected function getAuthorizeRequestArray(array $input)
    {
        $requestArray = [
            RequestFields::REF_NO           => $input['payment'][Payment\Entity::ID],
            RequestFields::AMOUNT           => $input['payment'][Payment\Entity::AMOUNT],
            RequestFields::PAYMENT_ID       => $input['payment'][Payment\Entity::ID],
            RequestFields::MERCHANT_CODE    => $this->getMerchantId(),
        ];

        return $requestArray;
    }
    protected function getAuthorizeRequest(array $input)
    {
        $request = $this->getStandardRequestArray();

        $requestArray = [
            RequestFields::REF_NO       => $input['payment'][Payment\Entity::ID],
            RequestFields::AMOUNT       => $input['payment'][Payment\Entity::AMOUNT] / 100,
            RequestFields::PAYMENT_ID   => $input['payment'][Payment\Entity::ID],
            RequestFields::REDIRECT_URL => $input['callbackUrl'],
            RequestFields::CANCEL_URL   => $input['callbackUrl'],
        ];

        $contentToEncrypt = $this->getFormattedRequest($requestArray);

        $gatewayMerchantId = $this->getMerchantId();

        $encryptedData = $this->encrypt($contentToEncrypt);

        $content = [
            RequestFields::ENCDATA          => $encryptedData,
            RequestFields::MERCHANT_CODE    => $gatewayMerchantId,
        ];

        $this->traceGatewayPaymentRequest(
            [
                'encrypted'     => $content,
                'content'       => $contentToEncrypt,
                'parsed'        => $requestArray,
                'merchant_code' => $gatewayMerchantId,
            ],
            $input,
            TraceCode::GATEWAY_PAYMENT_REQUEST);

        $request['content'] = $content;

        return $request;
    }

    //------------------- Callback helpers ----------------------------------//

    public function preProcessServerCallback($input) : array
    {
        try
        {
            $decryptedString = $this->decrypt($input['encdata']);
        }
        catch (\Exception $e)
        {
            throw new Exception\LogicException(
                'Callback response decryption failed',
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED);
        }

        $responseStringArray = explode('|', $decryptedString);

        $response = $this->getResponseArray($responseStringArray);

        $stringWithoutChecksum = explode('|checkSum', $decryptedString)[0];

        if ($response[RequestFields::CHECKSUM] !== md5($stringWithoutChecksum))
        {
            $this->trace->info(
                TraceCode::GATEWAY_CHECKSUM_VERIFY_FAILED,
                [
                    'actual'    => $response[RequestFields::CHECKSUM],
                    'generated' => md5($stringWithoutChecksum),
                ]);

            throw new Exception\RuntimeException('Failed checksum verification');
        }

        return $response;
    }

    public function getPaymentIdFromServerCallback($input)
    {
        return $input[ResponseFields::REF_NO];
    }

    protected function checkCallbackStatus(array $content, $gatewayPayment)
    {
        if ((empty($content[ResponseFields::STATUS]) === true) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->updateGatewayPaymentEntity($gatewayPayment, $content);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[ResponseFields::STATUS],
                $content[ResponseFields::STATUS_DESC],
                [
                    'callback_response' => $content,
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    //------------------- Verify Helpers ------------------------------------//

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $request = $this->getVerifyRequestData($verify);

        $response = $this->sendGatewayRequest($request);

        $data = [
            'gateway'         => $this->gateway,
            'response'        => $response->body,
            'payment_id'      => $verify->input['payment']['id']
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, $data);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }

    private function getVerifyRequestData($verify)
    {
        $request = $this->getStandardRequestArray();

        $requestArray = [
            RequestFields::REF_NO   => $verify->input['payment']['id'],
            RequestFields::AMOUNT   => $verify->input['payment']['amount'] / 100,
        ];

        $stringToEncrypt = $this->getFormattedRequest($requestArray);

        $request['content'] = [
            RequestFields::ENCDATA => $this->encrypt($stringToEncrypt)
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'data'       => $requestArray,
                'payment_id' => $verify->input['payment']['id'],
                'gateway'    => $this->gateway
            ]);

        return $request;
    }

    private function parseVerifyResponse($responseString): array
    {
        $response = $this->jsonToArray($responseString);

        $responseString = $this->decrypt($response['encdata']);

        $responseArray = $this->xmlToArray($responseString);

        return $responseArray['@attributes'];
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::STATUS]) === true)
            and ($content[ResponseFields::STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::AMOUNT]) === false)
        {
            return false;
        }

        return (($input['payment']['amount'] / 100) !== ((int) $content[ResponseFields::AMOUNT]));
    }

    protected function getVerifyAttributesToSave($content, $gatewayPayment)
    {
        $content[ResponseFields::AMOUNT] = $content[ResponseFields::AMOUNT] * 100;

        $attributesToSave = $this->getMappedAttributes($content);

        $attributesToSave[Base\Entity::RECEIVED] = true;

        return $attributesToSave;
    }

    //-------------------Verify common functions ----------------------------//

    // TODO: move all these functions to Base/Gateway

    protected function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyMatchStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->saveVerifyContent($verify);

        $verify->amountMismatch = $this->setVerifyAmountMismatch($verify);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            return VerifyResult::STATUS_MISMATCH;
        }

        return VerifyResult::STATUS_MATCH;
    }

    //--------------------Common Helper functions ---------------------------//

    // converts request array to request string with | delimiter
    protected function getFormattedRequest(array $requestArray)
    {
        $request = [];

        foreach ($requestArray as $key => $value)
        {
            $request[] = $key . '=' . $value;
        }

        $requestWithoutChecksum = implode('|', $request);

        $checksum = md5($requestWithoutChecksum);

        return $requestWithoutChecksum . '|' . RequestFields::CHECKSUM . '=' . $checksum;
    }

    private function getResponseArray($stringArray)
    {
        $response = [];

        foreach ($stringArray as $line)
        {
            $key = explode('=', $line)[0];
            $value = explode('=', $line)[1];

            $response[$key] = $value;
        }

        return $response;
    }

    private function encrypt($stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return base64_encode($this->aesCrypto->encryptString($stringToEncrypt));
    }

    private function decrypt($stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->decryptString(base64_decode($stringToDecrypt));
    }

    private function createCryptoIfNotCreated()
    {
        if ($this->aesCrypto === null)
        {
            $this->aesCrypto = new AESCrypto(
                AES::MODE_CBC,
                hex2bin($this->getSecret()),
                $this->getIv());
        }
    }

    private function getIv()
    {
        if ($this->isTestMode() === true)
        {
            return self::TEST_IV;
        }

        return self::LIVE_IV;
    }

    //TODO: move this to Base/Gateway
    protected function getMerchantId()
    {
        if ($this->isTestMode() === true)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }
}
