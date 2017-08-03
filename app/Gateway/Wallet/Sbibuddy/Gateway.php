<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Entity;
use RZP\Gateway\Wallet\Base\Action;
use RZP\Models\Payment\Entity as Payment;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_sbibuddy';

    protected $map = [
        RequestFields::MERCHANT_ID          => Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT               => Entity::AMOUNT,
        ResponseFields::STATUS_CODE         => Entity::STATUS_CODE,
        ResponseFields::ERROR_DESCRIPTION   => Entity::RESPONSE_DESCRIPTION,
        ResponseFields::TRANSACTION_ID      => Entity::GATEWAY_PAYMENT_ID,
        Entity::CONTACT                     => Entity::CONTACT,
        Entity::RECEIVED                    => Entity::RECEIVED,
        Entity::DATE                        => Entity::DATE,
        Entity::EMAIL                       => Entity::EMAIL

    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthRequest($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        $contentToSave = [
            RequestFields::MERCHANT_ID  => $this->getMerchantId(),
            Entity::PAYMENT_ID          => $input['payment'][Payment::ID],
            RequestFields::AMOUNT       => $input['payment'][Payment::AMOUNT],
            Entity::EMAIL               => $input['payment'][Payment::EMAIL],
            Entity::CONTACT             => $this->getFormattedContact($input['payment'][Payment::CONTACT]),
            Entity::RECEIVED            => false
        ];
        // sd($contentToSave);

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    public function callback(array $input)
    {
        $data = $this->parseResponse($input['gateway']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, $data);

        $this->assertPaymentId($input['payment']['id'], $data[ResponseFields::ORDER_ID]);

        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        // If status code is not success code
        if ($data[ResponseFields::STATUS_CODE] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $contentToSave = $data + [
                Entity::RECEIVED                => true,
                Entity::DATE                    => $date
            ];

            $wallet = $this->repo->findByPaymentIdAndAction(
                $input['payment']['id'], Action::AUTHORIZE);

            $this->updateGatewayPaymentEntity($wallet, $contentToSave);

            $this->handleCallbackFailure($data);
        }

        $this->callbackAuthSuccessFlow($input, $data);

        return $this->getCallbackResponseData($input);
    }

    protected function callbackAuthSuccessFlow($input, $data)
    {
        $content = $input['gateway'];

        $contentToSave = $data + [
            Entity::RECEIVED                => true,
        ];

        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $this->updateGatewayPaymentEntity($wallet, $contentToSave);
    }

    protected function handleCallbackFailure($content)
    {
        throw new Exception\GatewayErrorException(
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            $content[ResponseFields::STATUS_CODE],
            $content[ResponseFields::ERROR_DESCRIPTION]
        );
    }

    protected function parseResponse($input)
    {
        $cryptor = $this->getEncryptor();

        $decryptedInput = $cryptor->decrypt($input[ResponseFields::ENCRYPTED_DATA]);

        $data = [];

        parse_str($decryptedInput, $data);

        return $data;
    }

    protected function getEncryptor()
    {
        $secret = $this->getSecret();

        assert($secret !== null);

        return new Encryptor($secret);
    }

    protected function getAuthRequest($input)
    {
        $payment = $input['payment'];

        $request = $this->getPayloadForAuth($payment, $input['callbackUrl']);

        return $request;
    }

    protected function getPayloadForAuth($payment, $callbackUrl)
    {
        $data = [
            RequestFields::EXTERNAL_TRANSACTION_ID  => $payment[Payment::ID],
            RequestFields::ORDER_ID                 => $payment[Payment::ID],
            RequestFields::AMOUNT                   => $payment[Payment::AMOUNT],
            RequestFields::CURRENCY                 => $payment[Payment::CURRENCY],
            RequestFields::CALLBACK_URL             => $callbackUrl,
            RequestFields::BACK_URL                 => $callbackUrl,
            RequestFields::DESCRIPTION              => "WAPO",
            // RequestFields::CATEGORY              => 'Cat 1',
            // RequestFields::SUBCATEGORY           => 'Cat 2',
            RequestFields::PROCESSOR_ID             => 'ALL',
        ];

        $encodedData = http_build_query($data);

        $cryptor = $this->getEncryptor();

        $encrypted = $cryptor->encrypt($encodedData);

        $request = [
            'content' => [
                RequestFields::MERCHANT_ID    => $this->getMerchantId(),
                RequestFields::ENCRYPTED_DATA => $encrypted
            ]
        ];

        return $request;
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->terminal['gateway_merchant_id'];
    }
}
