<?php

namespace RZP\Gateway\Netbanking\Airtel;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Payment\Currency;
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

    const REVERSAL               = 'ECOMM_REVERSAL';

    const TIME_FORMAT            = 'dmYhis';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->createAuthorizeRequestData($input);

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

        $this->traceGatewayPaymentResponse($content, $input);

        $this->verifySecureHash($content);

        $attributes = $this->getCallbackAttributes($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        $this->checkActionStatus($content);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $this->checkRefundResponse($response, $input);
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

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $responseArray);

        $this->verifySecureHash($responseArray);

        $this->checkActionStatus($responseArray);
    }

    public function verifyPayment($verify)
    {
        $response = $verify->verifyResponseContent;

        $authContent = $this->getAuthContentFromVerifyResponse($verify, $response);

        $status = $this->getVerifyMatchStatus($verify, $authContent);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($verify, $response, $authContent);
    }

    protected function getVerifyMatchStatus($verify, $response)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $response);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkApiSuccess($verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess($verify, $response)
    {
        $verify->gatewaySuccess = false;

        if ((isset($response[VerifyFields::STATUS]) === true) and
            ($response[VerifyFields::STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function createAuthorizeRequestData($input)
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

    protected function getCallbackAttributes($content)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[AuthFields::STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[AuthFields::TRANSACTION_ID],
            Base\Entity::MERCHANT_CODE   => $content[AuthFields::CODE],
            Base\Entity::ERROR_MESSAGE   => $content[AuthFields::MSG],
            Base\Entity::DATE            => $content[AuthFields::TRANSACTION_DATE],
        ];

        return $attributes;
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;

        $amount = (string) $this->getFormattedAmount($input);

        $data = [
            VerifyFields::SESSION_ID               => uniqid(),
            VerifyFields::TRANSACTION_REFERENCE_NO => $input['payment']['id'],
            VerifyFields::TRANSACTION_DATE         => $this->getFormattedDate($input),
            VerifyFields::MERCHANT_ID              => $this->getMerchantId(),
            VerifyFields::AMOUNT                   => $amount
        ];

        $data[VerifyFields::HASH] = $this->getHashOfArray($data, 'request');

        return json_encode($data);
    }

    protected function saveVerifyContentIfNeeded($verify, $response, $content)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        if ((isset($content[VerifyFields::STATUS]) === true) and
            ($content[VerifyFields::STATUS]) === Status::SUCCESS)
        {
            $attributes = $this->getVerifyAttributes($response, $content);

            // Late authorization case
            if ($gatewayPayment[Base\Entity::RECEIVED] === false)
            {
                $gatewayPayment->fill($attributes);

                $this->repo->saveOrFail($gatewayPayment);
            }
        }

        return $gatewayPayment;
    }

    protected function getVerifyAttributes($response, $content)
    {
        $contentToSave = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[VerifyFields::STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[VerifyFields::TRANSACTION_ID],
            Base\Entity::MERCHANT_CODE   => $response[VerifyFields::CODE],
            Base\Entity::ERROR_MESSAGE   => $response[VerifyFields::MESSAGE_TEXT],
            Base\Entity::DATE            => $content[VerifyFields::TRANSACTION_DATE],
        ];

        return $contentToSave;
    }

    protected function getAuthContentFromVerifyResponse($verify, $response)
    {
        $bankPaymentId = $verify->payment->getBankPaymentId();

        foreach ($response[VerifyFields::TRANSACTION] as $transaction)
        {
            if ($transaction[VerifyFields::TRANSACTION_ID] === $bankPaymentId)
            {
                return $transaction;
            }
        }

        $this->trace->error(
            TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
            $response);
    }

    protected function getRefundRequestData($input)
    {
        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $tranId = $payment['bank_payment_id'];

        $merchantId = $this->getMerchantId();

        $amount = $this->getFormattedAmount($input);

        $request = [
            RefundFields::SESSION_ID        => uniqid(),
            RefundFields::TRANSACTION_ID    => $tranId,
            RefundFields::TRANSACTION_DATE  => $this->getFormattedDate($input),
            RefundFields::REQUEST           => self::REVERSAL,
            RefundFields::MERCHANT_ID       => $merchantId,
            RefundFields::AMOUNT            => "$amount"
        ];

        $hash = $this->getHashOfArray($request, 'request');

        $request[RefundFields::HASH] = $hash;

        return json_encode($request);
    }

    protected function checkRefundResponse($response, $input)
    {
        $content = $response->body;

        $responseArray = $this->jsonToArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            (array) $responseArray);

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

        return $input[$field]['amount'] / 100;
    }

    protected function getFormattedDate($input)
    {
        $date = Carbon::createFromTimestamp($input['payment']['created_at'], 'Asia/Kolkata')
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
                throw new Exception\RuntimeException('Action not set correctly');
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
    protected function getStringToHash($content, $glue = '', $type = 'response')
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
                throw new Exception\RuntimeException('Action not set correctly');
        }

        $salt = $this->getSecret();

        array_push($data, $salt);

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
        ];

        return $hashArray;
    }

    protected function getCallbackResponseHashArray($content)
    {
        $hashArray = [
            $content[AuthFields::MERCHANT_ID],
            $content[AuthFields::TRANSACTION_ID],
            $content[AuthFields::TRANSACTION_REFERENCE_NO],
            $content[AuthFields::TRANSACTION_AMOUNT],
            $content[AuthFields::TRANSACTION_DATE],
        ];

        return $hashArray;
    }

    protected function getVerifyRequestHashArray($data)
    {
        $hashArray = [
            $data[VerifyFields::MERCHANT_ID],
            $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            $data[VerifyFields::AMOUNT],
            $data[VerifyFields::TRANSACTION_DATE],
        ];

        return $hashArray;
    }

    protected function getVerifyResponseHashArray($content)
    {
        $hashArray = [
            $content[VerifyFields::MERCHANT_ID],
            json_encode($content[VerifyFields::TRANSACTION]),
            $content[VerifyFields::ERROR_CODE]
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
            $data[RefundFields::STATUS]
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
                throw new Exception/RuntimeException('Action not set correctly');
        }

        $successValue = ErrorCodes::getSuccessField();

        if ((isset($content[$statusField]) === false) or
            ($content[$statusField] !== $successValue))
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

        $this->trace->info(
            TraceCode::PAYMENT_CALLBACK_FAILURE,
            ['content' => $content]);

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException($errorCode, $statusField, $errorDescription);
    }

    public function getMerchantId()
    {
        $mid = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    /*
     * @Override parent method
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
}
