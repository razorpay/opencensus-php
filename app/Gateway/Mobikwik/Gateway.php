<?php

namespace RZP\Gateway\Mobikwik;

use Lib\PhoneBook;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'mobikwik';

    protected $sortRequestContent = false;

    protected $canRunOtpFlow = true;

    protected $topup = true;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackNormalFlow($input);
    }

    protected function callbackNormalFlow(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifySecureHash($input['gateway']);

        $this->assertPaymentId($input['payment']['id'], $input['gateway']['orderid']);
        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($input['gateway']['amount'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
                            $input['gateway']['orderid'], Action::AUTHORIZE);

        $input['gateway']['received'] = 1;

        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input['gateway']);

        return $this->getCallbackResponseData($input);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input, Action::PAY);

        $response = $this->sendGatewayRequest($request);
        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'content' => $content,
                'gateway' => 'mobikwik',
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifySecureHashForQueryRequest($content);

        unset($content['checksum']);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    public function sendRefundVerifyRequest($input)
    {
        $request = $this->getVerifyRequestArray($input, Action::REFUND);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'content'    => $content,
                'gateway'    => 'mobikwik',
                'payment_id' => $input['payment']['id'],
                'refund_id'  => $input['refund']['id'],
            ]);

        $this->verifySecureHashForQueryRefundRequest($content);

        unset($content['checksum']);

        return $content;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;

            // Could be the case where the transaction didn't even hit mobikwik
            if (($payment === null) and
                (($input['payment']['status'] === 'failed') or
                 ($input['payment']['status'] === 'created')))
            {
                $verify->apiSuccess = false;
            }
            else if (($payment['received'] === false) and
                     (($payment['statuscode'] === null) or
                      ($payment['statuscode'] !== Status::SUCCESS)))
            {
                $verify->apiSuccess = false;
            }
            else if ($payment['statuscode'] === Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = true;
            }
        }
        else if ($content['statuscode'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;

            if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed'))
            {
                $verify->apiSuccess = true;
            }
            else
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $this->verifyAmountMismatch($verify, $input, $content);

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new Base\ScroogeResponse();

        // Mobikwik returns an error when refund amount exceeds the remaining amount
        // on Mobikwik's end. We take advantage of this error and initiate refunds
        // for all the pending refunds whose amount is either equal to payment, i.e,
        // they are full refund or twice of refund amount is more than payment amount
        if ((2 * $input['refund']['amount']) <= $input['payment']['amount'])
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                                   ->toArray();
        }

        $content = $this->sendRefundVerifyRequest($input);

        $scroogeResponse->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if (($content[Entity::STATUSCODE] === Status::SUCCESS) and
            ($content[Entity::STATUSMSG] === 'Refund'))
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                               ->toArray();
    }

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if ($payment === null)
        {
            $walletAttributes = $this->getWalletContentFromVerify($payment, $content);

            $payment = $this->createGatewayPaymentEntity($walletAttributes);
        }
        else if ($payment['received'] === false)
        {
            $payment->fill($content);
            $payment->saveOrFail();
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestContentArray($input);

        $refund = $this->createGatewayRefundEntity($content, $input);

        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $content['received'] = 1;

        $refund->fill($content)->saveOrFail();

        $gatewayData = [
            Payment\Gateway::GATEWAY_RESPONSE => json_encode($content),
            Payment\Gateway::GATEWAY_KEYS     => $this->getGatewayData($content)
        ];

        if ($content[Entity::STATUSCODE] !== '0')
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content[Entity::STATUSCODE],
                $content[Entity::STATUSMSG],
                $gatewayData
            );
        }

        return $gatewayData;
    }

    public function topup($input)
    {
        return $this->authorize($input);
    }

    public function checkExistingUser($input)
    {
        $this->action($input, Action::CHECK_USER);

        $content = $this->getCheckExistingUserRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_CHECK_USER_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_CHECK_USER_RESPONSE, $content);

        $content['received'] = 1;

        $code = $content['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['statuscode'],
                $content['statusdescription']);
        }
    }

    public function createWalletUser($input)
    {
        $this->action($input, Action::CREATE_USER);

        $content = array(
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'email'         => $input['payment']['email'],
            'merchantname'  => 'Razorpay',
            'mid'           => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'       => MessageCode::CREATE_USER,
            'otp'           => $input['gateway']['otp'],
        );

        $content['checksum'] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $traceRequest = $request;

        unset($traceRequest['content']['otp']);

        $this->trace->info(TraceCode::GATEWAY_CREATE_USER_REQUEST, $traceRequest);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_CREATE_USER_RESPONSE, $content);

        $code = $content['statuscode'];

        if ($code !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Wallet creation fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['statuscode'],
                $content['statusdescription']);
        }
    }

    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $content = array(
            'amount'       => $input['payment']['amount'] / 100,
            'cell'         => $this->getFormattedContact($input['payment']['contact']),
            'merchantname' => $this->getMobikwikMerchantName($input['merchant']),
            'mid'          => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'      => MessageCode::OTP_GENERATE,
            'tokentype'    => '0',
        );

        $content['checksum'] = $this->getHashOfArray($content);
        $content['merchantAlias'] = $this->getMobikwikMerchantName($input['merchant']);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_GENERATE_RESPONSE, $content);

        $code = $content['statuscode'];

        if ($content['statuscode'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['statuscode'],
                $content['statusdescription']);
        }

        return $this->getOtpSubmitRequest($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $content = array(
            'amount'        => (string) ($input['payment']['amount'] / 100),
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'comment'       => 'Order id - ' . $input['payment']['public_id'],
            'merchantname'  => $this->getMobikwikMerchantName($input['merchant']),
            'mid'           => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'       => MessageCode::OTP_SUBMIT,
            'orderid'       => $input['payment']['id'],
            'otp'           => $input['gateway']['otp'],
            'txntype'       => 'debit',
        );

        $content['checksum'] = $this->getHashOfArray($content);

        $request = $this->getStandardRequestArray($content);

        $traceRequest = $request;

        unset($traceRequest['content']['otp']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_REQUEST, $traceRequest);

        $response = $this->sendGatewayRequest($request);
        $responseArray = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_OTP_SUBMIT_RESPONSE, $responseArray);

        $code = $responseArray['statuscode'];

        $content['email'] = $input['payment']['email'];

        if ($responseArray['statuscode'] !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            if (ResponseCodeMap::isWalletUserNotPresent($code))
            {
                // if user doesn't exist, first register the user
                // then throw insufficient funds exception
                // so that he's shown an 'Add Funds' button
                $this->createWalletUser($input);

                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
                    $responseArray['statuscode'],
                    $responseArray['statusdescription']);
            }
            else
            {
                $this->throwPaymentFailureException($responseArray);
            }
        }

        $content['received'] = true;

        if (isset($responseArray['statuscode']))
        {
            $content['statuscode'] = $responseArray['statuscode'];
        }

        if (isset($responseArray['statusdescription']))
        {
            $content['statusmessage'] = $responseArray['statusdescription'];
        }

        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($content);

        return $this->getCallbackResponseData($input);
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->findSuccessfulRefundByRefundId($refundId);

        if ($refundedEntities->count() === 0)
        {
            return false;
        }

        $refundEntity = $refundedEntities->first();

        $refundEntityPaymentId = $refundEntity->getPaymentId();
        $refundEntityRefundAmount = $refundEntity->getAmount();
        $refundEntityStatusCode = $refundEntity->getStatusCode();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input,
                'refund_payment_id'     => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount,
                'status_code'           => $refundEntityStatusCode,
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount) or
            ($refundEntityStatusCode !== '0'))
        {
            return false;
        }

        return true;
    }

    protected function getAuthorizeRequestContent($input)
    {
        $content = array(
            'email'         => $input['payment']['email'],
            'amount'        => $input['payment']['amount'] / 100,
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'orderid'       => $input['payment']['id'],
            'merchantname'  => $this->getMobikwikMerchantName($input['merchant']),
            'mid'           => $input['terminal']['gateway_merchant_id'],
            'redirecturl'   => $input['callbackUrl'],
        );

        if ($this->mode === Mode::TEST)
        {
            $this->addTerminalDetailsInTest($content);
        }

        if(isset($input['merchant']['category']))
        {
            $content['mccCode'] = $input['merchant']['category'];
        }

        $payment = $this->createGatewayPaymentEntity($content);
        $content['checksum'] = $this->getHashForAuthorizeRequest($content);
        $content['merchantAlias'] = $this->getMobikwikMerchantName($input['merchant']);

        return $content;
    }

    protected function getRefundRequestContentArray($input)
    {
        $content = [];

        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);

        $this->addTestMerchantIdIfTestMode($content);

        // $payment = $this->repo->findByPaymentIdAndActionOrFail(
        //                         $input['payment']['id'], Action::AUTHORIZE);

        $content['txid'] = $input['payment']['id'];
        $content['refundid'] = $input['refund']['id'];
        $content['amount'] = (string) ($input['refund']['amount'] / 100);

        $content['checksum'] = $this->getHashForRefundRequest(
                                        $content['mid'],
                                        $content['txid'],
                                        $content['refundid'],
                                        $content['amount']);

        if ($input['refund']['amount'] < $input['payment']['amount'])
        {
            $content['ispartial'] = 'yes';
        }

        return $content;
    }

    protected function getCheckExistingUserRequestContent($input)
    {
        $content = array(
            'action'        => 'existingusercheck',
            'cell'          => $this->getFormattedContact($input['payment']['contact']),
            'merchantname'  => 'Razorpay',
            'mid'           => $this->getMobikwikMerchantId($input['terminal']),
            'msgcode'       => MessageCode::CHECK_EXISTING_USER,
        );

        $content['checksum'] = $this->getHashForCheckExistingUserRequest($content);

        return $content;
    }

    protected function getVerifyRequestArray($input, $action)
    {
        $content['mid'] = $this->getMobikwikMerchantId($input['terminal']);

        $content['orderid'] = $input['payment']['id'];

        if($action === Action::REFUND)
        {
            $content['refundid'] = $input['refund']['id'];

            $content['checksum'] = $this->getHashForRefundVerifyRequest(
                                                $content['mid'],
                                                $content['orderid'],
                                                $content['refundid']);
        }
        else
        {
            $content['checksum'] = $this->getHashForVerifyRequest(
                                                $content['mid'],
                                                $content['orderid']);
        }

        $contentToTrace = http_build_query($content);

        $content = http_build_query($content);

        $request = $this->getStandardRequestArray($content);

        $requestToTrace = $request;

        $requestToTrace['content'] = $contentToTrace;

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $requestToTrace);

        return $request;
    }

    protected function addTerminalDetailsInTest(array & $content)
    {
        $content['merchantname'] = 'TestMerchant';
        $content['mid'] = $this->getTestMerchantId();
    }

    protected function getMobikwikMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestMerchantId();
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getMobikwikMerchantName($merchant) : string
    {
        return $merchant->getFilteredDba();
    }

    protected function isMobikwikOffersEnabled($merchant) : bool
    {
        return ($merchant->isFeatureEnabled(Feature\Constants::MOBIKWIK_OFFERS) === true);
    }

    protected function verifySecureHash(array $content)
    {
        $fieldsInOrder = array(
            'statuscode',
            'orderid',
            'amount',
            'statusmessage',
            'mid',
        );

        $actual = $content['checksum'];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generated = $this->getHashOfArray($content);

        $this->compareHashes($actual, $generated);
    }

    protected function verifySecureHashForQueryRequest($content)
    {
        $str = "'" . $content['statuscode'] . "'" .
            "'" . $content['orderid'] . "'" .
            "'" . $content['refid'] . "'" .
            "'" . $content['amount'] . "'" .
            "'" . $content['statusmessage'] . "'" .
            "'" . $content['ordertype'] . "'";

        $generated = $this->getHashOfString($str);

        $actual = $content['checksum'];

        $this->compareHashes($actual, $generated);
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getHashForVerifyRequest($mid, $orderId)
    {
        $str = "'" . $mid . "''" . $orderId . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashForRefundVerifyRequest($mid, $orderId, $refundid)
    {
        $str = "'" . $mid . "''" . $orderId . "''". $refundid . "'";

        return $this->getHashOfString($str);
    }

    protected function getStringToHash($content, $glue = '')
    {
        return "'" . parent::getStringToHash($content, "''") . "'";
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "''");

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return strtolower(hash_hmac('sha256', $str, $secret, false));
    }

    protected function getHashForRefundRequest($mid, $orderId, $refundid, $amount)
    {
        $str = "'" . $mid . "''" . $orderId . "''" . $refundid . "''" . $amount . "'";

        return $this->getHashOfString($str);
    }

    protected function verifySecureHashForQueryRefundRequest($content)
    {
        $str = "'" . $content['statuscode'] . "'" .
            "'" . $content['orderid'] . "'" .
            "'" . $content['refundid'] . "'" .
            "'" . $content['txnamount'] . "'" .
            "'" . $content['refundamount'] . "'" .
            "'" . $content['refid'] . "'" ;

        $generated = $this->getHashOfString($str);

        $actual = $content['checksum'];

        $this->compareHashes($actual, $generated);
    }

    protected function getHashForAuthorizeRequest($content)
    {
        $str = "'" .
            $content['cell']        . "''" .
            $content['email']       . "''" .
            $content['amount']      . "''" .
            $content['orderid']     . "''" .
            $content['redirecturl'] . "''" .
            $content['mid'] . "'";

        return $this->getHashOfString($str);
    }

    protected function getHashForCheckExistingUserRequest($content)
    {
        $str = "'" .
            $content['action']          . "''" .
            $content['cell']            . "''" .
            $content['merchantname']    . "''" .
            $content['mid']             . "''" .
            $content['msgcode'] . "'";

        return $this->getHashOfString($str);
    }

    protected function addTestMerchantIdIfTestMode(array & $content)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['mid'] = $this->getTestMerchantId();
        }
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr['txntype'] = Type::SALE;
        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attributes['orderid']);
        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayEntity($attributes)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $attributes['refund_id'] = $input['refund']['id'];
        $attributes['payment_id'] = $input['payment']['id'];
        $attributes['email'] = $input['payment']['email'];

        $refund = $this->createGatewayEntity($attributes);

        return $refund;
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        if ($input['statuscode'] !== Status::SUCCESS)
        {
            // Payment fails, throw exception
            $this->throwPaymentFailureException($input);
        }
    }

    protected function throwPaymentFailureException(array $response)
    {
        $code = $response['statuscode'];

        $errorCode = ResponseCodeMap::getApiErrorCode($code);

        $message = $response['statusmessage'] ?? $response['statusdescription'];

        throw new Exception\GatewayErrorException(
            $errorCode,
            $code,
            $message);
    }

    protected function getUrlDomain()
    {
        if ($this->mode === Mode::LIVE)
        {
            $apiDomainActionList = array(
                Action::CHECK_USER,
                Action::OTP_GENERATE,
                Action::OTP_SUBMIT);

            if (in_array($this->action, $apiDomainActionList))
            {
                $this->domainType = 'api';
            }
        }

        return parent::getUrlDomain();
    }

    protected function xmlToArray($xml)
    {
        $e = null;
        $res = null;

        try
        {
            $res = simplexml_load_string($xml);
        }
        catch (\Exception $e)
        {
            $res = false;
        }

        if ($res === false)
        {
            $this->trace->error(
                TraceCode::GATEWAY_REFUND_ERROR,
                ['xml' => $xml]);

            throw new Exception\RuntimeException(
                'Failed to convert xml to array',
                ['xml' => $xml],
                $e);
        }

        return (array) $res;
    }


    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = array(
            'mid'      => $this->getMobikwikMerchantId($this->input['terminal']),
            'amount'   => (string) ($this->input['payment']['amount'] / 100),
            'email'    => $this->input['payment']['email'],
            'cell'     => $this->getFormattedContact($this->input['payment']['contact']),
            'msgcode'  => MessageCode::OTP_SUBMIT,
            'orderid'  => $this->input['payment']['id'],
            'received' => true
        );

        if (isset($content['statuscode']))
        {
            $contentToSave['statuscode'] = $content['statuscode'];
        }

        if (isset($responseArray['statusmessage']))
        {
            $contentToSave['statusmessage'] = $content['statusmessage'];
        }

        return $contentToSave;
    }

    protected function getFormattedContact($contact)
    {
        $number = new PhoneBook($contact, true);

        return $number->format(PhoneBook::DOMESTIC);
    }

    protected function verifyAmountMismatch(Base\Verify $verify, array $input, array $response)
    {
        if (is_string($response['amount']) === false)
        {
            return;
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount   = number_format(floatval($response['amount']), 2, '.', '');

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                Entity::GATEWAY_REFID => $response[Entity::GATEWAY_REFID] ?? null,
                Entity::STATUS     => $response[Entity::STATUS] ?? null,
                Entity::STATUSCODE => $response[Entity::STATUSCODE] ?? null,
            ];
        }

        return [];
    }
}
