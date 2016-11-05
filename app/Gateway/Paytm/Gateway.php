<?php

namespace RZP\Gateway\Paytm;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Paytm;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = 'paytm';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $this->createGatewayPaymentEntity($content);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'content' => $content,
            'method' => 'post');

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->verifySecureHash($input['gateway']);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['gateway']['ORDERID'], Action::AUTHORIZE);

        $values = $this->lowerArrayKeys($input['gateway']);

        $values['received'] = 1;
        $payment->fill($values);
        $this->repo->saveOrFail($payment);

        $this->verifyPaymentCallbackResponse($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = array(
            'MID'           => $input['terminal']['gateway_merchant_id'],
            'TXNID'         => $payment['txnid'],
            'ORDERID'       => $input['payment']['id'],
            'TXNTYPE'       => Type::REFUND,
            'REFUNDAMOUNT'  => (string) ($input['refund']['amount'] / 100),
        );

        $this->addTestMerchantIdIfTestMode($content);

        $storeContent = $content;
        $storeContent['CUST_ID'] = $payment['cust_id'];
        $storeContent['CHANNEL_ID'] = $payment['channel_id'];
        $storeContent['INDUSTRY_TYPE_ID'] = $payment['industry_type_id'];
        $storeContent['REQUEST_TYPE'] = RequestType::THEDEFAULT;
        $storeContent['TXN_AMOUNT'] = $payment['txn_amount'];
        $storeContent['PAYMENTMODE'] = $payment['paymentmode'];
        $storeContent['PAYMENT_MODE_ONLY'] = $payment['payment_mode_only'];
        $storeContent['AUTH_MODE'] = $payment['auth_mode'];
        $storeContent['PAYMENT_TYPE_ID'] = $payment['payment_type_id'];
        $storeContent['BANK_CODE'] = $payment['bank_code'];

        $refund = $this->createGatewayRefundEntity($storeContent, $input);

        $content['CHECKSUM'] = $this->generateHash($content);

        $content = $this->postRequestToPaytm($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REFUND, ['paytm' => $content]);

        $attr = $this->lowerArrayKeys($content);
        $attr['received'] = 1;

        $refund->fill($attr);
        $this->repo->saveOrFail($refund);

        if ($content['STATUS'] !== Status::SUCCESS)
        {
            if ($content['RESPCODE'] === '610')
            {
                // This means payment is already refunded fully or partially.
                $refundAmt = (int) ($content['REFUNDAMOUNT'] * 100);

                if ($refundAmt === $input['refund']['amount'])
                {
                    return;
                }
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_REFUND_FAILED,
                    $content['RESPCODE'],
                    $content['RESPMSG']);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $content = array(
            'MID'       => $input['terminal']['gateway_merchant_id'],
            'ORDERID'  => $input['payment']['id']);

        $this->addTestMerchantIdIfTestMode($content);

        $content = $this->postRequestToPaytm($content);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        return $content;
    }

    protected function verifyPayment($verify)
    {
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

        if (($payment['received'] === false) and
            (in_array($content['RESPCODE'], $invalidOrderIdRespCode, true) === false))
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

    protected function getAuthRequestContentArray($input)
    {
        $content = $this->getAuthRequestDefaultContent($input);

        $method = $input['payment']['method'];

        if ($method === 'card')
        {
            $card = $input['card'];
            $expiryDate = $this->getFormattedCardExpiryDate($input);
            $cardDetails = $card['number'] . '|' . $card['cvv'] .
                '|' . $expiryDate;
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
        else if ($method === 'wallet')
        {
            ;
        }

        $this->addMerchantIdAndOtherDetails($content, $input['terminal']);

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
        $email = $this->getFormattedEmail($input['payment']['email']);

        $content = array(
            'REQUEST_TYPE'              => $type,
            'MID'                       => $input['terminal']['gateway_merchant_id'],
            'ORDER_ID'                  => $input['payment']['id'],
            'TXN_AMOUNT'                => $input['payment']['amount'] / 100,
            'CUST_ID'                   => $input['payment']['email'],
            'CHANNEL_ID'                => 'WEB',
            'INDUSTRY_TYPE_ID'          => $input['terminal']['gateway_terminal_id'],
            'WEBSITE'                   => $input['terminal']['gateway_access_code'],
            'CALLBACK_URL'              => $input['callbackUrl'],
            'MOBILE_NO'                 => $mobileNo,
            'EMAIL'                     => $input['payment']['email'],
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
            $content['WEBSITE'] = 'Razorweb';
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
}