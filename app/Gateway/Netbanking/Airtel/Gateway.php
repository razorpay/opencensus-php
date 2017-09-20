<?php

namespace RZP\Gateway\Netbanking\Airtel;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_airtel';

    protected $bank = 'airtel';

    protected $map = [
        AuthFields::AMOUNT => Base\Entity::AMOUNT
    ];

    const REVERSAL     = 'ECOMM_REVERSAL';

    const INQUIRY      = 'ECOMM_INQ';

    const TIME_FORMAT  = 'dmYhis';

    const ACTION_ERROR = 'Action not set correctly';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $contentToSave = [AuthFields::AMOUNT => $this->getFormattedAmount($input)];

        $this->createGatewayPaymentEntity($contentToSave);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->assertPaymentId($input['payment']['id'],
                               $content[AuthFields::TRANSACTION_REFERENCE_NO]);

        $this->traceGatewayPaymentResponse($content, $input);

        $this->verifySecureHash($content);

        $gatewayPayment = $this->saveCallbackContent($input, $content);

        $this->checkActionStatus($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->processRefundResponse($response, $input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response' => $response->body
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;

        $this->verifySecureHash($responseArray);

        $this->checkActionStatus($responseArray);
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

    protected function getAuthorizeRequestData($input)
    {
        $data = [
            AuthFields::MERCHANT_ID              => $this->getMerchantId(),
            AuthFields::TRANSACTION_REFERENCE_NO => $input['payment']['id'],
            AuthFields::AMOUNT                   => $this->getFormattedAmount($input),
            AuthFields::DATE                     => $this->getFormattedDate($input),
            AuthFields::SERVICE                  => PaymentMethod::NETBANKING,
            AuthFields::SUCCESS_URL              => $input['callbackUrl'],
            AuthFields::FAILURE_URL              => $input['callbackUrl'],
            AuthFields::CURRENCY                 => Currency::INR,
            AuthFields::CUSTOMER_MOBILE          => $input['payment']['contact'],
            AuthFields::CUSTOMER_EMAIL           => $input['payment']['email'],
        ];

        $data[AuthFields::HASH] = $this->getHashOfArray($data, 'request');

        return $data;
    }

    protected function saveCallbackContent($input, $content)
    {
        $attributes = $this->getCallbackAttributes($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function getCallbackAttributes($content)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[AuthFields::STATUS],
            Base\Entity::MERCHANT_CODE   => $content[AuthFields::CODE],
            Base\Entity::ERROR_MESSAGE   => $content[AuthFields::MSG],
            Base\Entity::BANK_PAYMENT_ID => $content[AuthFields::TRANSACTION_ID] ?? null,
            Base\Entity::DATE            => $content[AuthFields::TRANSACTION_DATE] ?? null
        ];

        return $attributes;
    }

    protected function getVerifyRequestData($verify)
    {
        $input = $verify->input;

        $data = [
            VerifyFields::SESSION_ID               => uniqid(),
            VerifyFields::TRANSACTION_REFERENCE_NO => $input['payment']['id'],
            VerifyFields::TRANSACTION_DATE         => $this->getFormattedDate($input),
            VerifyFields::REQUEST                  => self::INQUIRY,
            VerifyFields::MERCHANT_ID              => $this->getMerchantId(),
            VerifyFields::AMOUNT                   => (string) $this->getFormattedAmount($input)
        ];

        $data[VerifyFields::HASH] = $this->getHashOfArray($data, 'request');

        return json_encode($data);
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
        }
        else
        {
            $merchantCode = ErrorCodes::RANDOM_ERROR;
        }

        $message = ErrorCodes::getErrorCodeDescription($merchantCode);

        $contentToSave = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $authContent[VerifyFields::STATUS],
            Base\Entity::MERCHANT_CODE   => $merchantCode,
            Base\Entity::ERROR_MESSAGE   => $message,
            Base\Entity::BANK_PAYMENT_ID => $authContent[VerifyFields::TRANSACTION_ID] ?? null,
            Base\Entity::DATE            => $authContent[VerifyFields::TRANSACTION_DATE] ?? null
        ];

        return $contentToSave;
    }

    protected function getAuthContentFromVerifyResponse($verify, $response)
    {
        $bankPaymentId = $verify->payment->getBankPaymentId();

        if (empty($response[VerifyFields::TRANSACTION]) === true)
        {
            $authContent = $this->mockFailedVerifyTransaction();
        }
        else
        {
            foreach ($response[VerifyFields::TRANSACTION] as $transaction)
            {
                if ($transaction[VerifyFields::TRANSACTION_ID] === $bankPaymentId)
                {
                    return $transaction;
                }
            }

            // Saved bank_payment_id from Auth not found in Verify Response
            // We then mock a failed verify response, and return that transaction
            $this->trace->error(TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                [
                    'expected_txnid'  => $bankPaymentId,
                    'verify_response' => $response
                ]);

            if ($bankPaymentId !== null)
            {
                $authContent = $this->mockFailedVerifyTransaction();
            }
            else
            {
                // If bank payment id is null when authorize response wasn't saved
                // we set it to transaction in this case so that the verify response is saved
                $authContent = $transaction;
            }
        }

        return $authContent;
    }

    /*
     * Mocking a failed response from verify
     */
    protected function mockFailedVerifyTransaction()
    {
        $transaction = [
            VerifyFields::STATUS             => Status::FAILURE,
        ];

        return $transaction;
    }

    protected function getRefundRequestData($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $request = [
            RefundFields::SESSION_ID        => uniqid(),
            RefundFields::TRANSACTION_ID    => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
            RefundFields::TRANSACTION_DATE  => $this->getFormattedDate($input),
            RefundFields::MERCHANT_ID       => $this->getMerchantId(),
            RefundFields::REQUEST           => self::REVERSAL,
            RefundFields::AMOUNT            => (string) $this->getFormattedAmount($input),
        ];

        $hash = $this->getHashOfArray($request, 'request');

        $request[RefundFields::HASH] = $hash;

        return json_encode($request);
    }

    protected function processRefundResponse($response, $input)
    {
        $content = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            ['response' => $content]);

        $responseArray = $this->jsonToArray($content);

        $this->verifySecureHash($responseArray);

        $attributes = $this->getRefundAttributes($responseArray, $input);

        $this->createGatewayPaymentEntity($attributes);

        $this->checkActionStatus($responseArray);
    }

    protected function getRefundAttributes($response, $input)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::AMOUNT          => $input['refund']['amount'] / 100,
            Base\Entity::BANK_PAYMENT_ID => $response[RefundFields::TRANSACTION_ID],
            Base\Entity::STATUS          => $response[RefundFields::STATUS],
            Base\Entity::REFUND_ID       => $input['refund']['id'],
            Base\Entity::DATE            => $response[RefundFields::TRANSACTION_DATE],
            Base\Entity::ERROR_MESSAGE   => $response[RefundFields::MESSAGE_TEXT],
            Base\Entity::MERCHANT_CODE   => $response[RefundFields::CODE],
        ];

        return $attributes;
    }

    protected function getFormattedAmount($input)
    {
        switch ($this->action)
        {
            case Action::REFUND:
                $field = 'refund';
                break;

            default:
                $field = 'payment';
                break;
        }

        return number_format($input[$field]['amount'] / 100, 2, '.', '');
    }

    protected function getFormattedDate($input)
    {
        $date = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                                            ->format(self::TIME_FORMAT);

        return $date;
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getHashValueFromContent(array $content)
    {
        switch ($this->action)
        {
            case Action::CALLBACK:
                return $content[AuthFields::HASH];

            case Action::VERIFY:
                return $content[VerifyFields::HASH];

            case Action::REFUND:
                return $content[RefundFields::HASH];

            default:
                throw new Exception\RuntimeException(self::ACTION_ERROR);
        }
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getHashOfArray($content, $type = 'response')
    {
        $hashString = $this->getStringToHash($content, '#', $type);

        return $this->getHashOfString($hashString);
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
                throw new Exception\RuntimeException(self::ACTION_ERROR);
        }

        return implode($glue, $data);
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    public function getHashOfString($string)
    {
        return hash(HashAlgo::SHA512, $string);
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

    protected function checkActionStatus($content)
    {
        switch($this->action)
        {
            case Action::CALLBACK:
                $statusField = AuthFields::CODE;
                break;

            case Action::REFUND:
                $statusField = RefundFields::ERROR_CODE;
                break;

            case Action::VERIFY:
                $statusField = VerifyFields::ERROR_CODE;
                break;

            default:
                throw new Exception\RuntimeException(self::ACTION_ERROR);
        }

        if ((isset($content[$statusField]) === false) or
            (($content[$statusField] !== ErrorCodes::SUCCESS) and
            ($content[$statusField] !== ErrorCodes::TRANSACTION_NOT_PRESENT)))
        {
            $this->handleRequestError($content, $statusField);
        }
    }

    protected function handleRequestError($content, $statusField)
    {
        $errorDescription = ErrorCodes::getErrorCodeDescription(
                $content[$statusField]);

        $errorCode = ErrorCodes::getErrorCodeMap(
            $content[$statusField]);

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException($errorCode, $statusField, $errorDescription);
    }

    public function getMerchantId()
    {
        $mid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    protected function getMappedAttributes($attributes)
    {
        if ($this->action === Action::AUTHORIZE)
        {
            $attr = [];

            $map = $this->map;

            foreach ($attributes as $key => $value)
            {
                if (isset($map[$key]))
                {
                    $newKey = $map[$key];
                    $attr[$newKey] = $value;
                }
            }

            return $attr;
        }

        return $attributes;
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }
}
