<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use Carbon\Carbon;

use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Terminal;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

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

        // Create a payment gateway entity and save it.
        $contentToSave = [
            RequestFields::MID         => $this->getMerchantId(),
            RequestFields::CUST_EMAIL  => $input['payment']['email'],
            RequestFields::CUST_MOBILE => $this->getFormattedContact($input['payment']['contact']),
            RequestFields::AMT         => $input['payment']['amount'],
            'received'                 => false,
        ];

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        // It is a redirect to airtel money.
        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $this->callbackAuthFailureFlow($input);
        }
        else
        {
            $this->callbackAuthSuccessFlow($input);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->xmlToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, $content);

        $refundData = $this->getRefundWalletEntityData($input);

        // Save the error in gateway payment entity
        if ((isset($content[ResponseFields::STATUS]) === false) or
            ($content[ResponseFields::STATUS] !== Status::SUCCESS))
        {
            $refundData['response_description'] = substr($content[ResponseFields::MSG], 0, 255);
            $refundData['status_code'] = $content[ResponseFields::STATUS];

            $this->createGatewayRefundEntity($refundData);

            $this->handleRequestFailure($content);
        }
        else
        {
            $reference2 = $this->getEpochTime(
                $content[ResponseFields::NEW_FDC_TXN_DATE],
                DateFormat::NEW_FDC_TXN_DATE_FORMAT);

            $contentToSave = [
                'response_description'  => substr($content[ResponseFields::MSG], 0, 255),
                'status_code'           => $content[ResponseFields::STATUS],
                'gateway_refund_id'     => $content[ResponseFields::NEW_FDC_TXN_ID],
                'reference2'            => $reference2,
            ];

            foreach ($contentToSave as $param => $value)
            {
                $refundData[$param] = $value;
            }

            $this->createGatewayRefundEntity($refundData);
        }
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
        $requestDate = $this->getFormattedDate(
            $input['payment']['created_at'],
            DateFormat::REQUEST_DATE_FORMAT);

        $content = [
            RequestFields::MID        => $this->getMerchantId(),
            RequestFields::TXN_REF_NO => $input['payment']['id'],
            RequestFields::DATE       => $requestDate,
        ];

        $request = $this->getStandardRequestArray($content);

        $this->setProxy($request);

        return $request;
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        if (isset($content['nestedParams']) === false)
        {
            // Single transaction is present under razorpay_payment_id, Check
            // if it was successful on airtel's end.
            if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
            {
                $this->verifyStatusOnGatewayFailure($verify, $gatewayPayment, $input);
            }
            else if ($content[ResponseFields::STATUS] === Status::SUCCESS)
            {
                $this->verifyStatusOnGatewaySuccess($verify, $input);
            }
        }
        else
        {
            // Verify API of airtel sends multiple transaction per API call.
            // In our case, Refund and Authorize transactions as they use same
            // payment ID. We handle the case differently.
            $content = (array) $content['nestedParams'];
            $content = $content['nParamList'];
            $authTransaction  = $this->getVerifyArrayFromXml((array) $content[0]);
            $refundTransaction = $this->getVerifyArrayFromXml((array) $content[1]);

            $verify->gatewaySuccess = false;

            // If both refund and authorize are successful. Mark it as gateway
            // success.
            if (($authTransaction[ResponseFields::STATUS] === Status::SUCCESS) and
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

        $verify->payment = $this->saveVerifyContentIfNeeded($gatewayPayment, $content);

        return $verify->status;
    }

    protected function verifyStatusOnGatewayFailure($verify, $gatewayPayment, $input)
    {
        $verify->gatewaySuccess = false;

        if (($gatewayPayment === null) or
            (($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created')))
        {
            $verify->apiSuccess = false;
        }
        else if (($gatewayPayment['received'] === false) and
                 (($gatewayPayment['status_code'] === null) or
                    ($gatewayPayment['status_code'] !== Status::SUCCESS)))
        {
            $verify->apiSuccess = false;
        }
        else if ($gatewayPayment['status_code'] === Status::SUCCESS)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
            $verify->apiSuccess = true;
        }
    }

    protected function verifyStatusOnGatewaySuccess($verify, $input)
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

    protected function saveVerifyContentIfNeeded($gatewayPayment, $content)
    {
        $this->action = Action::AUTHORIZE;

        if ((isset($content[ResponseFields::STATUS])) and
            ($content[ResponseFields::STATUS] === Status::SUCCESS))
        {
            $walletAttributes = $this->getWalletContentFromVerify(
                $gatewayPayment, $content);

            if ($gatewayPayment === null)
            {
                $gatewayPayment = $this->createGatewayPaymentEntity($walletAttributes);
            }
            else if ($gatewayPayment['received'] === false)
            {
                $attrs = $this->getMappedAttributes($walletAttributes);

                $gatewayPayment->fill($attrs);

                $gatewayPayment->saveOrFail();
            }
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }

    protected function getWalletContentFromVerify($gatewayPayment, $content)
    {
        $contentToSave = [
            RequestFields::MID         => $this->getMerchantId(),
            RequestFields::CUST_EMAIL  => $this->input['payment']['email'],
            RequestFields::CUST_MOBILE => $this->getFormattedContact($this->input['payment']['contact']),
            ResponseFields::STATUS     => Status::SUCCESS,
            RequestFields::TXN_REF_NO  => $this->input['payment']['id'],
            'received'                 => true,
        ];

        // Payment was late authorized
        if ((empty($gatewayPayment['gateway_payment_id']) === true) and
            (empty($content[ResponseFields::FDC_TXN_ID]) === false))
        {
            $contentToSave[ResponseFields::TRAN_ID] = $content[ResponseFields::FDC_TXN_ID];
        }

        if (isset($gatewayPayment['amount']) === false)
        {
            $contentToSave[RequestFields::AMT] = $this->input['payment']['amount'];
        }

        return $contentToSave;
    }

    protected function getRefundWalletEntityData($input)
    {
        $contentToSave = [
            'payment_id'            => $input['payment']['id'],
            'action'                => $this->action,
            'amount'                => $input['refund']['amount'],
            'wallet'                => $input['payment']['wallet'],
            'email'                 => $input['payment']['email'],
            'received'              => true,
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId(),
            'refund_id'             => $input['refund']['id'],
        ];

        return $contentToSave;
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        assertTrue($this->mode === Mode::LIVE);

        // We are fetching merchant id from config
        // as it's common across all the merchants
        return $this->config['live_merchant_id'];
    }

    protected function getEndMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_end_mid'];
        }

        assertTrue($this->mode === Mode::LIVE);

        return $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
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
            if (isset($content[ResponseFields::MESSAGE]))
            {
                // Handle Generic Interface layer messages. They are not API
                // Messages from Airtel.
                // They have only two fields - STATUS and MESSAGE
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                    $content[ResponseFields::STATUS],
                    $content[ResponseFields::MESSAGE]);
            }
            else if (isset($content[ResponseFields::ERR_CODE]))
            {
                // It's a different exception of airtel money
                // Same amount transaction under same tran ID(airtel's),
                // there should be a time difference of 5 minutes.
                // Need more clarification on why it occurs.
                throw new Exception\GatewayErrorException(
                    ResponseCodeMap::getApiErrorCode($content[ResponseFields::ERR_CODE]),
                    $content[ResponseFields::ERR_CODE],
                    $content[ResponseFields::TEXT]);
            }

            throw new Exception\GatewayErrorException(
                ResponseCodeMap::getApiErrorCode($content[ResponseFields::CODE]),
                $content[ResponseFields::CODE],
                substr($content[ResponseFields::MSG], 0, 255));
        }
    }

    protected function callbackAuthSuccessFlow(array $input)
    {
        $content = $input['gateway'];

        $date = $this->getEpochTime(
            $content[ResponseFields::TRAN_DATE],
            DateFormat::TRAN_DATE_FORMAT);

        // Create a payment gateway entity and save it.
        $contentToSave = [
            ResponseFields::STATUS     => $content[ResponseFields::STATUS],
            ResponseFields::MSG        => substr($content[ResponseFields::MSG], 0, 255),
            ResponseFields::TXN_REF_NO => $content[ResponseFields::TXN_REF_NO],
            ResponseFields::TRAN_ID    => $content[ResponseFields::TRAN_ID],
            ResponseFields::TRAN_DATE  => $date,
            'received'                 => true
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);

        //TODO Temporary solution for checksum
        $this->verifyPaymentInAuthorize($input, $content);

    }

    protected function verifyPaymentInAuthorize(array $input, array $content)
    {
        $this->action = Action::VERIFY;

        $verify = new Verify($this->gateway, $input);
        $verifyContent = $this->sendPaymentVerifyRequest($verify);

        $this->action = Action::CALLBACK;

        assertTrue($verifyContent[ResponseFields::STATUS] === Status::SUCCESS);
        assertTrue((float) $verifyContent[ResponseFields::TXN_AMT] === (float) $content[ResponseFields::TRAN_AMT]);
    }

    /**
     * Store the failure details in payment gateway entity
     * and throw an exception
     */
    protected function callbackAuthFailureFlow(array $input)
    {
        $content = $input['gateway'];

        // Create a payment gateway entity and save it.
        $contentToSave = [
            ResponseFields::STATUS  => $content[ResponseFields::STATUS],
            ResponseFields::MSG     => substr($content[ResponseFields::MSG], 0, 255),
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);

        // Throw exception for the failure
        $this->handleRequestFailure($content);
    }

    protected function getHashOfString($hashString)
    {
        // Secret should only be accessed here.
        $secret = $this->getSecret();

        $hashString = $hashString.'#'.$secret;

        return hash(HashAlgo::SHA512, $hashString, false);
    }

    protected function getAuthRedirectRequestArray($input)
    {
        $payment = $input['payment'];

        $date = $this->getFormattedDate(
            $payment['created_at'],
            DateFormat::REQUEST_DATE_FORMAT);

        $content = [
            RequestFields::MID         => $this->getMerchantId(),
            RequestFields::TXN_REF_NO  => $payment['id'],
            RequestFields::SU          => $input['callbackUrl'],
            RequestFields::FU          => $input['callbackUrl'],
            RequestFields::AMT         => ($input['payment']['amount'] / 100),
            RequestFields::CUR         => 'INR',
            RequestFields::DATE        => $date,
            RequestFields::CUST_MOBILE => $this->getFormattedContact($payment['contact']),
            RequestFields::CUST_EMAIL  => $payment['email'],
            RequestFields::END_MID     => $this->getEndMerchantId($input['terminal']),
        ];

        $content[RequestFields::HASH] = $this->getHashOfArray($content);

        return $this->getStandardRequestArray($content);
    }

    protected function getStringToHash($content, $glue = '')
    {
        $hashArray = [
            $content['MID'],
            $content['TXN_REF_NO'],
            $content['AMT'],
            $content['DATE'],
        ];

        return implode('#', $hashArray);
    }

    protected function getRefundRequestArray(array $input)
    {
        $wallet = $this->repo->fetchWalletByPaymentId($input['payment']['id']);

        $date = $this->getFormattedDate(
            $wallet['reference1'],
            DateFormat::REQUEST_DATE_FORMAT);

        $content = [
            RequestFields::MID     => $this->getMerchantId(),
            RequestFields::TXN_ID  => $wallet['gateway_payment_id'],
            RequestFields::AMT     => ($input['amount'] / 100),
            RequestFields::DATE    => $date,
            RequestFields::REMARKS => 'Razorpay Refund',
        ];

        $request = $this->getStandardRequestArray($content);

        $this->setProxy($request);

        return $request;
    }

    protected function setProxy(& $request)
    {
        if ($this->mode === Mode::LIVE)
        {
            $request['options']['proxy'] = $this->proxy;
        }
    }

    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }
}
