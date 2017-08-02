<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Wallet\Base\Entity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_sbibuddy';

    protected $map = [
        RequestFields::MERCHANT_ID => Entity::GATEWAY_MERCHANT_ID,
    ];

    public function authorize(array $input)
    {
        parent::authorize();

        $request = $this->getAuthRequest($input);

        $this->traceGatewayPaymentRequest($request, $input);

        $contentToSave = [
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
            Entity::PAYMENT_ID          => $input['payment'][Payment::ID],
            Entity::AMOUNT              => $input['payment'][Payment::AMOUNT],
            Entity::EMAIL               => $input['payment'][Payment::EMAIL],
            Entity::CONTACT             => $this->getFormattedContact($input['payment'][Payment::CONTACT]),
            Entity::RECEIVED            => false
        ];

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    public function callback(array $input)
    {
        sd($input);
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

        $encodedData = utf8_encode(http_build_query($data));

        $secret = $this->getSecret();

        $cryptor = new Encryptor($secret);

        $encrypted = $cryptor->encrypt($encodedData);

        $request = [
            'content' => [
                RequestFields::MERCHANT_ID    => '123',
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
