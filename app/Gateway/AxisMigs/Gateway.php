<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Payment\Processor\Notify;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\AxisMigs;
use RZP\Models\Payment;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'axis_migs';

    protected $authorize = false;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentAuthorizeRequestContent($input);

        $this->addSubMerchantDetails($content, $input);

        $content['vpc_SecureHash'] = $this->generateHash($content);

        $request = $this->getAuthRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if (isset($input['gateway']['vpc_MerchTxnRef']) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_CALLBACK, [$input['gateway']]);

            // Payment fails since vpc_MerchTxnRef not set, throw exception
            throw new Exception\GatewayErrorException(
                        Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $payment = $this->getRepo()->findByMerchantTxnRefAndCommand(
            $input['gateway']['vpc_MerchTxnRef'], Command::PAY);

        $this->verifySecureHash($input['gateway']);

        $input['gateway']['received'] = 1;
        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        return $this->verifyPaymentCallbackResponse($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        if ($this->authorize === true)
        {
            return $this->captureAuthorizedPayment($input);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndCommand(
                                $input['payment']['id'], Command::PAY);

        $content = $this->getPaymentRefundRequestContent($input, $payment);

        $toSaveContent = $content;
        $toSaveContent['refund_id'] = $input['refund']['id'];
        $toSaveContent['terminal_id'] = $input['terminal']['id'];

        $refund = $this->createGatewayPaymentEntity($toSaveContent, $input);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $content['received'] = 1;
        $refund->fill($content);
        $refund->saveOrFail();

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REFUND,
            ['content' => $content,
            'action' => $this->action,
            'payment' => $input['payment'],
            'refund' => $input['refund']]);

        $this->verifyAmaTransactionResponse($content, $input);
    }

    public function forceAuthorizeFailed($input)
    {
        $repo = $this->getRepo();

        $payment = $repo->findByPaymentIdAndCommand(
                                $input['payment']['id'], Command::PAY);

        // assert ($payment['received'] === false);
        // assert ($payment['vpc_TxnResponseCode'] !== '0');

        if (isset($input['gateway']['vpc_TransactionNo']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Correct field not present for the required operation');
        }

        $txnNo = $input['gateway']['vpc_TransactionNo'];
        $txnNo = (int) $txnNo;
        assert (strlen($txnNo) === 10);
        assert (is_integer($txnNo) === true);

        $terminalId = $input['terminal']['id'];

        $count = $repo->countPaymentsNearTransactionNo($txnNo, $terminalId);

        if ($count === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No migs payments with nearby vpc_TransactionNo found');
        }

        $payment->setVpcTransactionNo($txnNo, $terminalId);
        $payment['vpc_TxnResponseCode'] = '0';

        $repo->saveOrFail($payment);

        return true;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function captureAuthorizedPayment(array $input)
    {
        assert ($input['payment']['status'] === 'authorized');

        $payment = $this->getRepo()->findByPaymentIdAndCommand(
            $input['payment']['id'], Command::PAY);

        $capturedAmount = (int) $payment['vpc_CapturedAmount'];

        if ($capturedAmount === $input['payment']['amount'])
        {
            //
            // Looks like the payment has already been captured on gateway,
            // but due to some previous error, this has not been recorded
            // on api.
            //
            // In this case we will silently return implying payment has
            // been captured on gateway
            //

            return;
        }

        $content = $this->getPaymentCaptureRequestContent($input, $payment);

        $payment = $this->createGatewayPaymentEntity($content, $input);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        if (isset($content['vpc_TxnResponseCode']) === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_CAPTURE_FAILURE,
                [
                    'payment_id' => $input['payment']['id'],
                    'gateway' => $this->gateway,
                    'vpc_TxnResponseCode' => null,
                    'content' => $content,
                ]
            );

            $content['vpc_TxnResponseCode'] = '?';
            $content['vpc_MerchTxnRef'] = $input['payment']['id'];
        }

        $content['received'] = 1;
        $payment->fill($content)->saveOrFail();

        $this->verifyAmaTransactionResponse($content, $input);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $content = $this->getPaymentVerifyRequestContent($input, $payment);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        if (isset($content['vpc_SecureHash']))
        {
            $this->verifySecureHash($content);
            unset($content['vpc_SecureHash']);
        }

        if (isset($content['vpc_Command']))
        {
            unset($content['vpc_Command']);
        }

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $status = VerifyResult::STATUS_MATCH;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            ['payment_id' => $input['payment']['id'],
             'content' => $content]);

        unset($content['vpc_Command']);

        if ((isset($content['vpc_DRExists']) === false) and
            ($content['vpc_TxnResponseCode'] === '7'))
        {
            // Most probably means AMA credentials are not correct.
            // However, not sure. Read the error message provided.
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                $content['vpc_TxnResponseCode'],
                $content['vpc_Message']);
        }

        if ($content['vpc_DRExists'] !== 'Y')
        {
            $this->verifyPaymentNonExistentCase($verify, $payment);
        }
        else
        {
            assert ($content['vpc_DRExists'] === 'Y');

            $this->verifyPaymentReconcileWithGatewayResponse($content, $verify, $status);
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $this->verifyPaymentBackfillDataIfRequired($content, $payment);

        return $status;
    }

    protected function verifyPaymentNonExistentCase($verify, $payment)
    {
        // Could be the case where the transaction didn't even hit migs
        if (($payment['received'] === false) and
            (($payment['vpc_TxnResponseCode'] === null) or
             ($payment['vpc_TxnResponseCode'] !== '0')))
        {
            $verify->apiSuccess = false;
            $verify->gatewaySuccess = false;
        }
        else
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = false;
            $verify->gatewaySuccess = false;
        }
    }

    protected function verifyPaymentReconcileWithGatewayResponse($content, $verify, & $status)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        if ($content['vpc_TxnResponseCode'] === '0')
        {
            $verify->gatewaySuccess = true;

            if (($payment['vpc_TxnResponseCode'] !== '0') or
                ($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created'))
            {
                $verify->apiSuccess = false;
                $status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = true;
            }
        }
        else
        {
            $verify->apiSuccess = false;
            $verify->gatewaySuccess = false;

            //
            // If payment is not marked as success then it shouldn't be success
            // on migs end as well.
            //

            if (($payment['vpc_TxnResponseCode'] === '0') or
                (($input['payment']['status'] !== 'failed') and
                 ($input['payment']['status'] !== 'created')))
            {
                // It's marked as success, in this case, if it's totally refunded,
                // then that means billdesk refunded the payment on it's own end
                // and we don't need to worry.

                $verify->gatewaySuccess = true;
                $status = VerifyResult::STATUS_MISMATCH;
            }
        }
    }

    protected function verifyPaymentBackfillDataIfRequired($content, $payment)
    {
        if ($payment['received'] === false)
        {
            unset($content['vpc_Command']);

            $payment->fill($content);
            $payment->saveOrFail();
        }
        else
        {
            // Fill only important fields that change during payment auth/capture/refund
            // lifecycle.
            $array = array(
                'vpc_AuthorisedAmount',
                'vpc_CapturedAmount',
                'vpc_RefundedAmount',
                'vpc_ShopTransactionNo');

            foreach ($array as $key)
            {
                if (empty($content[$key]) === false)
                {
                    $payment->setAttribute($key, $content[$key]);
                }
            }

            $payment->saveOrFail();
        }
    }

    protected function postAmaTransactionRequestAndGetContent(array & $content, $input)
    {
        $response = $this->postAmaTransactionRequest($content, $input);

        $content = $this->getAmaTxnResponseContent($response);

        return $content;
    }

    protected function parseQueryResponse($response)
    {
        parse_str($response->body, $content);

        return $content;
    }

    protected function getPaymentAuthorizeRequestContent($input)
    {
        $attributes = array(
            'vpc_Command'               => Command::PAY,
            'vpc_Amount'                => $input['payment']['amount'],
            'vpc_Currency'              => $input['payment']['currency'],
            'vpc_MerchTxnRef'           => $input['payment']['id'],
        );

        $this->createGatewayPaymentEntity($attributes, $input);

        $network = $input['card']['network'];

        $content = array(
            'vpc_Version'           => '1',
            'vpc_ReturnURL'         => $input['callbackUrl'],
            'vpc_Locale'            => 'en',
            'vpc_gateway'           => 'ssl',
            'vpc_Card'              => $this->getVpcCardValue($network),
            'vpc_CardNum'           => $input['card']['number'],
            'vpc_CardExp'           => $this->getFormattedCardExpiryDate($input),
            'vpc_CardSecurityCode'  => $input['card']['cvv'],
//            'vpc_OrderInfo'             => 'testinfo',
        );

        $content = array_merge($attributes, $content);

        if (($this->mode === Mode::TEST) and
            ($this->mock === false))
        {
            $this->addTestCardDetailsInTestMode($content);
        }

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        return $content;
    }

    protected function addSubMerchantDetails(array & $content, array $input)
    {
        ;
    }

    protected function getPaymentCaptureRequestContent($input, $payment)
    {
        $content = array(
            'vpc_Command'       => Command::CAPTURE,
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
            'vpc_Amount'        => $input['amount']
        );

        return $content;
    }

    protected function getPaymentVerifyRequestContent($input, $payment)
    {
        $content = array(
            'vpc_Command'       => AxisMigs\Command::QUERYDR,
            'vpc_Amount'        => $input['payment']['amount'],
            'vpc_MerchTxnRef'   => $input['payment']['id'],
        );

        return $content;
    }

    protected function getPaymentRefundRequestContent($input, $payment)
    {
        $content = array(
            'vpc_Command'       => AxisMigs\Command::REFUND,
            'vpc_Amount'        => $input['refund']['amount'],
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        );

        return $content;
    }

    protected function addAmaTransactionFields(array & $content, $input)
    {
        $content['vpc_Version'] = 1;

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $this->addAmaUserAndPassword($content, $input['terminal']);
    }

    protected function getAuthRequestArray($content)
    {
        $request = array(
            'url'       => $this->getUrl(Command::PAY),
            'content'   => $content,
            'method'    => 'post');

        return $request;
    }

    protected function getAmaRequestArray($content)
    {
        $request = array(
            'action'    => $this->action,
            'url'       => $this->getUrl('ama'),
            'content'   => $content,
            'method'    => 'post');

        return $request;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        // if ($paymentId )
        //     $paymentId = $attributes['vpc_MerchTxnRef'];

        $paymentId = $input['payment']['id'];
        $attributes['terminal_id'] = $input['terminal']['id'];

        $payment->setPaymentId($paymentId);
        $payment->setAction($this->action);

        $payment->fill($attributes);

        $payment->saveOrFail();

        return $payment;
    }

    protected function postAmaTransactionRequest(array & $content, $input)
    {
        $this->addAmaTransactionFields($content, $input);

        $request = $this->getAmaRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            ['action' => 'Support action request array',
            'content' => $content]);

        // send the request and get response
        $response = $this->postRequest($request);

        return $response;
    }

    public function postRequest($request)
    {
        $options['timeout'] = 60;
        $request['options'] = $options;

        $this->response = $this->sendGatewayRequest($request);

        return $this->response;
    }

    protected function getAmaTxnResponseContent($response)
    {
        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_RESPONSE,
            ['action' => 'Support action response string',
            'content' => $response->body]);

        parse_str($response->body, $content);

        return $content;
    }

    protected function getHashOfString($str)
    {
        $str = $this->getSecret() . $str;

        return strtoupper(md5($str));
    }

    protected function verifySecureHash($input)
    {
        $hash = strtoupper($input['vpc_SecureHash']);
        unset($input['vpc_SecureHash']);

        $generatedHash = $this->generateHash($input);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException('Failed checksum verification');
        }
    }

    protected function addMerchantIdAndAccessCode(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['vpc_Merchant'] = $this->config['test_merchant_id'];
            $content['vpc_AccessCode'] = $this->config['test_access_code'];
        }
        else
        {
            $content['vpc_Merchant'] = $terminal['gateway_merchant_id'];
            $content['vpc_AccessCode'] = $terminal['gateway_access_code'];
        }
    }

    protected function addAmaUserAndPassword(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['vpc_User'] = $this->config['test_ama_user'];
            $content['vpc_Password'] = $this->config['test_ama_password'];
        }
        else
        {
            $content['vpc_User'] = $terminal['gateway_terminal_id'];
            $content['vpc_Password'] = $terminal['gateway_terminal_password'];
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];

        $threeDSstatus = isset($input['gateway']['vpc_3DSstatus']) ? $input['gateway']['vpc_3DSstatus'] : null;

        $message = '';

        $apiErrorCode = null;

        if (isset($input['gateway']['vpc_Message']))
        {
            $message = $input['gateway']['vpc_Message'];
        }

        // check for success
        if ($txnResponseCode === '0')
        {
            //
            // Transaction has been successful
            // However, if 3dsecure failed and international is not enabled for the merchant,
            // then we need to block the transaction on the international card.
            //

            if ($this->getTwoFaStatus($threeDSstatus) === Payment\TwoFaStatus::FAILED)
            {
                if ($input['merchant']['international'] === false)
                {
                    $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED;
                }
                else if($input['merchant']['risk_rating'] > Notify::MIN_HIGH_RISK_RATING)
                {
                    $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK;
                }
                else
                {
                    return $this->getCallbackResponseData(array('threeDSstatus' => $threeDSstatus));; // payment succeeds
                }
            }
            else
            {
                // payment succeeds
                return $this->getCallbackResponseData(array('threeDSstatus' => $threeDSstatus));
            }
        }
        else
        {
            // Get appropriate error code
            $apiErrorCode = $this->getApiErrorCode($input);
        }

        $this->throwException($apiErrorCode, $txnResponseCode, $message, $threeDSstatus);
    }

    protected function getCallbackResponseData(array $input)
    {
        $twoFaStatus = $this->getTwoFaStatus($input['threeDSstatus']);

        $data = array(Payment\Entity::TWO_FA_STATUS => $twoFaStatus);

        return $data;
    }

    protected function throwException($code, $gatewayErrorCode, $gatewayErrorDesc, $threeDSstatus = null)
    {
        $e = new Exception\GatewayErrorException($code, $gatewayErrorCode, $gatewayErrorDesc);

        $twoFaStatus = $this->getTwoFaStatus($threeDSstatus);

        if ($twoFaStatus === Payment\TwoFaStatus::FAILED)
        {
            $e->markTwoFaError();
        }

        throw $e;
    }

    protected function getTwoFaStatus($threeDSstatus)
    {
        return ThreeDSecureStatus::getThreeDSstatus($threeDSstatus);
    }

    protected function getApiErrorCode($input)
    {
        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];

        if ($this->isSessionExpired($input))
        {
            return Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED;
        }

        // check if Acq error
        if (isset($input['gateway']['vpc_AcqResponseCode']))
        {
            $acqResponseCode = $input['gateway']['vpc_AcqResponseCode'];

            if (isset(AcqResponseCode::$map[$acqResponseCode]))
            {
                return AcqResponseCode::$map[$acqResponseCode];
            }
        }

        // Check for mapped TxnResponseCode value
        if ((isset(AxisMigs\TxnResponseCode::$map[$txnResponseCode])))
        {
            return AxisMigs\TxnResponseCode::$map[$txnResponseCode];
        }
        else
        {
            $this->trace->error(
                TraceCode::GATEWAY_UNKNOWN_ERROR,
                ['payment_id' => $input['payment']['id'],
                'action' => $this->action,
                'gateway_error_code' => $txnResponseCode,
                'gateway' => $this->gateway,
                'time' => time()]);

            return Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }
    }

    protected function isSessionExpired($input)
    {
        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];
        $message = $input['gateway']['vpc_Message'];

        if ((isset(AxisMigs\TxnResponseCode::$map[$txnResponseCode])) and
            ($txnResponseCode === 'Aborted') and
            ($message === 'Your Session has expired'))
        {
                return true;
        }
        return false;
    }

    protected function verifyAmaTransactionResponse($content, $input)
    {
        $txnResponseCode = null;

        if (isset($content['vpc_TxnResponseCode']))
        {
            $txnResponseCode = $content['vpc_TxnResponseCode'];
        }

        if ($txnResponseCode === '0')
        {
            return;
        }

        $msg = null;

        if (isset($content['vpc_Message']))
        {
            $msg = $content['vpc_Message'];
        }
        else if (isset($content['ERROR']))
        {
            $msg = $content['ERROR'];
        }

        $code = Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        if ($this->action === Base\Action::REFUND)
        {
            // Refund request failed. Just check if refund amount due to
            // previous requests matches the expected amount.
            // In that case, we will mark it as success.

            $ret = $this->returnIfRefundAmountMatches($content, $input);

            if ($ret === true)
            {
                return;
            }

            $code = Error\ErrorCode::BAD_REQUEST_REFUND_FAILED;
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                    $code,
                    $txnResponseCode,
                    $msg);
    }

    protected function returnIfRefundAmountMatches($content, $input)
    {
        if (isset($content['vpc_RefundedAmount']) === false)
        {
            return false;
        }

        $amount = $input['payment']['amount_refunded'] + $input['refund']['amount'];

        $vpcAmount = (int) $content['vpc_RefundedAmount'];

        return ($amount === $vpcAmount);
    }

    protected function getVpcCardValue($network)
    {
        return $network;
    }

    protected function addTestCardDetailsInTestMode(array & $content)
    {
        assert ($this->mode === Mode::TEST);

        if ($content['vpc_CardNum'] === '4111111111111111')
        {
            return;
        }

        $content['vpc_Card'] = 'MasterCard';
        $content['vpc_CardNum'] = '5123456789012346';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '333';
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10)
        {
            $expiryMonth = '0' . $expiryMonth;
        }

        $cardExp = substr($input['card']['expiry_year'], 2,2) . $expiryMonth;

        return $cardExp;
    }
}