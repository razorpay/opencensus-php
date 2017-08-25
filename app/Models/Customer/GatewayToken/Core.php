<?php

namespace RZP\Models\Customer\GatewayToken;

use RZP\Models\Base;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, Token\Entity $token, $reference)
    {
        $gatewayToken = new Entity;

        $merchant = $payment->merchant;
        $terminal = $payment->terminal;

        $gatewayToken->merchant()->associate($merchant);
        $gatewayToken->terminal()->associate($terminal);
        $gatewayToken->token()->associate($token);

        $gatewayToken->setReference($reference);

        // TODO: Set recurring for the older ones by DB update!
        $gatewayToken->setRecurring($token->isRecurring());

        return $this->repo->saveOrFail($gatewayToken);
    }
}
