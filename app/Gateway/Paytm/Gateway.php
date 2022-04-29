<?php

namespace RZP\Gateway\Paytm;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'paytm';

    use CommonGatewayTrait;

    const ACQUIRER = 'paytm';

    public function authorize(array $input)
    {
        parent::authorize($input);

        /**
         * Processing authorize requests for upi payment method paytm with upi common trait.
         * Checking method if upi then send to Mozart.
         */
        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }

        $content = $this->getAuthRequestContentArray($input);

        $this->createGatewayPaymentEntity($content);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST, [
            'content' => $content,
            'gateway' => 'paytm',
        ]);

        $request = array(
            'url' => $this->getUrl('pay')."?ORDER_ID=".$content['ORDER_ID'],
            'content' => $content,
            'method' => 'post');

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        /**
         * Processing callback requests for upi payment method paytm with upi mozart entity.
         * Checking method if upi then send to Mozart.
         */
        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            $mozart=$this->getUpiMozartGatewayWithModeSet();

            $response = $mozart->sendUpiMozartRequest($input,TraceCode::PAYMENT_CALLBACK_REQUEST, 'pay_verify');

            $input['gateway']= $response;

            return $this->upiCallback($input);
        }

        $this->verifySecureHash($input['gateway']);

        // assert payment id
        $this->assertPaymentId($input['payment']['id'], $input['gateway']['ORDERID']);

        //assert amount
        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount   = number_format($input['gateway']['TXNAMOUNT'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['gateway']['ORDERID'], Action::AUTHORIZE);

        $values = $this->lowerArrayKeys($input['gateway']);

        $values['received'] = 1;

        $gatewayPayment->fill($values);

        $this->repo->saveOrFail($gatewayPayment);

        $this->verifyPaymentCallbackResponse($input);

        /*
         * If callback status was a success, we verify the payment immediately
         * as we don't want rely only on redirect response
         */
        $this->verifyCallback($input, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $amount = $input['refund']['amount']/100;

        $body = array(
            'mid'           => $input['terminal']['gateway_merchant_id'],
            'txnType'       => Type::REFUND,
            'orderId'       => $input['payment']['id'],
            'txnId'         => $payment['txnid'],
            'refId'         => $input['refund']['id'],
            'refundAmount'  => sprintf('%0.2f', $amount),
        );

        // Commented to pick mid from terminal so that merchants can make test payment's for their mid in prod test mode

        //if ($this->mode === Mode::TEST)
        //{
        //    $content['mid'] = $this->config['test_merchant_id'];
        //}

        $checksum = Checksum::getChecksumFromString(json_encode($body), $this->getSecret());

        $content = [
            'body' => $body,
            'head' => [
                'signature' => $checksum
            ],
        ];

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'paytm' => $content
            ]);

        $content = $this->postRequestToPaytmV2($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'paytm' => $content
            ]);

        $this->verifySecureHashV2($content);

        if (isset($content['body']['resultInfo']['resultCode']) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                null,
                [
                    Payment\Gateway::GATEWAY_RESPONSE => json_encode($content['body']),
                    Payment\Gateway::GATEWAY_KEYS     => [],
                ]);
        }

        $responseCode = $content['body']['resultInfo']['resultCode'];

        $gatewayData = $this-> getGatewayKeysForRefund($content);

        if ($responseCode === '601')
        {
            $internalErrorCode = ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING;
        }
        else
        {
            $internalErrorCode = ResponseCodeMap::getApiErrorCode($responseCode);
        }

        $responseMessage = ResponseCode::getResponseMessage($responseCode);

        throw new Exception\GatewayErrorException(
            $internalErrorCode,
            $responseCode,
            $responseMessage,
            [
              Payment\Gateway::GATEWAY_RESPONSE => json_encode($content['body']),
              Payment\Gateway::GATEWAY_KEYS     => $gatewayData,
            ]);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        $scroogeResponse = new Base\ScroogeResponse();

        $body = array(
            'mid'           => $input['terminal']['gateway_merchant_id'],
            'orderId'       => $input['payment']['id'],
            'refId'         => $input['refund']['id'],
        );

        // Commented to pick mid from terminal so that merchants can make test payment's for their mid in prod test mode

        //if ($this->mode === Mode::TEST)
        //{
        //    $content['mid'] = $this->config['test_merchant_id'];
        //}

        $checksum = Checksum::getChecksumFromString(json_encode($body), $this->getSecret());

        $content = [
            'body' => $body,
            'head' => [
                'signature' => $checksum
            ],
        ];

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'paytm' => $content
            ]);

        $content = $this->postRequestToPaytmV2($content);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'paytm' => $content
            ]);

        $this->verifySecureHashV2($content);

        if (isset($content['body']['resultInfo']['resultCode']) === false)
        {
            throw new Exception\LogicException(
                'Unrecognized verify refund status',
                ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
                [
                    'gateway'  => 'paytm',
                    Payment\Gateway::GATEWAY_RESPONSE => json_encode($content['body']),
                    Payment\Gateway::GATEWAY_KEYS     => [],
                ]);
        }

        $gatewayData = $this-> getGatewayKeysForRefund($content);

        $scroogeResponse->setGatewayVerifyResponse(json_encode($content['body']))
                        ->setGatewayKeys($gatewayData);

        if ($gatewayData['resultCode'] === '10')
        {
            return $scroogeResponse->setSuccess(true)->toArray();
        }
        else if ($gatewayData['resultCode'] === '601')
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING)
                                   ->toArray();
        }
        else if ($gatewayData['resultCode'] === '631')
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }
        else
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::BAD_REQUEST_REFUND_FAILED)
                                   ->toArray();
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        /**
         * Processing verify requests for upi payment method paytm with upi common trait.
         * Checking method if upi then send to Mozart.
         */
        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiSendPaymentVerifyRequest($verify);
        }

        $content = array(
            'MID'       => $input['terminal']['gateway_merchant_id'],
            'ORDERID'  => $input['payment']['id']);

        $content['CHECKSUM'] = $this->getHashOfArrayForRefund($content);

        // Commented to pick mid from terminal so that merchants can make test payment's for their mid in prod test mode

        //$this->addTestMerchantIdIfTestMode($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $content);

        $content = $this->postRequestToPaytm($content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE, $content);

        return $content;
    }

    protected function verifyPayment($verify)
    {
        /**
         * Verifying payments for upi payment method paytm with upi common trait.
         * Checking method if upi then send to Mozart.
         */
        $method = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiVerifyPayment($verify);
        }

        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            $verify->gatewaySuccess = false;

            if ($payment['status'] !== Status::SUCCESS)
            {
                $verify->apiSuccess = false;
            }
            else if ($payment['status'] === Status::SUCCESS)
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = true;
            }
        }
        else if ($content['STATUS'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;

            if (($payment['status'] !== Status::SUCCESS) or
                ($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created'))
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
            else if ($payment['status'] === Status::SUCCESS)
            {
                $verify->apiSuccess = true;

                $amountRefunded = (int) ($content['REFUNDAMT'] * 100);

                // Check that refund amount matches.
                if ($amountRefunded !== $verify->input['payment']['amount_refunded'])
                {
                    $verify->status = VerifyResult::REFUND_AMOUNT_MISMATCH;
                }
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->verifyContentSaveIfNeeded($verify->match, $payment, $content);

        return $verify->status;
    }

    protected function verifyContentSaveIfNeeded($match, $payment, $content)
    {
        $invalidOrderIdRespCode = array(
            '334',
            '309');

        if (in_array($content['RESPCODE'], $invalidOrderIdRespCode, true) === false)
        {
            $contentToStore = [];

            foreach ($content as $key => & $value)
            {
                if ($value !== '')
                {
                    $contentToStore[$key] = $value;
                }
            }

            $attr = $this->lowerArrayKeys($contentToStore);
            $payment->fill($attr);
            $payment->saveOrFail();
        }
    }

    protected function postRequestToPaytm($content)
    {
        $content = 'JsonData='.json_encode($content);

        $request = array(
            'url' => $this->getUrl($this->action).'?'.$content,
            'content' => [],
            'method' => 'get');

        $response = $this->sendGatewayRequest($request);
        $content = json_decode($response->body, true);

        $this->response = $response;

        return $content;
    }

    protected function postRequestToPaytmV2($content)
    {
        $request = [
            'url'     => $this->getUrl($this->action),
            'content' => json_encode($content),
            'method'  => 'post',
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];

        $response = $this->sendGatewayRequest($request);
        $content = json_decode($response->body, true);

        $this->response = $response;

        return $content;
    }

    protected function getAuthRequestContentArray($input)
    {
        $content = $this->getAuthRequestDefaultContent($input);

        $method = $input['payment']['method'];

        if ($method === 'card')
        {
            $card = $input['card'];

            $expiryDate = $this->getFormattedCardExpiryDate($input);

            $cardDetails = $card['number'] . '|' . $card['cvv'] . '|' . $expiryDate;

            $content['PAYMENT_DETAILS'] = $this->getHashOfString($cardDetails);

            $content['AUTH_MODE'] = '3D';

            $type = $input['card']['type'];

            $cardType = Type::DC;

            if ($type === 'credit')
            {
                $cardType = Type::CC;
            }

            $content['PAYMENT_TYPE_ID'] = $cardType;

            $content['PAYMENT_MODE_ONLY'] = 'Yes';
        }
        else if ($method === 'netbanking')
        {
            $content['BANK_CODE'] = $this->getBankCode($input);

            $content['PAYMENT_TYPE_ID'] = Type::NB;

            $content['AUTH_MODE'] = 'USRPWD';

            $content['PAYMENT_MODE_ONLY'] = 'Yes';
        }
        // Commented to pick mid from terminal so that merchants can make test payment's for their mid in prod test mode

        //$this->addMerchantIdAndOtherDetails($content, $input['terminal']);

        return $content;
    }

    protected function getAuthRequestDefaultContent($input)
    {
        $method = $input['payment']['method'];

        $type = RequestType::THEDEFAULT;

        if ($method === 'card')
        {
            $type = RequestType::SEAMLESS;
        }

        $mobileNo = $this->getMobileNumber($input['payment']['contact']);

        $content = array(
            'REQUEST_TYPE'              => $type,
            'MID'                       => $input['terminal']['gateway_merchant_id'],
            'ORDER_ID'                  => $input['payment']['id'],
            'CUST_ID'                   => $input['payment']['email']?? 'void@razorpay.com',
            'MOBILE_NO'                 => $mobileNo,
            'EMAIL'                     => $input['payment']['email'] ?? 'void@razorpay.com',
            'CHANNEL_ID'                => 'WEB',
            'TXN_AMOUNT'                => (string) $input['payment']['amount'] / 100,
            'WEBSITE'                   => $input['terminal']['gateway_access_code'],
            'INDUSTRY_TYPE_ID'          => $input['terminal']['gateway_terminal_id'],
            'CALLBACK_URL'              => $input['callbackUrl'],
        );

        return $content;
    }

    protected function getBankCode($input)
    {
        $codes = BankCodes::$bankCodeMap;

        $bank = $input['payment']['bank'];

        return $codes[$bank];
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $attr = $this->lowerArrayKeys($attributes);
        $attr['txntype'] = Type::SALE;

        $payment = $this->getNewGatewayPaymentEntity();
        $payment->setPaymentId($attr['order_id']);
        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function createGatewayRefundEntity($attributes, $input)
    {
        $attributes['refund_id'] = $input['refund']['id'];
        $attributes['payment_id'] = $input['payment']['id'];

        $refund = $this->createGatewayEntity($attributes);

        return $refund;
    }

    protected function createGatewayEntity($attributes)
    {
        $attr = $this->lowerArrayKeys($attributes);

        $payment = $this->getNewGatewayPaymentEntity();

        $payment->setAction($this->action);
        $payment->setMethod($this->input['payment']['method']);

        $payment->fill($attr);

        $payment->saveOrFail();

        return $payment;
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer = [];

        if (empty($gatewayPayment[Entity::TXNID]) === false)
        {
            $acquirer['acquirer'] = [
                Payment\Entity::REFERENCE1 => $gatewayPayment->getGatewayTransactionId()
            ];
        }

        return $acquirer;
    }

    protected function lowerArrayKeys($array)
    {
        $ar = array();

        if (is_array($array) === true)
        {
            foreach ($array as $key => $value)
            {
                $ar[strtolower($key)] = $value;
            }
        }

        return $ar;
    }

    protected function addMerchantIdAndOtherDetails(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['MID'] = $this->config['test_merchant_id'];
            $content['WEBSITE'] = 'WEBSTAGING';
            $content['INDUSTRY_TYPE_ID'] = 'Retail';
        }
    }

    protected function addTestMerchantIdIfTestMode(array & $content)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['MID'] = $this->config['test_merchant_id'];
        }
    }

    protected function verifySecureHash(array $content)
    {
        $res = false;

        if (isset($content['CHECKSUMHASH']) === false)
        {
            $this->trace->error(TraceCode::GATEWAY_PAYMENT_ERROR, $content);

            if ($content['STATUS'] === Status::FAILURE)
            {
                return;
            }
        }
        else
        {
            $checksum = $content['CHECKSUMHASH'];
            unset($content['CHECKSUMHASH']);

            $secret = $this->getSecret();

            $res = Checksum::verifychecksum_e($content, $secret, $checksum);
        }

        if ($res === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getTestSecret()
    {
        return $this->input['terminal']['gateway_secure_secret'];
    }

    protected function verifySecureHashV2(array $content)
    {
        $res = false;

        if (isset($content['head']['signature']) === true)
        {
            $actual = $content['head']['signature'];

            $res = Checksum::verifychecksum_eFromStr(json_encode($content['body']), $this->getSecret(), $actual);
        }

        if ($res === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        $code = (int) $input['gateway']['RESPCODE'];

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            $errorCode = ResponseCodeMap::getApiErrorCode($code);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    $errorCode,
                    $input['gateway']['RESPCODE'],
                    $input['gateway']['RESPMSG']);
        }
    }

    protected function getHashOfArray($content)
    {
        $secret = $this->getSecret();

        return Checksum::getChecksumFromArray($content, $secret);
    }

    protected function getHashOfArrayForRefund($content)
    {
        $secret = $this->getSecret();

        return Checksum::getRefundChecksumFromArray($content, $secret);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return Checksum::encrypt_e($str, $secret);
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10) $expiryMonth = '0' . $expiryMonth;

        $cardExp = $expiryMonth . $input['card']['expiry_year'];

        return $cardExp;
    }

    protected function getFormattedEmail($email)
    {
        //
        // Remove all characters other than alhpanumeric, @ and .
        //
        return preg_replace("/[^a-zA-Z0-9@.]+/", '', $email);
    }

    protected function getMobileNumber($contact)
    {
        $chars = ['+', '(', ')'];
        return str_replace($chars, '', $contact);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getGatewayKeysForRefund($content)
    {
        return [
            'resultStatus'                   => $content['body']['resultInfo']['resultStatus'] ?? null,
            'resultCode'                     => $content['body']['resultInfo']['resultCode'],
            'refundId'                       => $content['body']['refundId'] ?? null,
            'rrn'                            => $content['body']['refundDetailInfoList']['rrn'] ?? null,
        ];
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        /*
         * If verify returns false, we throw an error as authorize request / response has been tampered with
         */
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $rzpAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $gatewayAmount = number_format($verify->verifyResponseContent['TXNAMOUNT'], 2, '.', '');

        $this->assertAmount($rzpAmount, $gatewayAmount);
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ($content['STATUS'] === Status::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    /**
     * For Paytm UPI using static callback route to process callback response.
     * Pre Process returning as it is because response is in plan text form.
     * @param $input
     * @return array
     */

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    /**
     * Extracting paymentId from pre process server callback function's response.
     * @param array $response
     * @param $gateway
     * @return string
     */

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        return $response['ORDERID'];
    }

    /**
     * Function to post process the response of callback. In case of success, returns true.
     * However in case of exception, suppress the error and returns failure response.
     * @param $input
     * @param null $exception
     * @return bool[] - true or false
     */

    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];

    }

    /**
     * If payment method upi then using upi repository to fetch payments details
     * Else parent repository to fetch payments details
     * @param Verify $verify
     * @return string
     */

    public function getPaymentToVerify(Verify $verify)
    {
        if ($verify->input['payment']['method'] === Payment\Method::UPI)
        {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                              $verify->input['payment']['id'], Action::AUTHORIZE);

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }

        return parent::getPaymentToVerify($verify);
    }
}
