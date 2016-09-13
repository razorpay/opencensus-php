<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    const PROXY_URL = 'https://splunk.razorpay.com:8888';

    protected $canRunOtpFlow = false;

    protected $topup = false;

    protected $gateway = 'wallet_airtelmoney';

    protected $map = [
        RequestFields::MID               => 'gateway_merchant_id',
        RequestFields::CUST_MOBILE       => 'contact',
        RequestFields::CUST_EMAIL        => 'email',
        RequestFields::TXN_REF_NO        => 'payment_id',
        RequestFields::AMT               => 'amount',
        ResponseFields::MSG              => 'response_description',
        ResponseFields::STATUS           => 'status_code',
        ResponseFields::TRAN_ID          => 'gateway_payment_id',
        ResponseFields::NEW_FDC_TXN_ID   => 'gateway_refund_id',
        ResponseFields::TRAN_DATE        => 'reference1',
        ResponseFields::NEW_FDC_TXN_DATE => 'reference2',
        'received'                       => 'received',
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthRedirectRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input);

        // It is a redirect to airtel money.
        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->handleRequestFailure($content);

        $this->callbackAuthSuccessFlow($input);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->xmlToArray($response->body);

        $this->handleRequestFailure($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $contentToSave = [
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            // Amount we are asking to refund or amount refunded by gateway
            'amount'                => $input['refund']['amount'],
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'received'              => true,
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'refund_id'             => $input['refund']['id'],
            'response_description'  => $content[ResponseFields::MSG],
            'status_code'           => $content[ResponseFields::STATUS],
            'gateway_refund_id'     => $content[ResponseFields::NEW_FDC_TXN_ID],
            'reference2'            => $this->getEpochTime(
                $content[ResponseFields::NEW_FDC_TXN_DATE],
                Constants::NEW_FDC_TXN_DATE_FORMAT),
        ];

        $this->createGatewayRefundEntity($contentToSave);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getVerifyRequestArray($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->response = $response;

        $content = $this->xmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'content'    => $content,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getVerifyRequestArray($input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $content = [
            RequestFields::MID        => $this->getMerchantId($input['terminal']),
            RequestFields::TXN_REF_NO => $input['payment']['id'],
            RequestFields::DATE       => $this->getFormattedDate(
                $wallet['reference1'],
                Constants::REQUEST_DATE_FORMAT),
        ];

        return $this->getStandardRequestArray($content, 'post');
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        // Verify API of airtel sends multiple transaction per API call.
        // In our case, Refund and Authorize transactions as they use same
        // payment ID. We handle the case differently.
        if (isset($content['nestedParams']) === false)
        {
            if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
            {
                $this->verifyStatusOnGatewayFailure($verify, $payment, $input);
            }
            else if ($content[ResponseFields::STATUS] === Status::SUCCESS)
            {
                $this->verifyStatusOnGatewaySuccess($verify, $payment, $input);
            }
        }
        else
        {
            $content = (array) $content['nestedParams'];
            $content = $content['nParamList'];
            $authTransaction  = $this->getVerifyArrayFromXml((array) $content[0]);
            $refundTransaction = $this->getVerifyArrayFromXml((array) $content[1]);

            $verify->gatewaySuccess = false;

            // If both refund and authorize are successful. Mark it as gateway
            // success.
            if ($authTransaction[ResponseFields::STATUS] === Status::SUCCESS and
                ($refundTransaction[ResponseFields::STATUS] === Status::SUCCESS))
            {
                $verify->gatewaySuccess = true;
            }

            if (($input['payment']['status'] !== 'created') and
                ($input['payment']['status'] !== 'failed'))
            {
                $verify->apiSuccess = true;
            }
            else
            {
                $verify->apiSuccess = false;

                if ($verify->gatewaySuccess)
                {
                    $verify->status = VerifyResult::STATUS_MISMATCH;
                }
            }

        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($payment, $content);

        return $verify->status;
    }

    protected function verifyStatusOnGatewayFailure($verify, $payment, $input)
    {
        $verify->gatewaySuccess = false;

        if (($payment === null) or
            (($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($payment['received'] === false) and
                    (($payment['status_code'] === null) or
                    ($payment['status_code'] !== Status::SUCCESS)))
        {
            $verify->apiSuccess = false;
        }
        else if ($payment['status_code'] === Status::SUCCESS)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyStatusOnGatewaySuccess($verify, $payment, $input)
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

    protected function getVerifyArrayFromXml(array $content)
    {
        $params = $content['nparam'];

        $transaction = array();

        foreach ($params as $param)
        {
            $attr = (array) $param;
            $attr = $attr['@attributes'];
            $transaction[$attr['name']] = $attr['value'];
        }
        return $transaction;
    }

    protected function saveVerifyContentIfNeeded($payment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if ((isset($content[ResponseFields::STATUS])) and
            ($content[ResponseFields::STATUS] === Status::VERIFY_SUCCESS))
        {
            $walletAttributes = $this->getWalletContentFromVerify($payment, $content);

            if ($payment === null)
            {
                $payment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if ($payment['received'] === false)
            {
                $payment->fill($walletAttributes);
                $payment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $payment;
    }

    protected function getWalletContentFromVerify($payment, array $content)
    {
        $contentToSave = [
            RequestFields::MID         => $this->getMerchantId($this->input['terminal']),
            RequestFields::CUST_EMAIL  => $this->input['payment']['email'],
            RequestFields::CUST_MOBILE => $this->getFormattedContact($this->input['payment']['contact']),
            ResponseFields::STATUS     => Status::SUCCESS,
            RequestFields::TXN_REF_NO  => $this->input['payment']['id'],
            'received'                 => true,
        ];

        if (isset($payment['amount']) === false)
        {
            $contentToSave[RequestFields::AMT] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getEndMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_end_mid'];
        }

        return $this->config['live_end_mid'];
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        return false;
    }

    protected function getFormattedDate($date = null, $format)
    {
        return Carbon::createFromTimestamp($date)->format($format);
    }

    protected function getEpochTime($date, $format)
    {
        return Carbon::createFromFormat($format, (string) $date)->timestamp;
    }

    protected function handleRequestFailure($content)
    {
        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->trace->error(TraceCode::GATEWAY_PAYMENT_ERROR, $content);

            if (isset($content[ResponseFields::MESSAGE]))
            {
                // Handle Generic Interface layer messages.
                // They have only two fields - STATUS and MESSAGE
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                    $content[ResponseFields::STATUS],
                    $content[ResponseFields::MESSAGE]);
            }
            else if (isset($content[ResponseFields::ERR_CODE]))
            {
                // It's a different exception of airtel money when refund for same
                // amount and same airtel transaction Id is initiated within a span
                // of 5 minutes.
                // TODO Good to have a test case to simulate it.
                throw new Exception\GatewayErrorException(
                    ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERR_CODE]),
                    $content[ResponseFields::ERR_CODE],
                    $content[ResponseFields::TEXT]);
            }

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::CODE]),
                $content[ResponseFields::CODE],
                $content[ResponseFields::MSG]);
        }
    }

    protected function callbackAuthSuccessFlow(array $input)
    {
        $content = $input['gateway'];

        $date = $this->getEpochTime($content[ResponseFields::TRAN_DATE], Constants::TRAN_DATE_FORMAT)

        // Create a payment gateway entity and save it.
        $contentToSave = [
            RequestFields::MID         => $this->getMerchantId($input['terminal']),
            RequestFields::CUST_EMAIL  => $input['payment']['email'],
            RequestFields::CUST_MOBILE => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::AMT         => $content[ResponseFields::TRAN_AMT]*100,
            ResponseFields::STATUS     => $content[ResponseFields::STATUS],
            ResponseFields::MSG        => $content[ResponseFields::MSG],
            ResponseFields::TXN_REF_NO => $content[ResponseFields::TXN_REF_NO],
            ResponseFields::TRAN_ID    => $content[ResponseFields::TRAN_ID],
            ResponseFields::TRAN_DATE  => $date,
            'received'                 => true
        ];

        // Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $this->createGatewayPaymentEntity($contentToSave);

        $this->action = Action::CALLBACK;
    }

    protected function getHashOfString($hashString)
    {
        return hash(HashAlgo::SHA512, $hashString, false);
    }

    protected function getAuthRedirectRequestArray($input)
    {
        $payment = $input['payment'];

        $date = $this->getFormattedDate($payment['created_at'], Constants::REQUEST_DATE_FORMAT)

        $content = [
            RequestFields::MID         => $this->getMerchantId($input['terminal']),
            RequestFields::TXN_REF_NO  => $payment['id'],
            RequestFields::SU          => $input['callbackUrl'],
            RequestFields::FU          => $input['callbackUrl'],
            RequestFields::AMT         => $input['payment']['amount']/100,
            RequestFields::CUR         => Constants::INR,
            RequestFields::DATE        => $date,
            RequestFields::CUST_MOBILE => $this->getFormattedContact($payment['contact']),
            RequestFields::CUST_EMAIL  => $payment['email'],
            RequestFields::END_MID     => $this->getEndMerchantId($input['terminal']),
        ];

        $content[RequestFields::HASH] = $this->getHashOfArray($content);

        return $this->getStandardRequestArray($content, 'post');
    }

    protected function getStringToHash($content, $glue = '')
    {
        $hashArray = [
            $content['MID'],
            $content['TXN_REF_NO'],
            $content['AMT'],
            $content['DATE'],
            $this->getSecret(),
        ];

        return implode('#', $hashArray);
    }

    protected function getRefundRequestArray(array $input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $date = $this->getFormattedDate($wallet['reference1'], Constants::REQUEST_DATE_FORMAT)

        $content = [
            RequestFields::MID     => $this->getMerchantId($input['terminal']),
            RequestFields::TXN_ID  => $wallet['gateway_payment_id'],
            RequestFields::AMT     => $input['amount']/100,
            RequestFields::DATE    => $date,
            RequestFields::REMARKS => 'Razorpay Refund',
        ];

        return $this->getStandardRequestArray($content, 'post');
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }
}
