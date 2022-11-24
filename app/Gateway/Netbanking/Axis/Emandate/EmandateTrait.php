<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Models\Currency\Currency;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

trait EmandateTrait
{
    //-----------------------Auth request helpers------------------------

    protected function authorizeRecurring(array $input)
    {
        $content = $this->getRecurringPaymentData($input);

        $entityAttributes = $this->getEmandateEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttributes);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    /**
     * This method creates the recurring payment request data
     * We pass the token ID as customer reference number
     *
     * @param array $input
     * @return array
     */
    protected function getRecurringPaymentData(array $input): array
    {
        $maxAmount = $this->formatAmount($input['token']['max_amount']);

        $ppiArray = [
            $input['payment'][Payment\Entity::ID],
            Constants::PPI_AMOUNT_TYPE,
            Frequency::ADHOC,
            $input['token'][Token\Entity::ACCOUNT_NUMBER],
            Carbon::now(Timezone::IST)->format('m/d/Y'),
            Carbon::createFromTimestamp($input['token']['expired_at'], Timezone::IST)->format('m/d/Y'),
            $maxAmount,
        ];

        $data = [
            RequestFields::VERSION         => Constants::VERSION,
            RequestFields::CORP_ID         => $this->getEmandateMerchantId(),
            RequestFields::TYPE            => $this->getType(),
            RequestFields::REQUEST_ID      => $input['payment'][Payment\Entity::ID],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId(),
            RequestFields::CURRENCY        => Currency::INR,
            RequestFields::AMOUNT          => $maxAmount,
            RequestFields::RETURN_URL      => $input['callbackUrl'],
            RequestFields::PRE_POP_INFO    => implode('|', $ppiArray),
            RequestFields::RESERVE_FIELD_1 => Constants::NO_MODIFICATION,
            RequestFields::RESERVE_FIELD_2 => '',
            RequestFields::RESERVE_FIELD_3 => '',
            RequestFields::RESERVE_FIELD_4 => '',
            RequestFields::RESERVE_FIELD_5 => '',
        ];

        $data[RequestFields::CHECKSUM] = $this->getChecksum($data);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'         => $this->gateway,
                'payment_id'      => $input['payment'][Payment\Entity::ID],
                'data_before_enc' => $data
            ]);

        $content = [
            RequestFields::DATA => $this->getEmandateEncryptedData($data)
        ];

        return $content;
    }

    //-----------------------Auth request helpers end---------------------

    //-----------------------Callback request helpers---------------------

    protected function handleEmandateCallback(array $input): array
    {
        $content = $input['gateway'];

        $gatewayEntity = $this->handleEmandateResponse($input, $content);

        $acquirerData = $this->getEmandateAcquirerData($gatewayEntity);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function handleEmandateResponse(array $input, array $content): Netbanking\Entity
    {
        $content = $this->getEmandateDecryptedData($content[ResponseFields::DATA], $input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'decrypted_response' => $content,
                'payment_id'         => $input['payment'][Payment\Entity::ID]
            ]);

        $this->validateCallbackChecksum($content);

        $this->assertPaymentId(
            $input['payment'][Payment\Entity::ID],
            $content[ResponseFields::REQUEST_ID]
        );

        //
        // In the auth request, we send max_amount in the amount field.
        // In the callback, we receive the same amount. That's why we
        // check for token's max_amount here and not payment's amount.
        // Payment's amount is 0.
        //
        $this->assertAmount(
            $this->formatAmount($input['token']['max_amount']),
            $content[ResponseFields::AMOUNT]
        );

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attributes = $this->getEmandateCallbackAttributes($content);

        $gatewayEntity->fill($attributes);

        $this->repo->saveOrFail($gatewayEntity);

        // We check the status of the payment, and not the SI registration here
        $this->checkEmandateResponseStatus($content, $input);

        return $gatewayEntity;
    }

    protected function getEmandateCallbackAttributes(array $content): array
    {
        $mandateNumber = $content[ResponseFields::MANDATE_NUMBER] ?? null;

        $statusCode = null;

        if ($mandateNumber !== null)
        {
            $statusCode = StatusCode::getEmandateStatus($mandateNumber);
        }

        return [
            Netbanking\Entity::RECEIVED        => true,

            // These values might not be set if the registration/initial payment is a failure
            Netbanking\Entity::STATUS          => ($content[ResponseFields::STATUS_CODE] ?? null),
            Netbanking\Entity::BANK_PAYMENT_ID => ($content[ResponseFields::BANK_REF_NO] ?? null),
            Netbanking\Entity::REFERENCE1      => $mandateNumber,

            // SI registration specific callback attributes
            Netbanking\Entity::SI_TOKEN        => $mandateNumber,
            Netbanking\Entity::SI_STATUS       => $statusCode,
            Netbanking\Entity::SI_MSG          => $content[ResponseFields::REMARKS],
        ];
    }

    protected function checkEmandateResponseStatus(array $content, array $input)
    {
        if (isset($content[ResponseFields::STATUS_CODE]) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                null,
                [
                    'content'    => $content,
                    'payment_id' => $input['payment'][Payment\Entity::ID]
                ]
            );
        }

        $statusCode = $content[ResponseFields::STATUS_CODE];

        if (StatusCode::isSuccess($statusCode) !== true)
        {
            $errorCode = StatusCode::getErrorCodeMap($content);

            throw new GatewayErrorException(
                $errorCode,
                $statusCode,
                '',
                [
                    'content'    => $content,
                    'payment_id' => $input['payment'][Payment\Entity::ID],
                    'gateway'    => $this->gateway
                ]
            );
        }
    }

    protected function getEmandateAcquirerData(Netbanking\Entity $gatewayPayment): array
    {
        $acquirerData = [
            'acquirer' => [
                Payment\Entity::REFERENCE1         => $gatewayPayment->getBankPaymentId(),
            ]
        ];

        return array_merge($acquirerData, $this->getRecurringData($gatewayPayment));
    }

    protected function getRecurringData(Netbanking\Entity $gatewayPayment)
    {
        $recurringStatus = null;

        $gatewaySiToken = $gatewayPayment->getSIToken();

        if (StatusCode::isEmandateRegistrationSuccess($gatewaySiToken) === true)
        {
            $recurringStatus = Token\RecurringStatus::CONFIRMED;
        }
        else
        {
            $recurringStatus = Token\RecurringStatus::REJECTED;
        }

        $recurringFailureReason = $gatewayPayment->getSIMessage();

        return [
            Token\Entity::GATEWAY_TOKEN            => $gatewayPayment->getSIToken(),
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason,
        ];
    }
    //---------------Callback request helpers end-----------------

    //-------------- Verify request helpers --------------------------
    public function getEmandatePaymentVerifyData(Verify $verify): array
    {
        $input = $verify->input;

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $data = [
            RequestFields::VERSION         => Constants::VERSION,
            RequestFields::CORP_ID         => $this->getEmandateMerchantId(),
            RequestFields::TYPE            => $this->getType(),
            RequestFields::REQUEST_ID      => $input['payment'][Payment\Entity::ID],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId(),
            RequestFields::BANK_REF_NO     => $gatewayEntity[Netbanking\Entity::BANK_PAYMENT_ID]
        ];

        $data[RequestFields::CHECKSUM] = $this->getChecksum($data);

        $content = [
            RequestFields::DATA => $this->getEmandateEncryptedData($data)
        ];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'                => $this->gateway,
                'payment_id'             => $input['payment'][Payment\Entity::ID],
                'data_before_encryption' => $data,
            ]
        );

        return $content;
    }

    /**
     * Sets statuses and matches for recurring verify
     *
     * @param Verify $verify
     */
    protected function setEmandateVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkVerifyGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->setRecurringVerifyAmountMismatch($verify);
    }

    protected function checkVerifyGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        $statusCode = $content[ResponseFields::STATUS_CODE];

        if (StatusCode::isSuccess($statusCode) === true)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function setRecurringVerifyAmountMismatch(Verify $verify)
    {
        $payment = $verify->input['payment'];
        $token = $verify->input['token'];

        if ($payment[Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            return;
        }

        $paymentAmount = $this->formatAmount($payment[Payment\Entity::AMOUNT]);

        $verifyAmount = $verify->verifyResponseContent[ResponseFields::AMOUNT] ?: '0';

        $verify->amountMismatch = ($paymentAmount !== $verifyAmount);
    }

    protected function saveEmandateVerifyResponseIfNeeded(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        if ((isset($content[ResponseFields::STATUS_CODE])) and
            (StatusCode::isSuccess($content[ResponseFields::STATUS_CODE]) == true))
        {
            $attributes = $this->getEmandateVerifyAttributes($verify, $gatewayPayment);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    protected function getEmandateVerifyAttributes(Verify $verify, $gatewayPayment)
    {
        $content = $verify->verifyResponseContent;

        $bankPaymentId = $gatewayPayment->getBankPaymentId();

        $attributes = [];

        if (empty($bankPaymentId) === true)
        {
            $attributes[Netbanking\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_REF_NO];
        }

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Netbanking\Entity::STATUS] = $content[ResponseFields::STATUS_CODE];
        }

        // If RID exists and status is registration success, set token related attributes here
        if ((empty($content[ResponseFields::MANDATE_NUMBER]) === false) and
            ($content[ResponseFields::STATUS_CODE] === StatusCode::SUCCESS))
        {
            $recurringData = [
                Netbanking\Entity::SI_TOKEN  => $content[ResponseFields::MANDATE_NUMBER],
                Netbanking\Entity::SI_STATUS => $content[ResponseFields::STATUS_CODE],
            ];

            $attributes = array_merge($attributes, $recurringData);
        }

        return $attributes;
    }

    protected function sendEmandatePaymentVerifyRequest(Verify $verify)
    {
        if ($verify->input['payment']['recurring_type'] === Payment\RecurringType::AUTO)
        {
            throw new PaymentVerificationException([], $verify, Payment\Verify\Action::FINISH);
        }

        $content = $this->getEmandatePaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $verify->input['payment'][Payment\Entity::ID],
                'request'    => $request,
            ]
        );

        $request['options']['hooks'] = $this->getRequestHooks();

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'     => $this->gateway,
                'payment_id'  => $verify->input['payment'][Payment\Entity::ID],
                'response'    => $response->body,
                'status_code' => $response->status_code,
            ]
        );

        $verify->verifyResponseContent = $this->getEmandateDecryptedData($response->body, $verify->input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'verify_response_content' => $verify->verifyResponseContent,
                'payment_id'              => $verify->input['payment']['id'],
            ]);
    }
    //---------------Verify request helpers end-----------------------

    //----------------------General helpers---------------------------
    public function getEmandateEncryptedData(array $data): string
    {
        return base64_encode(
            $this->getEmandateEncryptor()->encryptString(
                urldecode(http_build_query($data))
            )
        );
    }

    public function getEmandateDecryptedData(string $body, array $input = []): array
    {
        $decrypted = $this->getEmandateEncryptor()->decryptString(base64_decode($body));

        parse_str($decrypted, $output);

        if (empty($output) === true)
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
                null,
                null,
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment'][Payment\Entity::ID]
                ]);
        }

        return $output;
    }

    protected function getEmandateEncryptor()
    {
        $aes = new AESCrypto(AES::MODE_ECB, $this->getEmandateSecret());

        return $aes;
    }

    protected function getEmandateEntityAttributes(array $input): array
    {
        return [
            RequestFields::AMOUNT          => $this->formatAmount($input['payment']['amount']),
            RequestFields::REQUEST_ID      => $input['payment'][Payment\Entity::ID],
        ];
    }

    protected function getHashOfString($str) : string
    {
        return hash(HashAlgo::SHA256, $str);
    }

    /**
     * This method validates that any callback checksum matches that of the
     * expected value based on the algorithm that the bank have shared with us
     *
     * @param array $content
     * @throws GatewayErrorException
     */
    protected function validateCallbackChecksum(array $content)
    {
        $actualChecksum = $this->getChecksum($content);

        if ($actualChecksum !== $content[ResponseFields::CHECKSUM])
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
                null,
                null,
                [
                    'content'    => $content,
                    'payment_id' => $this->input['payment'][Payment\Entity::ID],
                    'action'     => $this->action,
                    'gateway'    => $this->gateway,
                ]);
        }
    }

    /**
     * Calculates the checksum attribute for authorize and verify calls
     *
     * @param array $data
     * @return mixed
     */
    protected function getChecksum(array $data) : string
    {
        $arrayToBeHashed = [
            $data[RequestFields::CORP_ID],
            $data[RequestFields::REQUEST_ID],
            $data[RequestFields::CUSTOMER_REF_NO],
            $data[RequestFields::AMOUNT] ?? null,
            $this->getEmandateChecksumSecret(),
        ];

        // Amount is not part of the hash for verify
        if ($this->action === Action::VERIFY)
        {
            unset($arrayToBeHashed[3]);
        }

        $str = implode('', $arrayToBeHashed);

        return $this->getHashOfString($str);
    }

    protected function getEmandateSecret() : string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_hash_secret_encrec'];
        }

        return $this->getEmandateLiveSecret();
    }

    protected function getEmandateLiveSecret()
    {
        return $this->input['terminal']['gateway_secure_secret'];
    }

    protected function getEmandateChecksumSecret() : string
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_hash_secret_rec'];
        }

        return $this->input['terminal']['gateway_terminal_password'];
    }

    protected function getType()
    {
        if ($this->mode === Mode::TEST)
        {
            return Constants::TYPE_TEST;
        }

        return Constants::TYPE_LIVE;
    }

    public function getEmandateMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id_rec'];
        }

        return $this->getLiveMerchantId();
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_REFERER, null);
    }
}
