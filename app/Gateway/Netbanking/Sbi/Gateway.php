<?php

namespace RZP\Gateway\Netbanking\Sbi;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Netbanking\Base\BankingType;
use RZP\Gateway\Netbanking\Base\Entity as GatewayEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const ENCRYPTION_METHOD = 'aes-256-gcm';

    protected $gateway = Payment\Gateway::NETBANKING_SBI;

    protected $crypto;

    protected $map = [
        /**
         * Fields from authorize request used to create gateway payment entity
         */
        RequestFields::AMOUNT                => Base\Entity::AMOUNT,
        RequestFields::MERCHANT_CODE         => Base\Entity::MERCHANT_CODE,

        /**
         * Fields from the authorize response
         */
        Base\Entity::RECEIVED                => Base\Entity::RECEIVED,
        ResponseFields::BANK_REF_NO          => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::STATUS               => Base\Entity::STATUS,
        ResponseFields::STATUS_DESC          => Base\Entity::ERROR_MESSAGE,

        /**
         *  Fields from emandate authorize response
         */
        ResponseFields::MANDATE_SBI_STATUS   => Base\Entity::STATUS,
        ResponseFields::MANDATE_SBI_REF      => Base\Entity::BANK_PAYMENT_ID,

        /**
         * Refund fields
         */
        Base\Entity::REFUND_ID               => Base\Entity::REFUND_ID,
        Base\Entity::AMOUNT                  => Base\Entity::AMOUNT,
        Base\Entity::REFERENCE1              => Base\Entity::REFERENCE1

    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        if (isset($input['payment']) === true)
        {
            if ($input['payment']['recurring'] === true)
            {
                $this->setBankingType(BankingType::RECURRING);
            }
            else
            {
                $this->setBankingType(BankingType::RETAIL);
            }
        }
    }

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

        $attributes = $this->getGatewayPaymentAttributes($input);

        $this->createGatewayPaymentEntity($attributes);

        return $this->getAuthorizeRequest($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $gatewayInput = $this->processGatewayResponse($input['gateway'][ResponseFields::ENCDATA]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'response'   => $gatewayInput,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        if ($this->isFirstRecurringPayment($input) === true)
        {
            return $this->handleFirstRecurringPaymentCallback($input, $gatewayInput);
        }

        $this->assertPaymentId($input['payment']['id'], $gatewayInput[ResponseFields::REF_NO]);

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        $actualAmount = $this->formatAmount($gatewayInput[ResponseFields::AMOUNT]);

        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayInput[Base\Entity::RECEIVED] = true;

        /**
         * @var $gatewayPayment GatewayEntity
         */
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->checkCallbackStatus($gatewayInput, $gatewayPayment);

        $this->verifyCallback($gatewayPayment, $input);

        // unsetting here as we do not want the amount to be updated again
        unset($gatewayInput[ResponseFields::AMOUNT]);
        // unsetting here as we do not want to set error_message in case of successful payment
        unset($gatewayInput[ResponseFields::STATUS_DESC]);

        $this->updateGatewayPaymentEntity($gatewayPayment, $gatewayInput);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
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
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway,
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                ]
            );
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $attributes = [
            Base\Entity::REFUND_ID  => $input['refund']['id'],
            Base\Entity::AMOUNT     => $input['refund']['amount'],
            Base\Entity::STATUS     => Status::SENT,
            Base\Entity::REFERENCE1 => $input['refund']['reference3']
        ];

        $this->createGatewayPaymentEntity($attributes);
    }

    //-------------------------- Authorize helper ---------------------------//

    // returns all the params for creating gateway payment entity
    protected function getGatewayPaymentAttributes(array $input)
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

        if ($this->isFirstRecurringPayment($input) === true)
        {
            $request = $this->getStandardRequestArray([], 'post', $this->action . '_MANDATE_' .$this->mode);

            $requestArray = $this->getEmandateParams($input);
        }
        else
        {
            $request = $this->getStandardRequestArray([], 'post', $this->action . '_' . $this->mode);

            $requestArray = [
                RequestFields::REF_NO       => $input['payment'][Payment\Entity::ID],
                RequestFields::AMOUNT       => $input['payment'][Payment\Entity::AMOUNT] / 100,
                RequestFields::PAYMENT_ID   => $input['payment'][Payment\Entity::ID],
                RequestFields::REDIRECT_URL => $input['callbackUrl'],
                RequestFields::CANCEL_URL   => $input['callbackUrl'],
            ];

            if ($input['merchant']->isTPVRequired() === true)
            {
                $requestArray[RequestFields::ACCOUNT_NUMBER] = $input['order']['account_number'];
            }
        }

        $contentToEncrypt = $this->getFormattedRequest($requestArray);

        $gatewayMerchantId = $this->getMerchantId();

        $encryptedData = $this->encrypt($contentToEncrypt);

        $content = [
            RequestFields::ENCDATA          => $encryptedData,
            RequestFields::MERCHANT_CODE    => $gatewayMerchantId,
        ];

        $traceRequestArray = $requestArray;

        unset($traceRequestArray[RequestFields::DEBIT_ACCOUNT_NUMBER]);

        $this->traceGatewayPaymentRequest(
            [
                'encrypted'     => $content,
                'content'       => $contentToEncrypt,
                'request_array' => $traceRequestArray,
                'merchant_code' => $gatewayMerchantId,
                'gateway'       => $this->gateway
            ],
            $input,
            TraceCode::GATEWAY_PAYMENT_REQUEST);

        $request['content'] = $content;

        return $request;
    }

    protected function isFirstRecurringPayment(array $input): bool
    {
        return ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL);
    }

    protected function getEmandateParams($input)
    {
        $token   = $input['token'];
        $payment = $input['payment'];

        $startDate = Carbon::createFromTimestamp($payment[Payment\Entity::CREATED_AT], Timezone::IST)
                             ->format('d/m/Y');

        $finalCollection = Carbon::now(Timezone::IST)->setTimestamp($token->getExpiredAt())->format('d/m/Y');

        return [
            RequestFields::MANDATE_HOLDER_NAME     => $token->getBeneficiaryName(),
            RequestFields::FREQUENCY               => Emandate\Constants::FREQUENCY,
            RequestFields::MANDATE_AMOUNT          => number_format(
                                                            $token->getMaxAmount() / 100,
                                                            2,
                                                            '.',
                                                            ''),
            RequestFields::MANDATE_END_DATE        => $finalCollection,
            RequestFields::MANDATE_START_DATE      => $startDate,
            RequestFields::MANDATE_PAYMENT_ID      => $payment['id'],
            RequestFields::DEBIT_ACCOUNT_NUMBER    => $token->getAccountNumber(),
            RequestFields::MANDATE_RETURN_URL      => $input['callbackUrl'],
            RequestFields::MANDATE_AMOUNT_TYPE     => Emandate\Constants::MAXIMUM,
            RequestFields::TOKEN_ID                => $token['id'],
            RequestFields::MANDATE_TXN_AMOUNT      => Emandate\Constants::TXN_AMOUNT,
            RequestFields::MANDATE_ERROR_URL       => $input['callbackUrl'],
            RequestFields::MANDATE_MODE            => 55454
        ];
    }

    //------------------- Callback helpers ----------------------------------//

    protected function handleFirstRecurringPaymentCallback($input, $gatewayInput)
    {
        $this->assertPaymentId($input['payment']['id'], $gatewayInput[ResponseFields::MANDATE_PAYMENT_ID]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $gatewayInput[Base\Entity::RECEIVED] = true;

        // unsetting here as we do not want the amount to be updated again
        unset($gatewayInput[ResponseFields::AMOUNT]);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $gatewayInput);

        $this->checkCallbackStatusRecurring($gatewayInput);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $acquirerData = array_merge($acquirerData, $recurringData);
        }

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function checkCallbackStatus(array $content, $gatewayPayment)
    {
        if ((empty($content[ResponseFields::STATUS]) === true) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $content[ResponseFields::AMOUNT] = $content[ResponseFields::AMOUNT] * 100;

            $this->updateGatewayPaymentEntity($gatewayPayment, $content);

            if(($content[ResponseFields::STATUS] === Status::PENDING))
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
                    $content[ResponseFields::STATUS],
                    $content[ResponseFields::STATUS_DESC],
                    [
                        'callback_response' => $content,
                        'payment_id'        => $this->input['payment']['id'],
                        'gateway'           => $this->gateway
                    ]);
            }
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

    protected function checkCallbackStatusRecurring(array $content)
    {
        if ((empty($content[ResponseFields::MANDATE_SBI_STATUS]) === true) or
            ($content[ResponseFields::MANDATE_SBI_STATUS] !== Status::SUCCESS))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[ResponseFields::MANDATE_SBI_STATUS] ?? '',
                $content[ResponseFields::MANDATE_SBI_DESCRIPTION],
                [
                    'callback_response' => $content,
                    'payment_id'        => $this->input['payment']['id'],
                    'gateway'           => $this->gateway
                ]);
        }
    }

    protected function getRecurringData($gatewayPayment)
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS         => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }

    //------------------- Verify Helpers ------------------------------------//

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $input = $verify->input;

        // Throw exception as verify is not available for second recurring request
        if (($input['payment']['recurring_type'] === Payment\RecurringType::AUTO) and
            ($input['payment']['recurring'] === true))
        {
            throw new Exception\PaymentVerificationException(
                [], $verify, Payment\Verify\Action::FINISH);
        }

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
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code,
                'gateway'       => $this->gateway
            ]);
    }

    private function getVerifyRequestData($verify)
    {
        $requestArray = [
            RequestFields::REF_NO   => $verify->input['payment']['id'],
            RequestFields::AMOUNT   => $verify->input['payment']['amount'] / 100,
        ];

        $type = $this->action . '_' . $this->mode;

        if ($verify->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $requestArray = [
                RequestFields::MANDATE_PAYMENT_ID            => $verify->input['payment']['id'],
                RequestFields::MANDATE_VERIFY_TXN_AMOUNT     => Emandate\Constants::TXN_AMOUNT,
            ];

            $type = 'verify_mandate'. '_' . $this->mode;
        }

        $stringToEncrypt = $this->getFormattedRequest($requestArray);

        $content = [
            RequestFields::ENCDATA          => $this->encrypt($stringToEncrypt),
            RequestFields::MERCHANT_CODE    => $this->getMerchantId(),
        ];

        $request = $this->getStandardRequestArray($content, 'post', $type);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'formatted_request' => $stringToEncrypt,
                'encrypted'         => $request['content'],
                'payment_id'        => $verify->input['payment']['id'],
                'gateway'           => $this->gateway
            ]);

        return $request;
    }

    private function parseVerifyResponse($responseString): array
    {
        return $this->processGatewayResponse($responseString);
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ($verify->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            if ((isset($content[ResponseFields::MANDATE_SBI_STATUS]) === true) and
                ($content[ResponseFields::MANDATE_SBI_STATUS] === Status::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }
        }
        else
        {
            if ((isset($content[ResponseFields::STATUS]) === true) and
                ($content[ResponseFields::STATUS] === Status::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }
        }
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            return;
        }

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::AMOUNT]) === false)
        {
            return false;
        }

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);
        $actualAmount = $this->formatAmount($content[ResponseFields::AMOUNT]);

        return ($expectedAmount !== $actualAmount);
    }

    protected function getVerifyAttributesToSave($content, $gatewayPayment)
    {
        $attributesToSave = $this->getMappedAttributes($content);

        $attributesToSave[Base\Entity::RECEIVED] = true;

        return $attributesToSave;
    }

    //-------------------Verify common functions ----------------------------//

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

    //--------------------force authorize---------------------------//
    /**
     * This function is implemented to enable force auth through dashboard i.e manual force auth. By default this
     * is not used and we rely on verify to convert payments from failed to authorized state. This may be usd in the
     * extreme case when verify is broken from bank end. In that case this is initiated manually as per the call taken
     * by the finops team.
     *
     * @param $input
     * @return bool
     * @throws Exception\BadRequestException
     */

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::AUTHORIZE);

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

    //--------------------Common Helper functions ---------------------------//

    protected function processGatewayResponse($gatewayResponse): array
    {
        $gatewayResponse = trim($gatewayResponse);

        $decryptedString = trim($this->decrypt($gatewayResponse));

        if ($decryptedString === false)
        {
            throw new Exception\LogicException(
                'Callback response decryption failed',
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED);
        }

        $response = [];

        parse_str(strtr($decryptedString, '|', '&'), $response);

        if (array_key_exists(ResponseFields::CHECKSUM, $response) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_ERROR,
                [
                    'decrypted' => $decryptedString
                ]);

            throw new Exception\RuntimeException('Invalid gateway response');
        }

        $stringWithoutChecksum = explode('|checkSum', $decryptedString)[0];

        $this->compareHashes($response[RequestFields::CHECKSUM], hash('sha256', $stringWithoutChecksum));

        return $response;
    }

    // converts request array to request string with | delimiter
    protected function getFormattedRequest(array $requestArray)
    {
        $requestWithoutChecksum = urldecode(http_build_query($requestArray, '', '|'));

        $checksum = hash('sha256', $requestWithoutChecksum);

        return $requestWithoutChecksum . '|' . RequestFields::CHECKSUM . '=' . $checksum;
    }

    protected function formatAmount($amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function encrypt($stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->crypto->encrypt($stringToEncrypt);
    }

    private function decrypt($stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->crypto->decrypt($stringToDecrypt);
    }

    private function createCryptoIfNotCreated()
    {
        if ($this->crypto === null)
        {
            $this->crypto = new Crypto(
                                   $this->getSecret(),
                                   $this->getIv(),
                           self::ENCRYPTION_METHOD
                                  );
        }
    }

    public function getIv()
    {
        return $this->config['iv'];
    }

    protected function getMerchantId()
    {
        if ($this->isTestMode() === true)
        {
            return $this->getTestMerchantId();
        }

        return $this->getLiveMerchantId();
    }

    public function getSecret()
    {
        if ($this->isTestMode() === true)
        {
            return hex2bin($this->getTestSecret());
        }

        return hex2bin($this->getLiveSecret());
    }

    protected function getTestSecret()
    {
        $secret = parent::getTestSecret();

        if ($this->bankingType === BankingType::RECURRING)
        {
            $secret = $this->config['test_hash_secret_recurring'];
        }

        return $secret;
    }

    protected function getLiveSecret()
    {
        if ($this->bankingType === BankingType::RECURRING)
        {
            $secret = $this->input['terminal']['gateway_secure_secret'];
        }
        else
        {
            $secret = $this->config['live_hash_secret'];
        }

        return $secret;
    }

    protected function getTestMerchantId()
    {
        $code = parent::getTestMerchantId();

        if ($this->bankingType === BankingType::RECURRING)
        {
            $code = $this->config['test_merchant_id_recurring'];
        }

        return $code;
    }
}
