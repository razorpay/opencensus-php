<?php

namespace RZP\Gateway\Netbanking\Bob;

use phpseclib\Crypt\AES;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_bob';

    protected $bank = 'bob';

    protected $map = [
        RequestFields::MERCHANT_ID              => NetbankingEntity::MERCHANT_CODE,
        RequestFields::PAYMENT_ID               => NetbankingEntity::PAYMENT_ID,
        RequestFields::AMOUNT                   => NetbankingEntity::AMOUNT,
        ResponseFields::STATUS                  => NetbankingEntity::STATUS,
        ResponseFields::BANK_REF_NUMBER         => NetbankingEntity::BANK_PAYMENT_ID,
        ResponseFields::CUSTOMER_ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
        NetbankingEntity::RECEIVED              => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $contentToSave = $this->getAuthorizeNetbankingContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getCallbackContent($input);

        $content = $content + [NetbankingEntity::RECEIVED => true];

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        // This asserts the payment id and amount from the response
        $this->checkCallbackResponse($gatewayPayment, $content);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        $this->checkCallbackStatus($content);

        return $this->getCallbackResponseData($input);
    }

    // -------------------- Auth helper methods-------------------------

    protected function getAuthorizeRequest($input)
    {
        $payment = $input['payment'];

        $content = [
            RequestFields::MERCHANT_ID      => $this->getMerchantId(),
            RequestFields::BANK_FIXED_VALUE => Constants::BANK_FIXED_VALUE,
            RequestFields::BILLER_NAME      => $this->input['merchant'][Merchant::NAME],
            RequestFields::AMOUNT           => $this->formatAmount($payment[Payment::AMOUNT]),
            RequestFields::CALLBACK_URL     => $input['callbackUrl'],
            RequestFields::PAYMENT_ID       => $payment[Payment::ID]
        ];

        $encryptedData = $this->getEncryptor()->encryptData($content);

        $decrypted = $this->getEncryptor()->decryptData($encryptedData);

        $requestData = [
            RequestFields::ENCRYPTED_DATA => $encryptedData
        ];

        $request = $this->getStandardRequestArray($requestData);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'    => $this->gateway,
                'content'    => $content,
                'payment_id' => $payment[Payment::ID],
                'request'    => $request
            ]
        );

        return $request;
    }

    protected function getAuthorizeNetbankingContentToSave($payment)
    {
        return [
            RequestFields::MERCHANT_ID => $this->getMerchantId(),
            RequestFields::AMOUNT      => $payment[Payment::AMOUNT]
        ];
    }

    // -------------------- Auth helper methods end----------------------

    // -------------------- Callback helper methods----------------------

    protected function getCallbackContent(array $input): array
    {
        $encryptedData = $input['gateway'][RequestFields::ENCRYPTED_DATA];

        $content = $this->getEncryptor()->decryptData($encryptedData);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'payment_id'    => $input['payment']['id'],
                'encryptedData' => $encryptedData,
                'content'       => $content
            ]
        );

        return $content;
    }

    protected function checkCallbackStatus(array $content)
    {
        if ($this->isGatewaySuccess($content) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'payment_id' => $content[ResponseFields::PAYMENT_ID],
                    'content' => $content
                ]
            );

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function checkCallbackResponse($gatewayPayment, $content)
    {
        assert($content[ResponseFields::AMOUNT] === $gatewayPayment[NetbankingEntity::AMOUNT]);

        assert($content[ResponseFields::PAYMENT_ID] === $gatewayPayment[NetbankingEntity::PAYMENT_ID]);
    }

    // -------------------- Callback helper methods end -----------------

    // -------------------- General helper methods ----------------------

    protected function isGatewaySuccess(array $content): bool
    {
        return ($content[ResponseFields::STATUS] === Constants::STATUS_SUCCESS);
    }

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->getLiveMerchantId();
    }

    public function getEncryptor(): AESCrypto
    {
        $secret = $this->getSecret();

        assert($secret !== null);

        return (new AESCrypto(AES::MODE_CBC, $secret));
    }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

}
