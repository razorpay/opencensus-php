<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Base\Entity as GatewayEntity;
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
        $ppiArray = [
            $input['payment'][Payment\Entity::ID],
            Constants::PPI_AMOUNT_TYPE,
            Frequency::ADHOC,
            $input['token'][Token\Entity::ACCOUNT_NUMBER],
            Carbon::now(Timezone::IST)->format('m/d/Y'),
            Carbon::now(Timezone::IST)->addYears(30)->format('m/d/Y'),
            $this->formatAmount($input['payment']['amount']),
        ];

        $data = [
            RequestFields::VERSION         => Constants::VERSION,
            RequestFields::CORP_ID         => $this->getEmandateMerchantId(),
            RequestFields::TYPE            => Constants::TYPE,
            RequestFields::REQUEST_ID      => $input['payment'][Payment\Entity::ID],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId(),
            RequestFields::CURRENCY        => Currency::INR,
            RequestFields::AMOUNT          => $this->formatAmount($input['payment']['amount']),
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
            ]
        );

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

    protected function handleEmandateResponse(array $input, array $content): GatewayEntity
    {
        $content = $this->getEmandateDecryptedData($content[ResponseFields::DATA], $input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'decrypted_response' => $content,
                'payment_id'         => $input['payment'][Payment\Entity::ID]
            ]
        );

        $this->validateCallbackChecksum($content);

        $this->assertPaymentId(
            $input['payment'][Payment\Entity::ID],
            $content[ResponseFields::REQUEST_ID]
        );

        $this->assertAmount(
            $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
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
        return [
            Netbanking\Entity::RECEIVED        => true,
            Netbanking\Entity::STATUS          => $content[ResponseFields::STATUS_CODE],
            Netbanking\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_REF_NO],
            Netbanking\Entity::REFERENCE1      => $content[ResponseFields::MANDATE_NUMBER],

            // SI registration specific callback attributes
            Netbanking\Entity::SI_TOKEN        => $content[ResponseFields::CUSTOMER_REF_NO],
            Netbanking\Entity::SI_STATUS       => StatusCode::getEmandateStatus(
                                                      $content[ResponseFields::MANDATE_NUMBER]
                                                  ),
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
            $errorCode = StatusCode::getErrorCodeMap($statusCode);

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
        $recurringStatus = null;

        $gatewaySiStatus = $gatewayPayment->getReference1();

        if (StatusCode::isEmandateRegistrationSuccess($gatewaySiStatus) === true)
        {
            $recurringStatus = Token\RecurringStatus::CONFIRMED;
        }
        else
        {
            $recurringStatus = Token\RecurringStatus::REJECTED;
        }

        $recurringFailureReason = $gatewayPayment->getSIMessage();

        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1         => $gatewayPayment->getBankPaymentId(),
            ],
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
            RequestFields::TYPE            => Constants::TYPE,
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

        $this->setRecurrringVerifyAmountMismatch($verify);
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

    protected function setRecurrringVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input['payment'][Payment\Entity::AMOUNT]);

        $verify->amountMismatch =
            ($paymentAmount !== $verify->verifyResponseContent[ResponseFields::AMOUNT]);
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

        if (empty($bankPaymentId) === true)
        {
            $attributes[Netbanking\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_REF_NO];
        }

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Netbanking\Entity::STATUS] = $content[ResponseFields::STATUS_CODE];
        }

        return $attributes ?? [];
    }

    protected function sendEmandatePaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getEmandatePaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->getEmandateDecryptedData($response->body, $verify->input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response_body' => $response->body,
                'content'       => $verify->verifyResponseContent,
                'payment_id'    => $verify->input['payment']['id'],
                'status_code'   => $response->status_code
            ]);
    }
    //---------------Verify request helpers end-----------------------

    //----------------------General helpers---------------------------
    public function getEmandateEncryptedData(array $data): string
    {
        return base64_encode(
            $this->getEncryptor()->encryptString(
                urldecode(http_build_query($data))
            )
        );
    }

    public function getEmandateDecryptedData(string $body, array $input = []): array
    {
        $decrypted = $this->getEncryptor()->decryptString(base64_decode($body));

        parse_str($decrypted, $output);

        if (empty($output) === true)
        {
            throw new GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
                null,
                null,
                [
                    'encrypted_string' => $body,
                    'payment_id'       => $input['payment'][Payment\Entity::ID]
                ]
            );
        }

        return $output;
    }

    protected function getEncryptor()
    {
        $aes = new AESCrypto(AES::MODE_ECB, $this->getRecSecret(true));

        return $aes;
    }

    protected function getEmandateEntityAttributes(array $input): array
    {
        return [
            RequestFields::AMOUNT          => $this->formatAmount($input['payment']['amount']),
            RequestFields::REQUEST_ID      => $input['payment'][Payment\Entity::ID],
            RequestFields::CUSTOMER_REF_NO => $input['token']->getId()
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
                ]
            );
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
            $this->getRecSecret(),
        ];

        // Amount is not part of the hash for verify
        if ($this->action === Action::VERIFY)
        {
            unset($arrayToBeHashed[3]);
        }

        return $this->generateHash($arrayToBeHashed);
    }

    protected function getRecSecret(bool $encryption = false) : string
    {
        if ($this->mode === Mode::TEST)
        {
            $key = (($encryption === true) ? 'test_hash_secret_encrec' : 'test_hash_secret_rec');

            return $this->config[$key];
        }

        return (
                ($encryption === true) ?
                ($this->input['terminal']->getGatewaySecureSecretAttribute()) :
                ($this->input['terminal']->getGatewayTerminalPasswordAttribute())
        );
    }

    public function getEmandateMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id_rec'];
        }

        return $this->getLiveMerchantId();
    }
}
