<?php

namespace RZP\Gateway\Netbanking\Pnb;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = Payment\Gateway::NETBANKING_PNB;

    const CHECKSUM_ATTRIBUTE = RequestFields::CHECKSUM;

    protected $bank = 'pnb';

    protected $sortRequestContent = false;

    protected $map = [
        RequestFields::MERCHANT_AMOUNT => Base\Entity::AMOUNT,
        RequestFields::CHALLAN_NUMBER  => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE       => Base\Entity::CAPS_PAYMENT_ID,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $entityAttrs = $this->getNetbankingEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttrs);

        $content = $this->getAuthorizeRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $this->getDataFromCallbackResponse($input[Payment\Entity::GATEWAY]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'gateway_response'   => $input[Payment\Entity::GATEWAY],
                'payment_id'         => $input['payment'][Payment\Entity::ID],
                'decrypted response' => $content
            ]
        );

        $this->assertPaymentId($input['payment'][Payment\Entity::ID],
                $content[ResponseFields::CHALLAN_NUMBER]);

        $this->assertAmount($this->formatAmount($input['payment']['amount']),
                $content[ResponseFields::BANK_AMOUNT_PAID]);

        $this->checkCallbackStatus($content);

        $this->verifySecureHash($content);

        $gatewayPayment = $this->saveCallbackResponse($content);

        $this->verifyCallback($input, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment'][Payment\Entity::ID],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response, $request);

        $this->verifySecureHash($verify->verifyResponseContent);
    }

    public function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);

        if ($verify->gatewaySuccess === true)
        {
            $this->setVerifyAmountMismatch($verify);
        }
    }

    protected function getEncryptedString(array $input, $glue = '|')
    {
        $str = urldecode(http_build_query($input, '', $glue));

        return $this->encryptString($str);
    }

    public function encryptString(string $queryString): string
    {
        $secret = $this->getSecret();

        $iv = $this->getTerminalPassword();

        $crypto = new AESCrypto($secret, $iv);

        $encryptedString = $crypto->encryptString($queryString);

        return $encryptedString;
    }

    public function decryptString(string $encryptedString): string
    {
        $decryption_key = $this->getSecret();

        $iv = $this->getTerminalPassword();

        $crypto = new AESCrypto($decryption_key, $iv);

        $decryptedString = $crypto->decryptString($encryptedString);

        $this->checkDecryptionFailure($decryptedString, $encryptedString);

        return $decryptedString;
    }

    protected function checkDecryptionFailure(
        string $decryptedString, string $encryptedString)
    {
        if (empty($decryptedString) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                '',
                '',
                [
                    'encrypted string' => $encryptedString,
                    'gateway'          => $this->gateway,
                ]
            );
        }
    }

    protected function getNetbankingEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::MERCHANT_AMOUNT => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::CHALLAN_NUMBER  => $input['payment'][Payment\Entity::ID],
            RequestFields::ITEM_CODE       => strtoupper($input['payment'][Payment\Entity::ID])
        ];

        return $entityAttributes;
    }

    protected function getAuthorizeRequestData(array $input): array
    {
        $content = $this->getRequestContentData($input);

        $content[RequestFields::USER_NAME]    = Constants::RZP_NAME;
        $content[RequestFields::EMAIL]        = Constants::RZP_EMAIL;
        $content[RequestFields::ADDRESS]      = Constants::RZP_ADDRESS;
        $content[RequestFields::PHONE_NUMBER] = Constants::RZP_PHONE;
        $content[RequestFields::REMARK]       = Constants::RZP_REMARK;
        $content[RequestFields::RETURN_URL]   = $input['callbackUrl'];

        if ($input['merchant']->isTPVRequired() === true)
        {
            $content[RequestFields::ACCOUNT_NUMBER] = $input['order']['account_number'];
        }

        $content[RequestFields::CHECKSUM] = $this->getHashOfArray($content);

        $encrypted = $this->getEncryptedString($content);

        unset($content[RequestFields::ACCOUNT_NUMBER]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'        => $this->gateway,
                'payment_id'     => $input['payment']['id'],
                'decrypted_data' => $content,
            ]);

        return [RequestFields::ENCDATA => $encrypted];
    }

    protected function getRequestContentData(array $input): array
    {
        $amount = $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]);

        // date has to be of format DDMMYYYY-24HHMMSS
        $date = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT],
            Timezone::IST)
            ->format('dmY-His');

        $paymentId = $input['payment']['id'];

        $data = [
            RequestFields::CHALLAN_NUMBER  => $paymentId,
            RequestFields::MERCHANT_DATE   => $date,
            RequestFields::MERCHANT_AMOUNT => $amount,
            RequestFields::ITEM_CODE       => strtoupper($paymentId),
        ];

        return $data;
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Payment\Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return back.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::SUCCESS))
        {
            return true;
        }

        if (empty($input['gateway']['gateway_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $attributes = [
            Base\Entity::STATUS          => Status::SUCCESS,
            Base\Entity::BANK_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getDataFromCallbackResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        return $this->formatDecryptedResponseString($decryptedString);
    }

    protected function saveCallbackResponse(array $content)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $content[ResponseFields::CHALLAN_NUMBER],
            Action::AUTHORIZE);

        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::BANK_PAYMENT_STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_TRANSACTION_ID]
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        return $gatewayEntity;
    }

    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::BANK_PAYMENT_STATUS]) === false) or
            ($content[ResponseFields::BANK_PAYMENT_STATUS] !== Status::SUCCESS))
        {
            $internalErrorCode = ErrorCodes::getErrorCodeMap($content[ResponseFields::BANK_PAYMENT_STATUS]);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $content[ResponseFields::BANK_PAYMENT_STATUS]
            );
        }
    }

    protected function getVerifyRequestData(Verify $verify): array
    {
        $content = $this->getRequestContentData($verify->input);

        $content[RequestFields::RETURN_URL] = Constants::RZP_URL;
        $content[RequestFields::CHECKSUM]   = $this->getHashOfArray($content);

        $encrypted = $this->getEncryptedString($content);

        return [RequestFields::ENCDATA => $encrypted];
    }

    protected function parseVerifyResponse($response, $request): array
    {
        $values = $this->getFormValues($response->body, $request['url']);

        $encryptedString = $values[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        $response = $this->formatDecryptedResponseString($decryptedString);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'decrypted'  => true,
                'response'   => $response,
            ]
        );

        return $response;
    }

    protected function formatDecryptedResponseString(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    protected function getVerifyStatus(Verify $verify) :string
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $response = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::BANK_PAYMENT_STATUS_VERIFY]) === true) and
            ($response[ResponseFields::BANK_PAYMENT_STATUS_VERIFY] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input['payment'][Payment\Entity::AMOUNT]);

        $verify->amountMismatch =
            ($paymentAmount !== $verify->verifyResponseContent[ResponseFields::BANK_AMOUNT_PAID_VERIFY]);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attrs = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(
        array $content, $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::BANK_PAYMENT_STATUS_VERIFY];
        }

        if (isset($content[ResponseFields::BANK_TRANSACTION_ID_VERIFY]) === true)
        {
            if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
            {
                $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_TRANSACTION_ID_VERIFY];
            }
            else if ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !==
                $content[ResponseFields::BANK_TRANSACTION_ID_VERIFY])
            {
                $this->trace->critical(
                    TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                    [
                        'payment_id'    => $content[ResponseFields::CHALLAN_NUMBER_VERIFY],
                        'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                        'verify_bid'    => $content[ResponseFields::BANK_TRANSACTION_ID_VERIFY]
                    ]
                );
            }
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus()
    {
        return Status::SUCCESS;
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return urldecode(http_build_query($content, '', $glue));
    }

    protected function getHashOfString($str)
    {
        return md5($str); // nosemgrep : php.lang.security.weak-crypto.weak-crypto
    }

    protected function getLiveSecret()
    {
        assertTrue ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
    }

    protected function getLiveTerminalPassword()
    {
        assertTrue ($this->mode === Mode::LIVE);

        return $this->config['live_terminal_password'];
    }
}
