<?php

namespace Gateway\AxisMigs;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\AxisMigs;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'axis_migs';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = array(
            'vpc_Command'               => Command::PAY,
            'vpc_Amount'                => $input['payment']['amount'],
            'vpc_Currency'              => $input['payment']['currency'],
            'vpc_MerchTxnRef'           => $input['payment']['id'],
        );

        $this->createGatewayPaymentEntity($attributes);

        $content = array(
            'vpc_Version'           => '1',
            'vpc_ReturnURL'         => $input['callbackUrl'],
            'vpc_Locale'            => 'en',
            'vpc_gateway'           => 'ssl',
            'vpc_Card'              => $input['card']['network'],
            'vpc_CardNum'           => $input['card']['number'],
            'vpc_CardExp'           => $this->getFormattedCardExpiryDate($input),
            'vpc_CardSecurityCode'  => $input['card']['cvv'],
//            'vpc_OrderInfo'             => 'testinfo',
        );

        $content = array_merge($attributes, $content);

        if ($this->mode === Mode::TEST)
        {
            $this->addTestCardDetailsInTestMode($content);
        }

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $content['vpc_SecureHash'] = $this->generateHash($content);

        $request = $this->getAuthRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $payment = $this->getRepo()->findByMerchantTxnRefAndCommand(
            $input['gateway']['vpc_MerchTxnRef'], Command::PAY);

        $this->verifySecureHash($input['gateway']);

        $input['gateway']['received'] = 1;
        $payment->fill($input['gateway']);
        $payment->saveOrFail();

        $this->verifyPaymentCallbackResponse($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        return;

        $payment = $this->getRepo()->findByPaymentIdAndCommand(
            $input['payment']['id'], Command::PAY);

        $content = $this->getPaymentCaptureRequestContent($input, $payment);

        $payment = $this->createGatewayPaymentEntity($content, $input['payment']['id']);

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

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->getRepo()->findByPaymentIdAndCommand(
                                $input['payment']['id'], Command::PAY);

        $content = $this->getPaymentRefundRequestContent($input, $payment);

        $toSaveContent = $content;
        $toSaveContent['refund_id'] = $input['refund']['id'];

        $refund = $this->createGatewayPaymentEntity($toSaveContent, $input['payment']['id']);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $content['received'] = 1;
        $refund->fill($content);
        $refund->saveOrFail();

        $this->verifyAmaTransactionResponse($content, $input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
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
            // Could be the case where the transaction didn't even hit migs
            if (($payment['received'] === false) and
                (($payment['vpc_TxnResponseCode'] === null) or
                 ($payment['vpc_TxnResponseCode'] === '0')))
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
        else
        {
            assert ($content['vpc_DRExists'] === 'Y');

            if ($payment['vpc_TxnResponseCode'] === '0')
            {
                $verify->apiSuccess = true;

                if ($content['vpc_TxnResponseCode'] === '0')
                {
                    $verify->gatewaySuccess = true;
                }
                else
                {
                    $verify->gatewaySuccess = false;
                    $status = VerifyResult::STATUS_MISMATCH;
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

                if ($content['vpc_TxnResponseCode'] === '0')
                {
                    // It's marked as success, in this case, if it's totally refunded,
                    // then that means billdesk refunded the payment on it's own end
                    // and we don't need to worry.

                    $verify->gatewaySuccess = true;
                    $status = VerifyResult::STATUS_MISMATCH;
                }
            }
        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if (($verify->match === true) and
            ($payment['received'] === false))
        {
            unset(
                $content['TxnAmount'],
                $content['BankID'],
                $content['ItemCode']);

            $payment->fill($content);
            $payment->saveOrFail();
        }

        return $status;
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

    protected function createGatewayPaymentEntity($attributes, $paymentId = null)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        if ($paymentId === null)
            $paymentId = $attributes['vpc_MerchTxnRef'];

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
            TraceCode::GATEWAY_PAYMENT_REFUND,
            ['action' => 'Refund request array',
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
        if ((isset($input['gateway']['vpc_TxnResponseCode']) === true) and
            ($input['gateway']['vpc_TxnResponseCode'] === '0'))
        {
            return; // Payment succeeds
        }

        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];
        $message = '';

        if (isset($input['gateway']['vpc_Message']))
        {
            $message = $input['gateway']['vpc_Message'];
        }

        $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        if (isset(AxisMigs\TxnResponseCode::$map[$txnResponseCode]))
        {
            $apiErrorCode = AxisMigs\TxnResponseCode::$map[$txnResponseCode];

            if (($txnResponseCode === 'Aborted') and
                ($message === 'Your Session has expired'))
            {
                $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED;
            }
            else if (isset($input['gateway']['vpc_AcqResponseCode']))
            {
                $acqResponseCode = $input['gateway']['vpc_AcqResponseCode'];

                if (isset(AcqResponseCode::$map[$acqResponseCode]))
                {
                    $apiErrorCode = AcqResponseCode::$map[$acqResponseCode];
                }
            }
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
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
                    $apiErrorCode,
                    $txnResponseCode,
                    $input['gateway']['vpc_Message']);
    }

    protected function verifyAmaTransactionResponse($content, $input)
    {
        $txnResponseCode = null;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REFUND,
            ['content' => $content,
            'action' => $this->action,
            'payment' => $input['payment'],
            'refund' => $input['refund']]);

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

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = substr($input['card']['expiry_year'], 2,2) . $expiryMonth;

        return $cardExp;
    }
}