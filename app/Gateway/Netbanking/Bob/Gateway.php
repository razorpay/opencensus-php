<?php

namespace RZP\Gateway\Netbanking\Bob;

use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_bob';

    protected $bank = 'bob';

    protected $map = [
        RequestFields::BANK_ID    => Base\Entity::AMOUNT,
        RequestFields::PAYMENT_ID => Base\Entity::PAYMENT_ID,
        RequestFields::AMOUNT     => Base\Entity::AMOUNT,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequestArray($input);

        $contentToSave = $this->getAuthorizeNetbankingContentToSave($input['payment']);

        $this->createGatewayPaymentEntity($contentToSave, Action::AUTHORIZE);

        return $request;
    }

    protected function getAuthorizeRequestArray($input)
    {
        sd($input);
    }

    protected function getAuthorizeNetbankingContentToSave($payment)
    {
        # code...
    }
}
