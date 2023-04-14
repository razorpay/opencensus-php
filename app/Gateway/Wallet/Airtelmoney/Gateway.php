<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use RZP\Models\Payment\Refund;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment as PaymentModel;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_airtelmoney';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $authorizeAttributes = $this->getAuthorizeAttributes($input);

        $this->createGatewayPaymentEntity($authorizeAttributes, Action::AUTHORIZE);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input[Entity::PAYMENT][Payment\Entity::ID],
                               $content[AuthFields::TRANSACTION_REFERENCE_NO]);

        $this->verifySecureHash($content);

        $expectedAmount = number_format($input[Entity::PAYMENT][Payment\Entity::AMOUNT] / 100, 2, '.', '');

        $actualAmount = number_format($content[AuthFields::TRANSACTION_AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayPayment = $this->saveCallbackContent($input, $content);

        $this->checkActionStatus($content);

        return $this->getCallbackResponseData($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, [
            'gateway' => 'wallet_airtelmoney',
            'payment_id' => $input['payment']['id'],
            'request' => $request
        ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, [
            'gateway' => 'wallet_airtelmoney',
            'payment_id' => $input['payment']['id'],
            'response' => $response->body
        ]);

        $this->processRefundResponse($response, $input);

        return [
            PaymentModel\Gateway::GATEWAY_RESPONSE => json_encode($response->body),
            PaymentModel\Gateway::GATEWAY_KEYS     => $this->getGatewayData($this->jsonToArray($response->body)),
        ];
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false) {
            return [
                RefundFields::MERCHANT_ID  => $refundFields[RefundFields::MERCHANT_ID] ?? null,
                RefundFields::STATUS       => $refundFields[RefundFields::STATUS] ?? null,
                RefundFields::ERROR_CODE   => $refundFields[RefundFields::ERROR_CODE] ?? null,
                RefundFields::MESSAGE_TEXT => $refundFields[RefundFields::MESSAGE_TEXT] ?? null,
                RefundFields::CODE         => $refundFields[RefundFields::CODE] ?? null,
                RefundFields::TRANSACTION_ID => $refundFields[RefundFields::TRANSACTION_ID] ?? null,
            ];
        }

        return [];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }


    // for barricade flow return gateway verify response
    public function verifyGateway(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify = $this->sendPaymentVerifyRequestGateway($verify);

        return $verify->getDataToTrace();
    }

    public function sendPaymentVerifyRequestGateway($verify)
    {
        $content = $this->getVerifyRequestContent($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, [
            'gateway'    => 'wallet_airtelmoney',
            'payment_id' => $verify->input['payment']['id'],
            'request'    => $request
        ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => 'wallet_airtelmoney',
                'payment_id' => $verify->input['payment']['id'],
                'response'   => $response->body
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $responseArray;

        if ($responseArray[VerifyFields::ERROR_CODE] === ErrorCodes::SUCCESS)
        {
            $this->verifySecureHash($responseArray);
        }

        $this->checkActionStatus($responseArray);

        return $verify;
    }

    protected function saveCallbackContent($input, $content)
    {
        $attributes = $this->getCallbackAttributes($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input[Entity::PAYMENT][Payment\Entity::ID], Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getCallbackAttributes($content):array
    {
        $attributes = [
            Base\Entity::STATUS_CODE         => $content[AuthFields::STATUS],
            Base\Entity::ERROR_MESSAGE       => $content[AuthFields::MSG],
            Base\Entity::PAYMENT_ID          => $content[AuthFields::TRANSACTION_REFERENCE_NO],
            Base\Entity::GATEWAY_MERCHANT_ID => $content[AuthFields::MERCHANT_ID],
            Base\Entity::RECEIVED            => true,
        ];

        if ($content[AuthFields::CODE] === ErrorCodes::SUCCESS)
        {
            $attributes[Base\Entity::GATEWAY_PAYMENT_ID]  = $content[AuthFields::TRANSACTION_ID];
        }

        return $attributes;
    }

    protected function verifySecureHash(array $content)
    {
        switch ($this->action)
        {
            case Action::CALLBACK:
                $actual = $content[AuthFields::HASH];
                unset($content[AuthFields::HASH]);
                break;

            case Action::REFUND:
                $actual = $content[RefundFields::HASH];
                unset($content[RefundFields::HASH]);
                break;

            case Action::VERIFY:
                $actual = $content[VerifyFields::HASH];
                unset($content[VerifyFields::HASH]);
                break;

            default:
                throw new Exception\RuntimeException(Constants::ACTION_ERROR);
        }

        $this->checkIfHashEmptyOrUndefined($actual, $content);

        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function processRefundResponse($response, $input)
    {
        $content = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            ['response' => $content]);

        $responseArray = $this->jsonToArray($content);

        if ($responseArray[RefundFields::STATUS] !== Status::FAILED)
        {
            $this->verifySecureHash($responseArray);
        }

        $attributes = $this->getRefundAttributes($responseArray, $input);

        $this->createGatewayPaymentEntity($attributes, Action::REFUND);

        $this->checkActionStatus($responseArray);
    }

    protected function getRefundAttributes($response, $input)
    {
        $attributes = [
            Base\Entity::STATUS_CODE         => $response[RefundFields::STATUS],
            Base\Entity::AMOUNT              => $input[Entity::REFUND][Refund\Entity::AMOUNT],
            Base\Entity::REFUND_ID           => $input[Entity::REFUND][Refund\Entity::ID],
            Base\Entity::GATEWAY_MERCHANT_ID => $response[RefundFields::MERCHANT_ID],
            Base\Entity::ERROR_MESSAGE       => $response[RefundFields::MESSAGE_TEXT],
            Base\Entity::EMAIL               => $input[Entity::PAYMENT][Payment\Entity::EMAIL],
            Base\Entity::CONTACT             => $input[Entity::PAYMENT][Payment\Entity::CONTACT],
            Base\Entity::RECEIVED            => true,
        ];

        if ($response[RefundFields::STATUS] === Status::SUCCESS)
        {
            $attributes[Base\Entity::GATEWAY_PAYMENT_ID] = $response[RefundFields::TRANSACTION_ID];
            $attributes[Base\Entity::DATE]               = $response[RefundFields::TRANSACTION_DATE];
        }

        return $attributes;
    }

    protected function checkActionStatus($content)
    {
        switch ($this->action)
        {
            case Action::CALLBACK:
                $statusField = AuthFields::CODE;
                break;

            case Action::REFUND:
                $statusField = RefundFields::CODE;
                break;

            case Action::VERIFY:
                $statusField = VerifyFields::CODE;
                break;

            default:
                throw new Exception\RuntimeException(Constants::ACTION_ERROR);
        }

        if (($content[$statusField] !== ErrorCodes::SUCCESS) and
            ($content[$statusField] !== ErrorCodes::REFUND_VERIFY_SUCCESS))
        {
            $this->handleRequestError($content, $statusField);
        }
    }

    protected function handleRequestError($content, $statusField)
    {
        if ( $statusField !== AuthFields::CODE)
        {
            $statusField = ErrorCodes::REFUND_VERIFY_ERROR;
        }

        $errorDescription = ErrorCodes::getErrorCodeDescription($content[$statusField]);

        $errorCode = ErrorCodes::getErrorCodeMap($content[$statusField]);

        // Payment fails, throw exceptions(
        throw new Exception\GatewayErrorException(
            $errorCode,
            $content[$statusField],
            $errorDescription,
            [
                PaymentGateway::GATEWAY_RESPONSE => json_encode($content),
                PaymentGateway::GATEWAY_KEYS     => $this->getGatewayData($content)
            ]
        );
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestContent($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, [
            'gateway'    => 'wallet_airtelmoney',
            'payment_id' => $verify->input['payment']['id'],
            'request'    => $request
        ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => 'wallet_airtelmoney',
                'payment_id' => $verify->input['payment']['id'],
                'response'   => $response->body
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponse = $response;

        $verify->verifyResponseBody = $response->body;

        $verify->verifyResponseContent = $responseArray;

        if ($responseArray[VerifyFields::ERROR_CODE] === ErrorCodes::SUCCESS)
        {
            $this->verifySecureHash($responseArray);
        }

        $this->checkActionStatus($responseArray);
    }

    protected function getVerifyRequestContent($verify)
    {
        $input = $verify->input;

        $date = $this->getFormattedDate($input[Entity::PAYMENT][Payment\Entity::CREATED_AT]);

        $data = [
            VerifyFields::SESSION_ID               => uniqid(),
            VerifyFields::TRANSACTION_REFERENCE_NO => $input[Entity::PAYMENT][Payment\Entity::ID],
            VerifyFields::TRANSACTION_DATE         => $date,
            VerifyFields::REQUEST                  => Constants::INQUIRY,
            VerifyFields::MERCHANT_ID              => $this->getMerchantId2(),
            VerifyFields::AMOUNT                   => (string) $this->getFormattedAmount($input[Entity::PAYMENT][Payment\Entity::AMOUNT])
        ];

        $data[VerifyFields::HASH] = $this->getHashOfArray($data, 'request');

        return json_encode($data);
    }

    protected function verifyPayment($verify)
    {
        $response = $verify->verifyResponseContent;

        $authContent = $this->getAuthContentFromVerifyResponse($verify, $response);

        $status = $this->getVerifyMatchStatus($verify, $authContent);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContent($verify, $authContent);
    }

    protected function saveVerifyContent($verify, $authContent)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $attributes = $this->getVerifyAttributes($authContent);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributes($authContent)
    {
        if ($authContent[VerifyFields::STATUS] === Status::SUCCESS)
        {
            $merchantCode = ErrorCodes::SUCCESS;
            $statusCode   = Status::SUCCESS;
        }
        else
        {
            $merchantCode = ErrorCodes::RANDOM_ERROR;
            $statusCode   = Status::FAILED;
        }

        $message = ErrorCodes::getErrorCodeDescription($merchantCode);

        $contentToSave = [
            Base\Entity::RECEIVED           => true,
            Base\Entity::GATEWAY_PAYMENT_ID => $authContent[VerifyFields::TRANSACTION_ID] ?? null,
            Base\Entity::DATE               => $authContent[VerifyFields::TRANSACTION_DATE] ?? null,
            Base\Entity::STATUS_CODE        => $statusCode,
            Base\Entity::ERROR_MESSAGE      => $message,
        ];

        return $contentToSave;
    }

    protected function getAuthContentFromVerifyResponse($verify, array $response):array
    {
        $gatewayPaymentId = $verify->payment->getGatewayPaymentId();

        $expectedAmount = number_format($verify->payment->getAmount() / 100, 2, '.', '');

        if (empty($response[VerifyFields::TRANSACTION]) === true)
        {
            $authContent = $this->mockFailedVerifyResponse();
        }
        else
        {
            foreach ($response[VerifyFields::TRANSACTION] as $transaction)
            {
                if ($transaction[VerifyFields::TRANSACTION_ID] === $gatewayPaymentId)
                {
                    $this->assertAmount($expectedAmount, $transaction[VerifyFields::TRANSACTION_AMOUNT]);

                    return $transaction;
                }
            }

            $this->trace->error(TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                                [
                                    'expected_txnid'  => $gatewayPaymentId,
                                    'verify_response' => $response
                                ]);

            if ($gatewayPaymentId !== null)
            {
                $authContent = $this->mockFailedVerifyResponse();
            }
            else
            {
                $authContent = $transaction;
            }
        }

        return $authContent;
    }

    /*
     * Mocking a failed response from verify
     */
    protected function mockFailedVerifyResponse():array
    {
        return [
            VerifyFields::STATUS => Status::FAILED,
        ];
    }

    protected function getVerifyMatchStatus($verify, $authContent)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $authContent);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess($verify, $authContent)
    {
        $verify->gatewaySuccess = false;

        if ((isset($authContent[VerifyFields::STATUS]) === true) and
            ($authContent[VerifyFields::STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    public function verifyRefund(array $input)
    {
        $scroogeResponse = new GatewayBase\ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
                                ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                                ->toArray();
}

    public function getMerchantId2()
    {
        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId2();

            return $mid;
        }

        return $this->getLiveMerchantId2();
    }

    protected function getEndMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_end_merchant_id'];
        }

        return $this->getLiveMerchantId();

    }

    protected function getHashOfString($hashString)
    {
        return hash(HashAlgo::SHA512, $hashString, false);
    }

    protected function getAuthorizeRequestData($input)
    {
        $data = [
            AuthFields::MERCHANT_ID              => $this->getMerchantId2(),
            AuthFields::TRANSACTION_REFERENCE_NO => $input[Entity::PAYMENT]['id'],
            AuthFields::AMOUNT                   => $this->getFormattedAmount($input[Entity::PAYMENT]['amount']),
            AuthFields::DATE                     => $this->getFormattedDate($input[Entity::PAYMENT]['created_at']),
            AuthFields::SERVICE                  => Constants::SERVICE_TYPE,
            AuthFields::SUCCESS_URL              => $input['callbackUrl'],
            AuthFields::FAILURE_URL              => $input['callbackUrl'],
            AuthFields::CURRENCY                 => Currency::INR,
            AuthFields::END_MERCHANT_ID          => $this->getEndMerchantId(),
            AuthFields::CUSTOMER_MOBILE          => $this->input[Entity::PAYMENT]['contact'],
        ];

        $data[AuthFields::HASH] = $this->getHashOfArray($data, 'request');

        return $data;
    }

    protected function getFormattedDate($input):string
    {
        $date = Carbon::now(Timezone::IST)->setTimestamp($input)->format(Constants::TIME_FORMAT);

        return $date;
    }

    /*
    * Overrides the default method contained in Base/Gateway
    */
    protected function getHashOfArray($content, $type = 'response')
    {
        $hashString = $this->getStringToHash($content, '#', $type);

        return $this->getHashOfString($hashString);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getRefundRequestContent(array $input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input[Entity::PAYMENT]['id']);

        $date = $this->getFormattedDate($wallet['reference1']);

        $request = [
            RefundFields::SESSION_ID       => UniqueIdEntity::generateUniqueId(),
            RefundFields::TRANSACTION_ID   => $wallet[Base\Entity::GATEWAY_PAYMENT_ID],
            RefundFields::TRANSACTION_DATE => $date,
            RefundFields::MERCHANT_ID      => $this->getMerchantId2(),
            RefundFields::REQUEST          => Constants::REVERSAL,
            RefundFields::AMOUNT           => (string) $this->getFormattedAmount($input[Entity::REFUND][Refund\Entity::AMOUNT]),
        ];

        $hash = $this->getHashOfArray($request, 'request');

        $request[RefundFields::HASH] = $hash;

        return json_encode($request);
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getStringToHash($content, $glue = '#', $type = 'response')
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $data = $this->getAuthorizeRequestHashArray($content);
                break;

            case Action::CALLBACK:
                $data = $this->getCallbackResponseHashArray($content);
                break;

            case Action::VERIFY:
                $data = ($type === 'request') ? $this->getVerifyRequestHashArray($content) :
                    $this->getVerifyResponseHashArray($content);
                break;

            case Action::REFUND:
                $data = ($type === 'request') ? $this->getRefundRequestHashArray($content) :
                    $this->getRefundResponseHashArray($content);
                break;

            default:
                throw new Exception\RuntimeException(Constants::ACTION_ERROR);
        }

        return implode($glue, $data);
    }

    protected function getRefundRequestHashArray($data)
    {
        $hashArray = [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_DATE],
            $this->getSecret()
        ];

        return $hashArray;
    }

    protected function getVerifyResponseHashArray($content)
    {
        $hashArray = [
            $content[VerifyFields::MERCHANT_ID],
            json_encode($content[VerifyFields::TRANSACTION]),
            $content[VerifyFields::ERROR_CODE],
            $this->getSecret()
        ];

        return $hashArray;
    }

    protected function getAuthorizeRequestHashArray($content)
    {
        $hashArray = [
            $content[AuthFields::MERCHANT_ID],
            $content[AuthFields::TRANSACTION_REFERENCE_NO],
            $content[AuthFields::AMOUNT],
            $content[AuthFields::DATE],
            $content[AuthFields::SERVICE],
            $this->getSecret()
        ];

        return $hashArray;
    }

    protected function getCallbackResponseHashArray($content)
    {
        if ($content[AuthFields::CODE] === ErrorCodes::SUCCESS)
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $content[AuthFields::TRANSACTION_DATE],
                $this->getSecret()
            ];
        }
        else
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $this->getSecret(),
                $content[AuthFields::CODE],
                $content[AuthFields::STATUS]
            ];
        }

        return $hashArray;
    }

    protected function getVerifyRequestHashArray($data)
    {
        $hashArray = [
            $data[VerifyFields::MERCHANT_ID],
            $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            $data[VerifyFields::AMOUNT],
            $data[VerifyFields::TRANSACTION_DATE],
            $this->getSecret()
        ];

        return $hashArray;
    }

    protected function getRefundResponseHashArray($data)
    {
        $hashArray = [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::ERROR_CODE],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::TRANSACTION_DATE],
            $data[RefundFields::STATUS],
            $this->getSecret()
        ];

        return $hashArray;
    }

    protected function getAuthorizeAttributes($input)
    {
        $attributes = [
            Base\Entity::AMOUNT         => $input[Entity::PAYMENT][Payment\Entity::AMOUNT],
            Base\Entity::EMAIL          => $input[Entity::PAYMENT][Payment\Entity::EMAIL],
            Base\Entity::CONTACT        => $input[Entity::PAYMENT][Payment\Entity::CONTACT],
            Base\Entity::MERCHANT_ID    => $input[Entity::PAYMENT][Payment\Entity::MERCHANT_ID],
            Base\Entity::PAYMENT_ID     => $input[Entity::PAYMENT][Payment\Entity::ID],
        ];

        return $attributes;
    }

    /*
     * Overrides the default method contained in Base/Gateway
     * since the map will be empty so returning $attributes
     */
    protected function getMappedAttributes($attributes)
    {
        return $attributes;
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getLiveMerchantId2()
    {
        return $this->config['live_merchant_id'];
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::AUTHORIZE);

        // Return true if already authorized on gateway
        if (($gatewayPayment->getGatewayPaymentId() !== null) and
            ($gatewayPayment->getStatusCode() === Status::SUCCESS))
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

        $contentToSave = [
            WalletEntity::GATEWAY_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
            WalletEntity::STATUS_CODE        => Status::SUCCESS,
        ];

        $gatewayPayment->fill($contentToSave);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function checkIfHashEmptyOrUndefined($actual, $content)
    {
        if (($actual === Constants::UNDEFINED) or (empty($actual) === true))
        {
            //When the user cancels the transaction ,the hash is provided as undefined from the bank.
            $this->checkActionStatus($content);
            // Checks if the bank sends hash as undefined for a successful payment
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
        }
    }
}
