<?php

namespace RZP\Gateway\Netbanking\Bob;

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
        RequestFields::MERCHANT_ID => NetbankingEntity::MERCHANT_CODE,
        RequestFields::PAYMENT_ID  => NetbankingEntity::PAYMENT_ID,
        RequestFields::AMOUNT      => NetbankingEntity::AMOUNT,
        // NetbankingEntity::EMAIL         => NetbankingEntity::EMAIL,
        // NetbankingEntity::CONTACT       => NetbankingEntity::CONTACT,
        // NetbankingEntity::RECEIVED      => NetbankingEntity::RECEIVED
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $contentToSave = $this->getAuthorizeNetbankingContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
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

        $request = $this->getStandardRequestArray($content);

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

    protected function getMerchantId()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_merchant_id'];
        }

        return $this->getLiveMerchantId();
    }

    // public function getEncryptor(): AESCrypto
    // {
    //     $secret = $this->getSecret();

    //     assert($secret !== null);

    //     return (new AESCrypto(AES::MODE_CBC, $secret));
    // }

    public function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
