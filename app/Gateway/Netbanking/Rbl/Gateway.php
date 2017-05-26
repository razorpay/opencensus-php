<?php

namespace RZP\Gateway\Netbanking\Rbl;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base\Action;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_rbl';

    protected $bank = 'rbl';

    protected $map = [
        RequestFields::AMOUNT             => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_REFERENCE => Base\Entity::PAYMENT_ID
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $entityAttributes = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

        $request = $this->getStandardRequestArray($content);

        $request['url'] = $request['url'] . urldecode(http_build_query($content));

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::MERCHANT_REFERENCE]
        );

        $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify->input);

        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] =
        [
            'IPTYPE' => FileFormat::NV
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response->body);
    }

    protected function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify): string
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        if (($verify->input['payment']['status'] === 'created') or
            ($verify->input['payment']['status'] === 'failed'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        //
        // Verify response will contain ENTRY_STATUS and will have success
        // or failure
        //
        if ($content[ResponseFields::ENTRY_STATUS] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequestData(array $input): array
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
             $input['payment']['id'], Action::AUTHORIZE);

        $data = [
            RequestFields::BANK_ID          => Constants::BANK_ID,
            RequestFields::LANGUAGE_ID      => Constants::LANGUAGE_ID,
            RequestFields::CHANNEL_ID       => Constants::CHANNEL_ID,
            RequestFields::V_LOGIN_FLAG     => Constants::VERIFY_LOGIN_FLAG,
            RequestFields::SERVICE_ID       => Constants::SERVICE_ID,
            RequestFields::STATE_MODE       => Constants::STATE_MODE,
            RequestFields::RESPONSE_FORMAT  => FileFormat::XML,
            RequestFields::REQUEST_FORMAT   => FileFormat::NV,
            RequestFields::MULTIPLE_RECORDS => Constants::NO,
            RequestFields::USER_PRINCIPAL   => Constants::VIRTUAL_USER,
            RequestFields::ACCESS_CODE      => Constants::ACCESS_CODE,
            RequestFields::V_PAYEE_ID       => $this->getMerchantId(),
            RequestFields::BANK_REFERENCE   => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
            RequestFields::ENTITY_TYPE      => Constants::TYPE_PAYMENT,
            RequestFields::TRANS_CURRENCY   => Currency::INR,
        ];

        return $data;
    }

    protected function getAuthorizeRequestData(array $input): array
    {
        $data =
        [
            RequestFields::FORM_ID          => Constants::AUTHENTICATION,
            RequestFields::TRANSACTION_FLAG => Constants::YES,
            RequestFields::FG_BUTTON        => Constants::LOAD,
            RequestFields::ACTION_LOAD      => Constants::YES,
            RequestFields::BANK_ID          => Constants::BANK_ID,
            RequestFields::LOGIN_FLAG       => Constants::LOGIN_FLAG,
            RequestFields::USER_TYPE        => Constants::USER_TYPE,
            RequestFields::MENU_ID          => Constants::MENU_ID,
            RequestFields::CALL_MODE        => Constants::CALL_MODE,
            RequestFields::CATEGORY_ID      => Constants::CATEGORY,
            RequestFields::RETURN_URL       => $input['callbackUrl'],
        ];

        $dataToEncrypt =
        [
            RequestFields::CURRENCY           => Currency::INR,
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100,
            RequestFields::PAYEE_ID           => $this->getMerchantId(),
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
            RequestFields::MERCHANT_NAME      => Constants::MERCHANT_NAME

        ];

        if ($input['merchant']->isTPVRequired())
        {
            $dataToEncrypt[RequestFields::ACCOUNT_NUMBER] = '.' . $input['order']['account_number'];
        }

        $stringToEncrypt = $this->getStringToHash($dataToEncrypt, '|');

        $data[RequestFields::QUERY_STRING] = $this->getHashOfString($stringToEncrypt);

        return $data;
    }

    /*
     * @param Eg. $data = ['PRN' => "6vTX585l2WP6Bq", 'MD' => "P"]
     * @return Eg. string "PRN~6vTX585l2WP6Bq|MD~P"
     */
    protected function getStringToHash($data, $glue = ''): string
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '~' . $value;
        }

        $queryString = implode($glue, $queryArray);

        return $queryString;
    }

    protected function getHashOfString($stringToEncrypt): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->encryptString($stringToEncrypt);
    }

    public function getDecryptedString(string $stringToDecrypt): string
    {
        $masterKey = $this->getSecret();

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->decryptString($stringToDecrypt);
    }

    protected function getEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::AMOUNT             => $input['payment']['amount'] / 100,
            RequestFields::MERCHANT_REFERENCE => $input['payment']['id'],
        ];

        return $entityAttributes;
    }

    protected function saveCallbackResponse(array $content)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REFERENCE],
            Base\Entity::STATUS          => $content[ResponseFields::STATUS],
        ];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                    $content[ResponseFields::MERCHANT_REFERENCE],
                                    Payment\Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(array $content, Base\Entity $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::ENTRY_STATUS];
        }

        //
        // Saving BID from Verify response only if BID from authorize hasn't been saved
        //
        if (isset($content[ResponseFields::REFERENCE_ID]) === true)
        {
            if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
            {
                $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::REFERENCE_ID];
            }
            else if ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !== $content[ResponseFields::REFERENCE_ID])
            {
                $this->trace->error(
                    TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                    [
                        'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                        'verify_bid'    => $content[ResponseFields::REFERENCE_ID]
                    ]
                );
            }
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus(): string
    {
        return Status::SUCCESS;
    }

    protected function parseVerifyResponse(string $body): array
    {
        $xml = (array) simplexml_load_string($body);

        $transactionStatus = (array) $xml[ResponseFields::TRANSACTION_STATUS];

        return (array) $transactionStatus[ResponseFields::STATUS_RECORD];
    }

    protected function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }
}
