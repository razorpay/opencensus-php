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
