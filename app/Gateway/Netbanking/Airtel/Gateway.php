<?php

namespace RZP\Gateway\Netbanking\Airtel;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base as GatewayBase;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Terminal\Entity;

class Gateway extends Base\Gateway
{
    use GatewayBase\AuthorizeFailed;

    protected $gateway = 'netbanking_airtel';

    protected $bank = 'airtel';

    protected $map = [
        RequestFields::AMOUNT => 'amount'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->createAuthorizeRequestData($input);

        $entity = $this->createPaymentArray($input);

        $payment = $this->createGatewayPaymentEntity($entity);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $content);

        $attrs = $this->getCallackAttributes($content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], GatewayBase\Action::AUTHORIZE);

        $payment->fill($attrs);

        $payment->saveOrFail();

        if ($attrs['status'] !== Constants::SUCCESS)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new GatewayBase\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $response;
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseBody;

        // Response is originally a string.
        $response = $this->getVerifyResponseArray($content);

        // sd($response);

        $status = GatewayBase\VerifyResult::STATUS_MATCH;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            $response);

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if (isset($response[VerifyFields::STATUS]) and
            $response[VerifyFields::STATUS] === Constants::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = GatewayBase\VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === GatewayBase\VerifyResult::STATUS_MATCH)
                            ? true : false;

        return $status;
    }

    protected function createAuthorizeRequestData($input)
    {
        $defaultData = $this->getEncryptionArray($input);

        $hash = $this->getHash($defaultData);

        $callbackUrl = $input['callbackUrl'];

        $data = [
            RequestFields::SUCCESS_URL      => $callbackUrl,
            RequestFields::FAILURE_URL      => $callbackUrl,
            // Not using mer service and end mid for now
            RequestFields::CURRENCY         => Constants::INDIAN_RUPEE,
            RequestFields::CUSTOMER_MOBILE  => $input['payment']['contact'],
            RequestFields::CUSTOMER_EMAIL   => $input['payment']['email'],
            RequestFields::HASH             => $hash,
        ];

        $data = array_merge($defaultData, $data);

        return $data;
    }

    protected function createPaymentArray($input)
    {
        $amount = $input['payment']['amount'] / 100;

        return [
            RequestFields::AMOUNT => $amount
        ];
    }

    protected function getEncryptionArray($input)
    {
        $mid = $this->getMerchantId();

        $amount = (double) $input['payment']['amount'] / 100;

        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'UTC')
            ->format('dmYhms');

        return [
            RequestFields::MERCHANT_ID               => $mid,
            RequestFields::TRANSACTION_REFERENCE_NO  => $input['payment']['id'],
            RequestFields::AMOUNT                    => $amount,
            RequestFields::DATE                      => $date,
            RequestFields::SERVICE                   => Constants::NETBANKING,
        ];
    }

    protected function getHash($data)
    {
        $values = array_values($data);

        $salt = $this->getSalt();

        array_push($values, $salt);

        $text = implode('#', $values);

        return hash(Constants::HASH_ALGORITHM, $text);
    }

    protected function getCallackAttributes($content)
    {
        // double check
        return [
            'received'  => true,
            'status'    => $content[ResponseFields::STATUS],
            'bank_payment_id' => $content[ResponseFields::TRANSACTION_ID]
        ];
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;

        $date = Carbon::createFromTimestamp(
            $input['payment']['created_at'], 'UTC')
            ->format('dmYhms');

        $merchantId = $this->getMerchantId();

        $paymentId = $input['payment']['id'];

        $amount = $input['payment']['amount'] / 100;

        $data = [
            VerifyFields::SESSION_ID               => uniqid(),
            VerifyFields::TRANSACTION_REFERENCE_NO => $paymentId,
            VerifyFields::TRANSACTION_DATE         => $date,
            VerifyFields::MERCHANT_ID              => $merchantId,
            VerifyFields::HASH                     => '',
            VerifyFields::AMOUNT                   => "$amount"
        ];

        $hashArray = $this->getVerifyHashArray($data);

        $data[VerifyFields::HASH] = $this->getHash($hashArray);

        return json_encode($data);
    }

    protected function getVerifyResponseArray($content)
    {
        return (array) json_decode($content);
    }

    protected function getVerifyHashArray($data)
    {
        return [
            VerifyFields::MERCHANT_ID              => $data[VerifyFields::MERCHANT_ID],
            VerifyFields::TRANSACTION_REFERENCE_NO => $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            VerifyFields::AMOUNT                   => $data[VerifyFields::AMOUNT],
            VerifyFields::TRANSACTION_DATE         => $data[VerifyFields::TRANSACTION_DATE],
        ];
    }

    public function getMerchantId()
    {
        $mid = $this->terminal[Entity::GATEWAY_TERMINAL_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->config['test_merchant_id'];
        }

        return $mid;
    }

    public function getSalt()
    {
        $salt = $this->terminal[Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === Mode::TEST)
        {
            $salt = $this->config['test_salt'];
        }

        return $salt;
    }
}
