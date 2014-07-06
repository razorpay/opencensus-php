<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\DAL;

class Token
{
    public function create($merchantId, DAL\Card $card)
    {
        $input['merchant_id'] = $merchantId;

        $data = Manager\Token::createValidate($input)->getData();

        $token = new DAL\Token($data);

        $token->card()->associate($card);

        return $token;
    }
}
