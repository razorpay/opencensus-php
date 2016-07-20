<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Trace\Trace;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Wallet\Olamoney\Action;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Constants\HashAlgo;
use Carbon\Carbon;
use RZP\Gateway\Wallet\Olamoney\RequestFields as RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields as ResponseFields;
use RZP\Gateway\Wallet\Olamoney\Enquiry;
use RZP\Gateway\Wallet\Olamoney\Refund;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;
    use Enquiry;
    use Refund;

    protected $gateway = 'wallet_olamoney';

    protected $canRunOtpFlow = false;

    protected $map = array(
        'email'                 => 'email',
        'contact'               => 'contact',
        'status'                => 'status_code',
        'amount'                => 'amount',
        'received'              => 'received',
        'gateway_merchant_id'   => 'gateway_merchant_id',
        'transactionId'         => 'gateway_payment_id',
    );

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getBillGeneratorRequest($input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackRedirectFlow($input);
    }

    protected function callbackRedirectFlow($input)
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $input['gateway']);

        $this->verifySecureHash($input['gateway']);

        //  Changing action to AUTHORIZE to keep the action consistent
        $this->action = Action::AUTHORIZE;

        $gatewayPaymentAttrs = $this->getCreateWalletAttributes($input);

        $this->createGatewayPaymentEntity($gatewayPaymentAttrs);

        $this->action = Action::CALLBACK;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'request' => $input['gateway'],
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        $this->verifyPaymentCallbackResponse($input);
    }

    protected function getCreateWalletAttributes($input)
    {
        $contentToSave = array(
            'amount'                => (string) ($input['payment']['amount']),
            'received'              => true,
            'email'                 => $input['payment']['email'],
            'contact'               => $this->getFormattedContact($input['payment']['contact']),
            'gateway_merchant_id'   => $this->getMerchantId($input['terminal']),
            'status'                => $input['gateway']['status'],
            'transactionId'         => $input['gateway']['transactionId'],
        );

        return $contentToSave;
    }

    protected function verifyPaymentCallbackResponse($input)
    {
        $content = $input['gateway'];

        if ($content['status'] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['status'],
                $content['message']);
        }
    }

    public function verifySecureHash($content)
    {
        $fieldsInOrder = array(
            ResponseFields::TYPE,
            ResponseFields::STATUS,
            ResponseFields::MERCHANT_BILL_ID,
            ResponseFields::TRANSACTION_ID,
            ResponseFields::AMOUNT,
            ResponseFields::COMMENTS,
            ResponseFields::UDF,
            ResponseFields::TIMESTAMP,
        );

        $hash = $content[ResponseFields::HASH];

        $content = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        $generatedHash = $this->getHashOfArray($content);

        if ($generatedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    /**
     * This method will be used when power-wallet is enabled for Olamoney.
     * It is currently implemented using redirect-flow and not as a power-wallet.
     */
    public function otpGenerate($input)
    {
        $this->action($input, Action::OTP_GENERATE);

        $request = $this->getOtpGenerateRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $code = $content['status'];

        if ($code !== Status::SUCCESS)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

            if (isset($content['errorCode']))
            {
                $errorCode = ResponseCode::getApiErrorCode($content['errorCode']);
            }

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content['status'],
                $content['message']);
        }
    }

    /**
     * This method will be used when power-wallet is enabled for Olamoney.
     * It is currently implemented using redirect-flow and not as a power-wallet.
     */
    public function callbackOtpSubmit(array $input)
    {
        $this->action($input, Action::OTP_SUBMIT);

        $this->verifyOtpAttempts($input['payment']);

        $request = $this->getOtpSubmitRequestArray($input);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            $errorCode = ResponseCode::getApiErrorCode($content[ResponseFields::ERROR_CODE]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                $errorCode,
                $content[ResponseFields::STATUS],
                $content[ResponseFields::MESSAGE]);
        }
    }

    protected function getBillGeneratorRequest($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $queryArray = array(
            RequestFields::BILL   => base64_encode(json_encode($content)),
            RequestFields::PHONE  => $input['payment']['contact'],
        );

        $query = http_build_query($queryArray);

        $url = $this->getUrl();

        $request = [
            'method'  => 'get',
            'url'     => $url. '?' . $query,
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpGenerateAttributes($input)
    {
        $amount = (string) ($input['payment']['amount'] / 100);

        $content = array(
            RequestFields::COMMAND          => Command::DEBIT,
            RequestFields::ACCESS_TOKEN     => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID        => $input['payment']['id'],
            RequestFields::COMMENTS         => 'Razorpay_payment',
            RequestFields::UDF              => $input['payment']['public_id'],
            RequestFields::RETURN_URL       => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL => '',
            RequestFields::AMOUNT           => $amount,
            RequestFields::CURRENCY         => $input['payment']['currency'],
            RequestFields::COUPON_CODE      => 'NA',
        );

        $content[RequestFields::HASH] = $this->getHashForOtpGenerate($content);

        return $content;
    }

    protected function getOtpGenerateRequestArray($input)
    {
        $content = $this->getOtpGenerateAttributes($input);

        $request = $this->getStandardRequestArray($content);

        $request['headers'] = $this->getRequestHeaders();

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    protected function getOtpSubmitRequestArray($input)
    {
        $payment = $input['payment'];

        $content = array(
            RequestFields::COMMAND           => Command::CAPTURE,
            RequestFields::ACCESS_TOKEN       => $this->getAccessToken($input['terminal']),
            RequestFields::UNIQUE_ID          => $payment['id'],
            RequestFields::COMMENTS          => 'Razorpay payment',
            RequestFields::UDF               => $payment['public_id'],
            RequestFields::RETURN_URL         => $input['callbackUrl'],
            RequestFields::NOTIFICATION_URL   => '',
            RequestFields::AMOUNT            => (string) ($payment['amount'] / 100),
            RequestFields::CURRENCY          => $payment['currency'],
            RequestFields::COUPON_CODE        => 'NA',
            RequestFields::OTP               => $input['gateway']['otp'],
        );

        $content[RequestFields::HASH] = $this->getHashForOtpSubmit($content);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        return $request;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequest($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_REQUEST, $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->jsonToArray($response->body);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, $content);

        $this->createWalletRefundEntity($content, $input);

        if ($content[ResponseFields::STATUS] !== Status::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $content[ResponseFields::STATUS],
                $content[ResponseFields::MESSAGE]);
        }
    }

    protected function getAccessToken($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_access_code'];
        }

        return $terminal['gateway_access_code'];
    }

    protected function getMerchantId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getHashForOtpGenerate($content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::UNIQUE_ID,
            RequestFields::COMMENTS,
            RequestFields::UDF,
            RequestFields::RETURN_URL,
            RequestFields::NOTIFICATION_URL,
            RequestFields::CURRENCY,
            RequestFields::AMOUNT,
            RequestFields::COUPON_CODE,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    protected function getHashForOtpSubmit($content)
    {
        $fieldsInOrder = array(
            RequestFields::ACCESS_TOKEN,
            RequestFields::COMMAND,
            RequestFields::COMMENTS,
            RequestFields::NOTIFICATION_URL,
            RequestFields::OTP,
            RequestFields::RETURN_URL,
            RequestFields::UDF,
            RequestFields::UNIQUE_ID,
        );

        $orderedData = $this->getDataWithFieldsInOrder($content, $fieldsInOrder);

        return $this->getHashOfArray($orderedData);
    }

    public function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, "|");

        $str .= '|' . $this->getSecret();

        return $this->getHashOfString($str);
    }

    protected function getHashOfString($str)
    {
        return strtolower(hash(HashAlgo::SHA512, $str));
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        $url = constant($ns.'\Url::'.$type);

        $contact = $this->input['payment']['contact'];

        return strtr($url, [':contact' => $this->getFormattedContact($contact)]);
    }

    protected function getRequestHeaders()
    {
        return ['Content-Type' => 'application/json'];
    }
}
