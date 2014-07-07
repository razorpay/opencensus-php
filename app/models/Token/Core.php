<?php

namespace Models\Token;

use Models\Card;
use Models\Token;

class Core
{
    public function create($merchantId, Card\Entity $card)
    {
        $input['merchant_id'] = $merchantId;

        $token = (new Token\Entity)->build($input);

        $token->card()->associate($card);

        return $token;
    }
}
